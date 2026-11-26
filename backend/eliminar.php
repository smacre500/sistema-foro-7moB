<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
$usuario = usuarioActual(true);
$tipo = $datos['tipo'] ?? '';

if ($tipo !== 'publicacion' && $tipo !== 'comentario') {
    error('Contenido inválido.');
}

$idContenido = id($datos['id'] ?? null);

$pdo->beginTransaction();
$contenido = $tipo === 'publicacion'
    ? bloquearPublicacion($idContenido)
    : bloquearComentario($idContenido);

if (!$contenido) {
    error('El contenido ya no existe.', 404);
}
if ($contenido['id_autor'] != $usuario['id_usuario'] && $usuario['rol'] != 'administrador') {
    error('No tenés permiso para eliminarlo.', 403);
}
$motivo = $contenido['id_autor'] == $usuario['id_usuario']
    ? 'Eliminación solicitada por el autor'
    : campo($datos, 'motivo', 5, 500);

if ($tipo === 'publicacion') {
    consulta(
        'UPDATE publicaciones SET fecha_eliminacion=UTC_TIMESTAMP(),id_eliminado_por=?,motivo_eliminacion=?
         WHERE id_publicacion=?',
        [$usuario['id_usuario'], $motivo, $idContenido]
    );
} else {
    consulta(
        'UPDATE comentarios SET fecha_eliminacion=UTC_TIMESTAMP(),id_eliminado_por=?,motivo_eliminacion=?,id_categoria_eliminacion=?
         WHERE id_comentario=?',
        [$usuario['id_usuario'], $motivo, $contenido['id_categoria'], $idContenido]
    );
}
$pdo->commit();

responder(['ok' => true]);
