<?php
require __DIR__ . '/comun.php';

requerirGet();

$usuario = usuarioActual();
$miId = $usuario['id_usuario'] ?? 0;
if (isset($_GET['username'])) {
    $username = $_GET['username'];
    if (!is_string($username) || !preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        error('Usuario inválido.');
    }
    $perfil = consulta(
        'SELECT id_usuario,nombre,nombre_usuario,biografia,fecha_registro FROM usuarios WHERE nombre_usuario=?',
        [$username]
    )->fetch();
} else {
    $perfil = consulta(
        'SELECT id_usuario,nombre,nombre_usuario,biografia,fecha_registro FROM usuarios WHERE id_usuario=?',
        [id($_GET['id'] ?? $miId)]
    )->fetch();
}

if (!$perfil) {
    error('No se encontró el perfil.', 404);
}

responder(['ok' => true, 'perfil' => $perfil]);
