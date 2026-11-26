<?php
require __DIR__ . '/comun.php';
$datos = datosPost();
$usuario = usuarioActual(true);
$cuenta = consulta(
    'SELECT contrasena_hash FROM usuarios WHERE id_usuario=? AND version_sesion=? AND fecha_baja IS NULL',
    [$usuario['id_usuario'], $_SESSION['version_sesion']]
)->fetch();
$actual = $datos['actual'] ?? '';
$nueva = $datos['nueva'] ?? '';
if (!$cuenta || !is_string($actual) || !password_verify($actual, $cuenta['contrasena_hash'])) {
    error('La contraseña actual no es correcta.', 403);
}

if (!is_string($nueva) || strlen($nueva) < 10 || strlen($nueva) > 72) {
    error('La nueva contraseña debe tener entre 10 y 72 bytes.');
}

$cambio = consulta(
    'UPDATE usuarios SET contrasena_hash=?,debe_cambiar_clave=0,version_sesion=version_sesion+1
     WHERE id_usuario=? AND version_sesion=? AND fecha_baja IS NULL',
    [password_hash($nueva, PASSWORD_DEFAULT), $usuario['id_usuario'], $_SESSION['version_sesion']]
);
if (!$cambio->rowCount()) error('La sesión venció. Iniciá sesión nuevamente.', 401);

unset($_SESSION['usuario'], $_SESSION['version_sesion']);
responder(['ok' => true, 'mensaje' => 'Contraseña cambiada. Iniciá sesión nuevamente.']);
