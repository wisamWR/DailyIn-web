<div class="container">
    <!-- Button trigger modal -->
    <button type="button" class="btn btn-secondary mb-2" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg"></i> Tambah Article
    </button>
    <div class="row">
        <div class="table-responsive" id="article_data">

        </div>

        <!-- Awal Modal Tambah-->
        <div class="modal fade" id="modalTambah" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="staticBackdropLabel">Tambah Article</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="formGroupExampleInput" class="form-label">Judul</label>
                                <input type="text" class="form-control" name="judul" id="judulArtikel" placeholder="Tuliskan Judul Artikel" required>
                            </div>
                            <!-- Tombol Generate Content -->
                            <div class="mb-3">
                                <button type="button" class="btn btn-info btn-sm" id="btnGenContent">
                                    <i class="bi bi-robot"></i> Buatkan Isi Artikel (AI)
                                </button>
                            </div>
                            <div class="mb-3">
                                <label for="floatingTextarea2">Isi</label>
                                <textarea class="form-control" placeholder="Tuliskan Isi Artikel" name="isi" id="isiArtikel" required></textarea>
                            </div>
                            <div class="mb-3">
                                <button type="button" class="btn btn-warning btn-sm" id="btnGenSummary">
                                    <i class="bi bi-magic"></i> Buatkan Caption (AI) ✨
                                </button>
                            </div>
                            <div class="mb-3">
                                <label for="summaryResult">Caption (AI)</label>
                                <textarea class="form-control" placeholder="Caption artikel..." name="summary" id="summaryResult"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="formGroupExampleInput2" class="form-label">Gambar</label>
                                <input type="file" class="form-control" name="gambar">
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
                url: "article_data.php",
                method: "POST",
                data: {
                    hlm: hlm
                },
                success: function(data) {
                    $('#article_data').html(data);
                }
            })
        }
        $(document).on('click', '.halaman', function() {
            var hlm = $(this).attr("id");
            load_data(hlm);
        });

        // Logika AI Summary
        $('#btnGenSummary').click(function() {
            var isi = $('#isiArtikel').val();
            if (isi == '') {
                alert('Harap isi artikel terlebih dahulu!');
                return;
            }

            var btn = $(this);
            var originalText = btn.html();
            btn.prop('disabled', true).html('Loading...');

            $.ajax({
                url: 'ai_helper.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ text: isi, mode: 'generate_caption' }),
                success: function(response) {
                    if (response.result) {
                        $('#summaryResult').val(response.result);
                    } else if (response.error) {
                        alert('AI Error: ' + response.error);
                    }
                    btn.prop('disabled', false).html(originalText);
                },
                error: function(xhr, status, error) {
                    console.log(xhr.responseText);
                    alert('Gagal menghubungi AI Helper.');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Logika AI Generate Content
        $('#btnGenContent').click(function() {
            var judul = $('#judulArtikel').val();
            if (judul == '') {
                alert('Harap isi Judul terlebih dahulu!');
                return;
            }

            var btn = $(this);
            var originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sedang menulis...');

            $.ajax({
                url: 'ai_helper.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ text: judul, mode: 'generate_content' }),
                success: function(response) {
                    if (response.result) {
                        $('#isiArtikel').val(response.result);
                    } else if (response.error) {
                        alert('AI Error: ' + response.error);
                    }
                    btn.prop('disabled', false).html(originalText);
                },
                error: function(xhr, status, error) {
                    console.log(xhr.responseText);
                    alert('Gagal menghubungi AI Helper.');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // ==========================================================
        // FITUR AI UNTUK MODAL EDIT (Dynamic Content)
        // Gunakan Event Delegation karena modal edit diload via AJAX
        // ==========================================================

        // 1. Generate Content di Edit Modal
        $(document).on('click', '.btn-kreasikan-edit', function() {
            var btn = $(this);
            var form = btn.closest('form');
            var judul = form.find('input[name="judul"]').val();
            var targetIsi = form.find('textarea[name="isi"]');

            if (judul == '') {
                alert('Judul tidak boleh kosong!');
                return;
            }

            var originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Menulis...');

            $.ajax({
                url: 'ai_helper.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ text: judul, mode: 'generate_content' }),
                success: function(response) {
                    if (response.result) {
                        targetIsi.val(response.result);
                    } else if (response.error) {
                        alert('AI Error: ' + response.error);
                    }
                    btn.prop('disabled', false).html(originalText);
                },
                error: function(xhr, status, error) {
                    alert('Gagal menghubungi AI Helper.');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // 2. Generate Summary di Edit Modal
        $(document).on('click', '.btn-ringkas-edit', function() {
            var btn = $(this);
            var form = btn.closest('form');
            var isi = form.find('textarea[name="isi"]').val();
            var targetSummary = form.find('textarea[name="summary"]');

            if (isi == '') {
                alert('Isi artikel masih kosong!');
                return;
            }

            var originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Meringkas...');

            $.ajax({
                url: 'ai_helper.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ text: isi, mode: 'generate_caption' }),
                success: function(response) {
                    if (response.result) {
                        targetSummary.val(response.result);
                    } else if (response.error) {
                        alert('AI Error: ' + response.error);
                    }
                    btn.prop('disabled', false).html(originalText);
                },
                error: function(xhr, status, error) {
                    alert('Gagal menghubungi AI Helper.');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });
    });
</script>

<?php
include "upload_foto.php";

//jika tombol simpan diklik
if (isset($_POST['simpan'])) {
    $judul = $_POST['judul'];
    $isi = $_POST['isi'];
    $summary = $_POST['summary'];
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
                document.location='admin.php?page=article';
            </script>";
            die;
        }
    }

    //cek apakah ada id yang dikirimkan dari form
    if (isset($_POST['id'])) {
        //jika ada id, lakukan update data dengan id tersebut
        $id = $_POST['id'];

        if ($nama_gambar == '') {
            //jika tidak ganti gambar
            $gambar = $_POST['gambar_lama'];
        } else {
            //jika ganti gambar, hapus gambar lama
            if (file_exists("img/" . $_POST['gambar_lama'])) {
                unlink("img/" . $_POST['gambar_lama']);
            }
        }

        $stmt = $conn->prepare("UPDATE article 
                                SET 
                                judul =?,
                                isi =?,
                                summary = ?,
                                gambar = ?,
                                tanggal = ?,
                                username = ?
                                WHERE id = ?");

        $stmt->bind_param("ssssssi", $judul, $isi, $summary, $gambar, $tanggal, $username, $id);
        $simpan = $stmt->execute();
    } else {
        //jika tidak ada id, lakukan insert data baru
        $stmt = $conn->prepare("INSERT INTO article (judul,isi,summary,gambar,tanggal,username)
                                VALUES (?,?,?,?,?,?)");

        $stmt->bind_param("ssssss", $judul, $isi, $summary, $gambar, $tanggal, $username);
        $simpan = $stmt->execute();
    }

    if ($simpan) {
        echo "<script>
            alert('Simpan data sukses');
            document.location='admin.php?page=article';
        </script>";
    } else {
        echo "<script>
            alert('Simpan data gagal');
            document.location='admin.php?page=article';
        </script>";
    }

    $stmt->close();
    $conn->close();
}

//jika tombol hapus diklik
if (isset($_POST['hapus'])) {
    $id = $_POST['id'];
    $gambar = $_POST['gambar'];

    if ($gambar != '') {
        //hapus file gambar
        if (file_exists("img/" . $gambar)) {
            unlink("img/" . $gambar);
        }
    }

    $stmt = $conn->prepare("DELETE FROM article WHERE id =?");

    $stmt->bind_param("i", $id);
    $hapus = $stmt->execute();

    if ($hapus) {
        echo "<script>
            alert('Hapus data sukses');
            document.location='admin.php?page=article';
        </script>";
    } else {
        echo "<script>
            alert('Hapus data gagal');
            document.location='admin.php?page=article';
        </script>";
    }

    $stmt->close();
    $conn->close();
}
?>