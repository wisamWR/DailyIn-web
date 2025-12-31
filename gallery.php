<div class="container">
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
    $judul = $_POST['judul'];
    $tanggal = date("Y-m-d H:i:s");
    $username = $_SESSION['username'];
    $gambar = '';
    $nama_gambar = $_FILES['gambar']['name'];

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
            echo "<script>
                alert('" . $cek_upload['message'] . "');
                document.location='admin.php?page=gallery';
            </script>";
            die;
        }
    }

    //buat query untuk insert data
    $sql = "INSERT INTO gallery (judul, gambar, tanggal, username) VALUES ('$judul', '$gambar', '$tanggal', '$username')";

    //eksekusi query
    if ($conn->query($sql) === TRUE) {
        echo "<script>
            alert('Data berhasil disimpan');
            document.location='admin.php?page=gallery';
        </script>";
    } else {
        echo "<script>
            alert('Data gagal disimpan');
            document.location='admin.php?page=gallery';
        </script>";
    }
}

//jika tombol simpan diklik untuk edit
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $judul = $_POST['judul'];
    $gambar = '';
    $nama_gambar = $_FILES['gambar']['name'];

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
            echo "<script>
                alert('" . $cek_upload['message'] . "');
                document.location='admin.php?page=gallery';
            </script>";
            die;
        }
    }

    //buat query untuk update data
    if ($nama_gambar == '') {
        $sql = "UPDATE gallery SET judul='$judul' WHERE id='$id'";
    } else {
        $gambar_lama = $_POST['gambar_lama'];
        $sql = "UPDATE gallery SET judul='$judul', gambar='$gambar' WHERE id='$id'";
        if ($gambar_lama != '') {
            unlink("img/" . $gambar_lama);
        }
    }

    //eksekusi query
    if ($conn->query($sql) === TRUE) {
        echo "<script>
            alert('Data berhasil diupdate');
            document.location='admin.php?page=gallery';
        </script>";
    } else {
        echo "<script>
            alert('Data gagal diupdate');
            document.location='admin.php?page=gallery';
        </script>";
    }
}

//jika tombol hapus diklik
if (isset($_POST['hapus'])) {
    $id = $_POST['hapus'];
    $gambar = $_POST['gambar'];

    if ($gambar != '') {
        unlink("img/" . $gambar);
    }

    $sql = "DELETE FROM gallery WHERE id='$id'";
    if ($conn->query($sql) === TRUE) {
        echo "<script>
            alert('Data berhasil dihapus');
            document.location='admin.php?page=gallery';
        </script>";
    } else {
        echo "<script>
            alert('Data gagal dihapus');
            document.location='admin.php?page=gallery';
        </script>";
    }
}
?>