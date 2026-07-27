<?php
session_start();
require_once '../config.php';

$jabatan_lower = strtolower(trim($_SESSION['jabatan'] ?? ''));
if (!isset($_SESSION['jabatan']) || !in_array($jabatan_lower, ['inventory & purchasing officer', 'administrator', 'admin'])) {
    exit('Akses Ditolak.');
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["new_invoice"])) {
    $id_pengadaan = $_POST['id_pengadaan'];

    // 1. Ambil nama file lama untuk dihapus nanti
    $stmt_old = $conn->prepare("SELECT file_invoice FROM pengadaan_barang WHERE id_pengadaan = ?");
    $stmt_old->bind_param("s", $id_pengadaan);
    $stmt_old->execute();
    $old_res = $stmt_old->get_result()->fetch_assoc();
    $old_file_name = $old_res ? $old_res['file_invoice'] : null;
    $stmt_old->close();
    
    // 2. Proses upload file baru
    $target_dir = "../uploads/invoices/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $new_file_name = time() . "_" . basename($_FILES["new_invoice"]["name"]);
    $target_file = $target_dir . $new_file_name;

    if (move_uploaded_file($_FILES["new_invoice"]["tmp_name"], $target_file)) {
        // 3. Update database dengan nama file baru
        $stmt_update = $conn->prepare("UPDATE pengadaan_barang SET file_invoice = ? WHERE id_pengadaan = ?");
        $stmt_update->bind_param("ss", $new_file_name, $id_pengadaan);
        
        if($stmt_update->execute()){
            // 4. Hapus file lama jika ada
            if ($old_file_name && file_exists($target_dir . $old_file_name)) {
                unlink($target_dir . $old_file_name);
            }
            header("Location: pengadaan_barang.php?status=update_success");
        } else {
            unlink($target_file); // Hapus file baru jika db error
            header("Location: pengadaan_barang.php?status=error");
        }
    } else {
        header("Location: pengadaan_barang.php?status=error");
    }
}
?>