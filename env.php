<?php
// Membaca konfigurasi dari file .env (lokal), atau dari environment variable server
// (misalnya Vercel: Settings > Environment Variables). Nilai di .env didahulukan.
function env($key, $default = '')
{
    static $ENV = null;

    if ($ENV === null) {
        $ENV = [];
        $envFile = __DIR__ . '/.env';

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
    }

    if (array_key_exists($key, $ENV)) {
        return $ENV[$key];
    }

    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}
