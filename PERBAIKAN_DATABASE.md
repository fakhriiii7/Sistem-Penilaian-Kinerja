# Perbaikan Database dan Sistem Pegawai

## Masalah yang Diperbaiki

Sebelumnya, admin hanya bisa membuat data pegawai tanpa membuat akun login, sehingga pegawai tidak bisa login ke sistem.

## Perubahan yang Dilakukan

### 1. Modifikasi Form Tambah Pegawai (`admin/pegawai.php`)
- ✅ Menambahkan checkbox "Buat Akun Login" (opsional)
- ✅ Menambahkan field Username dan Password (muncul jika checkbox dicentang)
- ✅ Saat menambah pegawai, admin bisa sekaligus membuat akun login

### 2. Fitur Buat Akun untuk Pegawai yang Sudah Ada
- ✅ Menambahkan kolom "Status Akun" di tabel data pegawai
- ✅ Menampilkan status: "Sudah Punya Akun" atau "Belum Punya Akun"
- ✅ Tombol "Buat Akun" untuk pegawai yang belum punya akun
- ✅ Modal form untuk membuat akun login

### 3. Perbaikan Relasi Database
- ✅ Sistem sekarang menggunakan kolom `user_id` di tabel `pegawai` untuk menghubungkan dengan tabel `users`
- ✅ Saat membuat akun, sistem otomatis mengupdate `user_id` di tabel pegawai

### 4. File SQL untuk Update Database
- ✅ File `database_update.sql` berisi script untuk menambahkan kolom `user_id` jika belum ada

## Cara Menggunakan

### Untuk Admin:

1. **Menambah Pegawai Baru dengan Akun:**
   - Buka menu "Data Pegawai" → "Tambah Pegawai"
   - Isi data pegawai
   - Centang checkbox "Buat akun login untuk pegawai ini"
   - Isi Username dan Password
   - Klik "Simpan"

2. **Membuat Akun untuk Pegawai yang Sudah Ada:**
   - Buka menu "Data Pegawai"
   - Cari pegawai yang statusnya "Belum Punya Akun"
   - Klik tombol "Buat Akun" (ikon user-plus)
   - Isi Username dan Password
   - Klik "Buat Akun"

### Update Database (Jika Perlu):

Jika kolom `user_id` belum ada di tabel `pegawai`, jalankan script SQL:
```sql
-- Lihat file database_update.sql
```

## Catatan Penting

- Kolom `user_id` di tabel `pegawai` bersifat nullable (boleh NULL)
- Tidak semua pegawai harus punya akun login
- Jika user dihapus, `user_id` di pegawai akan menjadi NULL (tidak menghapus data pegawai)
- Sistem tetap bisa mencari pegawai berdasarkan `nama_lengkap` jika `user_id` tidak ditemukan

## Struktur Database yang Disarankan

```sql
CREATE TABLE `pegawai` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NULL DEFAULT NULL,  -- Kolom baru
  `nip` VARCHAR(50) NOT NULL,
  `nama_lengkap` VARCHAR(255) NOT NULL,
  `jabatan` VARCHAR(100) NOT NULL,
  `unit_kerja` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `no_telp` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nip` (`nip`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_pegawai_user` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

