<?php
session_start();
require_once '../config.php';

// Proteksi halaman
$jabatan_lower = strtolower(trim($_SESSION['jabatan'] ?? ''));
if (!isset($_SESSION['jabatan']) || !in_array($jabatan_lower, ['inventory & purchasing officer', 'administrator', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

$id_pengajuan = $_GET['id'] ?? 0;

if ($id_pengajuan) {
    // Cek dulu statusnya
    $stmt = $conn->prepare("SELECT status_pengajuan FROM pengajuan_barang WHERE id_pengajuan = ?");
    $stmt->bind_param("s", $id_pengajuan);
    $stmt->execute();
    $pengajuan = $stmt->get_result()->fetch_assoc();

    if ($pengajuan && $pengajuan['status_pengajuan'] == 'Diajukan') {
        // Jika statusnya masih 'Diajukan', baru boleh hapus
        $delete_stmt = $conn->prepare("DELETE FROM pengajuan_barang WHERE id_pengajuan = ?");
        $delete_stmt->bind_param("s", $id_pengajuan);
        if ($delete_stmt->execute()) {
            header("Location: pengajuan_barang.php?status=success_delete");
        }
    } else {
        // Jika sudah disetujui/ditolak, kirim pesan error
        header("Location: pengajuan_barang.php?status=error_approved");
    }
} else {
    header("Location: pengajuan_barang.php");
}
?>