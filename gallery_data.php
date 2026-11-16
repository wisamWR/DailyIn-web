<?php
require_once "auth.php";

//endpoint ini hanya untuk admin
require_login_json();

include "koneksi.php";

$limit = 3;
$total_records = (int) $conn->query("SELECT COUNT(*) AS total FROM gallery")->fetch_assoc()['total'];
$jumlah_page = max(1, (int) ceil($total_records / $limit));

//nomor halaman harus angka 1..jumlah_page
$hlm = (int) ($_POST['hlm'] ?? 1);
$hlm = min(max($hlm, 1), $jumlah_page);
$limit_start = ($hlm - 1) * $limit;
$no = $limit_start + 1;

$sql = "SELECT * FROM gallery ORDER BY tanggal DESC LIMIT $limit_start, $limit";
$hasil = $conn->query($sql);
?>

<!-- Table Gallery -->
<table class="table table-hover">
    <thead class="table-dark">
        <tr>
            <th>No</th>
            <th class="w-25">Judul</th>
            <th class="w-25">Gambar</th>
            <th class="w-25">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        while ($row = $hasil->fetch_assoc()) {
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
                    if ($row["gambar"] != '') {
                        if (file_exists('img/' . $row["gambar"] . '')) {
                    ?>
                            <img src="img/<?= e($row["gambar"]) ?>" width="100">
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
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Edit Gallery</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post" action="" enctype="multipart/form-data">
                                    <?= csrf_field() ?>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label">Judul</label>
                                            <input type="hidden" name="id" value="<?= e($row["id"]) ?>">
                                            <input type="text" class="form-control" name="judul" placeholder="Tuliskan Judul Gallery" value="<?= e($row["judul"]) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput2" class="form-label">Ganti Gambar</label>
                                            <input type="file" class="form-control" name="gambar">
                                        </div>
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput3" class="form-label">Gambar Lama</label>
                                            <?php
                                            if ($row["gambar"] != '') {
                                                if (file_exists('img/' . $row["gambar"] . '')) {
                                            ?>
                                                    <br><img src="img/<?= e($row["gambar"]) ?>" width="100">
                                            <?php
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <input type="submit" value="edit" name="edit" class="btn btn-primary">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- Akhir Modal Edit -->

                    <!-- Awal Modal Hapus -->
                    <div class="modal fade" id="modalHapus<?= e($row["id"]) ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Konfirmasi Hapus Gallery</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post" action="">
                                    <?= csrf_field() ?>
                                    <div class="modal-body">
                                        <p>Yakin akan menghapus gallery <strong><?= e($row["judul"]) ?></strong>?</p>
                                        <input type="hidden" name="id" value="<?= e($row["id"]) ?>">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <input type="submit" value="Hapus" name="hapus" class="btn btn-danger">
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
<p>Total gallery : <?php echo $total_records; ?></p>
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