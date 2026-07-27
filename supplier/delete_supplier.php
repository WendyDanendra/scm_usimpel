<?php
session_start();
require_once '../config.php';

// Proteksi halaman
if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Inventory & Purchasing Officer') {
    header('Location: ../login.php');
    exit();
}

$id_supplier = $_GET['id'] ?? null;

if ($id_supplier) {
    // 1. Cek apakah supplier digunakan di tabel 'barang'
    $stmt_check_barang = $conn->prepare("SELECT COUNT(*) as count FROM barang WHERE id_supplier = ?");
    $stmt_check_barang->bind_param("s", $id_supplier);
    $stmt_check_barang->execute();
    $result_barang = $stmt_check_barang->get_result()->fetch_assoc();
    $stmt_check_barang->close();

    // 2. Cek apakah supplier digunakan di tabel 'pengajuan_barang'
    $stmt_check_pengajuan = $conn->prepare("SELECT COUNT(*) as count FROM pengajuan_barang WHERE id_supplier = ?");
    $stmt_check_pengajuan->bind_param("s", $id_supplier);
    $stmt_check_pengajuan->execute();
    $result_pengajuan = $stmt_check_pengajuan->get_result()->fetch_assoc();
    $stmt_check_pengajuan->close();

    // 3. Jika tidak ada di kedua tabel, baru hapus
    if ($result_barang['count'] == 0 && $result_pengajuan['count'] == 0) {
        $stmt_delete = $conn->prepare("DELETE FROM supplier WHERE id_supplier = ?");
        $stmt_delete->bind_param("s", $id_supplier);
        
        if ($stmt_delete->execute()) {
            header("Location: supplier.php?status=success_delete");
            exit();
        } else {
            // Untuk error tak terduga lainnya
            header("Location: supplier.php?status=error_delete");
            exit();
        }
        $stmt_delete->close();
    } else {
        // Jika supplier masih digunakan, kirim status error constraint
        header("Location: supplier.php?status=error_constraint");
        exit();
    }

} else {
    // Redirect jika tidak ada ID
    header("Location: supplier.php");
    exit();
}
$conn->close();
?>