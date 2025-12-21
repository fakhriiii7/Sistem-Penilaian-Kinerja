# Panduan Setup Database Sistem Penilaian Kinerja

## File-file SQL

1. **database_create.sql** - Script lengkap untuk membuat database dari awal (RECOMMENDED)
2. **database_update_v2.sql** - Script untuk update database yang sudah ada (menambah kolom nama_depan, nama_belakang)
3. **generate_password_hash.php** - Script PHP untuk generate password hash baru

## Cara Menggunakan

### Opsi 1: Import via phpMyAdmin (Paling Mudah)

1. Buka phpMyAdmin (http://localhost/phpmyadmin)
2. Klik tab "Import"
3. Pilih file `database_create.sql`
4. Klik "Go" atau "Import"
5. Selesai!

### Opsi 2: Import via Command Line

```bash
# Windows (XAMPP)
cd C:\xampp\mysql\bin
mysql.exe -u root -p < C:\xampp\htdocs\sistem-penilaian-kinerja\database_create.sql

# Linux/Mac
mysql -u root -p < database_create.sql
```

### Opsi 3: Copy-Paste Manual

1. Buka file `database_create.sql`
2. Copy semua isinya
3. Buka phpMyAdmin → SQL tab
4. Paste dan klik "Go"

## Akun Login Default

Setelah import database, gunakan akun berikut untuk login:

### Admin
- **Username:** `admin`
- **Password:** `password123`

### Pimpinan
- **Username:** `pimpinan1` atau `pimpinan2`
- **Password:** `password123`

### Pegawai
- **Username:** `budi` → Password: `budi123`
- **Username:** `sari` → Password: `sari123`
- **Username:** `doni` → Password: `doni123`
- **Username:** `lina` → Password: `lina123`

## Data yang Sudah Tersedia

Setelah import, database sudah berisi:

- ✅ **7 Users** (1 admin, 2 pimpinan, 4 pegawai)
- ✅ **6 Pegawai** (4 dengan akun, 2 tanpa akun)
- ✅ **2 Penilai** (pimpinan)
- ✅ **5 Kriteria Penilaian** (Disiplin, Kualitas, Kuantitas, Kerja Sama, Inisiatif)
- ✅ **3 Periode Penilaian** (2 selesai, 1 aktif, 1 draft)
- ✅ **5 Penilaian** (4 selesai, 1 draft)
- ✅ **25 Detail Penilaian** (nilai per kriteria)

## Struktur Database

### Tabel Utama:
1. **users** - Akun login (admin, pimpinan, pegawai)
2. **pegawai** - Data pegawai yang dinilai
3. **penilai** - Data penilai/pimpinan
4. **kriteria** - Kriteria penilaian
5. **periode_penilaian** - Periode waktu penilaian
6. **penilaian** - Data penilaian pegawai
7. **detail_penilaian** - Detail nilai per kriteria

## Generate Password Hash Baru

Jika ingin membuat password hash baru:

```bash
php generate_password_hash.php
```

Atau di PHP:
```php
<?php
echo password_hash('password123', PASSWORD_DEFAULT);
?>
```

## Troubleshooting

### Error: "Table already exists"
- Hapus database lama terlebih dahulu:
  ```sql
  DROP DATABASE IF EXISTS sistem_penilaian_kinerja;
  ```
- Atau hapus komentar di baris pertama file SQL

### Error: "Access denied"
- Pastikan user MySQL memiliki hak akses
- Gunakan user `root` atau user dengan privilege CREATE DATABASE

### Error: "Foreign key constraint fails"
- Pastikan semua tabel dibuat dalam urutan yang benar
- Import ulang dari awal jika ada error

## Keamanan

⚠️ **PENTING:** 
- Segera ubah password setelah login pertama kali!
- Jangan gunakan password default di production
- Gunakan password yang kuat dan unik

## Support

Jika ada masalah, pastikan:
1. MySQL/MariaDB sudah terinstall dan running
2. User memiliki hak akses yang cukup
3. File SQL tidak corrupt
4. Encoding file adalah UTF-8

