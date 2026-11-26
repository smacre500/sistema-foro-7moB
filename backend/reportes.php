<?php
require __DIR__ . '/comun.php';
requerirGet();
administrador();

$zona = new DateTimeZone('America/Argentina/Buenos_Aires');
$inicioTexto = $_GET['desde'] ?? '';
$finTexto = $_GET['hasta'] ?? '';
$categoria = empty($_GET['categoria']) ? 0 : id($_GET['categoria']);
if (!is_string($inicioTexto) || !is_string($finTexto)) {
    error('Rango de fechas inválido.');
}

$inicio = DateTimeImmutable::createFromFormat('!Y-m-d', $inicioTexto, $zona);
$finDia = DateTimeImmutable::createFromFormat('!Y-m-d', $finTexto, $zona);
if (!$inicio || !$finDia || $inicio->format('Y-m-d') !== $inicioTexto || $finDia->format('Y-m-d') !== $finTexto) {
    error('Elegí fechas válidas.');
}

if ($finDia < $inicio) {
    error('La fecha final debe ser igual o posterior a la inicial.');
}

if ($inicio->diff($finDia)->days > 365) {
    error('El período máximo del reporte es de un año.');
}

$fin = $finDia->modify('+1 day');
$utc = new DateTimeZone('UTC');
$desdeUtc = $inicio->setTimezone($utc)->format('Y-m-d H:i:s');
$hastaUtc = $fin->setTimezone($utc)->format('Y-m-d H:i:s');

$porCategoria = consulta(
    'SELECT c.nombre, COUNT(p.id_publicacion) AS cantidad
     FROM categorias c
     LEFT JOIN publicaciones p
       ON p.id_categoria = c.id_categoria
      AND p.fecha_publicacion >= ?
      AND p.fecha_publicacion < ?
      AND p.fecha_eliminacion IS NULL
     WHERE (? = 0 OR c.id_categoria = ?)
     GROUP BY c.id_categoria, c.nombre
     ORDER BY c.nombre',
    [$desdeUtc, $hastaUtc, $categoria, $categoria]
)->fetchAll();

$masGustadas = consulta(
    'SELECT p.id_publicacion, p.titulo, c.nombre AS categoria,
            COUNT(l.id_like) AS me_gusta
     FROM publicaciones p
     JOIN categorias c ON c.id_categoria = p.id_categoria
     LEFT JOIN likes l ON l.id_publicacion = p.id_publicacion
     WHERE p.fecha_publicacion >= ?
       AND p.fecha_publicacion < ?
       AND p.fecha_eliminacion IS NULL
       AND (? = 0 OR p.id_categoria = ?)
     GROUP BY p.id_publicacion, p.titulo, c.nombre
     ORDER BY me_gusta DESC, p.id_publicacion DESC
     LIMIT 10',
    [$desdeUtc, $hastaUtc, $categoria, $categoria]
)->fetchAll();

$eliminaciones = consulta(
    "SELECT e.tipo,e.id_contenido,e.id_autor_original,e.id_actor,e.motivo,e.fecha,
            u.nombre_usuario AS actor,cat.nombre AS categoria
     FROM (
         SELECT 'publicacion' AS tipo,id_publicacion AS id_contenido,id_categoria,
                id_autor AS id_autor_original,id_eliminado_por AS id_actor,
                motivo_eliminacion AS motivo,fecha_eliminacion AS fecha
         FROM publicaciones WHERE fecha_eliminacion >= ? AND fecha_eliminacion < ?
         UNION ALL
         SELECT 'comentario',id_comentario,id_categoria_eliminacion,id_autor,id_eliminado_por,
                motivo_eliminacion,fecha_eliminacion
         FROM comentarios WHERE fecha_eliminacion >= ? AND fecha_eliminacion < ?
     ) e
     JOIN usuarios u ON u.id_usuario=e.id_actor
     JOIN categorias cat ON cat.id_categoria=e.id_categoria
     WHERE (? = 0 OR e.id_categoria = ?)
     ORDER BY e.fecha DESC,e.tipo,e.id_contenido DESC
     LIMIT 50",
    [$desdeUtc, $hastaUtc, $desdeUtc, $hastaUtc, $categoria, $categoria]
)->fetchAll();
responder([
    'ok' => true,
    'por_categoria' => $porCategoria,
    'mas_gustadas' => $masGustadas,
    'eliminaciones' => $eliminaciones,
]);
