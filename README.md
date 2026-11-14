# 🗞️ DailyIn - Smart Daily Journal with AI

![DailyIn Banner](img/logo_bg.png)

**DailyIn** adalah platform manajemen konten artikel (CMS) berbasis **PHP Native** yang diperkuat dengan kecerdasan buatan (**Google Gemini AI**). Website ini tidak hanya mencatat jurnal harian, tetapi juga membantu Anda menulis artikel berkualitas, membuat caption media sosial, dan meringkas bacaan untuk pengunjung secara otomatis.

> 🚀 **Fitur Utama**: AI Writer, AI Caption Generator, AI Summary, Manajemen Artikel & Galeri, Responsive Admin Dashboard.

### 🌐 Live Demo
Coba langsung fitur AI tanpa install di sini: **[dailyin-ai.wuaze.com](https://dailyin-ai.wuaze.com/)**

---

## 🌟 Fitur Unggulan

### 🧠 1. AI Power (Powered by Gemini)
Website ini terintegrasi langsung dengan Google Gemini API untuk membantu produktivitas:
*   **✨ Auto-Write Article**: Bingung mau nulis apa? Cukup ketik judul, AI akan menuliskan artikel lengkap 600-800 kata yang terstruktur rapi.
*   **📱 Auto-Caption**: Secara otomatis membuat caption Instagram/Twitter yang *catchy* dan relevan dari isi artikel Anda.
*   **📝 Smart Summary**: Pengunjung malas membaca panjang? Klik fitur "Ringkasan", AI akan menyajikan poin-poin penting artikel dalam sekejap.

### 🛠️ 2. Manajemen Konten (CMS)
*   **CRUD Artikel**: Tambah, Edit (dengan Summernote WYSIWYG Editor), Hapus artikel.
*   **Manajemen Galeri**: Upload foto kegiatan dengan manajemen file yang rapi.
*   **Dashboard Admin**: Tampilan admin yang bersih, modern, dan **Mobile Responsive** (aman dibuka di HP).

### 🔒 3. Keamanan & Konfigurasi
*   **Environment Variables (.env)**: Kredensial database dan API Key disimpan di file `.env` (tidak ikut di-commit, akses web diblokir lewat `.htaccess`).
*   **Login System**: Password di-hash dengan `password_hash()` (bcrypt), session cookie `HttpOnly` + `SameSite`, session ID diganti setelah login. Password md5 lama otomatis di-upgrade saat login.
*   **CSRF Protection**: Semua form dan request AJAX admin memakai CSRF token.
*   **SQL Injection Protection**: Semua query yang memakai input user menggunakan Prepared Statements.
*   **XSS Protection**: Output di-escape, HTML artikel (termasuk hasil AI) dibersihkan dari script & event handler.
*   **Upload Aman**: Isi file dicek benar-benar gambar, nama file acak, eksekusi script di folder `img/` dimatikan.
*   **Endpoint AI Terlindungi**: Fitur tulis & caption hanya untuk admin yang login. Ringkasan untuk pengunjung hanya bisa untuk artikel yang ada, dan hasilnya di-cache di database.

---

## 💻 Teknologi yang Digunakan
*   **Backend**: PHP 8 (Native), PDO (MySQL lokal / PostgreSQL di Vercel).
*   **Hosting**: Vercel Container Runtime (FrankenPHP), Neon Postgres, Vercel Blob.
*   **Frontend**: HTML5, CSS3, Bootstrap 5.3, jQuery.
*   **AJAX**: Untuk interaksi AI yang mulus tanpa reload halaman.
*   **AI Service**: Google Gemini API via cURL.
*   **Editor**: Summernote JS.

---

## 🛠️ Instalasi & Cara Menjalankan

Ikuti langkah ini untuk menjalankan project di komputer lokal (XAMPP/Laragon):

### 1. Persiapan Database
1.  Buka **phpMyAdmin** (`http://localhost/phpmyadmin`).
2.  Buat database baru bernama `webdailyjournal`.
3.  Import file `webdailyjournal.sql`.

> ℹ️ **Sudah punya database lama?** Jalankan `migrations/001_hardening.sql` sekali di tab SQL phpMyAdmin (backup dulu). Migrasi ini mengubah kolom ke `utf8mb4` dan menambah kolom cache `ai_summary`.

### 2. Konfigurasi Environment (.env)
1.  Copy file `.env.example` menjadi `.env`.
2.  Isi konfigurasi berikut:
    ```env
    # Database Configuration
    DB_SERVER=localhost
    DB_USERNAME=root
    DB_PASSWORD=
    DB_NAME=webdailyjournal

    # Google Gemini AI Key
    GEMINI_API_KEY=AIzaSyDxxxxxxxxx...
    ```
    > ⚠️ **Penting**: Jangan lupa isi `GEMINI_API_KEY` agar fitur AI berfungsi.

    > 💡 **XAMPP di Windows** dan muncul error `SSL certificate problem`? Download [cacert.pem](https://curl.se/ca/cacert.pem) lalu isi `CURL_CA_BUNDLE=C:/path/ke/cacert.pem` di `.env`. Verifikasi SSL sengaja tidak dimatikan.

### 3. Menjalankan Website
1.  Letakkan folder project di `C:\xampp\htdocs\DailyIn-web` (atau folder `www` Laragon).
2.  Buka browser dan akses: `http://localhost/DailyIn-web`.
3.  Untuk masuk ke admin, akses `/login.php`. Akun bawaan: `admin` / `123456`.
4.  **Segera ganti password** lewat menu nama user → **Ganti Password**.

---

## ▲ Deploy ke Vercel

Project ini di-deploy sebagai **container** (lihat `Dockerfile` + `Caddyfile`). Vercel otomatis memakai preset *Container* karena ada `Dockerfile` di root.

Karena server Vercel tidak menyimpan file secara permanen dan tiap instance terpisah:

| Kebutuhan | Di Vercel memakai | Environment variable |
|---|---|---|
| Database | **Neon Postgres** (Marketplace) | `DATABASE_URL` (otomatis dari integrasi) |
| Upload gambar | **Vercel Blob** | `BLOB_READ_WRITE_TOKEN` (otomatis saat Blob store dihubungkan) |
| Session login | Tabel `sessions` di database | `SESSION_DRIVER=database` |
| AI | Google Gemini | `GEMINI_API_KEY` |

Langkah:
1.  Import repo ini di Vercel (atau `vercel deploy`).
2.  Tambahkan **Neon** dari tab *Storage / Marketplace* dan hubungkan ke project.
3.  Jalankan `database/postgres.sql` sekali di SQL Editor Neon (membuat tabel + data awal).
4.  Buat **Blob store** dan hubungkan ke project.
5.  Tambahkan env `SESSION_DRIVER=database` dan `GEMINI_API_KEY`, lalu redeploy.
6.  Login sebagai `admin` / `123456` dan **langsung ganti password**.

> `.htaccess` hanya berlaku di hosting Apache. Di Vercel, aturan blokir file sensitif ada di `Caddyfile`.

---

## 📂 Struktur Folder
```
DailyIn-web/
├── 📂 api/              # API endpoints internal
├── 📂 img/              # Folder upload gambar (eksekusi script diblokir)
├── 📂 database/         # Skema PostgreSQL (Neon / Vercel)
├── 📂 docker/           # php.ini untuk container
├── 📂 migrations/       # Script SQL untuk upgrade database lama (MySQL)
├── 📄 Dockerfile        # Image FrankenPHP untuk Vercel
├── 📄 Caddyfile         # Web server config di container (pengganti .htaccess)
├── 📄 .env              # File Konfigurasi Rahasia (salin dari .env.example)
├── 📄 .htaccess         # Blokir akses file sensitif + header keamanan
├── 📄 admin.php         # Dashboard Admin (Main Layout)
├── 📄 ai_helper.php     # Logika & Koneksi ke Gemini AI
├── 📄 article.php       # Halaman Manajemen Artikel
├── 📄 article_data.php  # Tabel Artikel (backend load)
├── 📄 auth.php          # Session, cek login, CSRF, escape & sanitasi HTML
├── 📄 env.php           # Pembaca file .env
├── 📄 index.php         # Halaman Depan (Landing Page)
├── 📄 koneksi.php       # Koneksi Database (PDO, MySQL/PostgreSQL) + helper query
├── 📄 storage.php       # Simpan gambar (folder img/ atau Vercel Blob)
├── 📄 password.php      # Ganti Password admin
└── ...
```

---

## 🤝 Kontribusi & Pengembangan
Project ini dikembangkan sebagai latihan penerapan **AJAX** dan integrasi **AI** dalam pengembangan web PHP.

*   **Kampus**: Universitas Dian Nuswantoro
*   **Lisensi**: Open Source

---
*Happy Coding! 🚀*
