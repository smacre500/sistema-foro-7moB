<?php

$host = "127.0.0.1";
$db = "foro_itlp";
$user = "root";
$password = "";

$dsn = "mysql:host=$host;dbname=$db";

$options = [
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    // Si falla la conexión, devuelve un JSON con el mensaje de error
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "error" => "Error de conexion: " . $e->getMessage()
    ]);

    exit();
}

?>