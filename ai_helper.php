<?php
header('Content-Type: application/json');
// Disable error reporting for JSON endpoint
error_reporting(0);
// Panggilan AI bisa lama; beri waktu lebih dari budget AI (lihat AI_TIME_BUDGET)
set_time_limit(60);
ini_set('display_errors', 0);

require_once 'auth.php';

// ==================================================================
// KONFIGURASI AI
// ==================================================================
// Ambil API key dari .env (GEMINI_API_KEY). config_ai.php lama tetap didukung.
$apiKey = env('GEMINI_API_KEY');
if ($apiKey === '' && file_exists(__DIR__ . '/config_ai.php')) {
    require_once __DIR__ . '/config_ai.php';
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
}

// Daftar model untuk dicoba (Fallback Strategy), bisa diganti lewat GEMINI_MODELS di .env
// Prioritas: Model Ringan (Lite, cepat) -> Versi Latest. Alias *-latest selalu menunjuk model terbaru,
// jadi tidak 404 saat versi lama dihentikan Google
$models_to_try = array_filter(array_map('trim', explode(',', env('GEMINI_MODELS', 'gemini-flash-lite-latest,gemini-flash-latest'))));

/** @return never */
function json_out($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Ubah Markdown sederhana (judul #, **tebal**, *miring*, daftar -, paragraf) menjadi HTML
function markdown_to_html($text)
{
    $html = '';
    foreach (preg_split('/\n\s*\n/', trim(str_replace("\r", '', $text))) as $block) {
        $block = trim($block);
        if ($block === '') continue;

        $inline = function ($s) {
            $s = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
            $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);
            return preg_replace('/(?<!\*)\*(?!\s)(.+?)(?<!\s)\*(?!\*)/s', '<em>$1</em>', $s);
        };

        if (preg_match('/^#{1,6}\s+(.+?)(?:\n(.*))?$/su', $block, $m)) {
            // judul (tingkat berapa pun) menjadi <h2>, sisa blok menjadi paragraf
            $html .= '<h2>' . $inline(trim($m[1], " #")) . "</h2>\n";
            if (!empty($m[2]) && trim($m[2]) !== '') {
                $html .= '<p>' . $inline(trim($m[2])) . "</p>\n";
            }
        } elseif (preg_match('/^\s*[-*•]\s+/u', $block)) {
            $items = preg_split('/\n\s*[-*•]\s+/u', "\n" . $block);
            $html .= "<ul>\n";
            foreach ($items as $item) {
                if (trim($item) !== '') $html .= '<li>' . $inline(trim(preg_replace('/^\s*[-*•]\s+/u', '', $item))) . "</li>\n";
            }
            $html .= "</ul>\n";
        } else {
            $html .= '<p>' . $inline(preg_replace('/\s*\n\s*/', ' ', $block)) . "</p>\n";
        }
    }
    return $html;
}

// Kirim prompt ke Gemini, coba model satu per satu sampai berhasil
function ask_gemini($prompt, $mode, $apiKey, $models_to_try)
{
    if ($apiKey === '') {
        return ['error' => 'GEMINI_API_KEY belum diatur di file .env'];
    }

    $final_result = ['error' => 'Gagal menghubungi AI.'];

    // Total waktu untuk semua model harus di bawah batas durasi function di Vercel
    $deadline = microtime(true) + (int) env('AI_TIME_BUDGET', 45);

    foreach ($models_to_try as $model_name) {
        $remaining = (int) floor($deadline - microtime(true));
        if ($remaining < 5) {
            $final_result = ['error' => 'AI terlalu lama merespons. Coba lagi sebentar lagi.'];
            break;
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . rawurlencode($model_name) . ":generateContent";

        // Siapkan Payload Data
        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ];

        // Kirim Request dengan cURL (API key lewat header, bukan di URL)
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'x-goog-api-key: ' . $apiKey]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, min(30, $remaining));
        // Beberapa container tidak punya jalur IPv6 keluar, jadi pakai IPv4 supaya tidak menggantung
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        // Verifikasi SSL tetap aktif. Untuk XAMPP di Windows yang tidak punya CA bundle,
        // isi CURL_CA_BUNDLE di .env dengan path file cacert.pem (lihat README)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        if (env('CURL_CA_BUNDLE') !== '') {
            curl_setopt($ch, CURLOPT_CAINFO, env('CURL_CA_BUNDLE'));
        }

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        error_log(sprintf("Gemini %s: HTTP %d in %.1fs%s", $model_name, $http_code, curl_getinfo($ch, CURLINFO_TOTAL_TIME), $err ? " ($err)" : ''));
        curl_close($ch);

        if ($err) {
            $final_result = ['error' => 'Koneksi Error (Curl): ' . $err];
            continue; // Coba model berikutnya jika error koneksi
        }

        $result = json_decode($response, true);

        // Jika berhasil dapat teks
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $generatedText = $result['candidates'][0]['content']['parts'][0]['text'];

            // Tambahkan validasi panjang caption
            if ($mode === 'generate_caption') {
                $words = str_word_count($generatedText);
                if ($words > 20) {
                    // Fallback: potong jadi 15 kata pertama
                    $words_array = explode(' ', $generatedText);
                    $generatedText = implode(' ', array_slice($words_array, 0, 15));
                }
            }

            // Post-Processing untuk generate_content
            if ($mode === 'generate_content') {
                // Remove markdown code blocks jika ada
                $generatedText = preg_replace('/```html|```/i', '', $generatedText);

                // Beberapa model menjawab dengan Markdown, bukan HTML: ubah dulu ke HTML
                if (!preg_match('/<p[\s>]/i', $generatedText)) {
                    $generatedText = markdown_to_html($generatedText);
                }

                // Pastikan minimal ada 2 <h2> dan 3 <p> (tag boleh punya atribut)
                $h2_count = preg_match_all('/<h2[\s>]/i', $generatedText);
                $p_count = preg_match_all('/<p[\s>]/i', $generatedText);

                if ($h2_count < 2 || $p_count < 3) {
                    // Anggap model ini gagal, coba model berikutnya
                    error_log("Gemini $model_name: struktur artikel kurang (h2=$h2_count, p=$p_count): " . mb_substr(preg_replace('/\s+/', ' ', $generatedText), 0, 200));
                    $final_result = ['error' => 'Artikel yang dihasilkan tidak memenuhi standar struktur.'];
                    continue;
                }

                // Buang tag berbahaya dari HTML hasil AI
                $generatedText = sanitize_html(trim($generatedText));
            }

            return ['result' => trim($generatedText), 'model_used' => $model_name];
        }

        // Simpan error terakhir untuk debugging
        if (isset($result['error'])) {
            $errorMsg = $result['error']['message'] ?? '';
            $status = $result['error']['status'] ?? '';

            // Deteksi Limit / Quota Exceeded
            if ($http_code == 429 || $status == 'RESOURCE_EXHAUSTED' || stripos($errorMsg, 'quota') !== false) {
                $final_result = ['error' => "Maaf, kuota AI harian Anda telah habis. Silakan coba lagi besok."];
            } else {
                // Hanya update error jika belum ada error prioritas (seperti quota habis)
                if (strpos($final_result['error'], 'Maaf, kuota') === false) {
                    $final_result = ['error' => 'Google API Error (' . $model_name . '): ' . $errorMsg];
                }
            }
        }
    }

    return $final_result;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'Method not allowed'], 405);
}

// Ambil input JSON dari frontend
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) $data = [];
$mode = $data['mode'] ?? '';

// ==================================================================
// MODE PUBLIK: ringkasan artikel untuk pengunjung
// Hanya bisa meringkas artikel yang ada di database (berdasarkan id),
// dan hasilnya disimpan supaya tidak memanggil AI berulang-ulang.
// ==================================================================
if ($mode === 'generate_summary') {
    $id = (int) ($data['id'] ?? 0);
    $cache = has_ai_summary_column();

    $article = db_one("SELECT judul, isi, status, published_at" . ($cache ? ", ai_summary" : "") . " FROM article WHERE id = ?", [$id]);

    // draft / terjadwal tidak boleh diringkas oleh pengunjung (isinya belum publik)
    if (!$article || (!is_published($article) && !is_logged_in())) {
        json_out(['error' => 'Artikel tidak ditemukan'], 404);
    }
    if ($cache && !empty($article['ai_summary'])) {
        json_out(['result' => $article['ai_summary'], 'cached' => true]);
    }

    $title = $article['judul'];
    $text = trim(html_entity_decode(strip_tags($article['isi'] ?? ''), ENT_QUOTES, 'UTF-8'));
    if ($text === '') {
        json_out(['error' => 'Artikel ini belum memiliki isi untuk diringkas.']);
    }

    $prompt = "Kamu adalah asisten yang meringkas artikel menjadi poin-poin penting.

Tugas: Buatkan ringkasan artikel dalam bentuk bullet points.

ATURAN KETAT:
- Buat 3-5 poin utama
- Setiap poin: 1 kalimat singkat (8-12 kata)
- Format: gunakan bullet '•' di awal setiap poin
- Bahasa Indonesia yang ringkas dan jelas
- Fokus pada informasi penting, bukan detail kecil
- JANGAN ada pengantar atau penutup
- Langsung list poin-poinnya

ARTIKEL:
Judul: {$title}
Isi: " . mb_substr($text, 0, 1000) . "...

Buatkan ringkasan dalam format bullet points:";

    $hasil = ask_gemini($prompt, $mode, $apiKey, $models_to_try);

    if (isset($hasil['result']) && $cache) {
        db_run("UPDATE article SET ai_summary = ? WHERE id = ?", [$hasil['result'], $id]);
    }
    if (isset($hasil['error']) && strpos($hasil['error'], 'Maaf, kuota') === false) {
        // detail error Google hanya untuk admin, pengunjung cukup pesan umum
        error_log('AI summary error: ' . $hasil['error']);
        $hasil = ['error' => 'Ringkasan AI sedang tidak tersedia. Coba lagi nanti.'];
    }
    json_out($hasil);
}

// ==================================================================
// MODE ADMIN: wajib login + CSRF token (header X-CSRF-Token)
// ==================================================================
require_login_json();
verify_csrf();

$text = mb_substr(trim($data['text'] ?? ''), 0, 20000);
$title = mb_substr(trim($data['title'] ?? ''), 0, 200);

if ($text === '') {
    json_out(['error' => 'Teks tidak boleh kosong']);
}

// Tentukan Prompt sesuai mode
if ($mode == 'generate_caption') {
    $prompt = "Kamu adalah copywriter profesional yang membuat caption menarik untuk media sosial.

Tugas: Buatkan 1 kalimat caption yang CATCHY dan ENGAGING berdasarkan artikel berikut.

ATURAN KETAT:
- Maksimal 15 kata
- 1 kalimat saja (boleh tanpa titik di akhir)
- Gaya bahasa: Casual tapi informatif
- Harus menarik perhatian (seperti caption Instagram/Facebook)
- Jangan gunakan kata 'artikel ini', 'tulisan ini', atau sejenisnya
- Langsung ke inti artikel
- Boleh gunakan emoji (1-2 emoji maksimal)

CONTOH CAPTION YANG BAIK:
- 'Rahasia produktif belajar online yang jarang orang tahu 📚'
- '5 cara simpel tingkatkan fokus belajar dari rumah'
- 'Belajar online tapi tetap efektif, ini caranya! ✨'

JUDUL ARTIKEL: {$title}

ISI ARTIKEL (potongan):
" . mb_substr($text, 0, 300) . "...

Sekarang buatkan caption dalam 1 kalimat (maksimal 15 kata):";
} else if ($mode == 'generate_content') {
    $prompt = "Kamu adalah penulis artikel profesional untuk blog Daily Journal Indonesia.

Tugas: Tulis artikel LENGKAP dan BERKUALITAS yang SANGAT RELEVAN dengan judul yang diberikan.

ATURAN KETAT:
1. Tulis HANYA tentang topik di judul. Jangan melebar ke topik lain.
2. Panjang: 600-800 kata (WAJIB).
3. Struktur HARUS seperti ini:
   - Paragraf Pembuka (langsung bahas inti topik dari judul)
   - 3-4 Subjudul yang spesifik membahas aspek-aspek dari judul
   - Paragraf Penutup (kesimpulan yang kuat)
4. Bahasa Indonesia baku tapi mengalir (enak dibaca).
5. Gaya penulisan: Seperti artikel feature di Kompas atau Tirto.
6. Hindari basa-basi seperti 'Pada artikel ini kita akan membahas...'.
7. Berikan data, contoh, atau konteks nyata yang relevan dengan judul.

FORMAT OUTPUT (HTML):
<p>Paragraf pembuka...</p>

<h2>Subjudul 1</h2>
<p>Isi...</p>

<h2>Subjudul 2</h2>
<p>Isi...</p>

<p>Penutup...</p>

JUDUL ARTIKEL: \"" . mb_substr($text, 0, 200) . "\"

Mulai menulis artikel sekarang, pastikan isinya 100% nyambung dengan judul di atas:";
} else if ($mode == 'generate_tags') {
    $prompt = "Kamu adalah editor blog yang memberi tag pada artikel.

Tugas: Berikan 3 sampai 5 tag yang paling relevan untuk artikel berikut.

ATURAN KETAT:
- Tag dalam Bahasa Indonesia (istilah teknis boleh bahasa Inggris, misalnya PHP, AI)
- Setiap tag 1-3 kata, tanpa tanda pagar (#)
- Tulis dalam SATU baris, dipisahkan koma
- JANGAN ada penjelasan atau teks lain

JUDUL ARTIKEL: {$title}

ISI ARTIKEL (potongan):
" . mb_substr($text, 0, 1500) . "

Tag:";
} else {
    json_out(['error' => 'Mode AI tidak dikenal'], 400);
}

$hasil = ask_gemini($prompt, $mode, $apiKey, $models_to_try);

// rapikan saran tag: buang #, baris baru, duplikat (pakai aturan yang sama dengan penyimpanan tag)
if ($mode == 'generate_tags' && isset($hasil['result'])) {
    $hasil['result'] = implode(', ', parse_tags(str_replace(["\n", '#', '*'], [',', '', ''], $hasil['result'])));
}

json_out($hasil);
