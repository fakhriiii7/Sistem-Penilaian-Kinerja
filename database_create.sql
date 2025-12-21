-- =====================================================
-- SCRIPT PEMBUATAN DATABASE SISTEM PENILAIAN KINERJA
-- =====================================================
-- Jalankan script ini di phpMyAdmin atau MySQL client
-- Script ini akan membuat database dan semua tabel dari awal
-- =====================================================

-- Hapus database jika sudah ada (HATI-HATI: ini akan menghapus semua data!)
-- DROP DATABASE IF EXISTS `sistem_penilaian_kinerja`;

-- Buat database baru
CREATE DATABASE IF NOT EXISTS `sistem_penilaian_kinerja` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `sistem_penilaian_kinerja`;

-- =====================================================
-- TABEL: users (Akun Login)
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'pimpinan', 'pegawai') NOT NULL,
  `nama_lengkap` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL: pegawai (Data Pegawai)
-- =====================================================
CREATE TABLE IF NOT EXISTS `pegawai` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NULL DEFAULT NULL,
  `nip` VARCHAR(50) NOT NULL,
  `nama_depan` VARCHAR(100) NULL DEFAULT NULL,
  `nama_belakang` VARCHAR(100) NULL DEFAULT NULL,
  `nama_lengkap` VARCHAR(255) NOT NULL,
  `jabatan` VARCHAR(100) NOT NULL,
  `unit_kerja` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `no_telp` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nip` (`nip`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_nama_depan` (`nama_depan`),
  CONSTRAINT `fk_pegawai_user` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL: penilai (Data Penilai/Pimpinan)
-- =====================================================
CREATE TABLE IF NOT EXISTS `penilai` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `nama_penilai` VARCHAR(255) NOT NULL,
  `jabatan` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_penilai_user` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL: kriteria (Kriteria Penilaian)
-- =====================================================
CREATE TABLE IF NOT EXISTS `kriteria` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_kriteria` VARCHAR(10) NOT NULL,
  `nama_kriteria` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `bobot` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_kriteria` (`kode_kriteria`),
  KEY `idx_bobot` (`bobot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL: periode_penilaian (Periode Penilaian)
-- =====================================================
CREATE TABLE IF NOT EXISTS `periode_penilaian` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_periode` VARCHAR(100) NOT NULL,
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_selesai` DATE NOT NULL,
  `status` ENUM('aktif', 'selesai', 'draft') NOT NULL DEFAULT 'draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_tanggal` (`tanggal_mulai`, `tanggal_selesai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL: penilaian (Data Penilaian)
-- =====================================================
CREATE TABLE IF NOT EXISTS `penilaian` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `periode_id` INT(11) NOT NULL,
  `pegawai_id` INT(11) NOT NULL,
  `penilai_id` INT(11) NOT NULL,
  `tanggal_penilaian` DATE NOT NULL,
  `status` ENUM('draft', 'selesai') NOT NULL DEFAULT 'draft',
  `catatan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_periode` (`periode_id`),
  KEY `idx_pegawai` (`pegawai_id`),
  KEY `idx_penilai` (`penilai_id`),
  KEY `idx_status` (`status`),
  KEY `idx_tanggal` (`tanggal_penilaian`),
  CONSTRAINT `fk_penilaian_periode` 
    FOREIGN KEY (`periode_id`) 
    REFERENCES `periode_penilaian` (`id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_penilaian_pegawai` 
    FOREIGN KEY (`pegawai_id`) 
    REFERENCES `pegawai` (`id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_penilaian_penilai` 
    FOREIGN KEY (`penilai_id`) 
    REFERENCES `penilai` (`id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL: detail_penilaian (Detail Nilai per Kriteria)
-- =====================================================
CREATE TABLE IF NOT EXISTS `detail_penilaian` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `penilaian_id` INT(11) NOT NULL,
  `kriteria_id` INT(11) NOT NULL,
  `nilai` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `catatan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_penilaian_kriteria` (`penilaian_id`, `kriteria_id`),
  KEY `idx_kriteria` (`kriteria_id`),
  CONSTRAINT `fk_detail_penilaian` 
    FOREIGN KEY (`penilaian_id`) 
    REFERENCES `penilaian` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_detail_kriteria` 
    FOREIGN KEY (`kriteria_id`) 
    REFERENCES `kriteria` (`id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DATA: Users (Akun Login)
-- =====================================================
-- Password untuk semua user: password123
-- Hash password di-generate dengan PHP: password_hash('password123', PASSWORD_DEFAULT)
-- 
-- Untuk generate hash baru, jalankan di PHP:
-- echo password_hash('password123', PASSWORD_DEFAULT);
--
-- Akun Login:
-- - Admin: admin / password123
-- - Pimpinan 1: pimpinan1 / password123
-- - Pimpinan 2: pimpinan2 / password123
-- - Pegawai: budi / budi123, sari / sari123, doni / doni123, lina / lina123

INSERT INTO `users` (`username`, `password`, `role`, `nama_lengkap`) VALUES
('admin', '$2y$10$TyUPfgpyMZzoFxXD0q764.QOBx8OLKOiJI.E5rSmOQs6GVYIDGDnG', 'admin', 'Administrator'),
('pimpinan1', '$2y$10$TyUPfgpyMZzoFxXD0q764.QOBx8OLKOiJI.E5rSmOQs6GVYIDGDnG', 'pimpinan', 'Dr. Ahmad Wijaya, S.Kom., M.Kom'),
('pimpinan2', '$2y$10$TyUPfgpyMZzoFxXD0q764.QOBx8OLKOiJI.E5rSmOQs6GVYIDGDnG', 'pimpinan', 'Drs. Siti Nurhaliza, M.M'),
('budi', '$2y$10$kUHveSLnjxkFZvCYqt3lEe1TX72L6cIIVVk11V5iV1yNq.bkc/UCW', 'pegawai', 'Budi Santoso'),
('sari', '$2y$10$XKUXM81HOdiKP6HJvlZX/.E9KW.mxxfS5wWZ/CHwZgeye7f4YCPli', 'pegawai', 'Sari Indah'),
('doni', '$2y$10$83zKM.LFcrNN5G6KN29LruurZr6sG3h9.27Rk61MTNs.6CFmCcqEO', 'pegawai', 'Doni Prasetyo'),
('lina', '$2y$10$EHPwBxjzUz0pkpNJrKugY.i6dsXtEv0kPxsVoVw4ZmHLlsjjCzV7G', 'pegawai', 'Lina Wati');

-- =====================================================
-- INSERT DATA: Pegawai
-- =====================================================
INSERT INTO `pegawai` (`user_id`, `nip`, `nama_depan`, `nama_belakang`, `nama_lengkap`, `jabatan`, `unit_kerja`, `email`, `no_telp`) VALUES
(4, '19600101198001001', 'Budi', 'Santoso', 'Budi Santoso', 'Staff IT', 'Bidang Teknologi Informasi', 'budi.santoso@example.com', '081234567890'),
(5, '19600202198002002', 'Sari', 'Indah', 'Sari Indah', 'Staff Administrasi', 'Bidang Administrasi', 'sari.indah@example.com', '081234567891'),
(6, '19600303198003003', 'Doni', 'Prasetyo', 'Doni Prasetyo', 'Staff Keuangan', 'Bidang Keuangan', 'doni.prasetyo@example.com', '081234567892'),
(7, '19600404198004004', 'Lina', 'Wati', 'Lina Wati', 'Staff HRD', 'Bidang Sumber Daya Manusia', 'lina.wati@example.com', '081234567893'),
(NULL, '19600505198005005', 'Rudi', 'Hartono', 'Rudi Hartono', 'Staff Marketing', 'Bidang Pemasaran', 'rudi.hartono@example.com', '081234567894'),
(NULL, '19600606198006006', 'Maya', 'Sari', 'Maya Sari', 'Staff Produksi', 'Bidang Produksi', 'maya.sari@example.com', '081234567895');

-- =====================================================
-- INSERT DATA: Penilai
-- =====================================================
INSERT INTO `penilai` (`user_id`, `nama_penilai`, `jabatan`) VALUES
(2, 'Dr. Ahmad Wijaya, S.Kom., M.Kom', 'Kepala Bidang Teknologi Informasi'),
(3, 'Drs. Siti Nurhaliza, M.M', 'Kepala Bidang Administrasi');

-- =====================================================
-- INSERT DATA: Kriteria Penilaian
-- =====================================================
INSERT INTO `kriteria` (`kode_kriteria`, `nama_kriteria`, `deskripsi`, `bobot`) VALUES
('K01', 'Disiplin Kerja', 'Kedisiplinan dalam waktu kerja, absensi, dan kepatuhan terhadap peraturan', 25.00),
('K02', 'Kualitas Kerja', 'Tingkat kualitas hasil kerja yang dihasilkan', 30.00),
('K03', 'Kuantitas Kerja', 'Jumlah pekerjaan yang dapat diselesaikan dalam periode tertentu', 20.00),
('K04', 'Kerja Sama Tim', 'Kemampuan bekerja sama dengan rekan kerja dan tim', 15.00),
('K05', 'Inisiatif dan Inovasi', 'Kemampuan mengambil inisiatif dan memberikan ide inovatif', 10.00);

-- =====================================================
-- INSERT DATA: Periode Penilaian
-- =====================================================
INSERT INTO `periode_penilaian` (`nama_periode`, `tanggal_mulai`, `tanggal_selesai`, `status`) VALUES
('Penilaian Kinerja Semester 1 Tahun 2024', '2024-01-01', '2024-06-30', 'selesai'),
('Penilaian Kinerja Semester 2 Tahun 2024', '2024-07-01', '2024-12-31', 'aktif'),
('Penilaian Kinerja Semester 1 Tahun 2025', '2025-01-01', '2025-06-30', 'draft');

-- =====================================================
-- INSERT DATA: Penilaian
-- =====================================================
INSERT INTO `penilaian` (`periode_id`, `pegawai_id`, `penilai_id`, `tanggal_penilaian`, `status`) VALUES
(1, 1, 1, '2024-06-25', 'selesai'),
(1, 2, 2, '2024-06-26', 'selesai'),
(1, 3, 1, '2024-06-27', 'selesai'),
(2, 1, 1, '2024-12-15', 'selesai'),
(2, 2, 2, '2024-12-16', 'draft');

-- =====================================================
-- INSERT DATA: Detail Penilaian
-- =====================================================
-- Penilaian 1: Budi Santoso (Periode 1) - Nilai: 85.5
INSERT INTO `detail_penilaian` (`penilaian_id`, `kriteria_id`, `nilai`, `catatan`) VALUES
(1, 1, 85.00, 'Sangat disiplin, jarang absen'),
(1, 2, 88.00, 'Kualitas kerja sangat baik'),
(1, 3, 82.00, 'Menyelesaikan target tepat waktu'),
(1, 4, 87.00, 'Bekerja sama dengan baik'),
(1, 5, 85.00, 'Sering memberikan ide baru');

-- Penilaian 2: Sari Indah (Periode 1) - Nilai: 90.0
INSERT INTO `detail_penilaian` (`penilaian_id`, `kriteria_id`, `nilai`, `catatan`) VALUES
(2, 1, 92.00, 'Sangat disiplin dan tepat waktu'),
(2, 2, 90.00, 'Kualitas kerja excellent'),
(2, 3, 88.00, 'Produktivitas tinggi'),
(2, 4, 91.00, 'Sangat kooperatif dengan tim'),
(2, 5, 89.00, 'Banyak inovasi yang diberikan');

-- Penilaian 3: Doni Prasetyo (Periode 1) - Nilai: 78.5
INSERT INTO `detail_penilaian` (`penilaian_id`, `kriteria_id`, `nilai`, `catatan`) VALUES
(3, 1, 75.00, 'Perlu peningkatan disiplin'),
(3, 2, 80.00, 'Kualitas kerja cukup baik'),
(3, 3, 78.00, 'Target tercapai dengan baik'),
(3, 4, 82.00, 'Bekerja sama cukup baik'),
(3, 5, 75.00, 'Perlu lebih proaktif');

-- Penilaian 4: Budi Santoso (Periode 2) - Nilai: 88.0
INSERT INTO `detail_penilaian` (`penilaian_id`, `kriteria_id`, `nilai`, `catatan`) VALUES
(4, 1, 88.00, 'Disiplin meningkat'),
(4, 2, 90.00, 'Kualitas kerja sangat baik'),
(4, 3, 85.00, 'Produktivitas meningkat'),
(4, 4, 89.00, 'Kerja sama tim sangat baik'),
(4, 5, 88.00, 'Lebih inovatif');

-- Penilaian 5: Sari Indah (Periode 2) - Draft
INSERT INTO `detail_penilaian` (`penilaian_id`, `kriteria_id`, `nilai`, `catatan`) VALUES
(5, 1, 90.00, 'Sangat disiplin'),
(5, 2, 92.00, 'Kualitas excellent'),
(5, 3, 89.00, 'Produktivitas tinggi'),
(5, 4, 91.00, 'Kerja sama tim sangat baik'),
(5, 5, 90.00, 'Banyak inovasi');

-- =====================================================
-- CATATAN PENTING
-- =====================================================
-- 1. PASSWORD DEFAULT: password123 (untuk semua user)
--    Untuk generate hash baru, jalankan di PHP:
--    <?php echo password_hash('password123', PASSWORD_DEFAULT); ?>
-- 
-- 2. AKUN LOGIN:
--    Admin:
--      - Username: admin
--      - Password: password123
--    
--    Pimpinan:
--      - Username: pimpinan1 / Password: password123
--      - Username: pimpinan2 / Password: password123
--    
--    Pegawai:
--      - Username: budi / Password: budi123
--      - Username: sari / Password: sari123
--      - Username: doni / Password: doni123
--      - Username: lina / Password: lina123
--
-- 3. DATA YANG SUDAH DI-INSERT:
--    - 7 users (1 admin, 2 pimpinan, 4 pegawai)
--    - 6 pegawai (4 dengan akun, 2 tanpa akun)
--    - 2 penilai
--    - 5 kriteria penilaian
--    - 3 periode penilaian
--    - 5 penilaian (4 selesai, 1 draft)
--    - 25 detail penilaian
--
-- 4. KEAMANAN:
--    - Segera ubah password setelah login pertama kali!
--    - Password hash menggunakan bcrypt (PASSWORD_DEFAULT)
--    - Setiap hash unik meskipun password sama
--
-- 5. CARA MENGGUNAKAN:
--    - Import file ini ke phpMyAdmin atau MySQL client
--    - Atau jalankan: mysql -u root -p < database_create.sql
--    - Login dengan akun admin untuk mulai menggunakan sistem
-- =====================================================

