-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 31, 2025 at 11:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `webdailyjournal`
--

-- --------------------------------------------------------

--
-- Table structure for table `article`
--

CREATE TABLE `article` (
  `id` int(11) NOT NULL,
  `judul` text DEFAULT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `isi` mediumtext DEFAULT NULL,
  `gambar` text DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `ai_summary` text DEFAULT NULL,
  `tanggal` datetime DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'published',
  `published_at` datetime DEFAULT NULL,
  `mood` varchar(20) DEFAULT NULL,
  `views` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article`
--

INSERT INTO `article` (`id`, `judul`, `slug`, `isi`, `gambar`, `tanggal`, `username`, `status`, `published_at`) VALUES
(1, 'Perpustakaan Kampus', 'perpustakaan-kampus', NULL, 'book.jpg', '2025-12-10 13:30:06', 'admin', 'published', '2025-12-10 13:30:06'),
(2, 'Ruang Kelas', 'ruang-kelas', NULL, 'class.jpg', '2025-12-10 13:34:30', 'admin', 'published', '2025-12-10 13:34:30'),
(3, 'Pemandangan', 'pemandangan', NULL, 'alaska.jpg', '2025-12-10 13:36:01', 'admin', 'published', '2025-12-10 13:36:01'),
(4, 'ide', 'ide', NULL, 'ideas.jpg', '2025-12-10 13:36:01', 'admin', 'published', '2025-12-10 13:36:01'),
(5, 'Coding', 'coding', 'kumpulan project dan pelatihan', 'coding.jpg', '2025-12-15 20:50:34', 'admin', 'published', '2025-12-15 20:50:34'),
(6, 'Holiday Place', 'holiday-place', NULL, 'sf.jpg', '2025-12-10 13:36:01', 'admin', 'published', '2025-12-10 13:36:01'),
(7, 'Memory', 'memory', 'Kenangan yang akan terus di ingat sepanjang masa!!!', '20251215204343.jpg', '2025-12-15 20:43:43', 'admin', 'published', '2025-12-15 20:43:43');

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `judul` text DEFAULT NULL,
  `tanggal` datetime NOT NULL,
  `gambar` varchar(255) NOT NULL,
  `username` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `judul`, `tanggal`, `gambar`, `username`) VALUES
(1, 'Holiday Place', '2025-12-31 17:26:42', 'sf.jpg', 'admin'),
(2, 'Ruang Kelas', '2025-12-31 17:26:42', 'class.jpg', 'admin'),
(3, 'Perpustakaan Kampus', '2025-12-31 17:26:42', 'book.jpg', 'admin'),
(4, 'Pemandangan', '2025-12-31 17:26:42', 'banner.jpg', 'admin'),
(5, 'Alaska', '2025-12-31 17:26:42', 'alaska.jpg', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` text NOT NULL,
  `foto` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `foto`) VALUES
(1, 'admin', '$2y$12$vM1ESMjsvcWeeB/HL/hUceAFpPghH2K9fGPbPKysDzSDzazxNcpGq', '');

--
-- Table structure for table `login_attempts` (batas percobaan login)
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) NOT NULL,
  `username` varchar(50) NOT NULL,
  `attempted_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login_attempts_lookup` (`ip`,`username`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `article`
--
ALTER TABLE `article`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `article_slug` (`slug`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `article`
--
ALTER TABLE `article`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
-- --------------------------------------------------------

--
-- Tag artikel
--

CREATE TABLE `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(60) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `article_tag` (
  `article_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`article_id`, `tag_id`),
  KEY `article_tag_tag` (`tag_id`),
  CONSTRAINT `article_tag_article_fk` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`) ON DELETE CASCADE,
  CONSTRAINT `article_tag_tag_fk` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tag` (`id`, `name`, `slug`) VALUES
(1, 'Kampus', 'kampus'),
(2, 'Coding', 'coding'),
(3, 'Liburan', 'liburan');

CREATE TABLE `ai_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mode` varchar(30) NOT NULL,
  `model` varchar(60) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_usage_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `article_tag` (`article_id`, `tag_id`) VALUES (1, 1), (2, 1), (5, 2), (3, 3), (6, 3);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
