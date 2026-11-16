<?php
// Front controller sederhana: web server mengarahkan URL yang tidak dikenal ke index.php.
//   /                -> beranda (bisa ?q= untuk pencarian)
//   /artikel/{slug}  -> halaman detail artikel
//   /tag/{slug}      -> beranda difilter per tag
//   /sitemap.xml, /robots.txt
require_once 'auth.php';

$request_path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$route = strpos(rtrim($request_path, '/') . '/', base_path()) === 0
  ? trim((string) substr($request_path, strlen(base_path())), '/')
  : null;

$current_tag = null;
if ($route === '' || $route === 'index.php') {
  // beranda
} elseif ($route !== null && preg_match('#^artikel/([a-z0-9-]{1,200})$#', $route, $m)) {
  $article_slug = $m[1];
  require __DIR__ . '/artikel.php';
  exit;
} elseif ($route !== null && preg_match('#^tag/([a-z0-9-]{1,60})$#', $route, $m)) {
  $current_tag = db_one("SELECT name, slug FROM tag WHERE slug = ?", [$m[1]]);
  if (!$current_tag) {
    require __DIR__ . '/404.php';
    exit;
  }
} elseif ($route === 'sitemap.xml') {
  require __DIR__ . '/sitemap.php';
  exit;
} elseif ($route === 'robots.txt') {
  require __DIR__ . '/robots.php';
  exit;
} else {
  require __DIR__ . '/404.php';
  exit;
}

// pencarian (maks 100 karakter)
$search = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);

$page_title = $current_tag ? 'Tag: ' . $current_tag['name'] . ' | DailyIn' : ($search !== '' ? 'Cari: ' . $search . ' | DailyIn' : 'DailyIn - Smart Daily Journal');
$page_description = 'DailyIn: jurnal harian dan artikel seputar kampus, coding, dan keseharian, dibantu AI.';
$canonical = site_url($current_tag ? 'tag/' . $current_tag['slug'] : '');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <base href="<?= e(base_path()) ?>" />
  <title><?= e($page_title) ?></title>
  <meta name="description" content="<?= e($page_description) ?>" />
  <link rel="canonical" href="<?= e($canonical) ?>" />
  <?php if ($search !== '') : ?><meta name="robots" content="noindex, follow" /><?php endif; ?>
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="DailyIn" />
  <meta property="og:title" content="<?= e($page_title) ?>" />
  <meta property="og:description" content="<?= e($page_description) ?>" />
  <meta property="og:url" content="<?= e($canonical) ?>" />
  <meta property="og:image" content="<?= e(site_url('img/banner.jpg')) ?>" />
  <meta name="twitter:card" content="summary_large_image" />
  <link rel="icon" href="img/logo.png" />
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
    crossorigin="anonymous" />
</head>

<body>
  <!-- nav begin -->
  <nav class="navbar navbar-expand-lg bg-body-tertiary sticky-top">
    <div class="container">
      <a class="navbar-brand" href="#">DailyIn</a>
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
            <a class="nav-link" href="#">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#article">Article</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#gallery">Gallery</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#schedule">Schedule</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#profile">Profile</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="login.php" target="_blank">Login</a>
          </li>
          <li class="nav-item text-lg-start text-start ms-lg-3 ms-0">
            <span
              class="fw-semibold small d-lg-none mb-1 me-3 text-secondary">
              Toggle Theme
            </span>
            <div
              class="d-flex flex-row-reverse justify-content-end align-items-start gap-2 me-lg-0 me-3">
              <span
                class="icon-wrapper bg-warning text-white d-flex align-items-center justify-content-center"
                style="
                    width: 35px;
                    height: 35px;
                    cursor: pointer;
                    border-radius: 15%;
                  ">
                <i
                  id="lightIcon"
                  class="bi bi-brightness-high-fill"
                  role="button"></i>
              </span>
              <span
                class="icon-wrapper bg-secondary text-white d-flex align-items-center justify-content-center"
                style="
                    width: 35px;
                    height: 35px;
                    cursor: pointer;
                    border-radius: 15%;
                  ">
                <i
                  id="darkIcon"
                  class="bi bi-moon-stars-fill"
                  role="button"></i>
              </span>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  <!-- nav end -->
  <!-- hero begin -->
  <section id="hero" class="text-center p-3 p-sm-5 bg-primary-subtle text-sm-start">
    <div class="container">
      <div class="d-sm-flex flex-sm-row-reverse align-items-center">
        <img src="img/banner.jpg" class="img-fluid mb-4 mb-sm-0" width="300" />
        <div>
          <h1 class="fw-bold display-4">Setiap Mimpi Layak Dicatat</h1>
          <h4 class="lead display-6">
            Sebuah ruang untuk mengenal diri lebih jauh mencatat ide,
            rutinitas, dan impian yang ingin kamu capai.
          </h4>
          <h6>
            <span id="tanggal"></span>
            <span id="jam"></span>
          </h6>
        </div>
      </div>
    </div>
  </section>
  <!-- hero end -->
  <!-- article begin -->
  <section id="article" class="text-center p-3 p-sm-5">
    <?php
    // artikel yang sudah terbit (bukan draft, jadwal terbit sudah lewat)
    $where = PUBLISHED_SQL;
    $params = [now_string()];
    if ($current_tag) {
      $where .= " AND id IN (SELECT at.article_id FROM article_tag at JOIN tag t ON t.id = at.tag_id WHERE t.slug = ?)";
      $params[] = $current_tag['slug'];
    }
    if ($search !== '') {
      $like = '%' . addcslashes(mb_strtolower($search, 'UTF-8'), '%_\\') . '%';
      $where .= " AND (LOWER(judul) LIKE ? OR LOWER(isi) LIKE ?)";
      $params[] = $like;
      $params[] = $like;
    }
    $hasil = db_all("SELECT * FROM article WHERE $where ORDER BY COALESCE(published_at, tanggal) DESC", $params);
    $article_tags = tags_for_articles(array_column($hasil, 'id'));

    // tag populer (hanya dari artikel yang sudah terbit)
    $popular_tags = db_all(
      "SELECT t.name, t.slug, COUNT(*) AS total FROM tag t
       JOIN article_tag at ON at.tag_id = t.id JOIN article a ON a.id = at.article_id
       WHERE a.status = 'published' AND (a.published_at IS NULL OR a.published_at <= ?)
       GROUP BY t.id, t.name, t.slug ORDER BY total DESC, t.name LIMIT 12",
      [now_string()]
    );
    ?>
    <div class="container">
      <h1 class="fw-bold display-4 pb-3">Article</h1>

      <!-- pencarian -->
      <form class="row justify-content-center mb-3" method="get" action="<?= e(url()) ?>#article" role="search">
        <div class="col-12 col-md-6">
          <div class="input-group">
            <input type="search" class="form-control" name="q" value="<?= e($search) ?>" placeholder="Cari artikel..." aria-label="Cari artikel" maxlength="100">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
          </div>
        </div>
      </form>

      <!-- tag -->
      <?php if ($popular_tags) : ?>
        <div class="mb-4 d-flex flex-wrap justify-content-center gap-2">
          <a href="<?= e(url()) ?>#article" class="badge rounded-pill text-decoration-none <?= $current_tag ? 'text-bg-light border' : 'text-bg-primary' ?>">Semua</a>
          <?php foreach ($popular_tags as $tag) : ?>
            <a href="<?= e(url('tag/' . $tag['slug'])) ?>#article" class="badge rounded-pill text-decoration-none <?= $current_tag && $current_tag['slug'] === $tag['slug'] ? 'text-bg-primary' : 'text-bg-light border' ?>">#<?= e($tag['name']) ?> <span class="opacity-75">(<?= (int) $tag['total'] ?>)</span></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($current_tag || $search !== '') : ?>
        <p class="text-muted">
          <?= count($hasil) ?> artikel
          <?= $current_tag ? 'dengan tag <strong>#' . e($current_tag['name']) . '</strong>' : '' ?>
          <?= $search !== '' ? 'untuk pencarian <strong>&ldquo;' . e($search) . '&rdquo;</strong>' : '' ?>
          &middot; <a href="<?= e(url()) ?>#article">tampilkan semua</a>
        </p>
      <?php endif; ?>

      <div class="row row-cols-1 row-cols-md-3 g-4 justify-content-center text-start">
        <?php if (!$hasil) : ?>
          <div class="col-12 text-center text-muted py-4">Belum ada artikel yang cocok.</div>
        <?php endif; ?>
        <?php foreach ($hasil as $row) : ?>
          <div class="col">
            <div class="card h-100">
              <?php if (image_available($row["gambar"])) : ?>
                <a href="<?= e(article_url($row)) ?>">
                  <img src="<?= e(image_url($row["gambar"])) ?>" class="card-img-top" alt="<?= e($row["judul"]) ?>" loading="lazy" />
                </a>
              <?php endif; ?>
              <div class="card-body">
                <h5 class="card-title"><a href="<?= e(article_url($row)) ?>" class="text-reset text-decoration-none"><?= e($row["judul"]) ?></a></h5>
                <p class="card-text">
                  <?php $teaser = $row["summary"] != '' ? $row["summary"] : excerpt($row["isi"], 140); ?>
                  <?= $teaser !== '' ? e($teaser) : 'Artikel ini belum memiliki ringkasan.' ?>
                </p>
                <?php foreach ($article_tags[(int) $row['id']] ?? [] as $tag) : ?>
                  <a href="<?= e(url('tag/' . $tag['slug'])) ?>#article" class="badge text-bg-light border text-decoration-none me-1">#<?= e($tag['name']) ?></a>
                <?php endforeach; ?>
              </div>
              <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-body-secondary"><?= e(display_date($row)) ?></small>
                <a href="<?= e(article_url($row)) ?>" class="btn btn-primary btn-sm">Baca Selengkapnya</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <!-- article end -->
  <!-- gallery begin -->
  <section id="gallery" class="text-center p-3 p-sm-5 bg-primary-subtle">
    <div class="container">
      <h1 class="fw-bold display-4 pb-3">Gallery</h1>
      <div id="carouselExample" class="carousel slide">
        <div class="carousel-inner">
          <?php
          $hasil_gallery = db_all("SELECT * FROM gallery ORDER BY tanggal DESC");
          $active = true;
          foreach ($hasil_gallery as $row_gallery) {
            if (image_available($row_gallery["gambar"])) {
          ?>
              <div class="carousel-item <?php if ($active) {
                                          echo 'active';
                                          $active = false;
                                        } ?>">
                <img src="<?= e(image_url($row_gallery["gambar"])) ?>" class="d-block w-100" alt="<?= e($row_gallery["judul"]) ?>" />
              </div>
          <?php
            }
          }
          ?>
        </div>
        <button
          class="carousel-control-prev"
          type="button"
          data-bs-target="#carouselExample"
          data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button
          class="carousel-control-next"
          type="button"
          data-bs-target="#carouselExample"
          data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    </div>
  </section>
  <!-- gallery end -->

  <!-- schedule begin -->
  <section id="schedule" class="text-center p-3 p-sm-5">
    <div class="container">
      <h1 class="fw-bold display-5 pb-4">
        Jadwal Kuliah & Kegiatan Mahasiswa
      </h1>
      <div class="row justify-content-center g-4">
        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-primary mb-3 h-100">
            <div class="card-header bg-primary text-white fw-bold">Senin</div>
            <div class="card-body">
              <p class="mb-1 fw-semibold">09:30 - 12:00</p>
              <p class="mb-1">Logika Informatika</p>
              <p class="mb-3">Ruang H.5.12</p>
              <p class="mb-1 fw-semibold">14:10 - 15:50</p>
              <p class="mb-1">Basis Data</p>
              <p class="mb-1">Ruang H.5.10</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-success mb-3 h-100">
            <div class="card-header bg-success text-white fw-bold">
              Selasa
            </div>
            <div class="card-body">
              <p class="mb-1 fw-semibold">12:30 - 15:00</p>
              <p class="mb-1">Rekayasa Perangkat Lunak</p>
              <p class="mb-3">Ruang H.5.10</p>
              <p class="mb-1 fw-semibold">15:30 - 18:00</p>
              <p class="mb-1">Sistem Operasi</p>
              <p class="mb-1">Ruang H.3.2</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-danger mb-3 h-100">
            <div class="card-header bg-danger text-white fw-bold">Rabu</div>
            <div class="card-body">
              <p class="mb-1 fw-semibold">09:30 - 12:00</p>
              <p class="mb-1">Kriptografi</p>
              <p class="mb-3">Ruang H.5.13</p>
              <p class="mb-1 fw-semibold">12:30 - 14:10</p>
              <p class="mb-1">Pemrograman Berbasis Web</p>
              <p class="mb-1">Ruang D.2.J</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-warning mb-3 h-100">
            <div class="card-header bg-warning fw-bold">Kamis</div>
            <div class="card-body">
              <p class="mb-1 fw-semibold">14:10 - 15:50</p>
              <p class="mb-1">Basis Data</p>
              <p class="mb-3">Ruang D.2.K</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-info mb-3 h-100">
            <div class="card-header bg-info text-white fw-bold">Jumat</div>
            <div class="card-body">
              <p class="mb-1 fw-semibold">09:30 - 12:00</p>
              <p class="mb-1">Probabilitas dan Statistika</p>
              <p class="mb-3">Ruang H.3.2</p>
              <p class="mb-1 fw-semibold">13:00 - 15:00</p>
              <p class="mb-1">Data Mining</p>
              <p class="mb-1">Ruang H.4.3</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-secondary mb-3 h-100">
            <div class="card-header bg-secondary text-white fw-bold">
              Sabtu
            </div>
            <div class="card-body">
              <p class="mb-1 fw-semibold">08:00 - 10:00</p>
              <p class="mb-1">Rapat Kucing UDINUS</p>
              <p class="mb-3">Gedung F</p>
              <p class="mb-1 fw-semibold">15:00 - 16:30</p>
              <p class="mb-1">Street Feeding</p>
              <p class="mb-1">Jl. Nakula Raya</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-dark mb-3 h-100">
            <div class="card-header bg-dark text-white fw-bold">Minggu</div>
            <div class="card-body">
              <p class="text-muted fst-italic">Tidak Ada Jadwal</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- schedule end -->

  <!-- profile begin -->
  <section id="profile" class="text-center p-3 p-sm-5 bg-body-secondary">
    <div class="container">
      <h1 class="fw-bold display-5 pb-4">Profil Mahasiswa</h1>
      <div class="row align-items-center justify-content-center">
        <div class="col-12 col-md-4 mb-3 mb-md-0">
          <img
            src="img/wisam.jpg"
            alt="Foto Mahasiswa"
            class="img-fluid rounded-circle border border-3"
            style="width: 200px; height: 200px; object-fit: cover" />
        </div>
        <div class="col-12 col-md-6">
          <div class="card shadow-sm">
            <div class="card-body text-start">
              <h4 class="fw-bold text-center mb-3">
                Mohammad Wisam Wiraghina
              </h4>
              <h6 class="text-muted small mb-3 text-center">
                Mahasiswa Teknik Informatika
              </h6>
              <table class="table table-borderless">
                <tr>
                  <th scope="row">NIM</th>
                  <td>: A11.2024.15739</td>
                </tr>
                <tr>
                  <th scope="row">Program Studi</th>
                  <td>: Teknik Informatika</td>
                </tr>
                <tr>
                  <th scope="row">Email</th>
                  <td>: 111202415739@mhs.dinus.ac.id</td>
                </tr>
                <tr>
                  <th scope="row">Telepon</th>
                  <td>: +62 812 2272 67533</td>
                </tr>
                <tr>
                  <th scope="row">Alamat</th>
                  <td>: Jl. Baru II No. 163, Semarang</td>
                </tr>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- profile end -->
  <!-- footer begin -->
  <footer class="text-center p-3 p-sm-5">
    <div>
      <a href="https://www.instagram.com/udinusofficial"><i class="bi bi-instagram h2 p-2 text-dark"></i></a>
      <a href="https://www.twitter.com/udinusofficial"><i class="bi bi-twitter-x h2 p-2 text-dark"></i></a>
      <a href="https://wa.me/+6281268577"><i class="bi bi-whatsapp h2 p-2 text-dark"></i></a>
    </div>
    <div>Mohammad Wisam Wiraghina &copy; 2025</div>
  </footer>
  <!-- footer end -->

  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>
  <script type="text/javascript">
    window.setTimeout("tampilWaktu()", 1000);

    function tampilWaktu() {
      var waktu = new Date();
      var bulan = waktu.getMonth() + 1;

      setTimeout("tampilWaktu()", 1000);
      document.getElementById("tanggal").innerHTML =
        waktu.getDate() + "/" + bulan + "/" + waktu.getFullYear();
      document.getElementById("jam").innerHTML =
        waktu.getHours() +
        ":" +
        waktu.getMinutes() +
        ":" +
        waktu.getSeconds();
    }
  </script>
  <script type="text/javascript">
    const html = document.documentElement;
    const lightIcon = document.getElementById("lightIcon");
    const darkIcon = document.getElementById("darkIcon");

    if (window.matchMedia("(prefers-color-scheme: light)").matches) {
      html.setAttribute("data-bs-theme", "light");
    }

    lightIcon.addEventListener("click", () => {
      html.setAttribute("data-bs-theme", "light");
    });

    darkIcon.addEventListener("click", () => {
      html.setAttribute("data-bs-theme", "dark");
    });

  </script>
</body>

</html>