<?php
require_once '../config.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Ambil parameter id_barang dari GET
$id_barang = $_GET['id_barang'] ?? '';

if (empty($id_barang)) {
    echo json_encode(['error' => 'ID barang tidak ditemukan']);
    exit;
}

try {
    // Query untuk mengambil data transaksi barang keluar dalam 30 hari terakhir
    $sql = "SELECT tanggal, jumlah 
            FROM log_stok 
            WHERE id_barang = ? 
            AND jenis_log = 'keluar' 
            AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY tanggal DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_barang);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transaksi = [];
    while ($row = $result->fetch_assoc()) {
        $transaksi[] = [
            'tanggal' => date('d-m-Y', strtotime($row['tanggal'])),
            'jumlah' => $row['jumlah']
        ];
    }
    
    // Return data dalam format JSON
    echo json_encode([
        'success' => true,
        'transaksi' => $transaksi,
        'total_transaksi' => count($transaksi)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>