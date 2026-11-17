<?php
if (!defined('DAILYIN_ADMIN')) {
    http_response_code(403);
    exit;
}

$today = substr(now_string(), 0, 10);

//jumlah data
$jumlah_article = (int) db_value("SELECT COUNT(*) FROM article");
$jumlah_draft = (int) db_value("SELECT COUNT(*) FROM article WHERE status = 'draft'");
$jumlah_terjadwal = (int) db_value("SELECT COUNT(*) FROM article WHERE status = 'published' AND published_at > ?", [now_string()]);
$jumlah_gallery = (int) db_value("SELECT COUNT(*) FROM gallery");
$total_views = (int) db_value("SELECT COALESCE(SUM(views), 0) FROM article");

//streak menulis & kalender aktivitas 26 minggu
$streak = writing_streaks(entry_dates(), $today);
$weeks = 26;
$grid = activity_grid(count_by_day(entry_dates(date('Y-m-d', strtotime('-' . ($weeks * 7) . ' days', strtotime($today))))), $today, $weeks);
$active_days = [];
$total_entries = 0;
foreach ($grid as $week) {
    foreach ($week as $day) {
        if ($day['count'] > 0 && !$day['future']) {
            $active_days[] = $day;
            $total_entries += $day['count'];
        }
    }
}

$moods = mood_counts(30);
$max_mood = max(1, max($moods));
$total_mood = array_sum($moods);
$populer = popular_articles(5);
$kenangan = on_this_day($today);
$ai_usage = ai_usage_summary(30);
$ai_labels = [
    'generate_summary' => 'Ringkasan (pengunjung)',
    'generate_caption' => 'Caption sosmed',
    'generate_content' => 'Bantu Tulis',
    'generate_tags' => 'Saran Tag',
    'generate_mood' => 'Deteksi Mood',
];
$hari = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
$bulan_pendek = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$tanggal_id = fn($date) => (int) substr($date, 8, 2) . ' ' . $bulan_pendek[(int) substr($date, 5, 2)] . ' ' . substr($date, 0, 4);
?>
<style>
    /* Warna grafik (biru satu hue; tervalidasi untuk permukaan terang & gelap) */
    .viz { --viz-empty: #ebedf0; --viz-1: #86b6ef; --viz-2: #5598e7; --viz-3: #256abf; --viz-4: #104281; --viz-bar: #2a78d6; }
    [data-bs-theme="dark"] .viz { --viz-empty: #2b3035; --viz-1: #1c5cab; --viz-2: #2a78d6; --viz-3: #5598e7; --viz-4: #9ec5f4; --viz-bar: #3987e5; }
    .stat-tile .value { font-size: 2rem; font-weight: 700; line-height: 1.1; }
    .heatmap { display: grid; grid-auto-flow: column; grid-template-rows: 14px repeat(7, 13px); grid-template-columns: 30px; grid-auto-columns: 13px; justify-content: start; gap: 2px; overflow-x: auto; padding-bottom: 4px; }
    .heatmap .month { font-size: .7rem; color: var(--bs-secondary-color); height: 14px; white-space: nowrap; overflow: visible; }
    .heatmap .dayname { font-size: .65rem; color: var(--bs-secondary-color); line-height: 13px; padding-right: 4px; }
    .heat-cell { display: inline-block; width: 13px; height: 13px; border-radius: 2px; background: var(--viz-empty); }
    .heat-cell.l1 { background: var(--viz-1); } .heat-cell.l2 { background: var(--viz-2); }
    .heat-cell.l3 { background: var(--viz-3); } .heat-cell.l4 { background: var(--viz-4); }
    .heat-cell.future { background: transparent; }
    .heatmap .heat-cell:not(.future):hover { outline: 2px solid var(--bs-emphasis-color); outline-offset: 1px; }
    .mood-row { display: grid; grid-template-columns: 7.5rem 1fr 2.5rem; align-items: center; gap: .5rem; padding: 4px 0; }
    .mood-bar { height: 14px; background: var(--viz-bar); border-radius: 0 4px 4px 0; }
    .mood-row:hover .mood-label, .mood-row:hover .mood-value { font-weight: 600; }
</style>

<div class="viz pt-4">
    <!-- angka utama -->
    <div class="row row-cols-2 row-cols-lg-4 g-3 mb-4">
        <div class="col">
            <div class="card h-100 shadow-sm stat-tile">
                <div class="card-body">
                    <div class="text-muted small"><i class="bi bi-newspaper"></i> Article</div>
                    <div class="value"><?= $jumlah_article ?></div>
                    <div class="small text-muted"><?= $jumlah_draft ?> draft &middot; <?= $jumlah_terjadwal ?> terjadwal</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm stat-tile">
                <div class="card-body">
                    <div class="text-muted small"><i class="bi bi-camera"></i> Gallery</div>
                    <div class="value"><?= $jumlah_gallery ?></div>
                    <div class="small text-muted">foto kegiatan</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm stat-tile">
                <div class="card-body">
                    <div class="text-muted small"><i class="bi bi-eye"></i> Total dibaca</div>
                    <div class="value" id="statViews"><?= number_format($total_views, 0, ',', '.') ?></div>
                    <div class="small text-muted">view pengunjung</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm stat-tile">
                <div class="card-body">
                    <div class="text-muted small"><i class="bi bi-fire"></i> Streak menulis</div>
                    <div class="value" id="statStreak"><?= $streak['current'] ?> hari</div>
                    <div class="small text-muted">terpanjang <?= $streak['longest'] ?> hari<?= $streak['last'] ? ' &middot; terakhir ' . e($tanggal_id($streak['last'])) : '' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- kalender aktivitas -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-baseline mb-2">
                <h5 class="card-title mb-0">Kalender menulis</h5>
                <span class="small text-muted" id="heatmapSummary"><?= $total_entries ?> entri di <?= count($active_days) ?> hari, <?= $weeks ?> minggu terakhir</span>
            </div>
            <div class="heatmap" role="img" aria-label="Kalender aktivitas menulis: <?= $total_entries ?> entri di <?= count($active_days) ?> hari dalam <?= $weeks ?> minggu terakhir">
                <span class="month"></span>
                <?php foreach ($hari as $i => $nama) : ?>
                    <span class="dayname"><?= $i % 2 === 0 ? $nama : '' ?></span>
                <?php endforeach; ?>
                <?php foreach ($grid as $w => $week) : ?>
                    <?php
                    // label bulan di minggu pertama tiap bulan (kolom pertama hanya jika masih awal bulan,
                    // supaya tidak bertumpuk dengan label bulan berikutnya)
                    $first = $week[0]['date'];
                    $label = (substr($first, 8, 2) <= '07' || ($w === 0 && substr($first, 8, 2) <= '14')) ? $bulan_pendek[(int) substr($first, 5, 2)] : '';
                    ?>
                    <span class="month"><?= $label ?></span>
                    <?php foreach ($week as $day) : ?>
                        <?php if ($day['future']) : ?>
                            <span class="heat-cell future"></span>
                        <?php else : ?>
                            <span class="heat-cell l<?= activity_level($day['count']) ?>" data-bs-toggle="tooltip" title="<?= $day['count'] ?> entri &middot; <?= e($tanggal_id($day['date'])) ?>"></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-2 small text-muted">
                <details>
                    <summary>Lihat sebagai tabel</summary>
                    <table class="table table-sm mt-2 mb-0" style="max-width: 320px;">
                        <thead><tr><th>Tanggal</th><th class="text-end">Entri</th></tr></thead>
                        <tbody>
                            <?php foreach (array_reverse($active_days) as $day) : ?>
                                <tr><td><?= e($tanggal_id($day['date'])) ?></td><td class="text-end"><?= $day['count'] ?></td></tr>
                            <?php endforeach; ?>
                            <?php if (!$active_days) : ?><tr><td colspan="2">Belum ada entri.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </details>
                <span class="d-flex align-items-center gap-1" aria-hidden="true">
                    Sedikit
                    <span class="heat-cell"></span><span class="heat-cell l1"></span><span class="heat-cell l2"></span><span class="heat-cell l3"></span><span class="heat-cell l4"></span>
                    Banyak
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- mood 30 hari -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Mood 30 hari terakhir</h5>
                    <?php if ($total_mood === 0) : ?>
                        <p class="text-muted mb-0" id="moodEmpty">Belum ada entri dengan mood. Pilih mood di form artikel atau pakai tombol <em>Deteksi (AI)</em>.</p>
                    <?php else : ?>
                        <div id="moodChart" role="list" aria-label="Jumlah entri per mood, 30 hari terakhir">
                            <?php foreach (MOODS as $key => [$emoji, $label]) : ?>
                                <div class="mood-row" role="listitem" data-bs-toggle="tooltip" title="<?= e($label) ?>: <?= $moods[$key] ?> entri (<?= round($moods[$key] / $total_mood * 100) ?>%)">
                                    <span class="mood-label"><?= $emoji ?> <?= e($label) ?></span>
                                    <span><span class="mood-bar d-block" style="width: <?= round($moods[$key] / $max_mood * 100, 1) ?>%"></span></span>
                                    <span class="mood-value text-end" data-mood="<?= e($key) ?>"><?= $moods[$key] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- hari ini di tahun lalu -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Hari ini di tahun lalu</h5>
                    <p class="small text-muted"><?= e($tanggal_id($today)) ?></p>
                    <?php if (!$kenangan) : ?>
                        <p class="text-muted mb-0" id="onThisDayEmpty">Belum ada entri di tanggal ini pada tahun-tahun sebelumnya.</p>
                    <?php else : ?>
                        <ul class="list-unstyled mb-0" id="onThisDay">
                            <?php foreach ($kenangan as $row) : ?>
                                <li class="mb-2">
                                    <span class="badge text-bg-light border"><?= $row['years_ago'] ?> tahun lalu</span>
                                    <a href="<?= e(article_url($row)) ?>" target="_blank"><?= e($row['judul']) ?></a>
                                    <?= mood_label($row['mood'] ?? null) !== '' ? '<span class="small text-muted">&middot; ' . e(mood_label($row['mood'])) . '</span>' : '' ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- artikel terpopuler -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Artikel terpopuler</h5>
                    <?php if (!$populer) : ?>
                        <p class="text-muted mb-0">Belum ada view dari pengunjung.</p>
                    <?php else : ?>
                        <ol class="mb-0 ps-3" id="popularList">
                            <?php foreach ($populer as $row) : ?>
                                <li class="mb-1">
                                    <div class="d-flex justify-content-between gap-2">
                                        <a href="<?= e(article_url($row)) ?>" target="_blank" class="text-truncate"><?= e($row['judul']) ?></a>
                                        <span class="text-muted text-nowrap"><?= number_format((int) $row['views'], 0, ',', '.') ?>x</span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- pemakaian AI -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Pemakaian AI 30 hari terakhir</h5>
                    <?php if (!$ai_usage) : ?>
                        <p class="text-muted mb-0">Belum ada pemakaian AI yang tercatat.</p>
                    <?php else : ?>
                        <table class="table table-sm mb-0" id="aiUsage">
                            <thead>
                                <tr class="text-nowrap"><th>Fitur</th><th class="text-end">Panggilan</th><th class="text-end">Berhasil</th><th class="text-end">Dari cache</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ai_usage as $row) : ?>
                                    <tr data-mode="<?= e($row['mode']) ?>">
                                        <td><?= e($ai_labels[$row['mode']] ?? $row['mode']) ?></td>
                                        <td class="text-end"><?= (int) $row['total'] ?></td>
                                        <td class="text-end"><?= round((int) $row['ok'] / max(1, (int) $row['total']) * 100) ?>%</td>
                                        <td class="text-end"><?= (int) $row['cached'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // tooltip untuk sel kalender & bar mood (Bootstrap dimuat di akhir halaman)
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.viz [data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    });
</script>
