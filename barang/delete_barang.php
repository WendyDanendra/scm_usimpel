<?php
session_start();
require_once '../config.php';

// Proteksi halaman
if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Inventory & Purchasing Officer') {
    header('Location: ../login.php');
    exit();
}

$id_barang = $_GET['id'] ?? null;

if ($id_barang) {
    // Cek apakah barang digunakan di tabel lain (misal: pengajuan_barang, stok_barang)
    $stmt_check_pengajuan = $conn->prepare("SELECT COUNT(*) as count FROM pengajuan_barang WHERE id_barang = ?");
    $stmt_check_pengajuan->bind_param("s", $id_barang);
    $stmt_check_pengajuan->execute();
    $result_pengajuan = $stmt_check_pengajuan->get_result()->fetch_assoc();
    $stmt_check_pengajuan->close();
    
    $stmt_check_stok = $conn->prepare("SELECT COUNT(*) as count FROM stok_barang WHERE id_barang = ?");
    $stmt_check_stok->bind_param("s", $id_barang);
    $stmt_check_stok->execute();
    $result_stok = $stmt_check_stok->get_result()->fetch_assoc();
    $stmt_check_stok->close();

    // Jika tidak terpakai, baru hapus
    if ($result_pengajuan['count'] == 0 && $result_stok['count'] == 0) {
        $stmt_delete = $conn->prepare("DELETE FROM barang WHERE id_barang = ?");
        $stmt_delete->bind_param("s", $id_barang);
        
        if ($stmt_delete->execute()) {
            header("Location: barang.php?status=success_delete");
            exit();
        } else {
            header("Location: barang.php?status=error");
            exit();
        }
    } else {
        // Jika masih terpakai, kirim status error
        header("Location: barang.php?status=error_constraint");
        exit();
    }
} else {
    header("Location: barang.php");
    exit();
}
?>