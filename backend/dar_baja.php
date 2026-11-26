<?php
require __DIR__ . '/comun.php';
$datos = datosPost();
$usuario = usuarioActual(true);
$clave = $datos['contrasena'] ?? '';
if (($datos['confirmar'] ?? '') !== 'si') {
    error('Confirmá la baja de la cuenta.');
}

$pdo->beginTransaction();
if ($usuario['rol'] === 'administrador') {
    $activos = consulta(
        "SELECT id_usuario FROM usuarios WHERE rol='administrador' AND fecha_baja IS NULL ORDER BY id_usuario FOR UPDATE"
    )->fetchAll();
    if (count($activos) <= 1) {
        error('No se puede dar de baja al último administrador activo.', 409);
    }
}

$cuenta = consulta(
    'SELECT contrasena_hash FROM usuarios WHERE id_usuario=? AND version_sesion=? AND fecha_baja IS NULL FOR UPDATE',
    [$usuario['id_usuario'], $_SESSION['version_sesion']]
)->fetch();
if (!$cuenta || !is_string($clave) || !password_verify($clave, $cuenta['contrasena_hash'])) {
    error('La contraseña actual no es correcta.', 403);
}
consulta(
    'UPDATE usuarios SET fecha_baja=UTC_TIMESTAMP(),version_sesion=version_sesion+1 WHERE id_usuario=?',
    [$usuario['id_usuario']]
);
$pdo->commit();

unset($_SESSION['usuario'], $_SESSION['version_sesion']);
responder(['ok' => true]);
