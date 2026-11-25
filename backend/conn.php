<?php

$host = getenv('FORO_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FORO_DB_PORT') ?: 3306);
$db = getenv('FORO_DB_NAME') ?: 'foro_itlp';
$user = getenv('FORO_DB_USER') ?: 'root';
$password = getenv('FORO_DB_PASSWORD') ?: '';
$urlForo = rtrim(getenv('FORO_URL') ?: 'http://localhost/sistema-foro-7moB', '/');

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