<?php
if (!defined('DAILYIN_ADMIN')) {
    http_response_code(403);
    exit;
}
?>
    <!-- Button trigger modal -->
    <button type="button" class="btn btn-secondary mb-2" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg"></i> Tambah Gallery
    </button>
    <div class="row">
        <div class="table-responsive" id="gallery_data">

        </div>

        <!-- Awal Modal Tambah-->
        <div class="modal fade" id="modalTambah" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="staticBackdropLabel">Tambah Gallery</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="formGroupExampleInput" class="form-label">Judul</label>
                                <input type="text" class="form-control" name="judul" placeholder="Tuliskan Judul Gallery" required>
                            </div>
                            <div class="mb-3">
                                <label for="formGroupExampleInput2" class="form-label">Gambar</label>
                                <input type="file" class="form-control" name="gambar" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <input type="submit" value="simpan" name="simpan" class="btn btn-primary">
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Akhir Modal Tambah-->
    </div>

<script>
    $(document).ready(function() {
        load_data();

        function load_data(hlm) {
            $.ajax({
                url: "gallery_data.php",
                method: "POST",
                data: {
                    hlm: hlm
                },
                success: function(data) {
                    $('#gallery_data').html(data);
                }
            })
        }
        $(document).on('click', '.halaman', function() {
            var hlm = $(this).attr("id");
            load_data(hlm);
        });
    });
</script>


<?php
include "upload_foto.php";

//jika tombol simpan diklik
if (isset($_POST['simpan'])) {
    verify_csrf();

    $judul = trim($_POST['judul'] ?? '');
    $tanggal = date("Y-m-d H:i:s");
    $username = $_SESSION['username'];
    $gambar = '';
    $nama_gambar = $_FILES['gambar']['name'] ?? '';

    //jika ada file yang dikirim
    if ($nama_gambar != '') {
        //panggil function upload_foto untuk cek spesifikasi file yg dikirimkan user
        //function ini memiliki 2 keluaran yaitu status dan message
        $cek_upload = upload_foto($_FILES["gambar"]);

        //cek status true/false
        if ($cek_upload['status']) {
            //jika true maka message berisi nama file gambar
            $gambar = $cek_upload['message'];
        } else {
            //jika false maka message berisi pesan error, tampilkan dalam alert
            alert_redirect($cek_upload['message'], 'admin.php?page=gallery', 'danger');
            die;
        }
    }

    //prepared statement untuk insert data
    $simpan = db_run("INSERT INTO gallery (judul, gambar, tanggal, username) VALUES (?, ?, ?, ?)", [$judul, $gambar, $tanggal, $username]);

    //eksekusi query
    if ($simpan) {
        alert_redirect('Data berhasil disimpan', 'admin.php?page=gallery');
    } else {
        alert_redirect('Data gagal disimpan', 'admin.php?page=gallery', 'danger');
    }
}

//jika tombol simpan diklik untuk edit
if (isset($_POST['edit'])) {
    verify_csrf();

    $id = (int) $_POST['id'];
    $judul = trim($_POST['judul'] ?? '');
    $gambar = '';
    $nama_gambar = $_FILES['gambar']['name'] ?? '';

    //jika ada file yang dikirim
    if ($nama_gambar != '') {
        //panggil function upload_foto untuk cek spesifikasi file yg dikirimkan user
        //function ini memiliki 2 keluaran yaitu status dan message
        $cek_upload = upload_foto($_FILES["gambar"]);

        //cek status true/false
        if ($cek_upload['status']) {
            //jika true maka message berisi nama file gambar
            $gambar = $cek_upload['message'];
        } else {
            //jika false maka message berisi pesan error, tampilkan dalam alert
            alert_redirect($cek_upload['message'], 'admin.php?page=gallery', 'danger');
            die;
        }
    }

    //prepared statement untuk update data
    if ($nama_gambar == '') {
        $update = db_run("UPDATE gallery SET judul = ? WHERE id = ?", [$judul, $id]);
    } else {
        //nama gambar lama diambil dari database, bukan dari form
        $lama = db_one("SELECT gambar FROM gallery WHERE id = ?", [$id]);

        $update = db_run("UPDATE gallery SET judul = ?, gambar = ? WHERE id = ?", [$judul, $gambar, $id]);

        if ($update && $lama) {
            delete_image($lama['gambar']);
        }
    }

    //eksekusi query
    if ($update) {
        alert_redirect('Data berhasil diupdate', 'admin.php?page=gallery');
    } else {
        alert_redirect('Data gagal diupdate', 'admin.php?page=gallery', 'danger');
    }
}

//jika tombol hapus diklik
if (isset($_POST['hapus'])) {
    verify_csrf();

    $id = (int) $_POST['id'];

    //ambil nama gambar dari database, bukan dari form
    $row = db_one("SELECT gambar FROM gallery WHERE id = ?", [$id]);

    $hapus = db_run("DELETE FROM gallery WHERE id = ?", [$id]);

    if ($hapus && $row) {
        delete_image($row['gambar']);
    }

    if ($hapus) {
        alert_redirect('Data berhasil dihapus', 'admin.php?page=gallery');
    } else {
        alert_redirect('Data gagal dihapus', 'admin.php?page=gallery', 'danger');
    }
}
?>
