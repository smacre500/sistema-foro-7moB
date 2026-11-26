<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
$usuario = usuarioActual(true);
if (baneoActivo($usuario['id_usuario'])) {
    error('Tu cuenta está suspendida: no podés comentar.', 403);
}
$contenido = campo($datos, 'contenido', 1, 2000);

$pdo->beginTransaction();
if (!empty($datos['id_comentario'])) {
    $comentario = bloquearComentario(id($datos['id_comentario']));
    if (!$comentario) {
        error('El comentario ya no existe.', 404);
    }
    if ($comentario['id_autor'] != $usuario['id_usuario']) {
        error('Solo podés editar tus comentarios.', 403);
    }
    consulta('UPDATE comentarios SET contenido=? WHERE id_comentario=?', [$contenido, $comentario['id_comentario']]);
} else {
    $publicacion = id($datos['id_publicacion'] ?? null);
    if (!bloquearPublicacion($publicacion)) {
        error('La publicación ya no existe.', 404);
    }
    consulta(
        'INSERT INTO comentarios(id_publicacion,id_autor,contenido) VALUES (?,?,?)',
        [$publicacion, $usuario['id_usuario'], $contenido]
    );
}
$pdo->commit();

responder(['ok' => true]);
