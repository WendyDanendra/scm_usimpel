<?php
session_start();
require_once '../config.php';
require_once '../helpers.php'; // Panggil helper

if ($_SESSION['jabatan'] != 'Inventory & Purchasing Officer') exit('Akses Ditolak.');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file_invoice"])) {
    $id_pengajuan = $_POST['id_pengajuan'];
    // ... (kode validasi dan upload file yang sama seperti sebelumnya) ...
    $target_dir = "../uploads/invoices/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    $file_name = time() . "_" . basename($_FILES["file_invoice"]["name"]);
    $target_file = $target_dir . $file_name;
    if (move_uploaded_file($_FILES["file_invoice"]["tmp_name"], $target_file)) {
        // Generate ID baru
        $new_id = generate_id('PGDN', 'pengadaan_barang', 'id_pengadaan');
        
        $stmt_insert = $conn->prepare("INSERT INTO pengadaan_barang (id_pengadaan, id_pengajuan, tanggal_pengadaan, file_invoice, status_pengadaan) VALUES (?, ?, NOW(), ?, 'Invoice Diunggah')");
        // Tipe data kedua ID 's'
        $stmt_insert->bind_param("sss", $new_id, $id_pengajuan, $file_name);
        
        if($stmt_insert->execute()){
            // Auto-generate laporan pengadaan
            $id_laporan = generate_id('LPGN', 'laporan_pengadaan', 'id_laporan_pengadaan');
            $stmt_laporan = $conn->prepare("INSERT INTO laporan_pengadaan (id_laporan_pengadaan, id_pengadaan, tanggal_laporan) VALUES (?, ?, CURDATE())");
            $stmt_laporan->bind_param("ss", $id_laporan, $new_id);
            $stmt_laporan->execute();
            $stmt_laporan->close();
            
            header("Location: pengadaan_barang.php?status=upload_success");
        } else {
            unlink($target_file);
            header("Location: pengadaan_barang.php?status=error");
        }
    }
} else {
    header("Location: pengadaan_barang.php");
}
?>