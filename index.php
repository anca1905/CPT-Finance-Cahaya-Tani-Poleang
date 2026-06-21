<?php
require_once 'config/database.php';

// Cek apakah user sudah login, jika belum redirect ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

// Redirect ke dashboard
header("Location: pages/dashboard.php");
exit;
