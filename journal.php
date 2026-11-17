<?php
// Fitur jurnal & statistik: mood, streak menulis, kalender aktivitas, "hari ini di tahun lalu",
// jumlah view, dan pemakaian AI.

const MOODS = [
    'senang'   => ['😊', 'Senang'],
    'semangat' => ['🔥', 'Semangat'],
    'biasa'    => ['😐', 'Biasa'],
    'lelah'    => ['😴', 'Lelah'],
    'sedih'    => ['😢', 'Sedih'],
    'cemas'    => ['😟', 'Cemas'],
    'marah'    => ['😠', 'Marah'],
];

function valid_mood($mood)
{
    return is_string($mood) && isset(MOODS[$mood]) ? $mood : null;
}

// "😊 Senang" (atau '' jika tidak ada mood)
function mood_label($mood)
{
    return isset(MOODS[$mood]) ? MOODS[$mood][0] . ' ' . MOODS[$mood][1] : '';
}

// Tanggal entri jurnal: tanggal terbit, atau tanggal terakhir disimpan untuk draft
const ENTRY_DATE_SQL = "COALESCE(published_at, tanggal)";

// Daftar tanggal (Y-m-d) semua entri sampai hari ini (jadwal di masa depan tidak dihitung)
function entry_dates($since = null)
{
    $params = [now_string()];
    $where = ENTRY_DATE_SQL . " <= ?";
    if ($since !== null) {
        $where .= " AND " . ENTRY_DATE_SQL . " >= ?";
        $params[] = $since . ' 00:00:00';
    }
    $dates = [];
    foreach (db_all("SELECT " . ENTRY_DATE_SQL . " AS d FROM article WHERE $where", $params) as $row) {
        $dates[] = substr((string) $row['d'], 0, 10);
    }
    return $dates;
}

// Streak menulis dari daftar tanggal. Streak saat ini tetap berjalan jika hari ini belum menulis
// tapi kemarin menulis. Hasil: ['current' => int, 'longest' => int, 'last' => 'Y-m-d'|null]
function writing_streaks(array $dates, $today)
{
    $days = array_unique(array_filter($dates, fn($d) => $d <= $today));
    sort($days);
    if (!$days) return ['current' => 0, 'longest' => 0, 'last' => null];

    $longest = 1;
    $run = 1;
    for ($i = 1; $i < count($days); $i++) {
        $gap = (strtotime($days[$i]) - strtotime($days[$i - 1])) / 86400;
        $run = ((int) round($gap) === 1) ? $run + 1 : 1;
        $longest = max($longest, $run);
    }

    $last = end($days);
    $since_last = (int) round((strtotime($today) - strtotime($last)) / 86400);
    // $run sekarang = panjang rangkaian yang berakhir di tanggal terakhir
    $current = $since_last <= 1 ? $run : 0;

    return ['current' => $current, 'longest' => $longest, 'last' => $last];
}

// Jumlah entri per tanggal: ['2026-11-16' => 2, ...]
function count_by_day(array $dates)
{
    $counts = [];
    foreach ($dates as $d) {
        $counts[$d] = ($counts[$d] ?? 0) + 1;
    }
    return $counts;
}

// Grid kalender: $weeks kolom (Senin..Minggu), berakhir di minggu ini.
// Hasil: daftar minggu, tiap minggu daftar 7 hari ['date' => ..., 'count' => ..., 'future' => bool]
function activity_grid(array $counts, $today, $weeks = 26)
{
    $monday_this_week = strtotime('monday this week', strtotime($today));
    $start = strtotime('-' . ($weeks - 1) . ' weeks', $monday_this_week);
    $grid = [];
    for ($w = 0; $w < $weeks; $w++) {
        $week = [];
        for ($d = 0; $d < 7; $d++) {
            $date = date('Y-m-d', strtotime("+" . ($w * 7 + $d) . " days", $start));
            $week[] = ['date' => $date, 'count' => $counts[$date] ?? 0, 'future' => $date > $today];
        }
        $grid[] = $week;
    }
    return $grid;
}

// Tingkat warna heatmap (0 = kosong, 1..4 = makin banyak)
function activity_level($count)
{
    return $count <= 0 ? 0 : min(4, $count);
}

// Sebaran mood $days hari terakhir: ['senang' => 3, ...] (urutan sesuai MOODS)
function mood_counts($days = 30)
{
    $since = date('Y-m-d', strtotime("-" . ($days - 1) . " days", strtotime(now_string())));
    $rows = db_all(
        "SELECT mood, COUNT(*) AS total FROM article WHERE mood IS NOT NULL AND " . ENTRY_DATE_SQL . " >= ? AND " . ENTRY_DATE_SQL . " <= ? GROUP BY mood",
        [$since . ' 00:00:00', now_string()]
    );
    $counts = array_fill_keys(array_keys(MOODS), 0);
    foreach ($rows as $row) {
        if (isset($counts[$row['mood']])) $counts[$row['mood']] = (int) $row['total'];
    }
    return $counts;
}

function popular_articles($limit = 5)
{
    return db_all("SELECT id, judul, slug, views, status, published_at FROM article WHERE views > 0 ORDER BY views DESC, id DESC LIMIT " . (int) $limit);
}

// Entri pada tanggal & bulan yang sama di tahun-tahun sebelumnya
function on_this_day($today)
{
    $md = substr($today, 5, 5);
    $year = substr($today, 0, 4);
    $result = [];
    foreach (db_all("SELECT id, judul, slug, mood, status, published_at, tanggal, " . ENTRY_DATE_SQL . " AS d FROM article ORDER BY d DESC") as $row) {
        $d = substr((string) $row['d'], 0, 10);
        if (substr($d, 5, 5) === $md && substr($d, 0, 4) < $year) {
            $row['years_ago'] = (int) $year - (int) substr($d, 0, 4);
            $result[] = $row;
        }
    }
    return $result;
}

// Catat pemakaian AI (model 'cache' = dilayani dari cache, tidak memanggil Gemini)
function log_ai_usage($mode, $model, $success)
{
    try {
        db_query("INSERT INTO ai_usage (mode, model, success, created_at) VALUES (?, ?, ?, ?)", [(string) $mode, substr((string) $model, 0, 60), $success ? 1 : 0, now_string()]);
    } catch (PDOException $e) {
        // tabel belum ada (migrasi 005): pencatatan dilewati
    }
}

// Ringkasan pemakaian AI $days hari terakhir per mode
function ai_usage_summary($days = 30)
{
    $since = date('Y-m-d H:i:s', strtotime("-$days days", strtotime(now_string())));
    try {
        return db_all(
            "SELECT mode, COUNT(*) AS total, SUM(success) AS ok, SUM(CASE WHEN model = 'cache' THEN 1 ELSE 0 END) AS cached
             FROM ai_usage WHERE created_at >= ? GROUP BY mode ORDER BY total DESC",
            [$since]
        );
    } catch (PDOException $e) {
        return [];
    }
}

// Tambah 1 view (sekali per sesi per artikel, bukan admin, bukan bot/preview link)
function count_article_view($article_id)
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (is_logged_in() || $ua === '' || preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|discord|curl|wget|python|headless/i', $ua)) {
        return false;
    }
    $article_id = (int) $article_id;
    if (!empty($_SESSION['viewed'][$article_id])) return false;
    $_SESSION['viewed'][$article_id] = 1;
    return db_run("UPDATE article SET views = views + 1 WHERE id = ?", [$article_id]);
}
