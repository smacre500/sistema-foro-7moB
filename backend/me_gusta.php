<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
$usuario = usuarioActual(true);
$publicacion = id($datos['id_publicacion'] ?? null);

$pdo->beginTransaction();
if (!bloquearPublicacion($publicacion)) {
    error('La publicación ya no existe.', 404);
}

$like = consulta('SELECT id_like FROM likes WHERE id_usuario=? AND id_publicacion=?', [$usuario['id_usuario'], $publicacion])->fetch();
if ($like) {
    consulta('DELETE FROM likes WHERE id_usuario=? AND id_publicacion=?', [$usuario['id_usuario'], $publicacion]);
} else {
    consulta('INSERT INTO likes(id_usuario,id_publicacion) VALUES (?,?)', [$usuario['id_usuario'], $publicacion]);
}
$pdo->commit();

responder(['ok' => true]);
