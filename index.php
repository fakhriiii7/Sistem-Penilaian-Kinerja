<?php
session_start();

// Jika sudah login, redirect ke dashboard sesuai role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/index.php");
            break;
        case 'pimpinan':
            header("Location: pimpinan/index.php");
            break;
        case 'pegawai':
            header("Location: pegawai/index.php");
            break;
    }
    exit();
} else {
    // Jika belum login, redirect ke halaman login
    header("Location: login.php");
    exit();
}
?>