-- Skema + data awal DailyIn untuk PostgreSQL (Neon / Vercel).
-- Jalankan sekali di database kosong, misalnya lewat SQL Editor Neon atau:
--   psql "$DATABASE_URL" -f database/postgres.sql

CREATE TABLE article (
  id SERIAL PRIMARY KEY,
  judul TEXT,
  isi TEXT,
  gambar TEXT,
  summary TEXT,
  ai_summary TEXT,
  tanggal TIMESTAMP,
  username VARCHAR(50)
);

CREATE TABLE gallery (
  id SERIAL PRIMARY KEY,
  judul TEXT,
  tanggal TIMESTAMP NOT NULL,
  gambar VARCHAR(255) NOT NULL,
  username VARCHAR(50)
);

-- "user" adalah kata khusus di PostgreSQL, jadi selalu ditulis dengan tanda kutip
CREATE TABLE "user" (
  id SERIAL PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password TEXT NOT NULL,
  foto TEXT NOT NULL DEFAULT ''
);

-- Session login (SESSION_DRIVER=database)
CREATE TABLE sessions (
  id VARCHAR(128) PRIMARY KEY,
  data TEXT NOT NULL,
  updated_at INTEGER NOT NULL
);
CREATE INDEX sessions_updated_at ON sessions (updated_at);

-- Catatan login gagal (batas percobaan login)
CREATE TABLE login_attempts (
  id SERIAL PRIMARY KEY,
  ip VARCHAR(64) NOT NULL,
  username VARCHAR(50) NOT NULL,
  attempted_at INTEGER NOT NULL
);
CREATE INDEX login_attempts_lookup ON login_attempts (ip, username, attempted_at);

INSERT INTO article (id, judul, isi, gambar, tanggal, username) VALUES
(1, 'Perpustakaan Kampus', NULL, 'book.jpg', '2025-12-10 13:30:06', 'admin'),
(2, 'Ruang Kelas', NULL, 'class.jpg', '2025-12-10 13:34:30', 'admin'),
(3, 'Pemandangan', NULL, 'alaska.jpg', '2025-12-10 13:36:01', 'admin'),
(4, 'ide', NULL, 'ideas.jpg', '2025-12-10 13:36:01', 'admin'),
(5, 'Coding', 'kumpulan project dan pelatihan', 'coding.jpg', '2025-12-15 20:50:34', 'admin'),
(6, 'Holiday Place', NULL, 'sf.jpg', '2025-12-10 13:36:01', 'admin'),
(7, 'Memory', 'Kenangan yang akan terus di ingat sepanjang masa!!!', '20251215204343.jpg', '2025-12-15 20:43:43', 'admin');

INSERT INTO gallery (id, judul, tanggal, gambar, username) VALUES
(1, 'Holiday Place', '2025-12-31 17:26:42', 'sf.jpg', 'admin'),
(2, 'Ruang Kelas', '2025-12-31 17:26:42', 'class.jpg', 'admin'),
(3, 'Perpustakaan Kampus', '2025-12-31 17:26:42', 'book.jpg', 'admin'),
(4, 'Pemandangan', '2025-12-31 17:26:42', 'banner.jpg', 'admin'),
(5, 'Alaska', '2025-12-31 17:26:42', 'alaska.jpg', 'admin');

-- Akun bawaan: admin / 123456 -> SEGERA ganti lewat menu "Ganti Password"
INSERT INTO "user" (id, username, password, foto) VALUES
(1, 'admin', '$2y$12$vM1ESMjsvcWeeB/HL/hUceAFpPghH2K9fGPbPKysDzSDzazxNcpGq', '');

-- Lanjutkan penomoran id setelah data awal
SELECT setval('article_id_seq', (SELECT MAX(id) FROM article));
SELECT setval('gallery_id_seq', (SELECT MAX(id) FROM gallery));
SELECT setval('user_id_seq', (SELECT MAX(id) FROM "user"));
