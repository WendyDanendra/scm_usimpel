<?php
session_start();
require_once '../config.php';
require_once '../helpers.php'; // Tambahkan ini untuk fungsi generate_id()

if ($_SESSION['jabatan'] != 'Inventory & Purchasing Officer') exit('Akses Ditolak.');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $jenis_log = $_POST['jenis_log']; // 'masuk' atau 'keluar'
    $new_id = generate_id('LOGS', 'log_stok', 'id_log');
    $id_barang = $_POST['id_barang'];
    $jumlah = $_POST['jumlah'];
    $tanggal = date("Y-m-d");

    // Tentukan keterangan berdasarkan jenis log
    if ($jenis_log == 'masuk') {
        $id_pengadaan = $_POST['id_pengadaan'];
        
        // Ambil informasi lebih detail untuk keterangan
        $stmt_info = $conn->prepare("
            SELECT pb.id_pengajuan, b.produk, b.merek, s.nama_supplier
            FROM pengadaan_barang pd
            JOIN pengajuan_barang pb ON pd.id_pengajuan = pb.id_pengajuan
            JOIN barang b ON pb.id_barang = b.id_barang
            JOIN supplier s ON pb.id_supplier = s.id_supplier
            WHERE pd.id_pengadaan = ?
        ");
        $stmt_info->bind_param("s", $id_pengadaan);
        $stmt_info->execute();
        $info_result = $stmt_info->get_result();
        
        if ($info_result->num_rows > 0) {
            $info = $info_result->fetch_assoc();
            // Keterangan pendek dan informatif
            $keterangan = "Masuk dari " . $info['nama_supplier'] . " (" . $id_pengadaan . ")";
        } else {
            $keterangan = "Masuk dari pengadaan " . $id_pengadaan;
        }
    } else {
        $id_pengadaan = null; // Tidak ada ID pengadaan untuk barang keluar
        $keterangan = $_POST['keterangan'];
    }

    $conn->begin_transaction();
    try {
        // 1. Catat ke tabel log_stok (DENGAN KOLOM id_pengadaan)
        $stmt_log = $conn->prepare("INSERT INTO log_stok (id_log, id_barang, tanggal, jenis_log, jumlah, keterangan, id_pengadaan) VALUES (?, ?, ?, ?, ?, ?, ?)");
        // Tipe data: id_log(s), id_barang(s), tanggal(s), jenis_log(s), jumlah(i), keterangan(s), id_pengadaan(s)
        $stmt_log->bind_param("ssssiss", $new_id, $id_barang, $tanggal, $jenis_log, $jumlah, $keterangan, $id_pengadaan);
        $stmt_log->execute();

        // 2. Update tabel stok_barang
        // Cek jika stok untuk barang ini sudah ada
        $stmt_cek = $conn->prepare("SELECT id_stok, stok_tersedia FROM stok_barang WHERE id_barang = ?");
        $stmt_cek->bind_param("s", $id_barang);
        $stmt_cek->execute();
        $cek_stok = $stmt_cek->get_result();
        
        if ($cek_stok->num_rows > 0) {
            $row_stok = $cek_stok->fetch_assoc();
            $stok_saat_ini = $row_stok['stok_tersedia'];
            
            if ($jenis_log == 'masuk') {
                $stmt_stok = $conn->prepare("UPDATE stok_barang SET stok_tersedia = stok_tersedia + ? WHERE id_barang = ?");
                $stmt_stok->bind_param("is", $jumlah, $id_barang);
            } else {
                // Validasi stok untuk barang keluar
                if ($stok_saat_ini <= 0) {
                    throw new Exception("Barang saat ini tidak memiliki stok atau stok habis. Tidak dapat mencatat barang keluar.");
                }
                
                if ($jumlah > $stok_saat_ini) {
                    throw new Exception("Jumlah yang diminta ($jumlah) melebihi stok tersedia ($stok_saat_ini). Silakan kurangi jumlahnya.");
                }
                
                // Barang keluar: hanya update stok (ROP akan dihitung otomatis setelah log disimpan)
                $stmt_stok = $conn->prepare("UPDATE stok_barang SET stok_tersedia = stok_tersedia - ? WHERE id_barang = ?");
                $stmt_stok->bind_param("is", $jumlah, $id_barang);
            }
        } else {
            // Jika belum ada record stok, buat baru (untuk barang masuk pertama kali)
            if ($jenis_log == 'masuk') {
                $id_stok = generate_id('STOK', 'stok_barang', 'id_stok');
                $stmt_stok = $conn->prepare("INSERT INTO stok_barang (id_stok, id_barang, stok_tersedia, rop, safety_stock, status_stok, permintaan_harian) VALUES (?, ?, ?, 0, 0, 'Belum Ada Data', 0)");
                $stmt_stok->bind_param("ssi", $id_stok, $id_barang, $jumlah);
            } else {
                // Error: tidak bisa barang keluar jika belum ada stok
                throw new Exception("Barang belum pernah masuk ke stok sebelumnya. Tidak dapat mencatat barang keluar.");
            }
        }
        $stmt_stok->execute();

        // ** PERBAIKAN: Recalculate stok berdasarkan seluruh histori log **
        $stok_final = recalculateStok($conn, $id_barang);
        
        // Update ROP secara real-time untuk semua jenis log
        updateRopRealTime($conn, $id_barang);

        $conn->commit();
        
        // Log untuk debugging
        error_log("SCM: Stok berhasil diupdate untuk barang $id_barang. Stok final: $stok_final");
        
        // Tambahkan timestamp untuk memaksa refresh dan mencegah cache
        $timestamp = time();
        header("Location: pengelolaan_stok.php?status=success&t=$timestamp");

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $timestamp = time();
        header("Location: pengelolaan_stok.php?status=error&msg=" . urlencode("Database error: " . $exception->getMessage()) . "&t=$timestamp");
    } catch (Exception $e) {
        $conn->rollback();
        $timestamp = time();
        header("Location: pengelolaan_stok.php?status=error&msg=" . urlencode($e->getMessage()) . "&t=$timestamp");
    }

} else {
    header("Location: pengelolaan_stok.php");
}
$conn->close();
?>