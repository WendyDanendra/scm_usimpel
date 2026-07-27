<?php
session_start();
require_once '../config.php';

// Proteksi halaman, hanya Kepala Divisi yang bisa melakukan persetujuan
if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Kepala Divisi Produk & Pengadaan') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_pengajuan = $_POST['id_pengajuan'];
    $keputusan = $_POST['keputusan'];
    $catatan = $_POST['catatan'];
    
    // Jika catatan kosong, berikan teks default
    if (empty(trim($catatan))) {
        $catatan = "Tidak ada catatan";
    }

    $conn->begin_transaction();
    try {
        // Cek apakah kolom catatan_keputusan sudah ada, jika belum buat
        $check_column = $conn->query("SHOW COLUMNS FROM pengajuan_barang LIKE 'catatan_keputusan'");
        if ($check_column->num_rows == 0) {
            $conn->query("ALTER TABLE pengajuan_barang ADD COLUMN catatan_keputusan TEXT NULL");
        }
        
        // Update pengajuan_barang dengan status, catatan, dan tanggal keputusan
        $stmt_update = $conn->prepare("UPDATE pengajuan_barang SET status_pengajuan = ?, catatan_keputusan = ?, tanggal_keputusan = NOW() WHERE id_pengajuan = ? AND status_pengajuan = 'Diajukan'");
        
        if (!$stmt_update) {
            die("Error preparing statement: " . $conn->error);
        }
        
        // Status yang benar: 'Disetujui' atau 'Ditolak' 
        $status_final = ($keputusan === 'Disetujui') ? 'Disetujui' : 'Ditolak';
        $stmt_update->bind_param("sss", $status_final, $catatan, $id_pengajuan);
        
        if (!$stmt_update->execute()) {
            die("Error executing statement: " . $stmt_update->error);
        }

        if ($stmt_update->affected_rows > 0) {
            $conn->commit();
            header("Location: persetujuan_pengajuan.php?status=success");
            exit();
        } else {
            $conn->rollback();
            header("Location: persetujuan_pengajuan.php?status=error");
            exit();
        }
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        header("Location: persetujuan_pengajuan.php?status=error");
        exit();
    }

} else {
    // Jika diakses langsung tanpa POST, redirect
    header("Location: persetujuan_pengajuan.php");
    exit();
}
$conn->close();
?>