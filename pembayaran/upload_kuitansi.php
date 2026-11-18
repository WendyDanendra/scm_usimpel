<?php
session_start();
require_once '../config.php';
require_once '../helpers.php'; // Tambahkan helpers

if ($_SESSION['jabatan'] != 'Finance & Billing Officer') exit('Akses Ditolak.');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file_kuitansi"])) {
    $id_pembayaran = $_POST['id_pembayaran'];

    $file = $_FILES["file_kuitansi"];
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

    // Proses upload kuitansi
    $target_dir = "../uploads/receipts/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    // Sanitasi nama file: ganti spasi dengan underscore
    $safe_name = preg_replace('/\s+/', '_', basename($file["name"]));
    $file_name = time() . "_" . $safe_name;
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        $conn->begin_transaction();
        try {
            // Ambil id_pengadaan untuk anchor
            $stmt_get_id = $conn->prepare("SELECT id_pengadaan FROM pembayaran WHERE id_pembayaran = ?");
            $stmt_get_id->bind_param("s", $id_pembayaran);
            $stmt_get_id->execute();
            $result_id = $stmt_get_id->get_result();
            $row_id = $result_id->fetch_assoc();
            $id_pengadaan = $row_id['id_pengadaan'];
            
            // Update record pembayaran yang sudah ada
            $stmt = $conn->prepare("UPDATE pembayaran SET file_kuitansi = ?, status_pembayaran = 'Lunas' WHERE id_pembayaran = ?");
            $stmt->bind_param("ss", $file_name, $id_pembayaran);
            $stmt->execute();
            
            // Auto-update status pengadaan menjadi 'Sudah Dibayar'
            $stmt2 = $conn->prepare("UPDATE pengadaan_barang SET status_pengadaan = 'Sudah Dibayar' WHERE id_pengadaan = ?");
            $stmt2->bind_param("s", $id_pengadaan);
            $stmt2->execute();
            
            // Auto-generate laporan pengeluaran
            $id_laporan_pengeluaran = generate_id('LPLR', 'laporan_pengeluaran', 'id_laporan_pengeluaran');
            $stmt_laporan = $conn->prepare("INSERT INTO laporan_pengeluaran (id_laporan_pengeluaran, id_pembayaran, tanggal_laporan) VALUES (?, ?, CURDATE())");
            $stmt_laporan->bind_param("ss", $id_laporan_pengeluaran, $id_pembayaran);
            $stmt_laporan->execute();
            $stmt_laporan->close();
            
            // Log untuk debugging (opsional - bisa dihapus di production)
            error_log("Auto-update: Pembayaran {$id_pembayaran} -> Pengadaan {$id_pengadaan} status changed to 'Sudah Dibayar'");
            
            $conn->commit();
            header("Location: pembayaran.php?status=receipt_success");
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            unlink($target_file); // Hapus file jika DB error
            header("Location: pembayaran.php?status=error");
        }
    } else {
        header("Location: pembayaran.php?status=error");
    }
}
?>