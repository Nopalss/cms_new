-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 29, 2026 at 03:21 AM
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
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_key` int NOT NULL,
  `admin_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `jabatan` varchar(20) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `netpay_key` int NOT NULL,
  `netpay_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `perumahan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_contact` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `paket_internet` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sharelock` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `harga` int DEFAULT NULL,
  `ip` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_details`
--

CREATE TABLE `customer_details` (
  `detail_id` int NOT NULL,
  `netpay_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nik` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gender` enum('male','female') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birth_place` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `occupation` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `marital_status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ktp_url` text COLLATE utf8mb4_general_ci,
  `kk_url` text COLLATE utf8mb4_general_ci,
  `package_external_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `package_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `area_external_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `area_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pop_external_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pop_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `monthly_bill` decimal(12,2) DEFAULT NULL,
  `ikr_fee` decimal(12,2) DEFAULT NULL,
  `due_day` tinyint DEFAULT NULL,
  `vlan_id` int DEFAULT NULL,
  `device_brand` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modem_sn` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `inst_street` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `inst_rt` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `inst_rw` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `inst_village` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `inst_district` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `inst_city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `inst_province` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `inst_zip` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `same_with_installation` tinyint(1) DEFAULT '0',
  `ktp_street` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ktp_rt` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ktp_rw` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ktp_village` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ktp_district` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ktp_city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ktp_province` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ktp_zip` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `installed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detail_ratings`
--

CREATE TABLE `detail_ratings` (
  `dr_id` int NOT NULL,
  `tech_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `rating_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dismantle_reports`
--

CREATE TABLE `dismantle_reports` (
  `dismantle_key` int NOT NULL,
  `dismantle_id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'AUTO_INCREMENT',
  `schedule_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `netpay_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `tanggal` date NOT NULL,
  `jam` time NOT NULL,
  `alasan` text COLLATE utf8mb4_general_ci NOT NULL,
  `action` text COLLATE utf8mb4_general_ci NOT NULL,
  `part_removed` text COLLATE utf8mb4_general_ci,
  `kondisi_perangkat` text COLLATE utf8mb4_general_ci,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dismantle_report_pic`
--

CREATE TABLE `dismantle_report_pic` (
  `drp_id` int NOT NULL,
  `dismantle_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `tech_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ikr_report`
--

CREATE TABLE `ikr_report` (
  `ikr_key` int NOT NULL,
  `ikr_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `netpay_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_general_ci,
  `rt` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rw` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `desa` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kec` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kab` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sn` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type_ont` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `redaman` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `odp_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `odc_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jc_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mac_sebelum` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mac_sesudah` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `odp` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `odc` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `enclosure` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `schedule_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ikr_report_pic`
--

CREATE TABLE `ikr_report_pic` (
  `irp_id` int NOT NULL,
  `ikr_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `tech_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `issues_report`
--

CREATE TABLE `issues_report` (
  `issue_key` int NOT NULL,
  `issue_id` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `schedule_id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `reported_by` varchar(200) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `issue_type` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT (now()),
  `status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_general_ci DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ont_inventory`
--

CREATE TABLE `ont_inventory` (
  `ont_key` int NOT NULL,
  `ont_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `serial_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type_ont` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `brand` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mac_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('IN_STOCK','IN_USE','DAMAGED','LOST','REPAIR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'IN_STOCK',
  `current_netpay_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `condition_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ont_movement_log`
--

CREATE TABLE `ont_movement_log` (
  `movement_key` int NOT NULL,
  `movement_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ont_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `netpay_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `event_type` enum('INSTALL','SWAP_OUT','SWAP_IN','DISMANTLE','RETURN_TO_STOCK','REPAIR_OUT','REPAIR_IN','LOST') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ref_table` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ref_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `event_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `tech_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `queue_scheduling`
--

CREATE TABLE `queue_scheduling` (
  `queue_key` int NOT NULL,
  `netpay_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `queue_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type_queue` enum('Install','Maintenance','Dismantle','Service') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Accepted','Rejected','Pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `register`
--

CREATE TABLE `register` (
  `registrasi_key` int NOT NULL,
  `registrasi_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `perumahan` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `paket_internet` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_verified` enum('Verified','Unverified') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Unverified',
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_dismantle`
--

CREATE TABLE `request_dismantle` (
  `rd_key` int NOT NULL,
  `rd_id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `queue_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type_dismantle` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi_dismantle` text COLLATE utf8mb4_general_ci,
  `request_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_ikr`
--

CREATE TABLE `request_ikr` (
  `rikr_key` int NOT NULL,
  `rikr_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `registrasi_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `catatan` text COLLATE utf8mb4_general_ci,
  `queue_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_maintenance`
--

CREATE TABLE `request_maintenance` (
  `rm_key` int NOT NULL,
  `rm_id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `queue_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `server` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type_issue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi_issue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `verifikasi_noc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `request_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_key` int NOT NULL,
  `schedule_id` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tech_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tim_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date` date NOT NULL DEFAULT (0),
  `time` time NOT NULL DEFAULT (0),
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `job_type` enum('Instalasi','Maintenance','Dismantle','Service') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `target_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'On Time',
  `status` enum('Pending','Actived','Rescheduled','Cancelled','Done') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pending',
  `reason` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `queue_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `noc_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_reports`
--

CREATE TABLE `service_reports` (
  `srv_key` int NOT NULL,
  `srv_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date NOT NULL,
  `jam` time NOT NULL DEFAULT '00:00:00',
  `netpay_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `problem` text COLLATE utf8mb4_general_ci NOT NULL,
  `action` text COLLATE utf8mb4_general_ci NOT NULL,
  `part` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ont_bef` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `ont_aft` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `red_bef` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `red_aft` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `schedule_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_report_pic`
--

CREATE TABLE `service_report_pic` (
  `srp_id` int NOT NULL,
  `srv_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `tech_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sync_log`
--

CREATE TABLE `sync_log` (
  `id` int NOT NULL,
  `sync_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `batch_no` int DEFAULT NULL,
  `inserted` int DEFAULT NULL,
  `updated` int DEFAULT NULL,
  `failed` int DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `duration_second` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` mediumtext COLLATE utf8mb4_general_ci,
  `total_success_batch` int DEFAULT '0',
  `total_failed_batch` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sync_progress`
--

CREATE TABLE `sync_progress` (
  `id` int NOT NULL,
  `sync_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_netpay_id` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('idle','running','completed','failed') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `technician`
--

CREATE TABLE `technician` (
  `tech_key` int NOT NULL,
  `tech_id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `tim_id` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fcm_token` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `technician_ratings`
--

CREATE TABLE `technician_ratings` (
  `rating_key` int NOT NULL,
  `rating_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `schedule_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `netpay_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rating` tinyint DEFAULT NULL,
  `komentar` text COLLATE utf8mb4_general_ci,
  `is_sent` enum('Y','N') COLLATE utf8mb4_general_ci DEFAULT 'N',
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Pending','Rated') COLLATE utf8mb4_general_ci DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tim`
--

CREATE TABLE `tim` (
  `id` int NOT NULL,
  `tim_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama` varchar(150) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `type`
--

CREATE TABLE `type` (
  `id` int NOT NULL,
  `catatan` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('rm','rd','issue') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','teknisi') COLLATE utf8mb4_general_ci NOT NULL,
  `avatar` varchar(200) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`admin_id`) USING BTREE,
  ADD KEY `username` (`username`);

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`netpay_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`netpay_id`) USING BTREE;

--
-- Indexes for table `customer_details`
--
ALTER TABLE `customer_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD UNIQUE KEY `uk_customer_details_netpay_id` (`netpay_id`);

--
-- Indexes for table `detail_ratings`
--
ALTER TABLE `detail_ratings`
  ADD PRIMARY KEY (`dr_id`),
  ADD KEY `idx_tech_id` (`tech_id`),
  ADD KEY `idx_rating_id` (`rating_id`);

--
-- Indexes for table `dismantle_reports`
--
ALTER TABLE `dismantle_reports`
  ADD PRIMARY KEY (`dismantle_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`dismantle_id`),
  ADD KEY `schedule_key` (`schedule_id`,`netpay_id`);

--
-- Indexes for table `dismantle_report_pic`
--
ALTER TABLE `dismantle_report_pic`
  ADD PRIMARY KEY (`drp_id`),
  ADD KEY `idx_dismantle_id` (`dismantle_id`),
  ADD KEY `idx_tech_id` (`tech_id`);

--
-- Indexes for table `ikr_report`
--
ALTER TABLE `ikr_report`
  ADD PRIMARY KEY (`ikr_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`ikr_id`) USING BTREE,
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `netpay_id_2` (`netpay_id`);

--
-- Indexes for table `ikr_report_pic`
--
ALTER TABLE `ikr_report_pic`
  ADD PRIMARY KEY (`irp_id`),
  ADD KEY `idx_ikr_id` (`ikr_id`),
  ADD KEY `idx_tech_id` (`tech_id`);

--
-- Indexes for table `issues_report`
--
ALTER TABLE `issues_report`
  ADD PRIMARY KEY (`issue_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`issue_id`),
  ADD KEY `schedule_id` (`schedule_id`,`reported_by`);

--
-- Indexes for table `ont_inventory`
--
ALTER TABLE `ont_inventory`
  ADD PRIMARY KEY (`ont_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`ont_id`),
  ADD UNIQUE KEY `uk_serial_number` (`serial_number`),
  ADD KEY `idx_current_netpay_id` (`current_netpay_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `ont_movement_log`
--
ALTER TABLE `ont_movement_log`
  ADD PRIMARY KEY (`movement_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`movement_id`),
  ADD KEY `idx_ont_id` (`ont_id`),
  ADD KEY `idx_netpay_id` (`netpay_id`),
  ADD KEY `idx_ref` (`ref_table`,`ref_id`),
  ADD KEY `idx_event_type` (`event_type`);

--
-- Indexes for table `paket_internet`
--
ALTER TABLE `paket_internet`
  ADD PRIMARY KEY (`paket_id`);

--
-- Indexes for table `queue_scheduling`
--
ALTER TABLE `queue_scheduling`
  ADD PRIMARY KEY (`queue_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`queue_id`),
  ADD KEY `netpay_id` (`netpay_id`);

--
-- Indexes for table `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`registrasi_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`registrasi_id`);

--
-- Indexes for table `request_dismantle`
--
ALTER TABLE `request_dismantle`
  ADD PRIMARY KEY (`rd_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`rd_id`) USING BTREE,
  ADD KEY `netpay_key` (`queue_id`);

--
-- Indexes for table `request_ikr`
--
ALTER TABLE `request_ikr`
  ADD PRIMARY KEY (`rikr_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`rikr_id`),
  ADD KEY `queue_key` (`queue_id`);

--
-- Indexes for table `request_maintenance`
--
ALTER TABLE `request_maintenance`
  ADD PRIMARY KEY (`rm_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`rm_id`),
  ADD KEY `netpay_key` (`queue_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`schedule_id`),
  ADD KEY `tech_id` (`tech_id`,`queue_id`),
  ADD KEY `date` (`date`,`job_type`,`status`),
  ADD KEY `tim_id` (`tim_id`),
  ADD KEY `noc_id` (`noc_id`);

--
-- Indexes for table `service_reports`
--
ALTER TABLE `service_reports`
  ADD PRIMARY KEY (`srv_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`srv_id`),
  ADD KEY `netpay_key` (`schedule_id`),
  ADD KEY `netpay_id` (`netpay_id`);

--
-- Indexes for table `service_report_pic`
--
ALTER TABLE `service_report_pic`
  ADD PRIMARY KEY (`srp_id`),
  ADD KEY `idx_srv_id` (`srv_id`),
  ADD KEY `idx_tech_id` (`tech_id`);

--
-- Indexes for table `sync_log`
--
ALTER TABLE `sync_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sync_progress`
--
ALTER TABLE `sync_progress`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `technician`
--
ALTER TABLE `technician`
  ADD PRIMARY KEY (`tech_key`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`tech_id`),
  ADD KEY `username` (`username`),
  ADD KEY `tim_id` (`tim_id`);

--
-- Indexes for table `technician_ratings`
--
ALTER TABLE `technician_ratings`
  ADD PRIMARY KEY (`rating_key`);

--
-- Indexes for table `tim`
--
ALTER TABLE `tim`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `type`
--
ALTER TABLE `type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `UNIQUE KEY` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `netpay_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_details`
--
ALTER TABLE `customer_details`
  MODIFY `detail_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detail_ratings`
--
ALTER TABLE `detail_ratings`
  MODIFY `dr_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dismantle_reports`
--
ALTER TABLE `dismantle_reports`
  MODIFY `dismantle_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dismantle_report_pic`
--
ALTER TABLE `dismantle_report_pic`
  MODIFY `drp_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ikr_report`
--
ALTER TABLE `ikr_report`
  MODIFY `ikr_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ikr_report_pic`
--
ALTER TABLE `ikr_report_pic`
  MODIFY `irp_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `issues_report`
--
ALTER TABLE `issues_report`
  MODIFY `issue_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ont_inventory`
--
ALTER TABLE `ont_inventory`
  MODIFY `ont_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ont_movement_log`
--
ALTER TABLE `ont_movement_log`
  MODIFY `movement_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paket_internet`
--
ALTER TABLE `paket_internet`
  MODIFY `paket_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `queue_scheduling`
--
ALTER TABLE `queue_scheduling`
  MODIFY `queue_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `registrasi_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_dismantle`
--
ALTER TABLE `request_dismantle`
  MODIFY `rd_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_ikr`
--
ALTER TABLE `request_ikr`
  MODIFY `rikr_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_maintenance`
--
ALTER TABLE `request_maintenance`
  MODIFY `rm_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_reports`
--
ALTER TABLE `service_reports`
  MODIFY `srv_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_report_pic`
--
ALTER TABLE `service_report_pic`
  MODIFY `srp_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sync_log`
--
ALTER TABLE `sync_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sync_progress`
--
ALTER TABLE `sync_progress`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `technician`
--
ALTER TABLE `technician`
  MODIFY `tech_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `technician_ratings`
--
ALTER TABLE `technician_ratings`
  MODIFY `rating_key` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tim`
--
ALTER TABLE `tim`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `type`
--
ALTER TABLE `type`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customer_details`
--
ALTER TABLE `customer_details`
  ADD CONSTRAINT `fk_customer_details_customer` FOREIGN KEY (`netpay_id`) REFERENCES `customers` (`netpay_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
