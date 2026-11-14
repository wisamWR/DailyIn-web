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
            alert_redirect($cek_upload['message'], 'admin.php?page=gallery');
            die;
        }
    }

    //prepared statement untuk insert data
    $stmt = $conn->prepare("INSERT INTO gallery (judul, gambar, tanggal, username) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $judul, $gambar, $tanggal, $username);

    //eksekusi query
    if ($stmt->execute()) {
        alert_redirect('Data berhasil disimpan', 'admin.php?page=gallery');
    } else {
        alert_redirect('Data gagal disimpan', 'admin.php?page=gallery');
    }
    $stmt->close();
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
            alert_redirect($cek_upload['message'], 'admin.php?page=gallery');
            die;
        }
    }

    //prepared statement untuk update data
    if ($nama_gambar == '') {
        $stmt = $conn->prepare("UPDATE gallery SET judul = ? WHERE id = ?");
        $stmt->bind_param("si", $judul, $id);
        $update = $stmt->execute();
    } else {
        //nama gambar lama diambil dari database, bukan dari form
        $stmt = $conn->prepare("SELECT gambar FROM gallery WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $lama = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE gallery SET judul = ?, gambar = ? WHERE id = ?");
        $stmt->bind_param("ssi", $judul, $gambar, $id);
        $update = $stmt->execute();

        if ($update && $lama) {
            delete_image($conn, $lama['gambar']);
        }
    }

    //eksekusi query
    if ($update) {
        alert_redirect('Data berhasil diupdate', 'admin.php?page=gallery');
    } else {
        alert_redirect('Data gagal diupdate', 'admin.php?page=gallery');
    }
    $stmt->close();
}

//jika tombol hapus diklik
if (isset($_POST['hapus'])) {
    verify_csrf();

    $id = (int) $_POST['id'];

    //ambil nama gambar dari database, bukan dari form
    $stmt = $conn->prepare("SELECT gambar FROM gallery WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM gallery WHERE id = ?");
    $stmt->bind_param("i", $id);
    $hapus = $stmt->execute();

    if ($hapus && $row) {
        delete_image($conn, $row['gambar']);
    }

    if ($hapus) {
        alert_redirect('Data berhasil dihapus', 'admin.php?page=gallery');
    } else {
        alert_redirect('Data gagal dihapus', 'admin.php?page=gallery');
    }
    $stmt->close();
}
?>
