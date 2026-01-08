<?php
// Disable error reporting for JSON endpoint
error_reporting(0);
ini_set('display_errors', 0);

require_once '../koneksi.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID artikel tidak valid']);
    exit;
}

// Prepare statement untuk MySQLi
$stmt = $conn->prepare("SELECT judul, isi FROM article WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$article = $result->fetch_assoc();

if ($article) {
    echo json_encode([
        'success' => true,
        'judul' => $article['judul'],
        'isi_artikel' => strip_tags($article['isi']) // Bersihkan tag HTML agar tidak terlalu panjang
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Artikel tidak ditemukan']);
}

$stmt->close();
$conn->close();
?>
