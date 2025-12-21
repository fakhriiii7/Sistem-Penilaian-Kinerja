-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 21 Des 2025 pada 15.16
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
-- Database: `sistem_penilaian_kinerja`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_penilaian`
--

CREATE TABLE `detail_penilaian` (
  `id` int(11) NOT NULL,
  `penilaian_id` int(11) NOT NULL,
  `kriteria_id` int(11) NOT NULL,
  `nilai` decimal(5,2) NOT NULL DEFAULT 0.00,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `detail_penilaian`
--

INSERT INTO `detail_penilaian` (`id`, `penilaian_id`, `kriteria_id`, `nilai`, `catatan`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 85.00, 'Sangat disiplin, jarang absen', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(2, 1, 2, 88.00, 'Kualitas kerja sangat baik', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(3, 1, 3, 82.00, 'Menyelesaikan target tepat waktu', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(4, 1, 4, 87.00, 'Bekerja sama dengan baik', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(5, 1, 5, 85.00, 'Sering memberikan ide baru', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(6, 2, 1, 92.00, 'Sangat disiplin dan tepat waktu', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(7, 2, 2, 90.00, 'Kualitas kerja excellent', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(8, 2, 3, 88.00, 'Produktivitas tinggi', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(9, 2, 4, 91.00, 'Sangat kooperatif dengan tim', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(10, 2, 5, 89.00, 'Banyak inovasi yang diberikan', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(11, 3, 1, 75.00, 'Perlu peningkatan disiplin', '2025-12-20 04:49:08', '2025-12-20 04:49:08'),
(12, 3, 2, 80.00, 'Kualitas kerja cukup baik', '2025-12-20 04:49:08', '2025-12-20 04:49:08'),
(13, 3, 3, 78.00, 'Target tercapai dengan baik', '2025-12-20 04:49:08', '2025-12-20 04:49:08'),
(14, 3, 4, 82.00, 'Bekerja sama cukup baik', '2025-12-20 04:49:08', '2025-12-20 04:49:08'),
(15, 3, 5, 75.00, 'Perlu lebih proaktif', '2025-12-20 04:49:08', '2025-12-20 04:49:08'),
(16, 4, 1, 88.00, 'Disiplin meningkat', '2025-12-20 04:49:08', '2025-12-20 04:49:08'),
(17, 4, 2, 90.00, 'Kualitas kerja sangat baik', '2025-12-20 04:49:08', '2025-12-20 04:49:08'),
(18, 4, 3, 85.00, 'Produktivitas meningkat', '2025-12-20 04:49:08', '2025-12-20 04:49:08'),
(19, 4, 4, 89.00, 'Kerja sama tim sangat baik', '2025-12-20 04:49:08', '2025-12-20 04:49:08'),
(20, 4, 5, 88.00, 'Lebih inovatif', '2025-12-20 04:49:08', '2025-12-20 04:49:08'),
(51, 6, 2, 80.00, '', '2025-12-20 06:11:43', '2025-12-20 06:11:43'),
(52, 6, 1, 60.00, '', '2025-12-20 06:11:43', '2025-12-20 06:11:43'),
(53, 6, 3, 70.00, '', '2025-12-20 06:11:43', '2025-12-20 06:11:43'),
(54, 6, 4, 80.00, '', '2025-12-20 06:11:43', '2025-12-20 06:11:43'),
(55, 6, 5, 94.00, '', '2025-12-20 06:11:43', '2025-12-20 06:11:43'),
(56, 5, 2, 92.00, 'Kualitas excellent', '2025-12-20 06:14:06', '2025-12-20 06:14:06'),
(57, 5, 1, 90.00, 'Sangat disiplin', '2025-12-20 06:14:06', '2025-12-20 06:14:06'),
(58, 5, 3, 89.00, 'Produktivitas tinggi', '2025-12-20 06:14:06', '2025-12-20 06:14:06'),
(59, 5, 4, 91.00, 'Kerja sama tim sangat baik', '2025-12-20 06:14:06', '2025-12-20 06:14:06'),
(60, 5, 5, 90.00, 'Banyak inovasi', '2025-12-20 06:14:06', '2025-12-20 06:14:06'),
(61, 7, 2, 90.00, 'Lanjutkan kerja bagusnya', '2025-12-21 12:18:54', '2025-12-21 12:18:54'),
(62, 7, 1, 80.00, 'Sudah sangat baik', '2025-12-21 12:18:54', '2025-12-21 12:18:54'),
(63, 7, 3, 70.00, 'Baik, tapi perlu ditingkatkan lagi', '2025-12-21 12:18:54', '2025-12-21 12:18:54'),
(64, 7, 4, 80.00, 'Bekerja sangat baik dalam tim', '2025-12-21 12:18:54', '2025-12-21 12:18:54'),
(65, 7, 5, 60.00, 'Masih bisa ditingkatkan lagi', '2025-12-21 12:18:54', '2025-12-21 12:18:54'),
(66, 8, 2, 90.00, 'n', '2025-12-21 12:31:03', '2025-12-21 12:31:03'),
(67, 8, 1, 75.00, 'i', '2025-12-21 12:31:03', '2025-12-21 12:31:03'),
(68, 8, 3, 77.00, 'g', '2025-12-21 12:31:03', '2025-12-21 12:31:03'),
(69, 8, 4, 88.00, 'k', '2025-12-21 12:31:03', '2025-12-21 12:31:03'),
(70, 8, 5, 70.00, 'a', '2025-12-21 12:31:03', '2025-12-21 12:31:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kriteria`
--

CREATE TABLE `kriteria` (
  `id` int(11) NOT NULL,
  `kode_kriteria` varchar(10) NOT NULL,
  `nama_kriteria` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `bobot` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `kriteria`
--

INSERT INTO `kriteria` (`id`, `kode_kriteria`, `nama_kriteria`, `deskripsi`, `bobot`, `created_at`, `updated_at`, `status`) VALUES
(1, 'K01', 'Disiplin Kerja', 'Kedisiplinan dalam waktu kerja, absensi, dan kepatuhan terhadap peraturan', 25.00, '2025-12-20 04:49:07', '2025-12-20 06:53:01', 'Aktif'),
(2, 'K02', 'Kualitas Kerja', 'Tingkat kualitas hasil kerja yang dihasilkan', 30.00, '2025-12-20 04:49:07', '2025-12-20 04:49:07', 'Aktif'),
(3, 'K03', 'Kuantitas Kerja', 'Jumlah pekerjaan yang dapat diselesaikan dalam periode tertentu', 20.00, '2025-12-20 04:49:07', '2025-12-20 04:49:07', 'Aktif'),
(4, 'K04', 'Kerja Sama Tim', 'Kemampuan bekerja sama dengan rekan kerja dan tim', 15.00, '2025-12-20 04:49:07', '2025-12-20 04:49:07', 'Aktif'),
(5, 'K05', 'Inisiatif dan Inovasi', 'Kemampuan mengambil inisiatif dan memberikan ide inovatif', 10.00, '2025-12-20 04:49:07', '2025-12-20 04:49:07', 'Aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pegawai`
--

CREATE TABLE `pegawai` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nip` varchar(50) NOT NULL,
  `nama_depan` varchar(100) DEFAULT NULL,
  `nama_belakang` varchar(100) DEFAULT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `jabatan` varchar(100) NOT NULL,
  `unit_kerja` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pegawai`
--

INSERT INTO `pegawai` (`id`, `user_id`, `nip`, `nama_depan`, `nama_belakang`, `nama_lengkap`, `jabatan`, `unit_kerja`, `email`, `no_telp`, `created_at`, `updated_at`) VALUES
(1, 4, '19600101198001001', 'Budi', 'Santoso', 'Budi Santoso', 'Staff IT', 'Bidang Teknologi Informasi', 'budi.santoso@example.com', '081234567890', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(2, 5, '19600202198002002', 'Sari', 'Indah', 'Sari Indah', 'Staff Administrasi', 'Bidang Administrasi', 'sari.indah@example.com', '081234567891', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(3, 6, '19600303198003003', 'Doni', 'Prasetyo', 'Doni Prasetyo', 'Staff Keuangan', 'Bidang Keuangan', 'doni.prasetyo@example.com', '081234567892', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(4, 7, '19600404198004004', 'Lina', 'Wati', 'Lina Wati', 'Staff HRD', 'Bidang Sumber Daya Manusia', 'lina.wati@example.com', '081234567893', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(5, NULL, '19600505198005005', 'Rudi', 'Hartono', 'Rudi Hartono', 'Staff Marketing', 'Bidang Pemasaran', 'rudi.hartono@example.com', '081234567894', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(6, NULL, '19600606198006006', 'Maya', 'Sari', 'Maya Sari', 'Staff Produksi', 'Bidang Produksi', 'maya.sari@example.com', '081234567895', '2025-12-20 04:49:07', '2025-12-20 04:49:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penilai`
--

CREATE TABLE `penilai` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_penilai` varchar(255) NOT NULL,
  `jabatan` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `penilai`
--

INSERT INTO `penilai` (`id`, `user_id`, `nama_penilai`, `jabatan`, `created_at`, `updated_at`) VALUES
(1, 2, 'Dr. Ahmad Wijaya, S.Kom., M.Kom', 'Kepala Bidang Teknologi Informasi', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(2, 3, 'Drs. Siti Nurhaliza, M.M', 'Kepala Bidang Administrasi', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(3, 11, 'Dr. Seranda S.Kom, M. Kom', 'Investor Utama', '2025-12-20 06:55:09', '2025-12-20 06:55:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penilaian`
--

CREATE TABLE `penilaian` (
  `id` int(11) NOT NULL,
  `periode_id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `penilai_id` int(11) NOT NULL,
  `tanggal_penilaian` date NOT NULL,
  `status` enum('draft','selesai') NOT NULL DEFAULT 'draft',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `penilaian`
--

INSERT INTO `penilaian` (`id`, `periode_id`, `pegawai_id`, `penilai_id`, `tanggal_penilaian`, `status`, `catatan`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '2024-06-25', 'selesai', NULL, '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(2, 1, 2, 2, '2024-06-26', 'selesai', NULL, '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(3, 1, 3, 1, '2024-06-27', 'selesai', NULL, '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(4, 2, 1, 1, '2024-12-15', 'selesai', NULL, '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(5, 2, 2, 2, '2024-12-16', 'selesai', NULL, '2025-12-20 04:49:07', '2025-12-20 06:14:06'),
(6, 2, 4, 1, '2025-12-20', 'selesai', NULL, '2025-12-20 05:09:36', '2025-12-20 06:11:43'),
(7, 4, 1, 1, '2025-12-21', 'selesai', NULL, '2025-12-21 12:16:29', '2025-12-21 12:16:29'),
(8, 4, 3, 1, '2025-12-21', 'selesai', NULL, '2025-12-21 12:27:51', '2025-12-21 12:27:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `periode_penilaian`
--

CREATE TABLE `periode_penilaian` (
  `id` int(11) NOT NULL,
  `nama_periode` varchar(100) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `status` enum('aktif','selesai','draft') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `periode_penilaian`
--

INSERT INTO `periode_penilaian` (`id`, `nama_periode`, `tanggal_mulai`, `tanggal_selesai`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Penilaian Kinerja Semester 1 Tahun 2024', '2024-01-01', '2024-06-30', '', '2025-12-20 04:49:07', '2025-12-20 06:33:17'),
(2, 'Penilaian Kinerja Semester 2 Tahun 2024', '2024-07-01', '2024-12-31', '', '2025-12-20 04:49:07', '2025-12-21 04:03:01'),
(3, 'Penilaian Kinerja Semester 1 Tahun 2025', '2025-01-01', '2025-06-30', '', '2025-12-20 04:49:07', '2025-12-20 06:33:55'),
(4, 'Penilaian Kinerja Semester 2 Tahun 2025 ', '2025-07-01', '2025-12-31', 'aktif', '2025-12-20 06:33:55', '2025-12-21 04:03:01');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pimpinan','pegawai') NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `nama_lengkap`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$7Nz4M8W6VFC887tVOi7/8eEXSM866jpIrWR1rk2We9jQ/oQaWxWvW', 'admin', 'Administrator', '2025-12-20 04:49:07', '2025-12-21 12:13:00'),
(2, 'pimpinan1', '$2y$10$em3lvOCs5nJC1jNi4Ril9.tNx4qLN3jBot2/hJUVpScxZwPxoc23G', 'pimpinan', 'Dr. Ahmad Wijaya, S.Kom., M.Kom', '2025-12-20 04:49:07', '2025-12-21 12:14:02'),
(3, 'pimpinan2', '$2y$10$em3lvOCs5nJC1jNi4Ril9.tNx4qLN3jBot2/hJUVpScxZwPxoc23G', 'pimpinan', 'Drs. Siti Nurhaliza, M.M', '2025-12-20 04:49:07', '2025-12-21 12:14:18'),
(4, 'budi', '$2y$10$kUHveSLnjxkFZvCYqt3lEe1TX72L6cIIVVk11V5iV1yNq.bkc/UCW', 'pegawai', 'Budi Santoso', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(5, 'sari', '$2y$10$XKUXM81HOdiKP6HJvlZX/.E9KW.mxxfS5wWZ/CHwZgeye7f4YCPli', 'pegawai', 'Sari Indah', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(6, 'doni', '$2y$10$83zKM.LFcrNN5G6KN29LruurZr6sG3h9.27Rk61MTNs.6CFmCcqEO', 'pegawai', 'Doni Prasetyo', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(7, 'lina', '$2y$10$EHPwBxjzUz0pkpNJrKugY.i6dsXtEv0kPxsVoVw4ZmHLlsjjCzV7G', 'pegawai', 'Lina Wati', '2025-12-20 04:49:07', '2025-12-20 04:49:07'),
(8, 'budi1', '$2y$10$jdggNxOUeahBQZKtA/jc7uqSDpfwzBU8V1J7MOTPVAJk7n4tq/0.u', 'pegawai', 'budi doremi', '2025-12-20 06:35:01', '2025-12-20 06:35:01'),
(9, 'budi2', '$2y$10$wmvfXT/CsQdz.bRuIIyBMup4hTG7NYKUmlApW71LzzI6tx4ZNdL9i', 'pegawai', 'Budi Doremi', '2025-12-20 06:41:01', '2025-12-20 06:41:01'),
(10, 'laras', '$2y$10$whdiPeVW4HlKAkpOK10ldeloJVIUrXQVp/FTINhZJP74yan98E.FW', 'pegawai', 'Laras Satika', '2025-12-20 06:42:36', '2025-12-20 06:42:36'),
(11, 'investor1', '$2y$10$ll.GMKSeeejijge9i90iIeEtoSF0Z6dzOKpu/rGgMHVDR6KK3ExDy', 'pimpinan', 'Dr. Seranda S.Kom, M. Kom', '2025-12-20 06:55:09', '2025-12-21 04:29:50');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_penilaian`
--
ALTER TABLE `detail_penilaian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_penilaian_kriteria` (`penilaian_id`,`kriteria_id`),
  ADD KEY `idx_kriteria` (`kriteria_id`);

--
-- Indeks untuk tabel `kriteria`
--
ALTER TABLE `kriteria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_kriteria` (`kode_kriteria`),
  ADD KEY `idx_bobot` (`bobot`);

--
-- Indeks untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_nama_depan` (`nama_depan`);

--
-- Indeks untuk tabel `penilai`
--
ALTER TABLE `penilai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indeks untuk tabel `penilaian`
--
ALTER TABLE `penilaian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_periode` (`periode_id`),
  ADD KEY `idx_pegawai` (`pegawai_id`),
  ADD KEY `idx_penilai` (`penilai_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tanggal` (`tanggal_penilaian`);

--
-- Indeks untuk tabel `periode_penilaian`
--
ALTER TABLE `periode_penilaian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tanggal` (`tanggal_mulai`,`tanggal_selesai`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_penilaian`
--
ALTER TABLE `detail_penilaian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT untuk tabel `kriteria`
--
ALTER TABLE `kriteria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `penilai`
--
ALTER TABLE `penilai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `penilaian`
--
ALTER TABLE `penilaian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `periode_penilaian`
--
ALTER TABLE `periode_penilaian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_penilaian`
--
ALTER TABLE `detail_penilaian`
  ADD CONSTRAINT `fk_detail_kriteria` FOREIGN KEY (`kriteria_id`) REFERENCES `kriteria` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_penilaian` FOREIGN KEY (`penilaian_id`) REFERENCES `penilaian` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  ADD CONSTRAINT `fk_pegawai_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `penilai`
--
ALTER TABLE `penilai`
  ADD CONSTRAINT `fk_penilai_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `penilaian`
--
ALTER TABLE `penilaian`
  ADD CONSTRAINT `fk_penilaian_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_penilaian_penilai` FOREIGN KEY (`penilai_id`) REFERENCES `penilai` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_penilaian_periode` FOREIGN KEY (`periode_id`) REFERENCES `periode_penilaian` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
