<?php
session_start();
require_once '../config.php';
require_once '../helpers.php'; // Panggil helper

if ($_SESSION['jabatan'] != 'Finance & Billing Officer') exit('Akses Ditolak.');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file_bukti_transfer"])) {
    $id_pengadaan = $_POST['id_pengadaan'];
    $nominal = $_POST['nominal'];
    
    // Proses upload bukti transfer
    $target_dir = "../uploads/transfers/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    $file_name = time() . "_" . basename($_FILES["file_bukti_transfer"]["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["file_bukti_transfer"]["tmp_name"], $target_file)) {
        // Update record pembayaran yang sudah ada dengan file bukti transfer
        $stmt = $conn->prepare("UPDATE pembayaran SET file_bukti_transfer = ?, status_pembayaran = 'Menunggu Kuitansi' WHERE id_pengadaan = ? AND status_pembayaran = 'Belum Bayar'");
        $stmt->bind_param("ss", $file_name, $id_pengadaan);
        
        if ($stmt->execute()) {
            header("Location: pembayaran.php?status=transfer_success");
        } else {
            unlink($target_file);
            header("Location: pembayaran.php?status=error");
        }
    } else {
        header("Location: pembayaran.php?status=error");
    }
}
?>