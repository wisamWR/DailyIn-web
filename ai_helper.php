<?php
header('Content-Type: application/json');

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
    if ($mode == 'summary') {
        $prompt = "Buatkan ringkasan informatif dalam Bahasa Indonesia (2-3 kalimat) yang menjelaskan poin utama dari artikel berikut. Jangan gunakan gaya promosi, fokus pada isi konten: \n\n" . $text;
    } else if ($mode == 'generate_content') {
        $prompt = "Buatkan artikel lengkap, informatif, dan menarik dalam Bahasa Indonesia tentang topik: '$text'. \n\nArtikel harus: \n- Memiliki paragraf pembuka, isi, dan penutup. \n- Panjang sekitar 3-4 paragraf. \n- Gaya bahasa santai tapi sopan (cocok untuk daily journal). \n- Jangan gunakan markdown heading (seperti # atau ##), gunakan paragraf biasa saja.";
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
