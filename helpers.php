<?php
// helpers.php

function generate_id($prefix, $table, $column) {
    // Pastikan file config.php disertakan atau gunakan koneksi global
    global $conn;
    
    // Ambil ID terakhir dari tabel dengan query sederhana
    $query = "SELECT $column FROM $table WHERE $column LIKE '$prefix-%' ORDER BY $column DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_id = $row[$column];
        
        // Ekstrak angka dari ID terakhir (skip prefix + delimiter)
        $number_str = substr($last_id, strlen($prefix) + 1);
        $last_number = intval($number_str);
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    // Format ID dengan padding zero (5 digit untuk semua)
    return $prefix . '-' . str_pad($new_number, 5, '0', STR_PAD_LEFT);
}

// Fungsi untuk mencatat log stok
function catat_log_stok($conn, $id_barang, $jenis_log, $jumlah, $keterangan) {
    $id_log = generate_id('LOG', 'log_stok', 'id_log');
    
    $stmt = $conn->prepare("INSERT INTO log_stok (id_log, id_barang, jenis_log, jumlah, tanggal, keterangan) VALUES (?, ?, ?, ?, CURDATE(), ?)");
    $stmt->bind_param("sssis", $id_log, $id_barang, $jenis_log, $jumlah, $keterangan);
    $stmt->execute();
    $stmt->close();
}

// Fungsi untuk memperbarui stok barang
function update_stok_barang($conn, $id_barang, $jumlah_perubahan, $jenis_log) {
    // Update stok di tabel stok_barang
    if ($jenis_log == 'masuk') {
        $sql = "UPDATE stok_barang SET stok_tersedia = stok_tersedia + ? WHERE id_barang = ?";
    } else {
        $sql = "UPDATE stok_barang SET stok_tersedia = stok_tersedia - ? WHERE id_barang = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $jumlah_perubahan, $id_barang);
    $stmt->execute();
    $stmt->close();
}

// Fungsi untuk recalculate stok berdasarkan seluruh histori log_stok
function recalculateStok($conn, $id_barang) {
    // Hitung total stok berdasarkan log_stok
    $stmt_calc = $conn->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN jenis_log = 'masuk' THEN jumlah ELSE 0 END), 0) as total_masuk,
            COALESCE(SUM(CASE WHEN jenis_log = 'keluar' THEN jumlah ELSE 0 END), 0) as total_keluar
        FROM log_stok 
        WHERE id_barang = ?
    ");
    $stmt_calc->bind_param("s", $id_barang);
    $stmt_calc->execute();
    $result_calc = $stmt_calc->get_result();
    $row_calc = $result_calc->fetch_assoc();
    $stmt_calc->close();
    
    $stok_seharusnya = $row_calc['total_masuk'] - $row_calc['total_keluar'];
    
    // Update atau insert ke stok_barang
    $stmt_cek = $conn->prepare("SELECT id_stok FROM stok_barang WHERE id_barang = ?");
    $stmt_cek->bind_param("s", $id_barang);
    $stmt_cek->execute();
    $cek_result = $stmt_cek->get_result();
    $stmt_cek->close();
    
    if ($cek_result->num_rows > 0) {
        // Update existing record
        $stmt_update = $conn->prepare("UPDATE stok_barang SET stok_tersedia = ? WHERE id_barang = ?");
        $stmt_update->bind_param("is", $stok_seharusnya, $id_barang);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        // Insert new record
        $id_stok = generate_id('STOK', 'stok_barang', 'id_stok');
        $stmt_insert = $conn->prepare("INSERT INTO stok_barang (id_stok, id_barang, stok_tersedia, rop, safety_stock, status_stok, permintaan_harian) VALUES (?, ?, ?, 0, 0, 'Belum Ada Data', 0)");
        $stmt_insert->bind_param("ssi", $id_stok, $id_barang, $stok_seharusnya);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    
    return $stok_seharusnya;
}

// Fungsi untuk mengupdate ROP secara real-time
function updateRopRealTime($conn, $id_barang) {
    // Ambil lead time dari master barang
    $stmt_barang = $conn->prepare("SELECT lead_time FROM barang WHERE id_barang = ?");
    $stmt_barang->bind_param("s", $id_barang);
    $stmt_barang->execute();
    $result_barang = $stmt_barang->get_result();
    
    if ($result_barang->num_rows == 0) {
        $stmt_barang->close();
        return; // Barang tidak ditemukan
    }
    
    $lead_time = (int) $result_barang->fetch_assoc()['lead_time'];
    $stmt_barang->close();
    
    // Hitung permintaan harian berdasarkan rata-rata barang keluar 30 hari terakhir
    $stmt_demand = $conn->prepare("
        SELECT AVG(jumlah) as avg_keluar 
        FROM log_stok 
        WHERE id_barang = ? 
        AND jenis_log = 'keluar' 
        AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt_demand->bind_param("s", $id_barang);
    $stmt_demand->execute();
    $result_demand = $stmt_demand->get_result();
    $row_demand = $result_demand->fetch_assoc();
    $permintaan_harian = (float) ($row_demand['avg_keluar'] ?? 0);
    $stmt_demand->close();
    
    // Hitung Safety Stock dan ROP hanya jika ada permintaan harian
    if ($permintaan_harian > 0) {
        $safety_stock = ceil(0.20 * ($permintaan_harian * $lead_time));
        $rop = ceil(($permintaan_harian * $lead_time) + $safety_stock);
    } else {
        $safety_stock = 0;
        $rop = 0;
    }
    
    // Ambil stok tersedia untuk menentukan status
    $stmt_stok = $conn->prepare("SELECT stok_tersedia FROM stok_barang WHERE id_barang = ?");
    $stmt_stok->bind_param("s", $id_barang);
    $stmt_stok->execute();
    $result_stok = $stmt_stok->get_result();
    
    if ($result_stok->num_rows > 0) {
        $stok_tersedia = (int) $result_stok->fetch_assoc()['stok_tersedia'];
        
        // Tentukan Status Stok
        if ($permintaan_harian > 0) {
            if ($stok_tersedia <= $rop) {
                $status_stok = 'Dibawah ROP';
            } else {
                $status_stok = 'Aman';
            }
        } else {
            $status_stok = 'Belum Ada Data';
        }
        
        // Update semua data ROP
        $stmt_update = $conn->prepare("UPDATE stok_barang SET permintaan_harian = ?, safety_stock = ?, rop = ?, status_stok = ? WHERE id_barang = ?");
        $stmt_update->bind_param("diiss", $permintaan_harian, $safety_stock, $rop, $status_stok, $id_barang);
        $stmt_update->execute();
        $stmt_update->close();
    }
    
    $stmt_stok->close();
}
?>
