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
    global $pdo;
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
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

function bloquearPublicacion($idPublicacion) {
    return consulta(
        'SELECT id_publicacion,id_autor,id_categoria FROM publicaciones
         WHERE id_publicacion=? AND fecha_eliminacion IS NULL FOR UPDATE',
        [$idPublicacion]
    )->fetch();
}

function bloquearComentario($idComentario) {
    $idPublicacion = consulta(
        'SELECT id_publicacion FROM comentarios WHERE id_comentario=?',
        [$idComentario]
    )->fetchColumn();

    $publicacion = $idPublicacion ? bloquearPublicacion($idPublicacion) : false;
    if (!$publicacion) {
        return false;
    }

    $comentario = consulta(
        'SELECT id_comentario,id_autor FROM comentarios
         WHERE id_comentario=? AND fecha_eliminacion IS NULL FOR UPDATE',
        [$idComentario]
    )->fetch();
    if ($comentario) {
        $comentario['id_categoria'] = $publicacion['id_categoria'];
    }
    return $comentario;
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
        'SELECT id_usuario,nombre,nombre_usuario,correo,rol,biografia,fecha_registro,fecha_baja,version_sesion,debe_cambiar_clave
         FROM usuarios WHERE id_usuario=?',
        [$_SESSION['usuario']]
    )->fetch();
    if (!$usuario) {
        unset($_SESSION['usuario']);
        if ($obligatorio) {
            error('Iniciá sesión para continuar.', 401);
        }
        return null;
    }
    if ($usuario['fecha_baja'] !== null || (int) ($_SESSION['version_sesion'] ?? 0) !== (int) $usuario['version_sesion']) {
        unset($_SESSION['usuario'], $_SESSION['version_sesion']);
        if ($obligatorio) error('La sesión venció o la cuenta fue desactivada. Iniciá sesión nuevamente.', 401);
        return null;
    }
    unset($usuario['fecha_baja'], $usuario['version_sesion']);
    $usuario['debe_cambiar_clave'] = (bool) $usuario['debe_cambiar_clave'];
    if ($usuario['debe_cambiar_clave']) {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $permitidos = ['inicio.php', 'perfil.php', 'cambiar_clave.php', 'salir.php'];
        if (!in_array($script, $permitidos, true)) {
            error('Antes de continuar, cambiá la contraseña temporal desde tu perfil.', 403);
        }
        if ($script === 'perfil.php') {
            $nombreSolicitado = $_GET['username'] ?? null;
            $idSolicitado = $_GET['id'] ?? $usuario['id_usuario'];
            $propio = $nombreSolicitado !== null
                ? is_string($nombreSolicitado) && strcasecmp($nombreSolicitado, $usuario['nombre_usuario']) === 0
                : !is_array($idSolicitado) && (string) $idSolicitado === (string) $usuario['id_usuario'];
            if (!$propio) error('Antes de continuar, cambiá la contraseña temporal desde tu perfil.', 403);
        }
    }
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
    $cuerpo = file_get_contents('php://input', false, null, 0, 1048577);
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
    error_log('Foro: ' . $e->getMessage());
    error('No se pudo completar la operación.', 500);
});

session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
}

require_once __DIR__ . '/conn.php';
