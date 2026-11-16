<?php
// Disable error reporting for JSON endpoint
error_reporting(0);
ini_set('display_errors', 0);

// koneksi + helper sanitize_html() dan image_url()
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID artikel tidak valid']);
    exit;
}

$article = db_one("SELECT id, judul, slug, isi, gambar, tanggal, status, published_at FROM article WHERE id = ?", [$id]);

// draft / terjadwal hanya untuk admin
if ($article && !is_published($article) && !is_logged_in()) {
    $article = null;
}

if ($article) {
    echo json_encode([
        'success' => true,
        'id' => (int) $article['id'],
        'judul' => $article['judul'],
        // HTML artikel yang sudah dibersihkan, aman untuk ditampilkan di modal
        'isi' => sanitize_html($article['isi'] ?? ''),
        // versi teks biasa (dipakai sebelumnya, tetap ada supaya kompatibel)
        'isi_artikel' => strip_tags($article['isi'] ?? ''),
        'gambar' => image_url($article['gambar']),
        'url' => article_url($article),
        'tanggal' => $article['tanggal'],
    ]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Artikel tidak ditemukan']);
}
