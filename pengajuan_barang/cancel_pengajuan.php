<?php
session_start();
if ($_SESSION['jabatan'] !== 'Inventory & Purchasing Officer') {
    header('Location: ../login.php');
    exit();
}

include '../config.php';

$id = $_GET['id'];

// Periksa apakah pengajuan dapat dibatalkan
$sql = "SELECT status_pengajuan FROM pengajuan_barang WHERE id_pengajuan = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$pengajuan = $result->fetch_assoc();

if (!$pengajuan) {
    header('Location: pengajuan_barang.php?error=Pengajuan tidak ditemukan');
    exit();
}

// Hanya bisa dibatalkan jika statusnya Diajukan atau Diproses
if (!in_array($pengajuan['status_pengajuan'], ['Diajukan', 'Diproses'])) {
    header('Location: pengajuan_barang.php?error=Pengajuan tidak dapat dibatalkan karena status ' . $pengajuan['status_pengajuan']);
    exit();
}

// Batalkan pengajuan
$sql = "UPDATE pengajuan_barang 
        SET status_pengajuan = 'Dibatalkan'
        WHERE id_pengajuan = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);

if ($stmt->execute()) {
    header("Location: pengajuan_barang.php?message=Pengajuan berhasil dibatalkan");
} else {
    header("Location: pengajuan_barang.php?error=Gagal membatalkan pengajuan");
}

$stmt->close();
$conn->close();
exit();
?>