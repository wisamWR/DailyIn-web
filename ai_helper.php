<?php
header('Content-Type: application/json');
// Disable error reporting for JSON endpoint
error_reporting(0);
ini_set('display_errors', 0);

// ==================================================================
// ==================================================================
// KONFIGURASI AI
// ==================================================================
require_once 'config_ai.php';
$apiKey = GEMINI_API_KEY;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil input JSON dari frontend
    $data = json_decode(file_get_contents('php://input'), true);
    $text = $data['text'] ?? '';
    $mode = $data['mode'] ?? 'summary'; // Opsi: 'summary', 'tags', 'mood'

    if (empty($text)) {
        echo json_encode(['error' => 'Teks tidak boleh kosong']);
        exit;
    }

    // Tentukan Prompt sesuai mode
    $prompt = "";
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

JUDUL ARTIKEL: \"{$text}\"

Mulai menulis artikel sekarang, pastikan isinya 100% nyambung dengan judul di atas:";
    } else if ($mode == 'generate_summary') {
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
    } else {
        $prompt = "Ringkas teks ini: " . $text;
    }

    // Daftar model untuk dicoba (Fallback Strategy)
    // Prioritas: Model Ringan (Lite) -> Versi Latest -> Versi Stabil
    $models_to_try = [
        "gemini-2.0-flash-lite-001",
        "gemini-flash-latest",
        "gemini-2.0-flash",
        "gemini-2.5-flash"
    ];

    $success = false;
    $final_result = [];

    foreach ($models_to_try as $model_name) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/$model_name:generateContent?key=" . $apiKey;

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

        // Kirim Request dengan cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        // FIX untuk XAMPP di Windows (Lewati verifikasi SSL)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

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
                     // Jika gagal validasi, jangan kirim hasil, tapi coba model lain?
                     // Untuk saat ini, kita anggap ini kegagalan model ini, dan continue ke model berikutnya?
                     // Atau return error langsung jika ini adalah iterasi terakhir?
                     // Sesuai instruksi user "return error", tapi ini dalam loop models.
                     // Kita set error dan continue
                     $final_result = ['error' => 'Artikel yang dihasilkan tidak memenuhi standar struktur.'];
                     continue; 
                }
                
                // Clean up
                $generatedText = trim($generatedText);
            }

            echo json_encode(['result' => trim($generatedText), 'model_used' => $model_name]);
            $success = true;
            break; // Stop loop, kita sudah berhasil
        }

        // Simpan error terakhir untuk debugging
        if (isset($result['error'])) {
            $final_result = ['error' => 'Google API Error (' . $model_name . '): ' . ($result['error']['message'] ?? 'Unknown'), 'raw_response' => $result];
        }
    }

    // Jika setelah mencoba semua model masih gagal
    if (!$success) {
        echo json_encode($final_result);
    }
} else {
    echo json_encode(['error' => 'Method not allowed']);
}
