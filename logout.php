<?php
// Selalu mulai sesi di awal
session_start();

// 1. Hapus semua variabel sesi
$_SESSION = array();

// 2. Hancurkan sesi (session)
session_destroy();

// 3. Arahkan pengguna kembali ke halaman login
header("location: login.php");
exit;
?>