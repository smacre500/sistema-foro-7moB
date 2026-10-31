<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
$admin = administrador();
$persona = consulta('SELECT id_usuario,rol FROM usuarios WHERE id_usuario=?', [id($datos['id_usuario'] ?? null)])->fetch();

if (!$persona) {
    error('Usuario no encontrado.', 404);
}
if ($persona['rol'] != 'usuario') {
    error('No se puede banear a un administrador.', 403);
}
if (baneoActivo($persona['id_usuario'])) {
    error('Este usuario ya tiene un baneo activo.', 409);
}

$dias = $datos['dias'] ?? '';
if ($dias != '0' && $dias != '1' && $dias != '7' && $dias != '30') {
    error('Duración inválida.');
}

$fin = (int) $dias === 0 ? null : gmdate('Y-m-d H:i:s', time() + (int) $dias * 86400);
consulta(
    'INSERT INTO baneos(id_usuario,id_administrador,motivo,fecha_fin) VALUES (?,?,?,?)',
    [$persona['id_usuario'], $admin['id_usuario'], campo($datos, 'motivo', 5, 500), $fin]
);

responder(['ok' => true]);
