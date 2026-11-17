<?php
// /sitemap.xml: beranda, artikel yang sudah terbit, dan halaman tag (dipanggil dari index.php)
require_once __DIR__ . '/auth.php';

$articles = db_all(
    "SELECT slug, id, tanggal, published_at FROM article WHERE " . PUBLISHED_SQL . " ORDER BY COALESCE(published_at, tanggal) DESC",
    [now_string()]
);
$tags = db_all(
    "SELECT DISTINCT t.slug FROM tag t JOIN article_tag at ON at.tag_id = t.id JOIN article a ON a.id = at.article_id
     WHERE a.status = 'published' AND (a.published_at IS NULL OR a.published_at <= ?) ORDER BY t.slug",
    [now_string()]
);

$xml = fn($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
echo '  <url><loc>' . $xml(site_url()) . '</loc><changefreq>daily</changefreq><priority>1.0</priority></url>' . "\n";
foreach ($articles as $row) {
    if (empty($row['slug'])) continue;
    $lastmod = date('Y-m-d', strtotime($row['tanggal'] ?: $row['published_at']));
    echo '  <url><loc>' . $xml(site_url('artikel/' . $row['slug'])) . '</loc><lastmod>' . $lastmod . '</lastmod><priority>0.8</priority></url>' . "\n";
}
foreach ($tags as $row) {
    echo '  <url><loc>' . $xml(site_url('tag/' . $row['slug'])) . '</loc><priority>0.5</priority></url>' . "\n";
}
echo '</urlset>' . "\n";
