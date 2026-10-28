<?php
require __DIR__ . '/comun.php';

requerirGet();

$usuario = usuarioActual();
$miId = $usuario['id_usuario'] ?? 0;
$buscar = '%' . campo($_GET, 'buscar', 0, 120) . '%';
$autor = empty($_GET['autor']) ? 0 : id($_GET['autor']);

$publicaciones = consulta(
    'SELECT p.*,u.nombre,u.nombre_usuario,c.nombre AS categoria,
    (SELECT COUNT(*) FROM likes WHERE id_publicacion=p.id_publicacion) AS likes,
    (SELECT COUNT(*) FROM likes WHERE id_publicacion=p.id_publicacion AND id_usuario=?) AS mi_like
    FROM publicaciones p JOIN usuarios u ON p.id_autor=u.id_usuario JOIN categorias c ON p.id_categoria=c.id_categoria
    WHERE (p.titulo LIKE ? OR p.contenido LIKE ? OR u.nombre LIKE ?) AND (?=0 OR p.id_autor=?)
    ORDER BY p.id_publicacion DESC',
    [$miId, $buscar, $buscar, $buscar, $autor, $autor]
)->fetchAll();

foreach ($publicaciones as &$publicacion) {
    $publicacion['comentarios'] = consulta(
        'SELECT c.*,u.nombre FROM comentarios c JOIN usuarios u ON c.id_autor=u.id_usuario WHERE c.id_publicacion=? ORDER BY c.id_comentario',
        [$publicacion['id_publicacion']]
    )->fetchAll();
}

responder(['ok' => true, 'publicaciones' => $publicaciones]);
