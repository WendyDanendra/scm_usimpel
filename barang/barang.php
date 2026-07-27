<?php
session_start();
require_once '../config.php';

// Proteksi halaman
$jabatan_lower = strtolower(trim($_SESSION['jabatan'] ?? ''));
if (!isset($_SESSION['jabatan']) || !in_array($jabatan_lower, ['inventory & purchasing officer', 'administrator', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

// Logika untuk menampilkan pesan status
$status_message = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success_create':
            $status_message = '<div class="status-message success">Data barang berhasil ditambahkan.</div>';
            break;
        case 'success_update':
            $status_message = '<div class="status-message success">Data barang berhasil diperbarui.</div>';
            break;
        case 'success_delete':
            $status_message = '<div class="status-message success">Data barang berhasil dihapus.</div>';
            break;
        case 'error_constraint':
            $status_message = '<div class="status-message error">Gagal menghapus! Barang masih digunakan di tabel lain (misal: Pengajuan atau Stok).</div>';
            break;
    }
}

// Query untuk mengambil data barang beserta nama suppliernya
$sql = "SELECT b.*, s.nama_supplier 
        FROM barang b 
        LEFT JOIN supplier s ON b.id_supplier = s.id_supplier 
        ORDER BY b.id_barang DESC";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .table-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px 15px; border: 1px solid #e1e1e1; text-align: center; vertical-align: middle; }
        .data-table thead th { background-color: #f8f9fa; font-weight: 600; text-align: center; }
        .data-table td:nth-child(2), .data-table td:nth-child(7) { text-align: left; } /* Produk dan Supplier columns */
        .action-buttons a { color: white; padding: 6px 12px; border-radius: 5px; text-decoration: none; margin-right: 5px; font-size: 0.85rem; }
        .btn-edit { background-color: var(--warning); }
        .btn-delete { background-color: var(--danger); }
        .btn-tambah { background-color: var(--secondary); color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; }
        .status-message { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .status-message.success { background-color: rgba(39, 174, 96, 0.2); color: #27ae60; border: 1px solid #27ae60; }
        .status-message.error { background-color: rgba(231, 76, 60, 0.2); color: var(--danger); border: 1px solid var(--danger); }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <div class="table-container">
                <div class="table-header">
                    <h2 class="dashboard-title" style="margin: 0;"><i class="fas fa-box"></i> Data Barang</h2>
                    <a href="create_barang.php" class="btn-tambah"><i class="fas fa-plus"></i> Tambah Barang</a>
                </div>
                <?php echo $status_message; ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produk</th>
                            <th>Merek</th>
                            <th>Satuan</th>
                            <th>Harga</th>
                            <th>Lead Time (Hari)</th>
                            <th>Supplier</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row["id_barang"]; ?></td>
                                    <td><?php echo htmlspecialchars($row["produk"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["merek"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["satuan"]); ?></td>
                                    <td>Rp <?php echo number_format($row["harga"], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($row["lead_time"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["nama_supplier"] ?? 'N/A'); ?></td>
                                    <td class="action-buttons">
                                        <a href="update_barang.php?id=<?php echo $row["id_barang"]; ?>" class="btn-edit">Edit</a>
                                        <a href="delete_barang.php?id=<?php echo $row["id_barang"]; ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus?');">Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align: center;">Tidak ada data barang.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>