<?php
require __DIR__ . '/comun.php';

requerirGet();

$usuario = usuarioActual();
$miId = $usuario['id_usuario'] ?? 0;
$perfil = consulta(
    'SELECT id_usuario,nombre,nombre_usuario,biografia,fecha_registro FROM usuarios WHERE id_usuario=?',
    [id($_GET['id'] ?? $miId)]
)->fetch();

if (!$perfil) {
    error('No se encontró el perfil.', 404);
}

responder(['ok' => true, 'perfil' => $perfil]);
