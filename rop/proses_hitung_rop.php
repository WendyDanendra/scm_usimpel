<?php
session_start();
require_once '../config.php';
require_once '../helpers.php';

// Proteksi halaman
if ($_SESSION['jabatan'] != 'Inventory & Purchasing Officer') {
    exit('Akses ditolak.');
}

$conn->begin_transaction();

try {
    // 1. Ambil SEMUA barang dari data master
    $result_barang = $conn->query("SELECT id_barang, lead_time FROM barang");

    while ($barang = $result_barang->fetch_assoc()) {
        $id_barang = $barang['id_barang'];
        $lead_time = (int) $barang['lead_time'];

        // 2. Cek apakah barang ini sudah ada di tabel stok, jika belum, buat record baru
        $stmt_cek = $conn->prepare("SELECT stok_tersedia, permintaan_harian FROM stok_barang WHERE id_barang = ?");
        $stmt_cek->bind_param("s", $id_barang);
        $stmt_cek->execute();
        $result_stok = $stmt_cek->get_result();
        
        if ($result_stok->num_rows > 0) {
            $row_stok = $result_stok->fetch_assoc();
            $stok_tersedia = (int) $row_stok['stok_tersedia'];
        } else {
            // Jika belum ada, buat record baru dengan stok 0
            $id_stok = generate_id('STOK', 'stok_barang', 'id_stok');
            $stmt_insert = $conn->prepare("INSERT INTO stok_barang (id_stok, id_barang, stok_tersedia, rop, safety_stock, status_stok, permintaan_harian) VALUES (?, ?, 0, 0, 0, 'Belum Ada Data', 0)");
            $stmt_insert->bind_param("ss", $id_stok, $id_barang);
            $stmt_insert->execute();
            $stok_tersedia = 0;
        }
        $stmt_cek->close();

        // 3. Hitung permintaan harian yang BENAR: Total Unit ÷ Jumlah Hari (bukan per transaksi)
        $stmt_permintaan = $conn->prepare("
            SELECT 
                CASE 
                    WHEN COUNT(DISTINCT DATE(tanggal)) > 0 
                    THEN SUM(jumlah) / COUNT(DISTINCT DATE(tanggal))
                    ELSE 0 
                END as avg_keluar_per_hari
            FROM log_stok 
            WHERE id_barang = ? 
            AND jenis_log = 'keluar' 
            AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt_permintaan->bind_param("s", $id_barang);
        $stmt_permintaan->execute();
        $result_permintaan = $stmt_permintaan->get_result();
        $row_permintaan = $result_permintaan->fetch_assoc();
        $permintaan_harian = (float) ($row_permintaan['avg_keluar_per_hari'] ?? 0);
        $stmt_permintaan->close();

        // 4. Hitung Safety Stock (SS) dan ROP hanya jika ada permintaan harian
        if ($permintaan_harian > 0) {
            // Safety Stock = 20% dari (permintaan harian × lead time)
            $safety_stock = ceil(0.20 * ($permintaan_harian * $lead_time));
            
            // ROP = (permintaan harian × lead time) + safety stock
            $rop = ceil(($permintaan_harian * $lead_time) + $safety_stock);
            
            // Tentukan Status Stok
            if ($stok_tersedia <= $rop) {
                $status_stok = 'Dibawah ROP';
            } else {
                $status_stok = 'Aman';
            }
        } else {
            // Jika tidak ada permintaan harian, set semua ke 0
            $safety_stock = 0;
            $rop = 0;
            $status_stok = 'Belum Ada Data';
        }

        // 7. Update tabel stok_barang - SEKARANG update permintaan_harian juga
        $stmt_update = $conn->prepare("UPDATE stok_barang SET permintaan_harian = ?, safety_stock = ?, rop = ?, status_stok = ? WHERE id_barang = ?");
        $stmt_update->bind_param("diiss", $permintaan_harian, $safety_stock, $rop, $status_stok, $id_barang);

        if (!$stmt_update->execute()) {
            throw new mysqli_sql_exception("Gagal update data untuk barang ID: " . $id_barang);
        }
        $stmt_update->close();

    }

    $conn->commit();
    header("Location: perhitungan_rop.php?status=success");

} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    // Untuk debugging, catat error ke log server
    error_log("ROP Calculation Failed: " . $exception->getMessage());
    header("Location: perhitungan_rop.php?status=error");
}

$conn->close();
?>