<?php
require_once "auth.php";

//endpoint ini hanya untuk admin
require_login_json();

$limit = 3;
$total_records = (int) db_value("SELECT COUNT(*) FROM article");
$jumlah_page = max(1, (int) ceil($total_records / $limit));

//nomor halaman harus angka 1..jumlah_page
$hlm = (int) ($_POST['hlm'] ?? 1);
$hlm = min(max($hlm, 1), $jumlah_page);
$limit_start = ($hlm - 1) * $limit;
$no = $limit_start + 1;

$hasil = db_all("SELECT * FROM article ORDER BY tanggal DESC LIMIT $limit OFFSET $limit_start");
?>

<!-- Table Article -->
<table class="table table-hover">
    <thead class="table-dark">
        <tr>
            <th>No</th>
            <th class="w-25">Judul</th>
            <th class="w-75">Isi</th>
            <th class="w-25">Gambar</th>
            <th class="w-25">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($hasil as $row) {
        ?>
            <tr>
                <td><?= $no++ ?></td>
                <td>
                    <strong><?= e($row["judul"]) ?></strong>
                    <br>pada : <?= e($row["tanggal"]) ?>
                    <br>oleh : <?= e($row["username"]) ?>
                </td>
                <td>
                    <?php
                    $isi = $row["isi"];
                    $clean_isi = strip_tags($isi); // Bersihkan tag HTML untuk tampilan pendek
                    
                    if (mb_strlen($clean_isi) > 150) {
                        $short = mb_substr($clean_isi, 0, 150) . '...';
                        ?>
                        <div class="short-text">
                            <?= e($short) ?>
                            <a href="#" onclick="toggleContent(this); return false;" class="text-decoration-none fw-bold"><i class="bi bi-chevron-down"></i></a>
                        </div>
                        <div class="full-text d-none">
                            <?= sanitize_html($isi) ?>
                            <a href="#" onclick="toggleContent(this); return false;" class="text-decoration-none fw-bold"><i class="bi bi-chevron-up"></i></a>
                        </div>
                        <?php
                    } else {
                        // Jika pendek, tampilkan plain/html sesuai kebutuhan. 
                        // Karena di tabel sebaiknya rapi, kita bisa strip_tags juga atau biarkan (biasanya strip_tags lebih rapi di tabel)
                        // Tapi agar konsisten dengan "Full text", kita tampilkan apa adanya tapi mungkin di-limit di CSS jika mau.
                        // User minta "sebagian saja", jadi logic di atas sudah menghandle yang panjang.
                        // Yang pendek kita tampilkan apa adanya.
                        echo sanitize_html($isi);
                    }
                    ?>
                    <?php if (!empty($row["summary"])) : ?>
                        <br><br>
                        <div class="alert alert-light" role="alert">
                            <strong><i class="bi bi-magic"></i> AI Summary:</strong><br>
                            <?= e($row["summary"]) ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    if ($row["gambar"] != '') {
                        if (image_available($row["gambar"])) {
                    ?>
                            <img src="<?= e(image_url($row["gambar"])) ?>" width="100">
                    <?php
                        }
                    }
                    ?>
                </td>
                <td>
                    <!-- untuk tombol aksi update dan delete -->
                    <a href="#" title="edit" class="badge rounded-pill text-bg-success" data-bs-toggle="modal" data-bs-target="#modalEdit<?= e($row["id"]) ?>"><i class="bi bi-pencil"></i></a>
                    <a href="#" title="delete" class="badge rounded-pill text-bg-danger" data-bs-toggle="modal" data-bs-target="#modalHapus<?= e($row["id"]) ?>"><i class="bi bi-x-circle"></i></a>

                    <!-- Awal Modal Edit -->
                    <div class="modal fade" id="modalEdit<?= e($row["id"]) ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                            <form method="post" action="" enctype="multipart/form-data" style="display:contents">
                                <?= csrf_field() ?>
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="staticBackdropLabel">Edit Article</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <input type="hidden" name="id" value="<?= e($row["id"]) ?>">
                                        
                                        <!-- Judul Full Width -->
                                        <div class="mb-4">
                                            <label for="formGroupExampleInput" class="form-label small text-muted">JUDUL ARTIKEL</label>
                                            <input type="text" class="form-control form-control-lg fw-bold" name="judul" placeholder="Tulis Judul Artikel yang Menarik" value="<?= e($row["judul"]) ?>" required>
                                        </div>

                                        <div class="row">
                                            <!-- Kolom Kiri: Isi Artikel (Lebih Lebar) -->
                                            <div class="col-md-8">
                                                <div class="mb-4">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <label for="floatingTextarea2" class="form-label small text-muted mb-0">ISI ARTIKEL</label>
                                                        <button type="button" class="btn btn-outline-info btn-sm btn-kreasikan-edit">
                                                            <i class="bi bi-robot"></i> ✨ Bantu Tulis (AI)
                                                        </button>
                                                    </div>
                                                    <!-- Add class 'summernote' for initialization -->
                                                    <textarea class="form-control summernote" placeholder="Mulai menulis artikel anda di sini..." name="isi" rows="15" required><?= e($row["isi"]) ?></textarea>
                                                </div>
                                            </div>

                                            <!-- Kolom Kanan: Sidebar (Caption & Gambar) -->
                                            <div class="col-md-4 border-start">
                                                <div class="mb-4">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <label for="summaryResult" class="form-label small text-muted mb-0">CAPTION SOSMED</label>
                                                        <button type="button" class="btn btn-outline-warning btn-sm btn-ringkas-edit">
                                                            <i class="bi bi-stars"></i> ✨ Buat Caption
                                                        </button>
                                                    </div>
                                                    <textarea class="form-control bg-light" placeholder="Caption otomatis..." name="summary" rows="6"><?= e($row["summary"]) ?></textarea>
                                                </div>

                                                <div class="mb-4">
                                                    <label for="formGroupExampleInput2" class="form-label small text-muted">GANTI GAMBAR</label>
                                                    <input type="file" class="form-control" name="gambar">
                                                </div>
                                                <div class="mb-4">
                                                    <label for="formGroupExampleInput3" class="form-label small text-muted">GAMBAR SAAT INI</label>
                                                    <?php
                                                    if ($row["gambar"] != '') {
                                                        if (image_available($row["gambar"])) {
                                                    ?>
                                                            <br><img src="<?= e(image_url($row["gambar"])) ?>" width="100" class="img-thumbnail mt-2">
                                                    <?php
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <input type="submit" value="Simpan Perubahan" name="simpan" class="btn btn-primary">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Akhir Modal Edit -->

                    <!-- Awal Modal Hapus -->
                    <div class="modal fade" id="modalHapus<?= e($row["id"]) ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Konfirmasi Hapus Article</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post" action="" enctype="multipart/form-data">
                                    <?= csrf_field() ?>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label">Yakin akan menghapus artikel "<strong><?= e($row["judul"]) ?></strong>"?</label>
                                            <input type="hidden" name="id" value="<?= e($row["id"]) ?>">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">batal</button>
                                        <input type="submit" value="hapus" name="hapus" class="btn btn-primary">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- Akhir Modal Hapus -->
                </td>
            </tr>
        <?php
        }
        ?>
    </tbody>
</table>

<?php
?>
<p>Total article : <?php echo $total_records; ?></p>
<nav class="mb-2">
    <ul class="pagination justify-content-end">
        <?php
        $jumlah_number = 1; //jumlah halaman ke kanan dan kiri dari halaman yang aktif
        $start_number = ($hlm > $jumlah_number) ? $hlm - $jumlah_number : 1;
        $end_number = ($hlm < ($jumlah_page - $jumlah_number)) ? $hlm + $jumlah_number : $jumlah_page;

        if ($hlm == 1) {
            echo '<li class="page-item disabled"><a class="page-link" href="#">First</a></li>';
            echo '<li class="page-item disabled"><a class="page-link" href="#"><span aria-hidden="true">&laquo;</span></a></li>';
        } else {
            $link_prev = ($hlm > 1) ? $hlm - 1 : 1;
            echo '<li class="page-item halaman" id="1"><a class="page-link" href="#">First</a></li>';
            echo '<li class="page-item halaman" id="' . $link_prev . '"><a class="page-link" href="#"><span aria-hidden="true">&laquo;</span></a></li>';
        }

        for ($i = $start_number; $i <= $end_number; $i++) {
            $link_active = ($hlm == $i) ? ' active' : '';
            echo '<li class="page-item halaman ' . $link_active . '" id="' . $i . '"><a class="page-link" href="#">' . $i . '</a></li>';
        }

        if ($hlm == $jumlah_page) {
            echo '<li class="page-item disabled"><a class="page-link" href="#"><span aria-hidden="true">&raquo;</span></a></li>';
            echo '<li class="page-item disabled"><a class="page-link" href="#">Last</a></li>';
        } else {
            $link_next = ($hlm < $jumlah_page) ? $hlm + 1 : $jumlah_page;
            echo '<li class="page-item halaman" id="' . $link_next . '"><a class="page-link" href="#"><span aria-hidden="true">&raquo;</span></a></li>';
            echo '<li class="page-item halaman" id="' . $jumlah_page . '"><a class="page-link" href="#">Last</a></li>';
        }
        ?>
    </ul>
</nav>