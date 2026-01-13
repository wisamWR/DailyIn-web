<?php
date_default_timezone_set('Asia/Jakarta');

$envFile = __DIR__ . '/.env';
$ENV = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $ENV[trim($name)] = trim(trim($value), "\"'");
        }
    }
}

// Ambil dari array biasa, bukan getenv()
$servername = $ENV['DB_SERVER'] ?? '';
$username   = $ENV['DB_USERNAME'] ?? '';
$password   = $ENV['DB_PASSWORD'] ?? '';
$db         = $ENV['DB_NAME'] ?? '';

// Debug sementara
// var_dump($ENV);

$conn = new mysqli($servername, $username, $password, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}