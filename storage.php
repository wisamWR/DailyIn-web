<?php
// Penyimpanan gambar upload.
// - Lokal (XAMPP/Laragon): disimpan di folder img/, database menyimpan nama file.
// - Vercel: folder di server tidak permanen, jadi gambar disimpan di Vercel Blob
//   (jika BLOB_READ_WRITE_TOKEN diatur), database menyimpan URL lengkap.

require_once __DIR__ . '/env.php';

function blob_enabled()
{
    return env('BLOB_READ_WRITE_TOKEN') !== '';
}

function is_blob_url($value)
{
    $host = parse_url((string) $value, PHP_URL_HOST);
    return is_string($host) && preg_match('/\.public\.blob\.vercel-storage\.com$/', $host)
        && parse_url($value, PHP_URL_SCHEME) === 'https';
}

// Hanya izinkan nama file gambar lokal (tanpa folder / "../")
function safe_image_name($name)
{
    $name = basename((string) $name);
    return preg_match('/^[A-Za-z0-9_\-]+\.(jpg|jpeg|png|gif|webp)$/i', $name) ? $name : '';
}

// URL untuk dipakai di <img src="...">
function image_url($gambar)
{
    if (is_blob_url($gambar)) return $gambar;
    $name = safe_image_name($gambar);
    return $name === '' ? '' : 'img/' . $name;
}

// Apakah gambar bisa ditampilkan
function image_available($gambar)
{
    if (is_blob_url($gambar)) return true;
    $name = safe_image_name($gambar);
    return $name !== '' && is_file(__DIR__ . '/img/' . $name);
}

function blob_request($method, $path, $body, $headers)
{
    $token = env('BLOB_READ_WRITE_TOKEN');
    // format token: vercel_blob_rw_<storeId>_<secret>
    $parts = explode('_', $token);
    $store_id = $parts[3] ?? '';

    $ch = curl_init('https://vercel.com/api/blob' . $path);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
        'authorization: Bearer ' . $token,
        'x-api-version: 13',
        'x-vercel-blob-store-id: ' . $store_id,
    ], $headers));

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($response === false || $code >= 300) {
        error_log("Vercel Blob $method $path failed ($code): " . ($err ?: $response));
        return null;
    }
    return json_decode($response, true) ?? [];
}

// Simpan file upload. Hasilnya nilai untuk kolom `gambar` (nama file atau URL Blob), atau false jika gagal.
function store_image($tmp_path, $new_name, $mime)
{
    if (blob_enabled()) {
        $body = file_get_contents($tmp_path);
        $result = blob_request('PUT', '/?' . http_build_query(['pathname' => 'dailyin/' . $new_name]), $body, [
            'x-vercel-blob-access: public',
            'x-content-type: ' . $mime,
            'x-add-random-suffix: 0',
            'x-content-length: ' . strlen($body),
        ]);
        return is_blob_url($result['url'] ?? '') ? $result['url'] : false;
    }

    return move_uploaded_file($tmp_path, __DIR__ . '/img/' . $new_name) ? $new_name : false;
}

// Hapus file gambar (lokal atau Blob)
function remove_image($gambar)
{
    if (is_blob_url($gambar)) {
        if (blob_enabled()) {
            blob_request('POST', '/delete', json_encode(['urls' => [$gambar]]), ['content-type: application/json']);
        }
        return;
    }

    $name = safe_image_name($gambar);
    if ($name !== '' && is_file(__DIR__ . '/img/' . $name)) {
        unlink(__DIR__ . '/img/' . $name);
    }
}
