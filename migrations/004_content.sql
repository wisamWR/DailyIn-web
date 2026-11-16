-- Fase 3: slug URL, status draft/publish, jadwal terbit, dan tag.
-- Jalankan sekali di database yang SUDAH ada (backup dulu).
-- Slug untuk artikel lama diisi otomatis saat admin membuka dashboard.

-- ===================== MySQL / MariaDB (phpMyAdmin) =====================
ALTER TABLE `article`
  ADD COLUMN `slug` varchar(200) DEFAULT NULL AFTER `judul`,
  ADD COLUMN `status` varchar(10) NOT NULL DEFAULT 'published' AFTER `username`,
  ADD COLUMN `published_at` datetime DEFAULT NULL AFTER `status`,
  ADD UNIQUE KEY `article_slug` (`slug`);

UPDATE `article` SET `published_at` = `tanggal` WHERE `published_at` IS NULL;

CREATE TABLE IF NOT EXISTS `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(60) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `article_tag` (
  `article_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`article_id`, `tag_id`),
  KEY `article_tag_tag` (`tag_id`),
  CONSTRAINT `article_tag_article_fk` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`) ON DELETE CASCADE,
  CONSTRAINT `article_tag_tag_fk` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================== PostgreSQL / Neon =====================
-- (jalankan bagian ini saja di Neon, tanpa bagian MySQL di atas)
-- ALTER TABLE article ADD COLUMN IF NOT EXISTS slug VARCHAR(200) UNIQUE;
-- ALTER TABLE article ADD COLUMN IF NOT EXISTS status VARCHAR(10) NOT NULL DEFAULT 'published';
-- ALTER TABLE article ADD COLUMN IF NOT EXISTS published_at TIMESTAMP;
-- UPDATE article SET published_at = tanggal WHERE published_at IS NULL;
-- CREATE TABLE IF NOT EXISTS tag (id SERIAL PRIMARY KEY, name VARCHAR(50) NOT NULL, slug VARCHAR(60) NOT NULL UNIQUE);
-- CREATE TABLE IF NOT EXISTS article_tag (
--   article_id INTEGER NOT NULL REFERENCES article(id) ON DELETE CASCADE,
--   tag_id INTEGER NOT NULL REFERENCES tag(id) ON DELETE CASCADE,
--   PRIMARY KEY (article_id, tag_id)
-- );
-- CREATE INDEX IF NOT EXISTS article_tag_tag ON article_tag (tag_id);
