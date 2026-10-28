<?php
// funciones y configuración que usan todos los endpoints

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

function correoInstitucional($correo) {
    return filter_var($correo, FILTER_VALIDATE_EMAIL) &&
        strrchr(strtolower($correo), '@') === '@obralapiedad.com.ar';
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
    if (!correoInstitucional($usuario['correo']) || baneoActivo($usuario['id_usuario'])) {
        unset($_SESSION['usuario']);
        error('Tu cuenta no tiene acceso al foro. Revisá el correo institucional o consultá al administrador.', 403);
    }
    return $usuario;
}

function requerirGet() {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') != 'GET') {
        error('Método no permitido.', 405);
    }
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
