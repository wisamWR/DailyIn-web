<?php
// Halaman detail artikel: /artikel/{slug} (lewat index.php) atau artikel.php?id= (dialihkan ke URL slug)
require_once __DIR__ . '/auth.php';

if (isset($article_slug)) {
  $article = db_one("SELECT * FROM article WHERE slug = ?", [$article_slug]);
} else {
  $id = (int) ($_GET['id'] ?? 0);
  $article = $id > 0 ? db_one("SELECT * FROM article WHERE id = ?", [$id]) : null;
  // URL lama berbasis id -> alihkan permanen ke URL slug
  if ($article && !empty($article['slug']) && (is_published($article) || is_logged_in())) {
    header('Location: ' . article_url($article), true, 301);
    exit;
  }
}

// draft / terjadwal hanya bisa dilihat admin (mode pratinjau)
$is_preview = $article && !is_published($article);
if (!$article || ($is_preview && !is_logged_in())) {
  require __DIR__ . '/404.php';
  exit;
}

$tags = tags_for_articles([$article['id']])[(int) $article['id']];
$isi_html = sanitize_html($article['isi'] ?? '');
$description = $article['summary'] != '' ? excerpt($article['summary'], 160) : excerpt($isi_html, 160);
if ($description === '') $description = 'Artikel di DailyIn: ' . $article['judul'];
$canonical = !empty($article['slug']) ? site_url('artikel/' . $article['slug']) : site_url('artikel.php?id=' . (int) $article['id']);
$og_image = image_available($article['gambar']) ? absolute_image_url($article['gambar']) : site_url('img/banner.jpg');
$published_iso = date('c', strtotime($article['published_at'] ?: $article['tanggal']));

// artikel terkait: tag yang sama dulu, lalu artikel terbaru
$related = db_all(
  "SELECT * FROM article WHERE " . PUBLISHED_SQL . " AND id <> ?
     AND id IN (SELECT article_id FROM article_tag WHERE tag_id IN (SELECT tag_id FROM article_tag WHERE article_id = ?))
   ORDER BY COALESCE(published_at, tanggal) DESC LIMIT 3",
  [now_string(), $article['id'], $article['id']]
);
if (count($related) < 3) {
  $exclude = array_merge([(int) $article['id']], array_map('intval', array_column($related, 'id')));
  $more = db_all(
    "SELECT * FROM article WHERE " . PUBLISHED_SQL . " AND id NOT IN (" . implode(',', $exclude) . ")
     ORDER BY COALESCE(published_at, tanggal) DESC LIMIT " . (3 - count($related)),
    [now_string()]
  );
  $related = array_merge($related, $more);
}

$json_ld = [
  '@context' => 'https://schema.org',
  '@type' => 'BlogPosting',
  'headline' => $article['judul'],
  'description' => $description,
  'image' => [$og_image],
  'datePublished' => $published_iso,
  'author' => ['@type' => 'Person', 'name' => $article['username'] ?: 'admin'],
  'publisher' => ['@type' => 'Organization', 'name' => 'DailyIn', 'logo' => ['@type' => 'ImageObject', 'url' => site_url('img/logo.png')]],
  'mainEntityOfPage' => $canonical,
  'keywords' => tags_string($tags),
];
$share_text = rawurlencode($article['judul'] . ' ' . $canonical);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <base href="<?= e(base_path()) ?>" />
  <title><?= e($article['judul']) ?> | DailyIn</title>
  <meta name="description" content="<?= e($description) ?>" />
  <link rel="canonical" href="<?= e($canonical) ?>" />
  <?php if ($is_preview) : ?><meta name="robots" content="noindex, nofollow" /><?php endif; ?>
  <meta property="og:type" content="article" />
  <meta property="og:site_name" content="DailyIn" />
  <meta property="og:title" content="<?= e($article['judul']) ?>" />
  <meta property="og:description" content="<?= e($description) ?>" />
  <meta property="og:url" content="<?= e($canonical) ?>" />
  <meta property="og:image" content="<?= e($og_image) ?>" />
  <meta property="og:locale" content="id_ID" />
  <meta property="article:published_time" content="<?= e($published_iso) ?>" />
  <?php foreach ($tags as $tag) : ?>
    <meta property="article:tag" content="<?= e($tag['name']) ?>" />
  <?php endforeach; ?>
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?= e($article['judul']) ?>" />
  <meta name="twitter:description" content="<?= e($description) ?>" />
  <meta name="twitter:image" content="<?= e($og_image) ?>" />
  <script type="application/ld+json"><?= json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
  <link rel="icon" href="img/logo.png" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
    crossorigin="anonymous" />
  <style>
    .article-body { font-size: 1.1rem; line-height: 1.8; }
    .article-body img, .article-body iframe { max-width: 100%; height: auto; }
    .article-body iframe { aspect-ratio: 16 / 9; width: 100%; }
  </style>
</head>

<body>
  <nav class="navbar bg-body-tertiary sticky-top">
    <div class="container">
      <a class="navbar-brand fw-bold" href="<?= e(url()) ?>">DailyIn</a>
      <a class="btn btn-outline-primary btn-sm" href="<?= e(url()) ?>#article"><i class="bi bi-arrow-left"></i> Semua Artikel</a>
    </div>
  </nav>

  <?php if ($is_preview) : ?>
    <div class="alert alert-warning rounded-0 text-center mb-0" role="alert">
      <i class="bi bi-eye"></i> Pratinjau admin:
      <?= ($article['status'] ?? '') === 'draft' ? 'artikel ini masih <strong>draft</strong>' : 'artikel ini <strong>terjadwal</strong> terbit ' . e(display_date($article)) ?>
      dan belum terlihat oleh pengunjung.
    </div>
  <?php endif; ?>

  <main class="container py-4 py-md-5" style="max-width: 820px;">
    <article>
      <header class="mb-4">
        <h1 class="fw-bold display-6"><?= e($article['judul']) ?></h1>
        <p class="text-muted mb-2">
          <i class="bi bi-calendar3"></i> <?= e(display_date($article)) ?>
          &middot; <i class="bi bi-person"></i> <?= e($article['username'] ?: 'admin') ?>
        </p>
        <?php foreach ($tags as $tag) : ?>
          <a href="<?= e(url('tag/' . $tag['slug'])) ?>#article" class="badge text-bg-light border text-decoration-none me-1">#<?= e($tag['name']) ?></a>
        <?php endforeach; ?>
      </header>

      <?php if (image_available($article['gambar'])) : ?>
        <img src="<?= e(image_url($article['gambar'])) ?>" class="img-fluid rounded mb-4 w-100" alt="<?= e($article['judul']) ?>" />
      <?php endif; ?>

      <!-- ringkasan AI -->
      <div class="mb-4">
        <button type="button" class="btn btn-warning btn-sm" id="btnRingkasan" data-id="<?= (int) $article['id'] ?>">
          <i class="bi bi-stars"></i> Ringkas dengan AI
        </button>
        <div id="ringkasan" class="alert alert-info mt-3 d-none" role="status"></div>
      </div>

      <div class="article-body">
        <?= $isi_html !== '' ? $isi_html : '<p class="text-muted">Artikel ini belum memiliki isi.</p>' ?>
      </div>
    </article>

    <!-- bagikan -->
    <div class="border-top border-bottom py-3 my-4 d-flex flex-wrap align-items-center gap-2">
      <span class="text-muted me-2">Bagikan:</span>
      <a class="btn btn-outline-success btn-sm" target="_blank" rel="noopener" href="https://wa.me/?text=<?= $share_text ?>"><i class="bi bi-whatsapp"></i> WhatsApp</a>
      <a class="btn btn-outline-dark btn-sm" target="_blank" rel="noopener" href="https://x.com/intent/post?text=<?= $share_text ?>"><i class="bi bi-twitter-x"></i> X</a>
      <a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($canonical) ?>"><i class="bi bi-facebook"></i> Facebook</a>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCopyLink" data-url="<?= e($canonical) ?>"><i class="bi bi-link-45deg"></i> Salin link</button>
    </div>

    <?php if ($related) : ?>
      <section>
        <h2 class="h5 fw-bold mb-3">Artikel lainnya</h2>
        <div class="row row-cols-1 row-cols-md-3 g-3">
          <?php foreach ($related as $row) : ?>
            <div class="col">
              <a href="<?= e(article_url($row)) ?>" class="card h-100 text-reset text-decoration-none">
                <?php if (image_available($row['gambar'])) : ?>
                  <img src="<?= e(image_url($row['gambar'])) ?>" class="card-img-top" alt="<?= e($row['judul']) ?>" loading="lazy" />
                <?php endif; ?>
                <div class="card-body">
                  <h3 class="h6 card-title"><?= e($row['judul']) ?></h3>
                  <small class="text-muted"><?= e(display_date($row)) ?></small>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <footer class="text-center text-muted p-4 bg-body-tertiary">
    <a href="<?= e(url()) ?>" class="text-reset">DailyIn</a> &middot; Mohammad Wisam Wiraghina &copy; 2025
  </footer>

  <script>
    // ikuti tema gelap/terang perangkat
    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
      document.documentElement.setAttribute('data-bs-theme', 'dark');
    }

    // ringkasan AI (hasil ditampilkan sebagai teks, bukan HTML)
    const btnRingkasan = document.getElementById('btnRingkasan');
    const boxRingkasan = document.getElementById('ringkasan');
    btnRingkasan.addEventListener('click', async () => {
      if (!boxRingkasan.classList.contains('d-none') && boxRingkasan.dataset.loaded) {
        boxRingkasan.classList.add('d-none');
        return;
      }
      btnRingkasan.disabled = true;
      const label = btnRingkasan.innerHTML;
      btnRingkasan.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Meringkas...';
      boxRingkasan.classList.remove('d-none');
      boxRingkasan.textContent = 'Sedang membuat ringkasan...';
      try {
        const res = await fetch('ai_helper.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ mode: 'generate_summary', id: Number(btnRingkasan.dataset.id) })
        });
        const data = await res.json();
        boxRingkasan.textContent = '';
        if (data.result) {
          const title = document.createElement('strong');
          title.textContent = '⚡ Ringkasan AI';
          boxRingkasan.appendChild(title);
          data.result.split('\n').map(l => l.trim()).filter(Boolean).forEach(line => {
            boxRingkasan.appendChild(document.createElement('br'));
            boxRingkasan.appendChild(document.createTextNode(line));
          });
          boxRingkasan.dataset.loaded = '1';
        } else {
          boxRingkasan.textContent = data.error || 'Ringkasan belum tersedia.';
        }
      } catch (e) {
        boxRingkasan.textContent = 'Gagal menghubungi AI. Coba lagi nanti.';
      }
      btnRingkasan.disabled = false;
      btnRingkasan.innerHTML = label;
    });

    // salin link artikel
    document.getElementById('btnCopyLink').addEventListener('click', async (event) => {
      const btn = event.currentTarget;
      try {
        await navigator.clipboard.writeText(btn.dataset.url);
        btn.innerHTML = '<i class="bi bi-check2"></i> Tersalin';
      } catch (e) {
        window.prompt('Salin link ini:', btn.dataset.url);
      }
    });
  </script>
</body>

</html>
