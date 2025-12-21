<?php
// pimpinan/index.php - Redirect ke dashboard
require_once '../config/database.php';
checkRole(['pimpinan']);
header("Location: dashboard.php");
exit();
?>