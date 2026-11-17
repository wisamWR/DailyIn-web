<?php
// Halaman "tidak ditemukan". Dipanggil dari index.php untuk URL yang tidak ada.
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Halaman tidak ditemukan | DailyIn</title>
  <meta name="robots" content="noindex" />
  <link rel="icon" href="/img/logo.png" />
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
    crossorigin="anonymous" />
</head>

<body class="bg-primary-subtle d-flex align-items-center min-vh-100">
  <div class="container text-center py-5">
    <img src="/img/logo.png" alt="DailyIn" width="96" class="mb-4" />
    <h1 class="display-1 fw-bold text-primary">404</h1>
    <p class="lead mb-4">Halaman yang Anda cari tidak ditemukan.</p>
    <a href="/" class="btn btn-primary rounded-4 px-4">Kembali ke Beranda</a>
  </div>
</body>

</html>
