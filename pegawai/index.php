<?php
// pegawai/index.php - Redirect ke dashboard
require_once '../config/database.php';
checkRole(['pegawai']);
header("Location: dashboard.php");
exit();
?>