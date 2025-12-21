<?php
/**
 * Script untuk memperbaiki password hash di database
 * Jalankan script ini untuk update password hash yang salah
 * 
 * Usage: php fix_passwords.php
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== FIX PASSWORD HASH ===\n\n";

// Daftar username dan password yang benar
$users = [
    ['username' => 'admin', 'password' => 'password123'],
    ['username' => 'pimpinan1', 'password' => 'password123'],
    ['username' => 'pimpinan2', 'password' => 'password123'],
    ['username' => 'budi', 'password' => 'budi123'],
    ['username' => 'sari', 'password' => 'sari123'],
    ['username' => 'doni', 'password' => 'doni123'],
    ['username' => 'lina', 'password' => 'lina123'],
];

echo "Updating passwords...\n\n";

foreach ($users as $user) {
    $username = $db->escapeString($user['username']);
    $password = $user['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Verify hash sebelum update
    $verify_query = "SELECT password FROM users WHERE username = '$username'";
    $verify_result = $conn->query($verify_query);
    if ($verify_result->num_rows > 0) {
        $old_hash = $verify_result->fetch_assoc()['password'];
        if (password_verify($password, $old_hash)) {
            echo "✓ Password already correct for: {$username}\n\n";
            continue;
        }
    }
    
    // Verify hash baru
    if (!password_verify($password, $hash)) {
        echo "ERROR: Hash verification failed for {$username}\n";
        continue;
    }
    
    // Update password
    $query = "UPDATE users SET password = '$hash' WHERE username = '$username'";
    
    if ($conn->query($query)) {
        echo "✓ Updated password for: {$username} (password: {$password})\n";
        echo "  Hash: {$hash}\n\n";
    } else {
        echo "✗ Failed to update {$username}: " . $conn->error . "\n\n";
    }
}

echo "\n=== DONE ===\n";
echo "All passwords have been updated. You can now login with the correct passwords.\n";

