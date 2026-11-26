<?php
require __DIR__ . '/comun.php';

requerirGet();

$usuario = usuarioActual(true);

if (baneoActivo($usuario['id_usuario'])) {
    error('Tu cuenta está suspendida: no podés editar publicaciones.', 403);
}

$identificador = id($_GET['id'] ?? null);

$publicacion = consulta(
    'SELECT id_publicacion,id_autor,titulo,contenido,id_categoria
     FROM publicaciones
     WHERE id_publicacion=? AND fecha_eliminacion IS NULL',
    [$identificador]
)->fetch();

if (!$publicacion) {
    error('La publicación ya no existe.', 404);
}

if ((int) $publicacion['id_autor'] !== (int) $usuario['id_usuario']) {
    error('Solo podés editar tus publicaciones.', 403);
}

responder(['ok' => true, 'publicacion' => $publicacion]);
