<?php
require_once '../config/database.php';

// Check jika user adalah admin
checkRole(['admin']);

// Redirect ke dashboard
header("Location: dashboard.php");
exit();
?>