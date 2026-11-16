<?php
// Helper keamanan: session, cek login, CSRF token, escape output, sanitasi HTML

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/content.php';

// Session disimpan di database (SESSION_DRIVER=database). Wajib di Vercel karena
// tiap instance server punya folder sendiri, jadi session berbasis file akan hilang.
class DbSessionHandler implements SessionHandlerInterface
{
    public function open($path, $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string|false
    {
        $lifetime = (int) ini_get('session.gc_maxlifetime');
        $data = db_value("SELECT data FROM sessions WHERE id = ? AND updated_at > ?", [$id, time() - $lifetime]);
        return $data === false ? '' : (string) base64_decode($data);
    }

    public function write($id, $data): bool
    {
        $sql = DB_DRIVER === 'pgsql'
            ? "INSERT INTO sessions (id, data, updated_at) VALUES (?, ?, ?)
               ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, updated_at = EXCLUDED.updated_at"
            : "INSERT INTO sessions (id, data, updated_at) VALUES (?, ?, ?)
               ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = VALUES(updated_at)";
        return db_run($sql, [$id, base64_encode($data), time()]);
    }

    public function destroy($id): bool
    {
        return db_run("DELETE FROM sessions WHERE id = ?", [$id]);
    }

    public function gc($max_lifetime): int|false
    {
        $stmt = db_query("DELETE FROM sessions WHERE updated_at < ?", [time() - $max_lifetime]);
        return $stmt->rowCount();
    }
}

// HTTPS langsung, atau di belakang proxy (Vercel meneruskan request lewat HTTP biasa)
function is_https()
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

if (session_status() === PHP_SESSION_NONE) {
    if (env('SESSION_DRIVER') === 'database') {
        session_set_save_handler(new DbSessionHandler(), true);
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function is_logged_in()
{
    return isset($_SESSION['username']);
}

// Untuk halaman biasa: alihkan ke login lalu HENTIKAN script
function require_login()
{
    if (!is_logged_in()) {
        header("location:login.php");
        exit;
    }
}

// Untuk endpoint AJAX / JSON
function require_login_json()
{
    if (!is_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Silakan login terlebih dahulu']);
        exit;
    }
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// Token bisa dikirim lewat field form atau header X-CSRF-Token (AJAX)
function verify_csrf()
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die("Permintaan tidak valid (CSRF token salah). Muat ulang halaman lalu coba lagi.");
    }
}

// Escape teks sebelum ditampilkan di HTML
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Pindah halaman lewat JS alert (pola yang sudah dipakai di halaman admin)
// Simpan pesan untuk ditampilkan sebagai notifikasi (toast) di halaman berikutnya, lalu pindah halaman.
// $type: success | danger | warning
function alert_redirect($message, $location, $type = 'success')
{
    $_SESSION['flash'] = ['message' => (string) $message, 'type' => $type];
    echo "<script>document.location=" . json_encode($location) . ";</script>";
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . e($location) . '"></noscript>';
}

// Ambil pesan flash (sekali pakai)
function take_flash()
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// IP pengunjung. Di Vercel request lewat proxy, jadi IP asli ada di header X-Real-IP / X-Forwarded-For
function client_ip()
{
    if (env('VERCEL') !== '') {
        $ip = $_SERVER['HTTP_X_REAL_IP'] ?? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Batas percobaan login: maksimal LOGIN_MAX_ATTEMPTS gagal per IP + username dalam LOGIN_WINDOW detik
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW = 900;

// Sisa detik sampai boleh mencoba login lagi (0 = tidak terkunci)
function login_lock_remaining($username)
{
    try {
        $row = db_one(
            "SELECT COUNT(*) AS total, MIN(attempted_at) AS oldest FROM login_attempts WHERE ip = ? AND username = ? AND attempted_at > ?",
            [client_ip(), $username, time() - LOGIN_WINDOW]
        );
    } catch (PDOException $e) {
        // tabel belum dibuat (lihat migrations/003_login_attempts.sql): jangan blokir login
        error_log("login_attempts unavailable: " . $e->getMessage());
        return 0;
    }
    if ((int) $row['total'] < LOGIN_MAX_ATTEMPTS) return 0;
    return max(1, (int) $row['oldest'] + LOGIN_WINDOW - time());
}

function record_login_failure($username)
{
    db_run("INSERT INTO login_attempts (ip, username, attempted_at) VALUES (?, ?, ?)", [client_ip(), $username, time()]);
    db_run("DELETE FROM login_attempts WHERE attempted_at < ?", [time() - 86400]);
}

function clear_login_failures($username)
{
    db_run("DELETE FROM login_attempts WHERE ip = ? AND username = ?", [client_ip(), $username]);
}

// Hapus file gambar hanya jika valid (file lokal di img/ atau URL Vercel Blob)
// dan tidak dipakai lagi oleh artikel/gallery lain
function delete_image($gambar)
{
    if ($gambar === null || $gambar === '' || image_url($gambar) === '') return;

    $total = (int) db_value(
        "SELECT (SELECT COUNT(*) FROM article WHERE gambar = ?) + (SELECT COUNT(*) FROM gallery WHERE gambar = ?)",
        [$gambar, $gambar]
    );

    if ($total === 0) {
        remove_image($gambar);
    }
}

// Bersihkan HTML artikel (dari Summernote / AI): buang script, event handler, javascript: URL
function sanitize_html($html)
{
    $html = (string) $html;
    if (trim($html) === '') return '';

    $allowed_tags = ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'sub', 'sup', 'span', 'div', 'font',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'code', 'hr',
        'ul', 'ol', 'li', 'a', 'img', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'iframe'];
    $allowed_attrs = ['href', 'src', 'alt', 'title', 'style', 'class', 'target', 'rel', 'width', 'height',
        'colspan', 'rowspan', 'color', 'face', 'size', 'frameborder', 'allowfullscreen'];

    if (!class_exists('DOMDocument')) {
        return strip_tags($html, '<' . implode('><', array_diff($allowed_tags, ['iframe'])) . '>');
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);
    $root = $xpath->query('//*[@id="__root"]')->item(0);
    if (!$root) return '';

    foreach (iterator_to_array($xpath->query('.//*', $root)) as $node) {
        $tag = strtolower($node->nodeName);

        if (!in_array($tag, $allowed_tags, true)) {
            // Tag berbahaya dibuang beserta isinya, tag lain cukup "dibuka" (isi teks tetap)
            if (in_array($tag, ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'link', 'meta', 'base'], true)) {
                $node->parentNode->removeChild($node);
            } else {
                while ($node->firstChild) {
                    $node->parentNode->insertBefore($node->firstChild, $node);
                }
                $node->parentNode->removeChild($node);
            }
            continue;
        }

        foreach (iterator_to_array($node->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);

            if (!in_array($name, $allowed_attrs, true)) {
                $node->removeAttribute($attr->name);
                continue;
            }
            if (in_array($name, ['href', 'src'], true)) {
                // Browser mengabaikan spasi/tab/newline di URL ("java\tscript:"), jadi buang dulu sebelum dicek
                $check = preg_replace('/[\x00-\x20]+/', '', $value);
                $is_data_image = $tag === 'img' && preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#i', $check);
                if (!$is_data_image && !preg_match('#^(https?:|mailto:)#i', $check) && preg_match('#^[^/?\#]*:#', $check)) {
                    $node->removeAttribute($attr->name);
                }
            }
            if ($name === 'style' && preg_match('/expression|url\s*\(|javascript:/i', $value)) {
                $node->removeAttribute($attr->name);
            }
        }

        // iframe hanya untuk video YouTube / Vimeo (fitur "video" Summernote)
        if ($tag === 'iframe' && !preg_match('#^(https?:)?//(www\.)?(youtube\.com|youtube-nocookie\.com|player\.vimeo\.com)/#i', $node->getAttribute('src'))) {
            $node->parentNode->removeChild($node);
        }
    }

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return $out;
}
