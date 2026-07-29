-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 28, 2026 at 08:22 PM
-- Server version: 8.0.30
-- PHP Version: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
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
-- Table structure for table `paket_internet`
--

CREATE TABLE `paket_internet` (
  `paket_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `paket` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `harga` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paket_internet`
--

INSERT INTO `paket_internet` (`paket_id`, `name`, `paket`, `harga`) VALUES
(1, '10 Mbps UNLIMITED (1-4 Gadget)', '10', 250000),
(2, '20 Mbps UNLIMITED (5-8 Gadget)', '20', 500000),
(3, '30 Mbps UNLIMITED (9-12 Gadget)', '30', 650000),
(4, '50 Mbps UNLIMITED', '50', 850000),
(5, '100 Mbps UNLIMITED', '100', 1250000);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `paket_internet`
--
ALTER TABLE `paket_internet`
  ADD PRIMARY KEY (`paket_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `paket_internet`
--
ALTER TABLE `paket_internet`
  MODIFY `paket_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
