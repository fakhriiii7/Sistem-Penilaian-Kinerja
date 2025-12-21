<?php
/**
 * Script untuk generate password hash
 * Jalankan script ini untuk mendapatkan hash password baru
 * 
 * Usage: php generate_password_hash.php
 */

echo "=== GENERATE PASSWORD HASH ===\n\n";

// Password yang akan di-hash
$passwords = [
    'password123' => 'Password default untuk admin dan pimpinan',
    'budi123' => 'Password untuk pegawai Budi',
    'sari123' => 'Password untuk pegawai Sari',
    'doni123' => 'Password untuk pegawai Doni',
    'lina123' => 'Password untuk pegawai Lina',
];

foreach ($passwords as $password => $description) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "Password: {$password}\n";
    echo "Deskripsi: {$description}\n";
    echo "Hash: {$hash}\n";
    echo "SQL: INSERT INTO users (username, password, role, nama_lengkap) VALUES ('username', '{$hash}', 'role', 'Nama');\n";
    echo str_repeat('-', 80) . "\n\n";
}

// Verify hash
echo "\n=== VERIFY HASH ===\n";
$test_password = 'password123';
$test_hash = password_hash($test_password, PASSWORD_DEFAULT);
echo "Password: {$test_password}\n";
echo "Hash: {$test_hash}\n";
echo "Verify: " . (password_verify($test_password, $test_hash) ? 'SUCCESS' : 'FAILED') . "\n";

