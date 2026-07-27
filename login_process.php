<?php
session_start();
require_once 'config.php';

$base = defined('BASE_URL') ? BASE_URL : '/scm_usimpel/';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Prepared statement untuk login aman dan fleksibel
    $stmt = $conn->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Cek apakah user ada dan password benar
    if ($user && $password === $user['password']) {
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['name'] = $user['nama_user'];
        $_SESSION['jabatan'] = $user['jabatan']; // Simpan jabatan ke session

        $jab_lower = strtolower(trim($user['jabatan']));

        // Pengalihan berdasarkan jabatan (case-insensitive)
        if (in_array($jab_lower, ['admin', 'administrator'])) {
            header('Location: ' . $base . 'dashboard/dashboard_admin.php');
        } elseif ($jab_lower === strtolower('Inventory & Purchasing Officer')) {
            header('Location: ' . $base . 'dashboard/dashboard_inventory.php');
        } elseif ($jab_lower === strtolower('Finance & Billing Officer')) {
            header('Location: ' . $base . 'dashboard/dashboard_finance.php');
        } elseif ($jab_lower === strtolower('Kepala Divisi Produk & Pengadaan')) {
            header('Location: ' . $base . 'dashboard/dashboard_kepala_divisi.php');
        } elseif ($jab_lower === strtolower('Direktur Operasional')) {
            header('Location: ' . $base . 'dashboard/dashboard_direktur.php');
        } else {
            // Default fallback ke dashboard admin jika jabatan tidak terdaftar spesifik
            header('Location: ' . $base . 'dashboard/dashboard_admin.php');
        }
        exit();
    } else {
        header('Location: ' . $base . 'login.php?error=invalid');
        exit();
    }
} else {
    header('Location: ' . $base . 'login.php');
    exit();
}
?>
