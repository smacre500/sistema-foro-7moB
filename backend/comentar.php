<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
$usuario = usuarioActual(true);
if (baneoActivo($usuario['id_usuario'])) {
    error('Tu cuenta está suspendida: no podés comentar.', 403);
}
$contenido = campo($datos, 'contenido', 1, 2000);

if (!empty($datos['id_comentario'])) {
    $comentario = consulta('SELECT * FROM comentarios WHERE id_comentario=?', [id($datos['id_comentario'])])->fetch();
    if (!$comentario) {
        error('El comentario ya no existe.', 404);
    }
    if ($comentario['id_autor'] != $usuario['id_usuario']) {
        error('Solo podés editar tus comentarios.', 403);
    }
    consulta('UPDATE comentarios SET contenido=? WHERE id_comentario=?', [$contenido, $comentario['id_comentario']]);
} else {
    $publicacion = id($datos['id_publicacion'] ?? null);
    if (!consulta('SELECT id_publicacion FROM publicaciones WHERE id_publicacion=?', [$publicacion])->fetch()) {
        error('La publicación ya no existe.', 404);
    }
    consulta(
        'INSERT INTO comentarios(id_publicacion,id_autor,contenido) VALUES (?,?,?)',
        [$publicacion, $usuario['id_usuario'], $contenido]
    );
}

responder(['ok' => true]);
