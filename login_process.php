<?php
session_start();
require 'config.php'; // Pastikan ini mengarah ke file konfigurasi database

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query untuk mendapatkan data user
    $query = "SELECT * FROM user WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);
    
    // Cek apakah user ada dan password benar
    if ($user && $password === $user['password']) {
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['name'] = $user['nama_user'];
        $_SESSION['jabatan'] = $user['jabatan']; // Simpan jabatan ke session
        // Pengalihan berdasarkan jabatan
        switch ($user['jabatan']) {
            case 'Administrator':
            case 'Admin':
                header('Location: dashboard/dashboard_admin.php');
                break;
            case 'Inventory & Purchasing Officer':
                header('Location: dashboard/dashboard_inventory.php');
                break;
            case 'Finance & Billing Officer':
                header('Location: dashboard/dashboard_finance.php');
                break;
            case 'Kepala Divisi Produk & Pengadaan':
                header('Location: dashboard/dashboard_kepala_divisi.php');
                break;
            case 'Direktur Operasional':
                header('Location: dashboard/dashboard_direktur.php');
                break;
            default:
                header('Location: dashboard/dashboard_admin.php'); // Jika jabatan tidak dikenali / default admin
                break;
        }
        exit();
    } else {
        echo "Login gagal! Username atau password salah.";
    }
}
?>
