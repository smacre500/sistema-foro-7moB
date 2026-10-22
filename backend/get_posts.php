<?php

// Importante para que JS nos tome como JSON
header("Content-Type: application/json");

require_once "conn.php";

$limite = 10;
$pagina = isset($_POST["pagina"]) ? (int)$_POST["pagina"] : 1;
$offset = ($pagina - 1) * $limite;

$sql = "SELECT * FROM publicaciones ORDER BY id_publicacion DESC LIMIT :limite OFFSET :offset";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$zona_origen = new DateTimeZone("America/Argentina/Buenos_Aires");
$zona_utc = new DateTimeZone("UTC");

// Convertimos la fecha de cada publicación a formato UTC ISO 8601
// OJO: CONVERTIR A GMT-3 (Argentina) EN EL FRONTEND!!!!
foreach ($posts as &$post) {
    if (isset($post["fecha_publicacion"])) {
        $fecha = new DateTime($post["fecha_publicacion"], $zona_origen);
        $fecha->setTimezone($zona_utc);
        $post["fecha_publicacion"] = $fecha->format(DateTime::ATOM);
    }
}
unset($post);

echo json_encode([
    "status" => "ok",
    "pagina_actual" => $pagina,
    "posts" => $posts
]);

?>