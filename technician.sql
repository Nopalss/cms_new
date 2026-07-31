-- phpMyAdmin SQL Dump
-- version 4.9.6
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 26 Jul 2026 pada 07.05
-- Versi server: 10.5.6-MariaDB
-- Versi PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cms_database`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `technician`
--

CREATE TABLE `technician` (
  `tech_key` int(11) NOT NULL,
  `tech_id` varchar(100) NOT NULL,
  `name` varchar(200) NOT NULL,
  `phone` varchar(200) NOT NULL,
  `username` varchar(200) NOT NULL,
  `tim_id` varchar(200) DEFAULT NULL,
  `fcm_token` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `technician`
--

INSERT INTO `technician` (`tech_key`, `tech_id`, `name`, `phone`, `username`, `tim_id`) VALUES
(2, 'FJI-020119005', 'BUDI KURNIAWAN', '0823855484', 'budikurn28', NULL),
(3, 'FJI-020119007', 'BAHRUL FAUZI', '628181818111', 'bahrulfa46', 'TIM20260225115702'),
(4, 'FJI-020120009', 'MUHAMMAD RENDY RENALDI', '628181818111', 'muhammad73', 'TIM20260225115126'),
(5, 'FJI-020122010', 'ANGGORO PRASSETIO', '628979641228', 'anggorop94', 'TIM20260225115126'),
(6, 'FJI-020120011', 'AHMAD SUNANDAR', '628181818111', 'ahmadsun16', 'TIM20260225115126'),
(7, 'FJI-020120013', 'RIZKI ALFIAN', '628181818111', 'rizkialf17', 'TIM20260225115126'),
(8, 'FJI-020222017', 'JUJUN JUNAEDI', '628181818111', 'jujunjun17', 'TIM20260225115702'),
(9, 'FJI-020222015', 'RIZKI NOVIANA RAMDANI', '628181818111', 'rizkinov42', 'TIM20260225115702'),
(10, 'FJI-020422001', 'HILDAN HERDIANSYAH', '628181818111', 'hildanhe37', 'TIM20260225115702'),
(12, 'FJI-020123017', 'LILI KUSTARI', '6281381653372', 'lilikust81', NULL),
(13, 'FJI-020123018', 'MUHAMMAD RAFLI', '08138172718', 'muhammad28', NULL),
(16, 'FJI-020123019', 'UBAIDILLAH', '08181818111', 'ubaidill31', NULL),
(18, 'FJI-020124020', 'SINTA AULIA DWI KURNIA', '08181818111', 'sintaaul26', NULL),
(19, 'FJI-020125021', 'SHINTIA MARTIANA', '08181818111', 'shintiam58', NULL),
(20, 'FJI-020125022', 'DANRI NURKHOLIK', '0823855484', 'danrinur89', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `technician`
--
ALTER TABLE `technician`
  ADD PRIMARY KEY (`tech_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`tech_id`),
  ADD KEY `username` (`username`),
  ADD KEY `tim_id` (`tim_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `technician`
--
ALTER TABLE `technician`
  MODIFY `tech_key` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
