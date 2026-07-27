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
            $status_message = '<div class="status-message success">Pengajuan barang berhasil dibuat.</div>';
            break;
        case 'success_update':
            $status_message = '<div class="status-message success">Pengajuan barang berhasil diperbarui.</div>';
            break;
        case 'success_delete':
            $status_message = '<div class="status-message success">Pengajuan barang berhasil dibatalkan.</div>';
            break;
        case 'error_approved':
             $status_message = '<div class="status-message error">Gagal! Pengajuan yang sudah disetujui atau diproses tidak dapat diubah/dihapus.</div>';
            break;
    }
}

// Filter tanggal
$start_date = $_POST['start_date'] ?? date('Y-m-01');
$end_date = $_POST['end_date'] ?? date('Y-m-t');

// Query untuk mengambil data pengajuan dengan filter tanggal
$sql = "SELECT 
            pb.id_pengajuan, 
            pb.tanggal_pengajuan, 
            pb.id_barang,
            b.produk, 
            b.merek, 
            pb.jumlah_diajukan, 
            pb.id_supplier,
            s.nama_supplier, 
            pb.status_pengajuan
        FROM pengajuan_barang pb
        LEFT JOIN barang b ON pb.id_barang = b.id_barang
        LEFT JOIN supplier s ON pb.id_supplier = s.id_supplier
        WHERE pb.tanggal_pengajuan BETWEEN ? AND ?
        ORDER BY pb.tanggal_pengajuan DESC, pb.id_pengajuan DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Debug: Tampilkan error jika ada
if (!$result) {
    echo "Error: " . $conn->error;
}

// Debug: Tampilkan jumlah row yang ditemukan
echo "<!-- Debug: Jumlah data pengajuan ditemukan: " . ($result ? $result->num_rows : 0) . " -->";

// Debug: Tampilkan data mentah untuk debugging
if ($result && $result->num_rows > 0) {
    $result->data_seek(0); // Reset pointer ke awal
    $debug_row = $result->fetch_assoc();
    echo "<!-- Debug data: " . print_r($debug_row, true) . " -->";
    $result->data_seek(0); // Reset lagi untuk loop utama
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengajuan Barang - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .table-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px 15px; border: 1px solid #e1e1e1; text-align: center; }
        .data-table thead th { background-color: #f8f9fa; text-align: center; }
        .data-table td:nth-child(3), .data-table td:nth-child(5) { text-align: left; } /* Nama Barang dan Supplier columns */
        .action-buttons { display: flex; gap: 5px; }
        .action-buttons a { 
            color: white; 
            padding: 8px 12px; 
            border-radius: 5px; 
            text-decoration: none; 
            font-size: 0.85rem; 
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }
        .btn-edit { background-color: var(--warning); }
        .btn-edit:hover { background-color: #f39c12; }
        .btn-delete { background-color: var(--danger); }
        .btn-delete:hover { background-color: #c0392b; }
        .btn-tambah { background-color: var(--secondary); color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; }
        .status-badge { padding: 5px 12px; border-radius: 15px; font-size: 0.8rem; color: white; }
        .status-diajukan { background-color: var(--warning); }
        .status-disetujui { background-color: var(--success); }
        .status-ditolak { background-color: var(--danger); }
        .status-diproses { background-color: var(--primary); }
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
                    <h2 class="dashboard-title" style="margin: 0;"><i class="fas fa-file-signature"></i> Pengajuan Barang</h2>
                    <a href="create_pengajuan.php" class="btn-tambah"><i class="fas fa-plus"></i> Buat Pengajuan</a>
                </div>
                
                <!-- Filter Tanggal -->
                <div class="filter-section" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <form action="pengajuan_barang.php" method="POST">
                        <div style="display: flex; gap: 15px; align-items: flex-end;">
                            <div style="flex: 1;">
                                <label for="start_date" style="display: block; margin-bottom: 5px;">Dari Tanggal</label>
                                <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div style="flex: 1;">
                                <label for="end_date" style="display: block; margin-bottom: 5px;">Sampai Tanggal</label>
                                <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <button type="submit" style="padding: 10px 20px; border-radius: 5px; cursor: pointer; border: none; background: var(--secondary); color: white;">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php echo $status_message; ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tanggal</th>
                            <th>Nama Barang</th>
                            <th>Jumlah</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row["id_pengajuan"]); ?></td>
                                    <td><?php echo date("d-m-Y", strtotime($row["tanggal_pengajuan"])); ?></td>
                                    <td><?php echo htmlspecialchars(($row["produk"] ? $row["produk"] . ' - ' . $row["merek"] : 'Barang tidak ditemukan')); ?></td>
                                    <td><?php echo htmlspecialchars($row["jumlah_diajukan"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["nama_supplier"]); ?></td>
                                    <td>
                                        <?php
                                        $status_class = 'status-' . strtolower(str_replace(' ', '-', $row["status_pengajuan"]));
                                        echo '<span class="status-badge ' . $status_class . '">' . htmlspecialchars($row["status_pengajuan"]) . '</span>';
                                        ?>
                                    </td>
                                    <td class="action-buttons">
                                        <?php if ($row["status_pengajuan"] == 'Diajukan'): ?>
                                            <a href="update_pengajuan.php?id=<?php echo $row["id_pengajuan"]; ?>" class="btn-edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="delete_pengajuan.php?id=<?php echo $row["id_pengajuan"]; ?>" class="btn-delete" onclick="return confirm('Yakin ingin membatalkan pengajuan ini?');">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #6c757d; font-style: italic;">Tidak dapat diubah</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center;">Belum ada data pengajuan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>