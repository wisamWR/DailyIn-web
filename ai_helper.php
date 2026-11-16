<?php
header('Content-Type: application/json');
// Disable error reporting for JSON endpoint
error_reporting(0);
ini_set('display_errors', 0);

require_once 'auth.php';
require_once 'env.php';

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
// Prioritas: Model Ringan (Lite) -> Versi Latest -> Versi Stabil
$models_to_try = array_filter(array_map('trim', explode(',', env('GEMINI_MODELS', 'gemini-2.5-flash-lite,gemini-flash-latest,gemini-2.5-flash'))));

function json_out($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Kirim prompt ke Gemini, coba model satu per satu sampai berhasil
function ask_gemini($prompt, $mode, $apiKey, $models_to_try)
{
    if ($apiKey === '') {
        return ['error' => 'GEMINI_API_KEY belum diatur di file .env'];
    }

    $final_result = ['error' => 'Gagal menghubungi AI.'];

    foreach ($models_to_try as $model_name) {
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

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

                // Pastikan minimal ada 2 <h2> dan 3 <p>
                $h2_count = substr_count($generatedText, '<h2>');
                $p_count = substr_count($generatedText, '<p>');

                if ($h2_count < 2 || $p_count < 3) {
                    // Anggap model ini gagal, coba model berikutnya
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
    require_once 'koneksi.php';

    $id = (int) ($data['id'] ?? 0);
    $cache = has_ai_summary_column($conn);

    $stmt = $conn->prepare("SELECT judul, isi" . ($cache ? ", ai_summary" : "") . " FROM article WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $article = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$article) {
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
        $stmt = $conn->prepare("UPDATE article SET ai_summary = ? WHERE id = ?");
        $stmt->bind_param("si", $hasil['result'], $id);
        $stmt->execute();
        $stmt->close();
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
} else {
    json_out(['error' => 'Mode AI tidak dikenal'], 400);
}

json_out(ask_gemini($prompt, $mode, $apiKey, $models_to_try));
