<?php
require_once "auth.php";

//check jika belum ada user yang login arahkan ke halaman login (dan hentikan script)
require_login();

include "koneksi.php";

// penanda bahwa halaman di-include lewat admin.php (bukan diakses langsung)
define('DAILYIN_ADMIN', true);

// hanya halaman di daftar ini yang boleh di-include
$pages = ['dashboard', 'article', 'gallery', 'password'];
$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $pages, true)) {
    $page = 'dashboard';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dailying | Admin</title>
    <link rel="icon" href="img/logo.png" />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
        crossorigin="anonymous" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        // kirim CSRF token di setiap request AJAX (ai_helper.php, *_data.php)
        $.ajaxSetup({ headers: { 'X-CSRF-Token': <?= json_encode(csrf_token()) ?> } });
    </script>
    <!-- Summernote CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        #content {
            flex: 1;
        }

        footer {
            margin-top: auto;
        }
    </style>
</head>

<body>
    <!-- nav begin -->
    <nav class="navbar navbar-expand-sm bg-body-tertiary sticky-top bg-primary-subtle">
        <div class="container">
            <a class="navbar-brand" target="_blank" href=".">Dailyin</a>
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent"
                aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 text-dark">
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php?page=dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php?page=article">Article</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php?page=gallery">Gallery</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-primary fw-bold" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= e($_SESSION['username']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="admin.php?page=password">Ganti Password</a></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- nav end -->
    <!-- content begin -->
    <section id="content" class="p-3 p-sm-5">
        <div class="container">
            <h4 class="lead display-6 pb-2 border-bottom border-primary-subtle"><?= e(ucfirst($page)) ?></h4>
            <?php include($page . ".php"); ?>
        </div>
    </section>
    <!-- content end -->
    <!-- footer begin -->
    <footer class="text-center text-align-center justify-content-center p-3 p-sm-5 bg-primary-subtle">
        <div>
            <a href="https://www.instagram.com/udinusofficial"><i class="bi bi-instagram h2 p-2 text-dark"></i></a>
            <a href="https://twitter.com/udinusofficial"><i class="bi bi-twitter h2 p-2 text-dark"></i></a>
            <a href="https://wa.me/+62812685577"><i class="bi bi-whatsapp h2 p-2 text-dark"></i></a>
        </div>
        <div>Mohammad Wisam Wiraghina &copy; 2025</div>
    </footer>
    <!-- footer end -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous">
    </script>
    <!-- Summernote JS -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
</body>

</html>