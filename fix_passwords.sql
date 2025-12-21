-- =====================================================
-- SCRIPT UNTUK MEMPERBAIKI PASSWORD HASH DI DATABASE
-- =====================================================
-- Jalankan script ini jika password tidak bisa digunakan untuk login
-- Script ini akan update password hash dengan hash yang benar
-- =====================================================

USE `sistem_penilaian_kinerja`;

-- Update password untuk admin dan pimpinan (password: password123)
UPDATE `users` SET `password` = '$2y$10$TyUPfgpyMZzoFxXD0q764.QOBx8OLKOiJI.E5rSmOQs6GVYIDGDnG' WHERE `username` = 'admin';
UPDATE `users` SET `password` = '$2y$10$TyUPfgpyMZzoFxXD0q764.QOBx8OLKOiJI.E5rSmOQs6GVYIDGDnG' WHERE `username` = 'pimpinan1';
UPDATE `users` SET `password` = '$2y$10$TyUPfgpyMZzoFxXD0q764.QOBx8OLKOiJI.E5rSmOQs6GVYIDGDnG' WHERE `username` = 'pimpinan2';

-- Update password untuk pegawai
UPDATE `users` SET `password` = '$2y$10$kUHveSLnjxkFZvCYqt3lEe1TX72L6cIIVVk11V5iV1yNq.bkc/UCW' WHERE `username` = 'budi';
UPDATE `users` SET `password` = '$2y$10$XKUXM81HOdiKP6HJvlZX/.E9KW.mxxfS5wWZ/CHwZgeye7f4YCPli' WHERE `username` = 'sari';
UPDATE `users` SET `password` = '$2y$10$83zKM.LFcrNN5G6KN29LruurZr6sG3h9.27Rk61MTNs.6CFmCcqEO' WHERE `username` = 'doni';
UPDATE `users` SET `password` = '$2y$10$EHPwBxjzUz0pkpNJrKugY.i6dsXtEv0kPxsVoVw4ZmHLlsjjCzV7G' WHERE `username` = 'lina';

-- =====================================================
-- CATATAN:
-- =====================================================
-- Jika hash di atas tidak bekerja, jalankan script PHP:
-- php fix_passwords.php
-- 
-- Atau generate hash baru dengan:
-- <?php echo password_hash('password123', PASSWORD_DEFAULT); ?>
-- =====================================================

