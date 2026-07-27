<?php
require_once '../config.php';
header('Content-Type: application/json');

$id_barang = $_GET['id_barang'] ?? 0;

if ($id_barang) {
    $stmt = $conn->prepare("SELECT s.id_supplier, s.nama_supplier 
                            FROM barang b
                            JOIN supplier s ON b.id_supplier = s.id_supplier
                            WHERE b.id_barang = ?");
    $stmt->bind_param("s", $id_barang);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        echo json_encode($result);
    } else {
        echo json_encode(['error' => 'Supplier tidak ditemukan']);
    }
} else {
    echo json_encode(['error' => 'ID Barang tidak valid']);
}
?>