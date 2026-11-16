<?php
// Helper keamanan: session, cek login, CSRF token, escape output, sanitasi HTML

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
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
function alert_redirect($message, $location)
{
    echo "<script>
        alert(" . json_encode($message) . ");
        document.location=" . json_encode($location) . ";
    </script>";
}

// Hanya izinkan nama file gambar hasil upload (tanpa folder / "../")
function safe_image_name($name)
{
    $name = basename((string) $name);
    return preg_match('/^[A-Za-z0-9_\-]+\.(jpg|jpeg|png|gif|webp)$/i', $name) ? $name : '';
}

// Hapus file gambar hanya jika namanya valid dan tidak dipakai lagi oleh artikel/gallery lain
function delete_image($conn, $name)
{
    $name = safe_image_name($name);
    if ($name === '' || !is_file(__DIR__ . '/img/' . $name)) return;

    $stmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM article WHERE gambar = ?) + (SELECT COUNT(*) FROM gallery WHERE gambar = ?) AS total");
    $stmt->bind_param("ss", $name, $name);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($total === 0) {
        unlink(__DIR__ . '/img/' . $name);
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
