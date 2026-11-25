<?php
require __DIR__ . '/comun.php';

$datos = datosPost();
$usuario = usuarioActual(true);
$tipo = $datos['tipo'] ?? '';

if ($tipo != 'publicacion' && $tipo != 'comentario') {
    error('Contenido inválido.');
}

$tabla = $tipo == 'publicacion' ? 'publicaciones' : 'comentarios';
$columna = 'id_' . $tipo;
$idContenido = id($datos['id'] ?? null);
$contenido = consulta("SELECT * FROM $tabla WHERE $columna=?", [$idContenido])->fetch();

if (!$contenido) {
    error('El contenido ya no existe.', 404);
}
if ($contenido['id_autor'] != $usuario['id_usuario'] && $usuario['rol'] != 'administrador') {
    error('No tenés permiso para eliminarlo.', 403);
}
$motivo = $contenido['id_autor'] == $usuario['id_usuario']
    ? 'Eliminación solicitada por el autor'
    : campo($datos, 'motivo', 5, 500);

$pdo->beginTransaction();
if ($tipo == 'publicacion') {
    consulta('DELETE FROM comentarios WHERE id_publicacion=?', [$idContenido]);
    consulta('DELETE FROM likes WHERE id_publicacion=?', [$idContenido]);
}
consulta("DELETE FROM $tabla WHERE $columna=?", [$idContenido]);
registrarEliminacionLocal([
    'tipo' => $tipo,
    'id_contenido' => $idContenido,
    'id_autor_original' => (int) $contenido['id_autor'],
    'id_actor' => (int) $usuario['id_usuario'],
    'motivo' => $motivo,
    'fecha' => gmdate('Y-m-d H:i:s'),
]);
$pdo->commit();

responder(['ok' => true]);
