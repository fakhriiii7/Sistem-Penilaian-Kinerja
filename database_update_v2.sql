-- Script untuk update struktur database pegawai
-- Menambahkan kolom nama_depan dan nama_belakang
-- Jalankan script ini di phpMyAdmin atau MySQL client

-- 1. Tambahkan kolom nama_depan dan nama_belakang jika belum ada
ALTER TABLE `pegawai` 
ADD COLUMN IF NOT EXISTS `nama_depan` VARCHAR(100) NULL DEFAULT NULL AFTER `user_id`,
ADD COLUMN IF NOT EXISTS `nama_belakang` VARCHAR(100) NULL DEFAULT NULL AFTER `nama_depan`;

-- 2. Migrasi data dari nama_lengkap ke nama_depan dan nama_belakang
-- (Hanya untuk data yang belum punya nama_depan)
UPDATE `pegawai` 
SET 
    `nama_depan` = SUBSTRING_INDEX(`nama_lengkap`, ' ', 1),
    `nama_belakang` = CASE 
        WHEN LOCATE(' ', `nama_lengkap`) > 0 
        THEN SUBSTRING(`nama_lengkap`, LOCATE(' ', `nama_lengkap`) + 1)
        ELSE ''
    END
WHERE (`nama_depan` IS NULL OR `nama_depan` = '') 
AND `nama_lengkap` IS NOT NULL 
AND `nama_lengkap` != '';

-- 3. Pastikan kolom user_id ada (jika belum ada)
ALTER TABLE `pegawai` 
ADD COLUMN IF NOT EXISTS `user_id` INT(11) NULL DEFAULT NULL AFTER `id`,
ADD INDEX IF NOT EXISTS `idx_user_id` (`user_id`);

-- 4. Tambahkan foreign key jika belum ada (opsional, bisa di-comment jika sudah ada)
-- ALTER TABLE `pegawai` 
-- ADD CONSTRAINT `fk_pegawai_user` 
--     FOREIGN KEY (`user_id`) 
--     REFERENCES `users` (`id`) 
--     ON DELETE SET NULL 
--     ON UPDATE CASCADE;

-- Catatan:
-- - Kolom nama_depan dan nama_belakang dibuat nullable untuk kompatibilitas dengan data lama
-- - nama_lengkap tetap dipertahankan untuk kompatibilitas
-- - Script ini aman dijalankan beberapa kali (menggunakan IF NOT EXISTS)

