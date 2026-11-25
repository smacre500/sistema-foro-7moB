<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
ini_set('display_errors', '0');
date_default_timezone_set('UTC');

function responder($datos, $estado = 200) {
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

function error($mensaje, $estado = 400) {
    responder(['ok' => false, 'error' => $mensaje], $estado);
}

function consulta($sql, $parametros = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    return $stmt;
}

function campo($datos, $nombre, $minimo, $maximo) {
    $valor = $datos[$nombre] ?? '';
    if (!is_string($valor)) {
        error("El campo $nombre debe ser texto.");
    }
    $valor = trim($valor);
    if (mb_strlen($valor) < $minimo || mb_strlen($valor) > $maximo) {
        error("Revisá el campo $nombre (entre $minimo y $maximo caracteres).");
    }
    return $valor;
}

function id($valor) {
    if (is_array($valor) || !filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
        error('Identificador inválido.');
    }
    return (int) $valor;
}

function correoInstitucional($correo) {
    return filter_var($correo, FILTER_VALIDATE_EMAIL) &&
        strrchr(strtolower($correo), '@') === '@obralapiedad.com.ar';
}

function leerEstadoLocal() {
    $ruta = __DIR__ . '/storage/estado-cuentas.json';
    if (!is_file($ruta)) return ['usuarios' => [], 'tokens' => []];
    $archivo = fopen($ruta, 'rb');
    if (!$archivo) error('No se pudo leer el almacenamiento local.', 500);
    flock($archivo, LOCK_SH);
    $estado = json_decode(stream_get_contents($archivo), true);
    flock($archivo, LOCK_UN);
    fclose($archivo);
    return is_array($estado) ? array_merge(['usuarios' => [], 'tokens' => []], $estado) : ['usuarios' => [], 'tokens' => []];
}

function modificarEstadoLocal($cambio) {
    $ruta = __DIR__ . '/storage/estado-cuentas.json';
    $archivo = fopen($ruta, 'c+');
    if (!$archivo) error('No se pudo guardar el almacenamiento local. Revisá los permisos de backend/storage.', 500);
    flock($archivo, LOCK_EX);
    rewind($archivo);
    $estado = json_decode(stream_get_contents($archivo), true);
    if (!is_array($estado)) $estado = ['usuarios' => [], 'tokens' => []];
    $resultado = $cambio($estado);
    rewind($archivo);
    ftruncate($archivo, 0);
    fwrite($archivo, json_encode($estado, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    fflush($archivo);
    flock($archivo, LOCK_UN);
    fclose($archivo);
    return $resultado;
}

function datosCuentaLocal($idUsuario) {
    $estado = leerEstadoLocal();
    return $estado['usuarios'][(string) $idUsuario] ?? [];
}

function correoCuenta($idUsuario, $correoBase) {
    return datosCuentaLocal($idUsuario)['correo'] ?? $correoBase;
}

function cuentaActiva($idUsuario) {
    return (datosCuentaLocal($idUsuario)['activa'] ?? true) === true;
}

function versionSesionCuenta($idUsuario) {
    return (int) (datosCuentaLocal($idUsuario)['version_sesion'] ?? 1);
}

function claveCuenta($idUsuario, $claveBase) {
    return datosCuentaLocal($idUsuario)['contrasena_hash'] ?? $claveBase;
}

function buscarCorreoLocal($correo) {
    $estado = leerEstadoLocal();
    foreach ($estado['usuarios'] as $idUsuario => $datos) {
        if (!empty($datos['correo']) && strtolower($datos['correo']) === strtolower($correo)) {
            return (int) $idUsuario;
        }
    }
    return null;
}

function registrarEliminacionLocal($registro) {
    $ruta = __DIR__ . '/storage/eliminaciones.jsonl';
    $archivo = fopen($ruta, 'ab');
    if (!$archivo) error('No se pudo registrar la eliminación. No se borró el contenido.', 500);
    flock($archivo, LOCK_EX);
    fwrite($archivo, json_encode($registro, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL);
    fflush($archivo);
    flock($archivo, LOCK_UN);
    fclose($archivo);
}

function eliminacionesLocales($desde, $hasta) {
    $ruta = __DIR__ . '/storage/eliminaciones.jsonl';
    if (!is_file($ruta)) return [];
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $registros = [];
    foreach ($lineas as $linea) {
        $dato = json_decode($linea, true);
        if (is_array($dato) && ($dato['fecha'] ?? '') >= $desde && ($dato['fecha'] ?? '') < $hasta) $registros[] = $dato;
    }
    return array_slice(array_reverse($registros), 0, 50);
}

function baneoActivo($usuario) {
    return consulta(
        'SELECT * FROM baneos WHERE id_usuario=? AND activo=1 AND fecha_inicio<=UTC_TIMESTAMP() AND (fecha_fin IS NULL OR fecha_fin>UTC_TIMESTAMP()) ORDER BY id_baneo DESC LIMIT 1',
        [$usuario]
    )->fetch();
}

function usuarioActual($obligatorio = false) {
    if (empty($_SESSION['usuario'])) {
        if ($obligatorio) {
            error('Iniciá sesión para continuar.', 401);
        }
        return null;
    }
    $usuario = consulta(
        'SELECT id_usuario,nombre,nombre_usuario,correo,rol,biografia,fecha_registro FROM usuarios WHERE id_usuario=?',
        [$_SESSION['usuario']]
    )->fetch();
    if (!$usuario) {
        unset($_SESSION['usuario']);
        if ($obligatorio) {
            error('Iniciá sesión para continuar.', 401);
        }
        return null;
    }
    if (!cuentaActiva($usuario['id_usuario']) || (int) ($_SESSION['version_sesion'] ?? 0) !== versionSesionCuenta($usuario['id_usuario'])) {
        unset($_SESSION['usuario'], $_SESSION['version_sesion']);
        if ($obligatorio) error('La sesión venció o la cuenta fue desactivada. Iniciá sesión nuevamente.', 401);
        return null;
    }
    $datosLocales = datosCuentaLocal($usuario['id_usuario']);
    $usuario['debe_cambiar_clave'] = ($datosLocales['debe_cambiar_clave'] ?? false) === true;
    if ($usuario['debe_cambiar_clave']) {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $permitidos = ['inicio.php', 'perfil.php', 'cambiar_clave.php', 'salir.php'];
        if (!in_array($script, $permitidos, true)) {
            error('Antes de continuar, cambiá la contraseña temporal desde tu perfil.', 403);
        }
        if ($script === 'perfil.php') {
            $nombreSolicitado = $_GET['username'] ?? null;
            $idSolicitado = $_GET['id'] ?? null;
            $propio = ($nombreSolicitado === null && $idSolicitado === null)
                || ($nombreSolicitado === $usuario['nombre_usuario'])
                || ((string) $idSolicitado === (string) $usuario['id_usuario']);
            if (!$propio) error('Antes de continuar, cambiá la contraseña temporal desde tu perfil.', 403);
        }
    }
    $usuario['correo'] = correoCuenta($usuario['id_usuario'], $usuario['correo']);
    if (!correoInstitucional($usuario['correo'])) {
        unset($_SESSION['usuario']);
        error('Tu cuenta no tiene acceso al foro. Revisá el correo institucional o consultá al administrador.', 403);
    }
    return $usuario;
}

function administrador() {
    $usuario = usuarioActual(true);
    if ($usuario['rol'] != 'administrador') {
        error('Solo los administradores pueden realizar esta acción.', 403);
    }
    return $usuario;
}

function requerirGet() {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') != 'GET') {
        error('Método no permitido.', 405);
    }
}

function datosPost() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') != 'POST') {
        error('Método no permitido.', 405);
    }
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 1048576) {
        error('El contenido es demasiado grande.', 413);
    }
    if (!hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        error('Recargá la página y volvé a intentarlo.', 403);
    }
    $cuerpo = file_get_contents('php://input');
    if (strlen($cuerpo) > 1048576) {
        error('El contenido es demasiado grande.', 413);
    }
    $datos = json_decode($cuerpo, true);
    if (!is_array($datos)) {
        error('Datos inválidos.');
    }
    return $datos;
}

set_exception_handler(function ($e) {
    global $pdo;
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Foro: ' . $e->getMessage());
    error('No se pudo completar la operación.', 500);
});

session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
}

require_once __DIR__ . '/conn.php';
