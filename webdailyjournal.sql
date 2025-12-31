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
  `judul` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `isi` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `gambar` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `tanggal` datetime DEFAULT NULL,
  `username` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article`
--

INSERT INTO `article` (`id`, `judul`, `isi`, `gambar`, `tanggal`, `username`) VALUES
(1, 'Perpustakaan Kampus', NULL, 'book.jpg', '2025-12-10 13:30:06', 'admin'),
(2, 'Ruang Kelas', NULL, 'class.jpg', '2025-12-10 13:34:30', 'admin'),
(3, 'Pemandangan', NULL, 'place.jpg', '2025-12-10 13:36:01', 'admin'),
(4, 'ide', NULL, 'ideas.jpg', '2025-12-10 13:36:01', 'admin'),
(5, 'Coding', 'kumpulan project dan pelatihan', 'coding.jpg', '2025-12-15 20:50:34', 'admin'),
(6, 'Holiday Place', NULL, 'sf.jpg', '2025-12-10 13:36:01', 'admin'),
(7, 'Memory', 'Kenangan yang akan terus di ingat sepanjang masa!!!', '20251215204343.jpg', '2025-12-15 20:43:43', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `judul` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `tanggal` datetime NOT NULL,
  `gambar` varchar(255) NOT NULL,
  `username` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `judul`, `tanggal`, `gambar`, `username`) VALUES
(1, 'Holiday Place', '2025-12-31 17:26:42', 'sf.jpg', 'admin'),
(2, 'Ruang Kelas', '2025-12-31 17:26:42', 'class.jpg', 'admin'),
(3, 'Perpustakaan Kampus', '2025-12-31 17:26:42', 'book.jpg', 'admin'),
(4, 'Pemandangan', '2025-12-31 17:26:42', 'place.jpg', 'admin'),
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
(1, 'admin', 'e10adc3949ba59abbe56e057f20f883e', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `article`
--
ALTER TABLE `article`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
