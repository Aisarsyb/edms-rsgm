-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 02 Sep 2026 pada 23.06
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `edms_rsgm`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `documents`
--

CREATE TABLE `documents` (
  `id` bigint(20) NOT NULL,
  `employee_id` bigint(20) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `document_versions`
--

CREATE TABLE `document_versions` (
  `id` bigint(20) NOT NULL,
  `document_id` bigint(20) NOT NULL,
  `version_number` int(11) NOT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `expired_date` date DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` bigint(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) NOT NULL,
  `nip` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `gelar` varchar(100) DEFAULT NULL,
  `employee_type` varchar(100) NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status_kepegawaian` varchar(100) NOT NULL DEFAULT 'PNS',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `employees`
--

INSERT INTO `employees` (`id`, `nip`, `name`, `gelar`, `employee_type`, `deleted_at`, `created_at`, `updated_at`, `status_kepegawaian`, `is_active`) VALUES
(1, '198001012010121001', 'Fajarin Nova', 'drg., Sp.KG.', 'Dokter Gigi Spesialis', NULL, '2026-07-17 10:36:38', '2026-07-17 10:36:38', 'PNS', 1),
(2, '198001012010121002', 'Diah Savitri E.', 'Prof. Dr., drg., M.Si., Sp.PM(K)', 'Dokter Gigi Spesialis', NULL, '2026-07-17 10:36:38', '2026-07-17 10:36:38', 'PNS', 1),
(3, '198001012010121003', 'Adiastuti Endah P', 'drg., M.Kes., Sp.PM', 'Dokter Gigi Spesialis', NULL, '2026-07-17 10:36:38', '2026-07-17 10:36:38', 'P3K', 1),
(4, '198001012010121004', 'Desiana Radithia', 'Dr., drg., Sp.PM(K)', 'Dokter Gigi Spesialis', NULL, '2026-07-17 10:36:38', '2026-07-17 10:36:38', 'Pegawai Tetap (PT)', 1),
(5, '198001012010121005', 'Nurina Febriyanti A', 'drg., M.Kes., Ph.D., Sp.PM', 'Dokter Gigi', NULL, '2026-07-17 10:36:38', '2026-07-17 10:36:38', 'Kontrak / Honorer', 1),
(6, '198001012010121006', 'Reiska Kumala Bakti', 'drg., M.Ked.Trop', 'Dokter Gigi', NULL, '2026-07-17 10:36:38', '2026-07-17 10:36:38', 'Kontrak / Honorer', 1),
(7, '198001012010121007', 'Fatma Yasmin Mahdani', 'drg., M.Kes.', 'Dokter Gigi', NULL, '2026-07-17 10:36:38', '2026-07-17 10:36:38', 'P3K', 1),
(8, '198001012010121008', 'Meircurius Dwi Condro Surboyo', 'drg., Sp.PM', 'Dokter Gigi Spesialis', NULL, '2026-07-17 10:36:38', '2026-07-17 10:36:38', 'Pegawai Tetap (PT)', 1),
(9, '198001012010121009', 'Adioro Soetojo', 'Prof. Dr., drg., MS., Sp.KG(K)', 'Dokter Gigi Spesialis', NULL, '2026-07-17 10:36:38', '2026-07-17 10:36:38', 'PNS', 1),
(10, '198001012010121010', 'Ira Widjiastuti', 'Dr., drg., M.Kes., SpKG(K)', 'Dokter Gigi Spesialis', NULL, '2026-07-17 10:36:38', '2026-07-17 10:36:38', 'PNS', 1),
(11, '198001012010121011', 'Tamara Yuanita', 'Prof. Dr., drg., MS., Sp.KG(K)', 'Dokter Gigi Spesialis', NULL, '2026-07-17 10:36:38', '2026-07-17 10:36:38', 'PNS', 1),
(12, '198001012010121012', 'Ari Subiyanto', 'drg., M.Kes., Sp.KG(K)', 'Dokter Gigi Spesialis', NULL, '2026-07-17 10:36:38', '2026-07-17 10:36:38', 'P3K', 1),
(13, '198001012010121013', 'Kun Ismiyatin', 'Prof. Dr., drg., M.Kes., Sp.KG(K)', 'Dokter Gigi Spesialis', NULL, '2026-07-17 10:36:38', '2026-07-17 10:36:38', 'Pegawai Tetap (PT)', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `created_at`, `updated_at`) VALUES
(1, 'Muhammad Rizal Ramdhani, S.Kom', 'admin', '$2y$10$5kHdrcmCHIvXps.FctIBHOGPfO0TVfoim9T/itEpfCG1VgjlTDqrG', '2026-07-04 10:36:57', '2026-07-04 10:40:30');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_deleted_at` (`deleted_at`),
  ADD KEY `idx_employee_id` (`employee_id`);

--
-- Indeks untuk tabel `document_versions`
--
ALTER TABLE `document_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_document_id` (`document_id`),
  ADD KEY `idx_expired_date` (`expired_date`);

--
-- Indeks untuk tabel `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD KEY `idx_deleted_at` (`deleted_at`),
  ADD KEY `idx_nip` (`nip`),
  ADD KEY `idx_name` (`name`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `documents`
--
ALTER TABLE `documents`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `document_versions`
--
ALTER TABLE `document_versions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `document_versions`
--
ALTER TABLE `document_versions`
  ADD CONSTRAINT `document_versions_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_versions_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
