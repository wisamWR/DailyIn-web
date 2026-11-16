<?php
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/env.php';

$servername = env('DB_SERVER');
$username   = env('DB_USERNAME');
$password   = env('DB_PASSWORD');
$db         = env('DB_NAME');
$port       = (int) env('DB_PORT', 3306);

// Jangan tampilkan pesan error mysqli ke pengunjung (bisa membocorkan info server)
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($servername, $username, $password, $db, $port);

if ($conn->connect_error) {
    error_log("DB connection failed: " . $conn->connect_error);
    http_response_code(500);
    die("Koneksi database gagal.");
}

// utf8mb4 supaya emoji & karakter non-latin tersimpan dengan benar
$conn->set_charset('utf8mb4');

// Cek apakah kolom cache ringkasan AI sudah ada (lihat migrations/001_hardening.sql)
function has_ai_summary_column($conn)
{
    static $has = null;
    if ($has === null) {
        $res = $conn->query("SHOW COLUMNS FROM article LIKE 'ai_summary'");
        $has = $res && $res->num_rows > 0;
    }
    return $has;
}
