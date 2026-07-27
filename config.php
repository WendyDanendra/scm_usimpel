<?php

// Set timezone ke Indonesia untuk seluruh aplikasi
date_default_timezone_set('Asia/Jakarta');

$host = "localhost";  // Server database
$user = "root";       // Username database (default XAMPP)
$pass = "";           // Password database (kosong di XAMPP)
$db   = "scm_usimpel"; // Nama database

// Koneksi ke database
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>
