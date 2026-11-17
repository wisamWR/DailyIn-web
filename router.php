<?php
// Router untuk server bawaan PHP (development & CI):
//   php -S 127.0.0.1:8000 router.php
// File yang ada dilayani langsung, selain itu diteruskan ke index.php (seperti Caddy/Apache).
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

// path script disamakan dengan server produksi supaya base_path() benar
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
