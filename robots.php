<?php
// /robots.txt (dipanggil dari index.php supaya alamat sitemap selalu sesuai domain)
require_once __DIR__ . '/auth.php';

header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n";
echo "Disallow: " . url('admin.php') . "\n";
echo "Disallow: " . url('login.php') . "\n";
echo "Disallow: " . url('ai_helper.php') . "\n";
echo "Disallow: " . url('api/') . "\n";
echo "Disallow: " . url('*?q=') . "\n";
echo "\n";
echo "Sitemap: " . site_url('sitemap.xml') . "\n";
