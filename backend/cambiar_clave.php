<?php
require __DIR__ . '/comun.php';
$datos = datosPost();
$usuario = usuarioActual(true);
$cuenta = consulta('SELECT contrasena_hash FROM usuarios WHERE id_usuario=?', [$usuario['id_usuario']])->fetch();
$actual = $datos['actual'] ?? '';
$nueva = $datos['nueva'] ?? '';
if (!is_string($actual) || !password_verify($actual, claveCuenta($usuario['id_usuario'], $cuenta['contrasena_hash']))) {
    error('La contraseña actual no es correcta.', 403);
}

if (!is_string($nueva) || strlen($nueva) < 10 || strlen($nueva) > 72) {
    error('La nueva contraseña debe tener entre 10 y 72 bytes.');
}

modificarEstadoLocal(function (&$estado) use ($usuario, $nueva) {
    $id = (string) $usuario['id_usuario'];
    $estado['usuarios'][$id]['contrasena_hash'] = password_hash($nueva, PASSWORD_DEFAULT);
    $estado['usuarios'][$id]['version_sesion'] = (int)($estado['usuarios'][$id]['version_sesion'] ?? 1) + 1;
    $estado['usuarios'][$id]['debe_cambiar_clave'] = false;

    foreach ($estado['tokens'] as $hash => $token) {
        if ((int) ($token['id_usuario'] ?? 0) === (int) $usuario['id_usuario']) {
            unset($estado['tokens'][$hash]);
        }
    }
});

unset($_SESSION['usuario'], $_SESSION['version_sesion']);
responder(['ok' => true, 'mensaje' => 'Contraseña cambiada. Iniciá sesión nuevamente.']);
