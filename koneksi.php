<?php
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/env.php';

// Database bisa MySQL (XAMPP/Laragon) atau PostgreSQL (Neon di Vercel).
// Jika DATABASE_URL / POSTGRES_URL ada, dipakai PostgreSQL. Jika tidak, pakai DB_* (MySQL).
$database_url = env('DATABASE_URL', env('POSTGRES_URL'));

if ($database_url !== '') {
    $url = parse_url($database_url);
    parse_str($url['query'] ?? '', $query);
    $dsn = sprintf(
        "pgsql:host=%s;port=%d;dbname=%s;sslmode=%s",
        $url['host'] ?? 'localhost',
        $url['port'] ?? 5432,
        ltrim($url['path'] ?? '', '/'),
        $query['sslmode'] ?? 'require'
    );
    $username = rawurldecode($url['user'] ?? '');
    $password = rawurldecode($url['pass'] ?? '');
} else {
    $dsn = sprintf(
        "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
        env('DB_SERVER', 'localhost'),
        (int) env('DB_PORT', 3306),
        env('DB_NAME')
    );
    $username = env('DB_USERNAME');
    $password = env('DB_PASSWORD');
}

try {
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Jangan tampilkan pesan error ke pengunjung (bisa membocorkan info server)
    error_log("DB connection failed: " . $e->getMessage());
    http_response_code(500);
    die("Koneksi database gagal.");
}

define('DB_DRIVER', $conn->getAttribute(PDO::ATTR_DRIVER_NAME));

// "user" adalah kata khusus di PostgreSQL, jadi nama tabelnya harus diberi tanda kutip
define('USER_TABLE', DB_DRIVER === 'pgsql' ? '"user"' : '`user`');

// Jalankan query dengan parameter (prepared statement), hasilnya PDOStatement
function db_query($sql, $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Untuk INSERT / UPDATE / DELETE: true jika berhasil, false jika gagal (error dicatat di log)
function db_run($sql, $params = [])
{
    try {
        db_query($sql, $params);
        return true;
    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        return false;
    }
}

// Ambil satu baris (array) atau null
function db_one($sql, $params = [])
{
    $row = db_query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

// Ambil semua baris
function db_all($sql, $params = [])
{
    return db_query($sql, $params)->fetchAll();
}

// Ambil satu nilai (kolom pertama baris pertama)
function db_value($sql, $params = [])
{
    return db_query($sql, $params)->fetchColumn();
}

// Cek apakah kolom cache ringkasan AI sudah ada (lihat migrations/001_hardening.sql)
function has_ai_summary_column()
{
    static $has = null;
    if ($has === null) {
        try {
            db_query("SELECT ai_summary FROM article WHERE 1 = 0");
            $has = true;
        } catch (PDOException $e) {
            $has = false;
        }
    }
    return $has;
}
