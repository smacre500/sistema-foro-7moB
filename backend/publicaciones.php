<?php
require __DIR__ . '/comun.php';

requerirGet();

$usuario = usuarioActual();
$miId = $usuario['id_usuario'] ?? 0;
$buscar = '%' . campo($_GET, 'buscar', 0, 120) . '%';
$autor = empty($_GET['autor']) ? 0 : id($_GET['autor']);
$paginaEntrada = $_GET['pagina'] ?? '1';
if (!is_string($paginaEntrada)) error('Página inválida.');
$pagina = filter_var($paginaEntrada, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
if ($pagina === false) {
    error('Página inválida.');
}
$limite = 10;
$offset = ($pagina - 1) * $limite;

$sql =
    'SELECT p.id_publicacion,p.id_autor,p.id_categoria,p.titulo,p.contenido,p.fecha_publicacion,
    u.nombre,u.nombre_usuario,c.nombre AS categoria,
    (SELECT COUNT(*) FROM likes WHERE id_publicacion=p.id_publicacion) AS likes,
    (SELECT COUNT(*) FROM likes WHERE id_publicacion=p.id_publicacion AND id_usuario=?) AS mi_like
    FROM publicaciones p JOIN usuarios u ON p.id_autor=u.id_usuario JOIN categorias c ON p.id_categoria=c.id_categoria
    WHERE p.fecha_eliminacion IS NULL
    AND (p.titulo LIKE ? OR p.contenido LIKE ? OR u.nombre LIKE ?) AND (?=0 OR p.id_autor=?)
    ORDER BY p.id_publicacion DESC LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($sql);
$parametros = [$miId, $buscar, $buscar, $buscar, $autor, $autor, $limite + 1, $offset];
foreach ($parametros as $indice => $valor) {
    $stmt->bindValue($indice + 1, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$publicaciones = $stmt->fetchAll();
$hayMas = count($publicaciones) > $limite;
if ($hayMas) {
    array_pop($publicaciones);
}

$ids = array_column($publicaciones, 'id_publicacion');
$porPublicacion = [];
if ($ids) {
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $comentarios = consulta(
        "SELECT c.id_comentario,c.id_publicacion,c.id_autor,c.contenido,c.fecha_publicacion,u.nombre
         FROM comentarios c JOIN usuarios u ON c.id_autor=u.id_usuario
         WHERE c.fecha_eliminacion IS NULL AND c.id_publicacion IN ($marcas)
         ORDER BY c.id_comentario",
        $ids
    )->fetchAll();
    foreach ($comentarios as $comentario) {
        $porPublicacion[$comentario['id_publicacion']][] = $comentario;
    }
}
foreach ($publicaciones as &$publicacion) {
    $publicacion['comentarios'] = $porPublicacion[$publicacion['id_publicacion']] ?? [];
}
unset($publicacion);

responder(['ok' => true, 'publicaciones' => $publicaciones, 'pagina' => $pagina, 'hay_mas' => $hayMas]);
