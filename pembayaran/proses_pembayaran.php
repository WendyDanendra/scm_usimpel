<?php
session_start();
require_once '../config.php';
require_once '../helpers.php'; // Panggil helper

if ($_SESSION['jabatan'] != 'Finance & Billing Officer') exit('Akses Ditolak.');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file_bukti_transfer"])) {
    $id_pengadaan = $_POST['id_pengadaan'];
    $nominal = $_POST['nominal'];
    
    $file = $_FILES["file_bukti_transfer"];
    $max_size = 5 * 1024 * 1024; // 5 MB
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];
    $file_ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    
    // Validasi ukuran
    if ($file["size"] > $max_size) {
        header("Location: pembayaran.php?status=error_size");
        exit();
    }
    
    // Validasi tipe file
    if (!in_array($file_ext, $allowed_exts) || !in_array($file["type"], $allowed_types)) {
        header("Location: pembayaran.php?status=error_type");
        exit();
    }
    
    // Proses upload bukti transfer
    $target_dir = "../uploads/transfers/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    // Sanitasi nama file: ganti spasi dengan underscore
    $safe_name = preg_replace('/\s+/', '_', basename($file["name"]));
    $file_name = time() . "_" . $safe_name;
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        // Update record pembayaran yang sudah ada dengan file bukti transfer
        $stmt = $conn->prepare("UPDATE pembayaran SET file_bukti_transfer = ?, nominal = ?, status_pembayaran = 'Menunggu Kuitansi' WHERE id_pengadaan = ? AND status_pembayaran = 'Belum Bayar'");
        $stmt->bind_param("sss", $file_name, $nominal, $id_pengadaan);
        
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