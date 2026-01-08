<?php
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DailyIn</title>
  <link rel="icon" href="/img/logo.png" />
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
    crossorigin="anonymous" />
</head>

<body>
  <!-- nav begin -->
  <nav class="navbar navbar-expand-lg bg-body-tertiary sticky-top">
    <div class="container">
      <a class="navbar-brand" href="#">DailyIn</a>
      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarSupportedContent"
        aria-controls="navbarSupportedContent"
        aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 text-dark">
          <li class="nav-item">
            <a class="nav-link" href="#">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#article">Article</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#gallery">Gallery</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#schedule">Schedule</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#profile">Profile</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="login.php" target="_blank">Login</a>
          </li>
          <li class="nav-item text-lg-start text-start ms-lg-3 ms-0">
            <span
              class="fw-semibold small d-lg-none mb-1 me-3 text-secondary">
              Toggle Theme
            </span>
            <div
              class="d-flex flex-row-reverse justify-content-end align-items-start gap-2 me-lg-0 me-3">
              <span
                class="icon-wrapper bg-warning text-white d-flex align-items-center justify-content-center"
                style="
                    width: 35px;
                    height: 35px;
                    cursor: pointer;
                    border-radius: 15%;
                  ">
                <i
                  id="lightIcon"
                  class="bi bi-brightness-high-fill"
                  role="button"></i>
              </span>
              <span
                class="icon-wrapper bg-secondary text-white d-flex align-items-center justify-content-center"
                style="
                    width: 35px;
                    height: 35px;
                    cursor: pointer;
                    border-radius: 15%;
                  ">
                <i
                  id="darkIcon"
                  class="bi bi-moon-stars-fill"
                  role="button"></i>
              </span>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  <!-- nav end -->
  <!-- hero begin -->
  <section id="hero" class="text-center p-3 p-sm-5 bg-primary-subtle text-sm-start">
    <div class="container">
      <div class="d-sm-flex flex-sm-row-reverse align-items-center">
        <img src="img/banner.jpg" class="img-fluid mb-4 mb-sm-0" width="300" />
        <div>
          <h1 class="fw-bold display-4">Setiap Mimpi Layak Dicatat</h1>
          <h4 class="lead display-6">
            Sebuah ruang untuk mengenal diri lebih jauh mencatat ide,
            rutinitas, dan impian yang ingin kamu capai.
          </h4>
          <h6>
            <span id="tanggal"></span>
            <span id="jam"></span>
          </h6>
        </div>
      </div>
    </div>
  </section>
  <!-- hero end -->
  <!-- article begin -->
  <section id="article" class="text-center p-3 p-sm-5">
    <div class="container">
      <h1 class="fw-bold display-4 pb-3">Article</h1>

      <div class="row row-cols-1 row-cols-md-3 g-4 justify-content-center">
        <?php
        $sql = "SELECT * FROM article ORDER BY tanggal DESC";
        $hasil = $conn->query($sql);

        while ($row = $hasil->fetch_assoc()) {
        ?>
          <div class="col">
            <div class="card h-100">
              <img src="img/<?= $row["gambar"] ?>" class="card-img-top" alt="..." />
              <div class="card-body">
                <h5 class="card-title"><?= $row["judul"] ?></h5>
                <p class="card-text">
                  <?= $row["summary"] != '' ? $row["summary"] : 'Artikel ini belum memiliki ringkasan.' ?>
                </p>
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#articleModal" 
                   data-id="<?= $row["id"] ?>"
                   data-judul="<?= htmlspecialchars($row["judul"]) ?>" 
                   data-isi="<?= htmlspecialchars($row["isi"]) ?>"
                   data-gambar="<?= $row["gambar"] ?>"
                   data-tanggal="<?= $row["tanggal"] ?>">
                   Baca Selengkapnya
                </a>
              </div>
              <div class="card-footer">
                <small class="text-body-secondary">
                  <?= $row["tanggal"] ?>
                </small>
              </div>
            </div>
          </div>
        <?php
        }
        ?>
      </div>
    </div>
  </section>
  <!-- article end -->
  <!-- gallery begin -->
  <section id="gallery" class="text-center p-3 p-sm-5 bg-primary-subtle">
    <div class="container">
      <h1 class="fw-bold display-4 pb-3">Gallery</h1>
      <div id="carouselExample" class="carousel slide">
        <div class="carousel-inner">
          <?php
          $sql_gallery = "SELECT * FROM gallery ORDER BY tanggal DESC";
          $hasil_gallery = $conn->query($sql_gallery);
          $active = true;
          while ($row_gallery = $hasil_gallery->fetch_assoc()) {
            if ($row_gallery["gambar"] != '' && file_exists('img/' . $row_gallery["gambar"])) {
          ?>
              <div class="carousel-item <?php if ($active) {
                                          echo 'active';
                                          $active = false;
                                        } ?>">
                <img src="img/<?= $row_gallery["gambar"] ?>" class="d-block w-100" alt="<?= $row_gallery["judul"] ?>" />
              </div>
          <?php
            }
          }
          ?>
        </div>
        <button
          class="carousel-control-prev"
          type="button"
          data-bs-target="#carouselExample"
          data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button
          class="carousel-control-next"
          type="button"
          data-bs-target="#carouselExample"
          data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    </div>
  </section>
  <!-- gallery end -->

  <!-- schedule begin -->
  <section id="schedule" class="text-center p-3 p-sm-5">
    <div class="container">
      <h1 class="fw-bold display-5 pb-4">
        Jadwal Kuliah & Kegiatan Mahasiswa
      </h1>
      <div class="row justify-content-center g-4">
        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-primary mb-3 h-100">
            <div class="card-header bg-primary text-white fw-bold">Senin</div>
            <div class="card-body">
              <p class="mb-1 fw-semibold">09:30 - 12:00</p>
              <p class="mb-1">Logika Informatika</p>
              <p class="mb-3">Ruang H.5.12</p>
              <p class="mb-1 fw-semibold">14:10 - 15:50</p>
              <p class="mb-1">Basis Data</p>
              <p class="mb-1">Ruang H.5.10</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-success mb-3 h-100">
            <div class="card-header bg-success text-white fw-bold">
              Selasa
            </div>
            <div class="card-body">
              <p class="mb-1 fw-semibold">12:30 - 15:00</p>
              <p class="mb-1">Rekayasa Perangkat Lunak</p>
              <p class="mb-3">Ruang H.5.10</p>
              <p class="mb-1 fw-semibold">15:30 - 18:00</p>
              <p class="mb-1">Sistem Operasi</p>
              <p class="mb-1">Ruang H.3.2</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-danger mb-3 h-100">
            <div class="card-header bg-danger text-white fw-bold">Rabu</div>
            <div class="card-body">
              <p class="mb-1 fw-semibold">09:30 - 12:00</p>
              <p class="mb-1">Kriptografi</p>
              <p class="mb-3">Ruang H.5.13</p>
              <p class="mb-1 fw-semibold">12:30 - 14:10</p>
              <p class="mb-1">Pemrograman Berbasis Web</p>
              <p class="mb-1">Ruang D.2.J</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-warning mb-3 h-100">
            <div class="card-header bg-warning fw-bold">Kamis</div>
            <div class="card-body">
              <p class="mb-1 fw-semibold">14:10 - 15:50</p>
              <p class="mb-1">Basis Data</p>
              <p class="mb-3">Ruang D.2.K</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-info mb-3 h-100">
            <div class="card-header bg-info text-white fw-bold">Jumat</div>
            <div class="card-body">
              <p class="mb-1 fw-semibold">09:30 - 12:00</p>
              <p class="mb-1">Probabilitas dan Statistika</p>
              <p class="mb-3">Ruang H.3.2</p>
              <p class="mb-1 fw-semibold">13:00 - 15:00</p>
              <p class="mb-1">Data Mining</p>
              <p class="mb-1">Ruang H.4.3</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-secondary mb-3 h-100">
            <div class="card-header bg-secondary text-white fw-bold">
              Sabtu
            </div>
            <div class="card-body">
              <p class="mb-1 fw-semibold">08:00 - 10:00</p>
              <p class="mb-1">Rapat Kucing UDINUS</p>
              <p class="mb-3">Gedung F</p>
              <p class="mb-1 fw-semibold">15:00 - 16:30</p>
              <p class="mb-1">Street Feeding</p>
              <p class="mb-1">Jl. Nakula Raya</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4 col-lg-3">
          <div class="card border-dark mb-3 h-100">
            <div class="card-header bg-dark text-white fw-bold">Minggu</div>
            <div class="card-body">
              <p class="text-muted fst-italic">Tidak Ada Jadwal</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- schedule end -->

  <!-- profile begin -->
  <section id="profile" class="text-center p-3 p-sm-5 bg-body-secondary">
    <div class="container">
      <h1 class="fw-bold display-5 pb-4">Profil Mahasiswa</h1>
      <div class="row align-items-center justify-content-center">
        <div class="col-12 col-md-4 mb-3 mb-md-0">
          <img
            src="img/wisam.jpg"
            alt="Foto Mahasiswa"
            class="img-fluid rounded-circle border border-3"
            style="width: 200px; height: 200px; object-fit: cover" />
        </div>
        <div class="col-12 col-md-6">
          <div class="card shadow-sm">
            <div class="card-body text-start">
              <h4 class="fw-bold text-center mb-3">
                Mohammad Wisam Wiraghina
              </h4>
              <h6 class="text-muted small mb-3 text-center">
                Mahasiswa Teknik Informatika
              </h6>
              <table class="table table-borderless">
                <tr>
                  <th scope="row">NIM</th>
                  <td>: A11.2024.15739</td>
                </tr>
                <tr>
                  <th scope="row">Program Studi</th>
                  <td>: Teknik Informatika</td>
                </tr>
                <tr>
                  <th scope="row">Email</th>
                  <td>: 111202415739@mhs.dinus.ac.id</td>
                </tr>
                <tr>
                  <th scope="row">Telepon</th>
                  <td>: +62 812 2272 67533</td>
                </tr>
                <tr>
                  <th scope="row">Alamat</th>
                  <td>: Jl. Baru II No. 163, Semarang</td>
                </tr>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- profile end -->
  <!-- footer begin -->
  <footer class="text-center p-3 p-sm-5">
    <div>
      <a href="https://www.instagram.com/udinusofficial"><i class="bi bi-instagram h2 p-2 text-dark"></i></a>
      <a href="https://www.twitter.com/udinusofficial"><i class="bi bi-twitter-x h2 p-2 text-dark"></i></a>
      <a href="https://wa.me/+6281268577"><i class="bi bi-whatsapp h2 p-2 text-dark"></i></a>
    </div>
    <div>Mohammad Wisam Wiraghina &copy; 2025</div>
  </footer>
  <!-- footer end -->

  <!-- Modal Detail Artikel -->
  <div class="modal fade" id="articleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="articleModalLabel">Judul Artikel</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <img src="" id="modalGambar" class="img-fluid mb-3 w-100 rounded" alt="Gambar Artikel">
            <h6 class="text-muted mb-3" id="modalTanggal"></h6>
            <div id="modalIsi" style="text-align: justify;"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-warning" id="btnBuatRingkasan">Buat Ringkasan ✨</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>



  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>
  <script type="text/javascript">
    window.setTimeout("tampilWaktu()", 1000);

    function tampilWaktu() {
      var waktu = new Date();
      var bulan = waktu.getMonth() + 1;

      setTimeout("tampilWaktu()", 1000);
      document.getElementById("tanggal").innerHTML =
        waktu.getDate() + "/" + bulan + "/" + waktu.getFullYear();
      document.getElementById("jam").innerHTML =
        waktu.getHours() +
        ":" +
        waktu.getMinutes() +
        ":" +
        waktu.getSeconds();
    }
  </script>
  <script type="text/javascript">
    const html = document.documentElement;
    const lightIcon = document.getElementById("lightIcon");
    const darkIcon = document.getElementById("darkIcon");

    if (window.matchMedia("(prefers-color-scheme: light)").matches) {
      html.setAttribute("data-bs-theme", "light");
    }

    lightIcon.addEventListener("click", () => {
      html.setAttribute("data-bs-theme", "light");
    });

    darkIcon.addEventListener("click", () => {
      html.setAttribute("data-bs-theme", "dark");
    });

    // Script untuk mengisi data modal
    const articleModal = document.getElementById('articleModal');
    articleModal.addEventListener('show.bs.modal', event => {
        // Tombol yang memicu modal
        const button = event.relatedTarget;
        
        // Ambil data dari atribut data-bs-*
        const id = button.getAttribute('data-id');
        const judul = button.getAttribute('data-judul');
        const isi = button.getAttribute('data-isi');
        const gambar = button.getAttribute('data-gambar');
        const tanggal = button.getAttribute('data-tanggal');

        // Update isi modal
        const modalTitle = articleModal.querySelector('.modal-title');
        const modalBodyIsi = articleModal.querySelector('#modalIsi');
        const modalBodyGambar = articleModal.querySelector('#modalGambar');
        const modalBodyTanggal = articleModal.querySelector('#modalTanggal');

        modalTitle.textContent = judul;
        modalBodyIsi.innerHTML = isi; // Gunakan innerHTML agar tag HTML di isi (jika ada) ter-render
        modalBodyGambar.src = "img/" + gambar;
        modalBodyTanggal.textContent = "Diposting pada: " + tanggal;

        // Setup tombol Buat Ringkasan (Inline Toggle)
        const btnRingkasan = document.getElementById('btnBuatRingkasan');
        
        // Reset state tombol setiap kali modal dibuka
        btnRingkasan.textContent = "Buat Ringkasan ✨";
        btnRingkasan.className = "btn btn-warning";
        
        // Variabel untuk menyimpan state ringkasan di closure ini
        let summaryContent = '';
        const originalContent = isi; // Simpan konten asli

        btnRingkasan.onclick = async function() {
            // Cek text tombol untuk menentukan 'Mode'
            if (btnRingkasan.textContent.includes("Buat Ringkasan")) {
                
                // Jika ringkasan belum ada, generate dulu
                if (!summaryContent) {
                    const originalBtnText = btnRingkasan.innerHTML;
                    btnRingkasan.disabled = true;
                    btnRingkasan.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Meringkas...';
                    
                    try {
                        const response = await fetch('ai_helper.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                mode: 'generate_summary',
                                title: judul,
                                text: modalBodyIsi.innerText // Kirim plain text dari konten yang tampil
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.result) {
                            // Format bullet points HTML
                            summaryContent = '<div class="alert alert-info"><small>⚡ Ringkasan AI:</small><br>' + 
                                             data.result.split('\n')
                                                .filter(line => line.trim())
                                                .map(line => line.trim())
                                                .join('<br>') + 
                                             '</div>';
                        } else if (data.error) {
                            alert('AI Error: ' + data.error);
                            btnRingkasan.disabled = false;
                            btnRingkasan.innerHTML = originalBtnText;
                            return; // Stop
                        } else {
                            alert('Gagal mengambil ringkasan.');
                            btnRingkasan.disabled = false;
                            btnRingkasan.innerHTML = originalBtnText;
                            return; // Stop
                        }
                    } catch (error) {
                        alert('Error: ' + error.message);
                        btnRingkasan.disabled = false;
                        btnRingkasan.innerHTML = originalBtnText;
                        return; // Stop
                    }
                }
                
                // Tampilkan Ringkasan & Ubah Tombol
                modalBodyIsi.innerHTML = summaryContent;
                btnRingkasan.disabled = false;
                btnRingkasan.textContent = "Kembali ke Artikel ↩️";
                btnRingkasan.className = "btn btn-success";

            } else {
                // Mode 'Kembali ke Artikel'
                modalBodyIsi.innerHTML = originalContent; // Restore asli
                btnRingkasan.textContent = "Buat Ringkasan ✨";
                btnRingkasan.className = "btn btn-warning";
            }
        };
    });
  </script>
</body>

</html>