<?php

// Set timezone ke Indonesia untuk seluruh aplikasi
date_default_timezone_set('Asia/Jakarta');

// Tentukan BASE_URL secara dinamis berdasarkan lokasi folder aplikasi
if (!defined('BASE_URL')) {
    $doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : '';
    $current_dir = str_replace('\\', '/', __DIR__);
    
    if (!empty($doc_root) && strpos($current_dir, $doc_root) === 0) {
        $base_path = substr($current_dir, strlen($doc_root));
    } else {
        $base_path = '/scm_usimpel';
    }
    
    $base_url = rtrim($base_path, '/') . '/';
    define('BASE_URL', $base_url);
}

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
