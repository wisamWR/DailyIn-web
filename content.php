<?php
// Helper konten: URL & slug artikel, status terbit (draft / publish / terjadwal), tag, excerpt.

// Path dasar aplikasi, misalnya "/" di Vercel atau "/DailyIn-web/" di XAMPP
function base_path()
{
    $base = env('APP_BASE_PATH');
    if ($base === '') {
        $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        // script di subfolder aplikasi (misalnya api/get_article.php): buang subfolder itu dari base
        $app_dir = str_replace('\\', '/', (string) realpath(__DIR__));
        $script_dir = str_replace('\\', '/', (string) realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? __FILE__)));
        if ($app_dir !== '' && strpos($script_dir, $app_dir) === 0) {
            $sub = substr($script_dir, strlen($app_dir));
            if ($sub !== '' && substr($base, -strlen($sub)) === $sub) {
                $base = substr($base, 0, -strlen($sub));
            }
        }
    }
    return rtrim($base, '/') . '/';
}

// URL relatif terhadap aplikasi: url('artikel/judul') -> /artikel/judul
function url($path = '')
{
    return base_path() . ltrim($path, '/');
}

// URL lengkap (untuk Open Graph, canonical, sitemap). Pakai APP_URL jika diatur supaya
// tidak bergantung pada header Host dari request.
function site_url($path = '')
{
    $app_url = rtrim(env('APP_URL'), '/');
    if ($app_url === '') {
        $host = preg_replace('/[^A-Za-z0-9.\-:]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        $app_url = (is_https() ? 'https' : 'http') . '://' . $host . rtrim(base_path(), '/');
    }
    return $app_url . '/' . ltrim($path, '/');
}

// Gambar sebagai URL lengkap (Blob sudah lengkap, file lokal diberi domain)
function absolute_image_url($gambar)
{
    $src = image_url($gambar);
    if ($src === '' || preg_match('#^https?://#', $src)) return $src;
    return site_url($src);
}

function article_url($row)
{
    return !empty($row['slug']) ? url('artikel/' . $row['slug']) : url('artikel.php?id=' . (int) $row['id']);
}

// "Belajar PHP & AI!" -> "belajar-php-ai"
function slugify($text)
{
    $text = mb_strtolower(trim((string) $text), 'UTF-8');
    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($ascii !== false) $text = $ascii;
    }
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return substr($text, 0, 80) ?: 'artikel';
}

// Slug unik di tabel article (abaikan artikel $except_id sendiri saat edit)
function unique_slug($text, $except_id = 0)
{
    $base = rtrim(substr(slugify($text), 0, 70), '-');
    $slug = $base;
    for ($i = 2; db_value("SELECT COUNT(*) FROM article WHERE slug = ? AND id <> ?", [$slug, (int) $except_id]); $i++) {
        $slug = $base . '-' . $i;
    }
    return $slug;
}

// Isi slug untuk artikel lama yang belum punya slug (setelah migrasi 004)
function backfill_slugs()
{
    try {
        foreach (db_all("SELECT id, judul FROM article WHERE slug IS NULL OR slug = ''") as $row) {
            db_run("UPDATE article SET slug = ? WHERE id = ?", [unique_slug($row['judul'] ?: 'artikel', $row['id']), $row['id']]);
        }
    } catch (PDOException $e) {
        error_log("backfill_slugs: " . $e->getMessage());
    }
}

// Waktu sekarang dalam zona waktu aplikasi (Asia/Jakarta), dipakai untuk jadwal terbit
function now_string()
{
    return date('Y-m-d H:i:s');
}

// Kondisi SQL artikel yang boleh dilihat publik. Parameter: [now_string()]
const PUBLISHED_SQL = "status = 'published' AND (published_at IS NULL OR published_at <= ?)";

function is_published($row)
{
    return ($row['status'] ?? 'published') === 'published'
        && (empty($row['published_at']) || $row['published_at'] <= now_string());
}

// Tanggal yang ditampilkan ke pembaca
function display_date($row)
{
    $value = !empty($row['published_at']) ? $row['published_at'] : ($row['tanggal'] ?? '');
    $time = strtotime((string) $value);
    if (!$time) return '';
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return date('j', $time) . ' ' . $bulan[(int) date('n', $time)] . ' ' . date('Y', $time);
}

// Potongan teks polos untuk deskripsi / kartu
function excerpt($html, $length = 160)
{
    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8')));
    if (mb_strlen($text) <= $length) return $text;
    $cut = mb_substr($text, 0, $length);
    $space = mb_strrpos($cut, ' ');
    return rtrim($space > $length * 0.6 ? mb_substr($cut, 0, $space) : $cut, " ,.;:") . '…';
}

// "PHP, AI ,  php , Tips Belajar" -> ["PHP", "AI", "Tips Belajar"] (unik, maks 8 tag, maks 40 karakter)
function parse_tags($input)
{
    $tags = [];
    foreach (explode(',', (string) $input) as $name) {
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        $name = mb_substr($name, 0, 40);
        $slug = slugify($name);
        // lewati tag kosong atau tanpa huruf/angka latin (slugify jatuh ke nilai default "artikel")
        if ($name === '' || ($slug === 'artikel' && stripos($name, 'artikel') === false)) continue;
        $tags[$slug] = $name;
        if (count($tags) >= 8) break;
    }
    return $tags; // [slug => nama]
}

// Simpan ulang daftar tag sebuah artikel
function save_article_tags($article_id, $input)
{
    $article_id = (int) $article_id;
    db_run("DELETE FROM article_tag WHERE article_id = ?", [$article_id]);
    foreach (parse_tags($input) as $slug => $name) {
        $tag_id = db_value("SELECT id FROM tag WHERE slug = ?", [$slug]);
        if (!$tag_id) {
            db_run("INSERT INTO tag (name, slug) VALUES (?, ?)", [$name, $slug]);
            $tag_id = db_value("SELECT id FROM tag WHERE slug = ?", [$slug]);
        }
        if ($tag_id) {
            db_run("INSERT INTO article_tag (article_id, tag_id) VALUES (?, ?)", [$article_id, (int) $tag_id]);
        }
    }
    // tag yang tidak dipakai artikel mana pun dihapus
    db_run("DELETE FROM tag WHERE id NOT IN (SELECT tag_id FROM article_tag)");
}

// Tag untuk beberapa artikel sekaligus: [article_id => [[name, slug], ...]]
function tags_for_articles($ids)
{
    $ids = array_values(array_filter(array_map('intval', (array) $ids)));
    $result = array_fill_keys($ids, []);
    if (!$ids) return $result;
    try {
        $rows = db_all(
            "SELECT at.article_id, t.name, t.slug FROM article_tag at JOIN tag t ON t.id = at.tag_id
             WHERE at.article_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ") ORDER BY t.name",
            $ids
        );
    } catch (PDOException $e) {
        // tabel tag belum ada (migrasi 004 belum dijalankan)
        return $result;
    }
    foreach ($rows as $row) {
        $result[(int) $row['article_id']][] = ['name' => $row['name'], 'slug' => $row['slug']];
    }
    return $result;
}

function tags_string($tags)
{
    return implode(', ', array_column($tags, 'name'));
}
