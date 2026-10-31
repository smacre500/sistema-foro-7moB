<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
$usuario = usuarioActual(true);

consulta(
    'UPDATE usuarios SET nombre=?,biografia=? WHERE id_usuario=?',
    [
        campo($datos, 'nombre', 2, 100),
        campo($datos, 'biografia', 0, 500),
        $usuario['id_usuario'],
    ]
);
responder(['ok' => true]);
