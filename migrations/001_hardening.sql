-- Migrasi untuk database yang SUDAH ada (live / lokal lama).
-- Jalankan sekali di phpMyAdmin (tab SQL) pada database webdailyjournal.
-- Backup database dulu sebelum menjalankan.

-- 1. Ubah kolom latin1 menjadi utf8mb4 supaya emoji & karakter non-latin tersimpan benar
ALTER TABLE `article`
  MODIFY `judul` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  MODIFY `isi` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  MODIFY `gambar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  MODIFY `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;

ALTER TABLE `gallery`
  MODIFY `judul` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  MODIFY `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;

-- 2. Kolom cache ringkasan AI untuk pengunjung (supaya AI tidak dipanggil berulang)
ALTER TABLE `article` ADD COLUMN `ai_summary` text DEFAULT NULL AFTER `summary`;

-- 3. Username harus unik
ALTER TABLE `user` ADD UNIQUE KEY `username` (`username`);

-- Catatan password:
-- Password md5 lama otomatis diubah ke bcrypt saat login berikutnya.
-- Setelah login, SEGERA ganti password default lewat menu "Ganti Password".
