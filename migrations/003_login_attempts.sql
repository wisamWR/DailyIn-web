-- Tabel untuk membatasi percobaan login (maks 5 gagal per IP + username dalam 15 menit).
-- Jalankan sekali di database yang SUDAH ada. Tanpa tabel ini login tetap jalan, tapi tanpa batas percobaan.

-- MySQL / MariaDB (phpMyAdmin):
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) NOT NULL,
  `username` varchar(50) NOT NULL,
  `attempted_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login_attempts_lookup` (`ip`,`username`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- PostgreSQL / Neon (jalankan bagian ini saja, tanpa bagian MySQL di atas):
-- CREATE TABLE IF NOT EXISTS login_attempts (
--   id SERIAL PRIMARY KEY,
--   ip VARCHAR(64) NOT NULL,
--   username VARCHAR(50) NOT NULL,
--   attempted_at INTEGER NOT NULL
-- );
-- CREATE INDEX IF NOT EXISTS login_attempts_lookup ON login_attempts (ip, username, attempted_at);
