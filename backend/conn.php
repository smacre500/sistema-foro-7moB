<?php

$host = "127.0.0.1";
$port = 3306;
$db = "foro_itlp";
$user = "root";
$password = "";
if (file_exists(__DIR__ . "/config.local.php")) {
    $config = require __DIR__ . "/config.local.php";
    $host = $config["host"];
    $port = $config["port"];
    $db = $config["database"];
    $user = $config["username"];
    $password = $config["password"];
}

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Error de conexion: " . $e->getMessage()]);
    exit;
}
