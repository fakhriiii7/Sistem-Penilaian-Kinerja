# Perbaikan Sistem Pegawai - Versi Sederhana

## Perubahan yang Dilakukan

Sistem sekarang lebih sederhana dan otomatis:

### 1. Form Input Nama
- ✅ Nama dibagi menjadi **Nama Depan** dan **Nama Belakang** (bukan nama lengkap)
- ✅ Lebih terstruktur dan mudah diolah

### 2. Auto-Generate Username & Password
- ✅ **Username**: Otomatis dibuat dari nama depan (lowercase, tanpa spasi dan karakter khusus)
- ✅ **Password**: Otomatis dibuat dengan format `username123`
- ✅ Tidak perlu input manual username/password lagi!

### 3. Contoh:
- **Nama Depan**: "Budi"
- **Nama Belakang**: "Santoso"
- **Username**: `budi` (otomatis)
- **Password**: `budi123` (otomatis)

### 4. Handle Duplikat Username
- Jika username sudah ada, sistem otomatis menambahkan angka: `budi`, `budi1`, `budi2`, dst.

## Cara Menggunakan

### Untuk Admin:

1. **Menambah Pegawai Baru:**
   - Buka menu "Data Pegawai" → "Tambah Pegawai"
   - Isi:
     - NIP
     - **Nama Depan** (contoh: "Budi")
     - **Nama Belakang** (contoh: "Santoso")
     - Jabatan
     - Unit Kerja (opsional)
     - Email (opsional)
     - No. Telepon (opsional)
   - Klik "Simpan"
   - **Akun login otomatis dibuat!**
   - Sistem akan menampilkan username dan password yang dibuat

2. **Membuat Akun untuk Pegawai Lama:**
   - Buka menu "Data Pegawai"
   - Cari pegawai yang statusnya "Belum Punya Akun"
   - Klik tombol "Buat Akun" (ikon user-plus)
   - Konfirmasi, dan akun akan dibuat otomatis

## Update Database

Jalankan script SQL dari file `database_update_v2.sql`:

```sql
-- Menambahkan kolom nama_depan dan nama_belakang
-- Migrasi data dari nama_lengkap
-- Lihat file database_update_v2.sql untuk detail lengkap
```

## Struktur Database

```sql
CREATE TABLE `pegawai` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NULL DEFAULT NULL,
  `nama_depan` VARCHAR(100) NULL DEFAULT NULL,      -- Kolom baru
  `nama_belakang` VARCHAR(100) NULL DEFAULT NULL,   -- Kolom baru
  `nama_lengkap` VARCHAR(255) NOT NULL,            -- Tetap ada untuk kompatibilitas
  `nip` VARCHAR(50) NOT NULL,
  `jabatan` VARCHAR(100) NOT NULL,
  `unit_kerja` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `no_telp` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nip` (`nip`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Keuntungan Sistem Baru

1. ✅ **Lebih Sederhana**: Tidak perlu input username/password manual
2. ✅ **Lebih Cepat**: Proses input lebih cepat
3. ✅ **Konsisten**: Format username dan password selalu sama
4. ✅ **Otomatis**: Akun langsung dibuat saat tambah pegawai
5. ✅ **Mudah Diingat**: Password format `username123` mudah diingat

## Catatan Penting

- Kolom `nama_lengkap` tetap dipertahankan untuk kompatibilitas dengan data lama
- Sistem otomatis membuat `nama_lengkap` dari `nama_depan + nama_belakang`
- Data lama yang belum punya `nama_depan` dan `nama_belakang` akan otomatis di-split dari `nama_lengkap`
- Username dibuat lowercase dan hanya huruf/angka (spasi dan karakter khusus dihapus)

