-- (Opsional, MySQL) Tabel session jika ingin menyimpan session login di database.
-- Hanya dibutuhkan jika SESSION_DRIVER=database diatur di .env.
-- Untuk PostgreSQL/Neon tabel ini sudah ada di database/postgres.sql.

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(128) NOT NULL,
  `data` mediumtext NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
