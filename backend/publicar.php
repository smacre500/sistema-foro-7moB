<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
$usuario = usuarioActual(true);
$titulo = campo($datos, 'titulo', 3, 150);
$contenido = campo($datos, 'contenido', 5, 10000);
$categoria = id($datos['id_categoria'] ?? null);

if (!consulta('SELECT id_categoria FROM categorias WHERE id_categoria=?', [$categoria])->fetch()) {
    error('Categoría inválida.');
}

if (!empty($datos['id_publicacion'])) {
    $publicacion = consulta('SELECT * FROM publicaciones WHERE id_publicacion=?', [id($datos['id_publicacion'])])->fetch();
    if (!$publicacion) {
        error('La publicación ya no existe.', 404);
    }
    if ($publicacion['id_autor'] != $usuario['id_usuario']) {
        error('Solo podés editar tus publicaciones.', 403);
    }
    consulta(
        'UPDATE publicaciones SET titulo=?,contenido=?,id_categoria=? WHERE id_publicacion=?',
        [$titulo, $contenido, $categoria, $publicacion['id_publicacion']]
    );
} else {
    consulta(
        'INSERT INTO publicaciones(id_autor,id_categoria,titulo,contenido) VALUES (?,?,?,?)',
        [$usuario['id_usuario'], $categoria, $titulo, $contenido]
    );
}

responder(['ok' => true]);
