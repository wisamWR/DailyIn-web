<?php
// Membaca file .env sekali saja, lalu bisa dipakai di file mana pun lewat env()
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

    return $ENV[$key] ?? $default;
}
