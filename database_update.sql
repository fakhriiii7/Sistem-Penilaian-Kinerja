-- Script untuk menambahkan kolom user_id ke tabel pegawai
-- Jalankan script ini di phpMyAdmin atau MySQL client jika kolom user_id belum ada

-- Cek dan tambahkan kolom user_id jika belum ada
ALTER TABLE `pegawai` 
ADD COLUMN `user_id` INT(11) NULL DEFAULT NULL AFTER `id`,
ADD INDEX `idx_user_id` (`user_id`),
ADD CONSTRAINT `fk_pegawai_user` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE;

-- Catatan:
-- - Kolom user_id bersifat nullable (boleh NULL) karena tidak semua pegawai harus punya akun
-- - Foreign key ke tabel users untuk menjaga integritas data
-- - ON DELETE SET NULL: jika user dihapus, user_id di pegawai akan menjadi NULL
-- - ON UPDATE CASCADE: jika id user berubah, user_id di pegawai akan ikut berubah

