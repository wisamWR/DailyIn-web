-- Fase 4: mood entri, jumlah view, dan catatan pemakaian AI.
-- Jalankan sekali di database yang SUDAH ada (backup dulu).

-- ===================== MySQL / MariaDB (phpMyAdmin) =====================
ALTER TABLE `article`
  ADD COLUMN `mood` varchar(20) DEFAULT NULL AFTER `published_at`,
  ADD COLUMN `views` int(11) NOT NULL DEFAULT 0 AFTER `mood`;

CREATE TABLE IF NOT EXISTS `ai_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mode` varchar(30) NOT NULL,
  `model` varchar(60) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_usage_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================== PostgreSQL / Neon =====================
-- (jalankan bagian ini saja di Neon, tanpa bagian MySQL di atas)
-- ALTER TABLE article ADD COLUMN IF NOT EXISTS mood VARCHAR(20);
-- ALTER TABLE article ADD COLUMN IF NOT EXISTS views INTEGER NOT NULL DEFAULT 0;
-- CREATE TABLE IF NOT EXISTS ai_usage (
--   id SERIAL PRIMARY KEY,
--   mode VARCHAR(30) NOT NULL,
--   model VARCHAR(60) NOT NULL,
--   success SMALLINT NOT NULL,
--   created_at TIMESTAMP NOT NULL
-- );
-- CREATE INDEX IF NOT EXISTS ai_usage_created ON ai_usage (created_at);
