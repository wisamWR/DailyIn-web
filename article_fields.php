<?php
// Field tambahan form artikel (dipakai form Tambah di article.php dan form Edit di article_data.php)
if (!is_logged_in()) {
    http_response_code(403);
    exit;
}

// $row = data artikel saat edit, null saat tambah; $tags_value = daftar tag "a, b, c"
function render_article_fields($row = null, $tags_value = '')
{
    $status = $row['status'] ?? 'published';
    $published_at = !empty($row['published_at']) ? date('Y-m-d\TH:i', strtotime($row['published_at'])) : '';
    $slug = $row['slug'] ?? '';
?>
    <div class="mb-3">
        <label class="form-label small text-muted">STATUS</label>
        <select name="status" class="form-select">
            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Terbit</option>
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft (tidak tampil)</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label small text-muted">JADWAL TERBIT <span class="fw-normal">(opsional)</span></label>
        <input type="datetime-local" name="published_at" class="form-control" value="<?= e($published_at) ?>">
        <div class="form-text">Kosong = terbit sekarang. Isi waktu mendatang untuk menjadwalkan.</div>
    </div>
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label small text-muted mb-0">TAG</label>
            <button type="button" class="btn btn-outline-secondary btn-sm btn-saran-tag">
                <i class="bi bi-tags"></i> Saran (AI)
            </button>
        </div>
        <input type="text" name="tags" class="form-control" value="<?= e($tags_value) ?>" placeholder="kampus, coding, tips" maxlength="300">
        <div class="form-text">Pisahkan dengan koma, maksimal 8 tag.</div>
    </div>
    <div class="mb-4">
        <label class="form-label small text-muted">SLUG URL <span class="fw-normal">(opsional)</span></label>
        <div class="input-group input-group-sm">
            <span class="input-group-text">/artikel/</span>
            <input type="text" name="slug" class="form-control" value="<?= e($slug) ?>" placeholder="otomatis dari judul" maxlength="80">
        </div>
    </div>
<?php
}
