<?php
session_start();
require_once '../config.php';
require_once '../helpers.php';

if ($_SESSION['jabatan'] != 'Inventory & Purchasing Officer') exit('Akses Ditolak.');

$id_pengajuan = $_GET['id'] ?? null;
if (!$id_pengajuan) {
    header("Location: pengadaan_barang.php?status=error");
    exit();
}

// Cek apakah pengajuan valid dan sudah ada pengadaan dengan invoice
$stmt_check = $conn->prepare("
    SELECT pd.id_pengadaan, pb.jumlah_diajukan, b.harga 
    FROM pengajuan_barang pb 
    JOIN pengadaan_barang pd ON pb.id_pengajuan = pd.id_pengajuan 
    JOIN barang b ON pb.id_barang = b.id_barang
    WHERE pb.id_pengajuan = ? AND pb.status_pengajuan = 'Disetujui' AND pd.file_invoice IS NOT NULL
");
$stmt_check->bind_param("s", $id_pengajuan);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows == 0) {
    header("Location: pengadaan_barang.php?status=error");
    exit();
}

$data = $result->fetch_assoc();
$id_pengadaan = $data['id_pengadaan'];
$nominal = $data['jumlah_diajukan'] * $data['harga'];

// Cek apakah sudah ada record pembayaran untuk pengadaan ini
$stmt_payment_check = $conn->prepare("SELECT id_pembayaran FROM pembayaran WHERE id_pengadaan = ?");
$stmt_payment_check->bind_param("s", $id_pengadaan);
$stmt_payment_check->execute();
$payment_exists = $stmt_payment_check->get_result()->num_rows > 0;

if (!$payment_exists) {
    // Buat record pembayaran baru
    $id_pembayaran = generate_id('BYRN', 'pembayaran', 'id_pembayaran');
    $tanggal_pembayaran = date('Y-m-d');
    
    $stmt_payment = $conn->prepare("
        INSERT INTO pembayaran (id_pembayaran, id_pengadaan, nominal, tanggal_pembayaran, status_pembayaran) 
        VALUES (?, ?, ?, ?, 'Belum Bayar')
    ");
    $stmt_payment->bind_param("ssds", $id_pembayaran, $id_pengadaan, $nominal, $tanggal_pembayaran);
    
    if ($stmt_payment->execute()) {
        header("Location: pengadaan_barang.php?status=sent_to_finance");
    } else {
        header("Location: pengadaan_barang.php?status=error");
    }
} else {
    // Sudah ada record pembayaran, redirect dengan sukses
    header("Location: pengadaan_barang.php?status=sent_to_finance");
}
?>