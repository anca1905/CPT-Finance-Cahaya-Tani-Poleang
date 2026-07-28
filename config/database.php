<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_ctp_finance';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

session_start();

function base_url($path = '')
{
    // Sesuaikan dengan folder project Anda
    return "http://localhost/sistem_ctp/" . ltrim($path, '/');
}
