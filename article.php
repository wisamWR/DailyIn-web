    <!-- Button trigger modal -->
    <button type="button" class="btn btn-secondary mb-2" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg"></i> Tambah Article
    </button>
    <div class="row">
        <div class="table-responsive" id="article_data">

        </div>

        <!-- Awal Modal Tambah-->
        <div class="modal fade" id="modalTambah" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <form method="post" action="" enctype="multipart/form-data" style="display:contents">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="staticBackdropLabel">Tambah Article</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row">
                                <!-- Kolom Kiri: Judul & Isi Artikel (85% Width equivalent if needed, but keeping col-md-8 for now) -->
                                <div class="col-md-8">
                                    <!-- Judul Section -->
                                    <div class="mb-4">
                                        <label for="formGroupExampleInput" class="form-label small text-muted">JUDUL ARTIKEL</label>
                                        <input type="text" class="form-control form-control-lg fw-bold" name="judul" id="judulArtikel" placeholder="Tulis Judul Artikel yang Menarik" required>
                                    </div>

                                    <!-- Isi Artikel Section -->
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label for="floatingTextarea2" class="form-label small text-muted mb-0">ISI ARTIKEL</label>
                                            <button type="button" class="btn btn-outline-info btn-sm" id="btnGenContent">
                                                <i class="bi bi-robot"></i> ✨ Bantu Tulis (AI)
                                            </button>
                                        </div>
                                        <textarea class="form-control" placeholder="Mulai menulis artikel anda di sini..." name="isi" id="isiArtikel" rows="15" required></textarea>
                                    </div>
                                </div>

                                <!-- Kolom Kanan: Sidebar (Caption & Gambar) -->
                                <div class="col-md-4 border-start">
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label for="summaryResult" class="form-label small text-muted mb-0">CAPTION SOSMED</label>
                                            <button type="button" class="btn btn-outline-warning btn-sm" id="btnGenSummary">
                                                <i class="bi bi-stars"></i> ✨ Buat Caption
                                            </button>
                                        </div>
                                        <textarea class="form-control bg-light" placeholder="Caption otomatis..." name="summary" id="summaryResult" rows="6"></textarea>
                                    </div>

                                    <div class="mb-4">
                                        <label for="formGroupExampleInput2" class="form-label small text-muted">GAMBAR SAMPUL</label>
                                        <input type="file" class="form-control" name="gambar">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <input type="submit" value="Simpan Artikel" name="simpan" class="btn btn-primary">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Akhir Modal Tambah-->
    </div>

<script>
    function toggleContent(el) {
        var $el = $(el);
        var $td = $el.closest('td');
        var $short = $td.find('.short-text');
        var $full = $td.find('.full-text');
        var $icon = $el.find('i');

        $short.toggleClass('d-none');
        $full.toggleClass('d-none');
        
        if ($full.hasClass('d-none')) {
            $icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
        } else {
            $icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
        }
    }

    $(document).ready(function() {
        load_data();

        // Initialize Summernote
        $('#isiArtikel').summernote({
            tabsize: 2,
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });

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
            // Get content from Summernote
            var isi = $('#isiArtikel').summernote('code');
            // Strip HTML tags for summary generation to save tokens/cleaner input
            var textOnly = $("<div/>").html(isi).text();

            if (textOnly.trim() == '') {
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
                data: JSON.stringify({ text: textOnly, mode: 'generate_caption' }),
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
                        // Set content to Summernote
                        $('#isiArtikel').summernote('code', response.result);
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


        // 1. Generate Content di Edit Modal
        // Note: Logic for Edit Modal might need similar Summernote updates if the edit modal uses a different ID or class for the textarea.
        // Assuming 'textarea[name="isi"]' refers to the same element or a new one in a different modal. 
        // If it's a dynamic modal (edit), standard summernote init might need to happen on modal show.
        // For simplicity in this task, I will leave existing edit logic but warn that it needs Summernote support if it uses rich text too.
        
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
                         // Check if this textarea has summernote initialized
                         if (targetIsi.next('.note-editor').length > 0) {
                             targetIsi.summernote('code', response.result);
                         } else {
                             targetIsi.val(response.result);
                         }
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
            var targetIsi = form.find('textarea[name="isi"]');
            var targetSummary = form.find('textarea[name="summary"]');
            
            // Get content safely
            var isi = '';
            if (targetIsi.next('.note-editor').length > 0) {
                 isi = targetIsi.summernote('code');
                 isi = $("<div/>").html(isi).text(); // Strip tags
            } else {
                 isi = targetIsi.val();
            }

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