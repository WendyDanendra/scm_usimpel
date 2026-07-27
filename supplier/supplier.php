<?php
session_start();

// Proteksi halaman
$jabatan_lower = strtolower(trim($_SESSION['jabatan'] ?? ''));
if (!isset($_SESSION['jabatan']) || !in_array($jabatan_lower, ['inventory & purchasing officer', 'administrator', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

// Sertakan config database dan ambil data
require_once '../config.php';
$result = $conn->query("SELECT * FROM supplier ORDER BY id_supplier DESC");
// TAMBAHKAN BLOK INI UNTUK MENANGANI PESAN STATUS
$status_message = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success_create':
            $status_message = '<div class="status-message success">Data supplier berhasil ditambahkan.</div>';
            break;
        case 'success_update':
            $status_message = '<div class="status-message success">Data supplier berhasil diperbarui.</div>';
            break;
        case 'success_delete':
            $status_message = '<div class="status-message success">Data supplier berhasil dihapus.</div>';
            break;
        case 'error_constraint':
            // Ini pesan error baru kita
            $status_message = '<div class="status-message error">Gagal menghapus! Supplier masih digunakan oleh data Barang atau Pengajuan.</div>';
            break;
        case 'error_delete':
            $status_message = '<div class="status-message error">Gagal menghapus data supplier.</div>';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Supplier - SCM</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">

    <style>
        .table-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 12px 15px;
            border: 1px solid #e1e1e1;
            text-align: left;
            vertical-align: middle;
        }
        .data-table thead th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-align: center;
        }
        .data-table tbody tr:nth-of-type(even) {
            background-color: #fdfdfd;
        }
        .data-table td:nth-child(6) { text-align: center; } /* Aksi column */
        .action-buttons a { color: white; padding: 6px 12px; border-radius: 5px; text-decoration: none; margin-right: 10px; font-size: 0.85rem; }

        .btn-edit { background-color: var(--warning); }
        .btn-delete { background-color: var(--danger); }
        .btn-tambah {
            background-color: var(--secondary);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        .status-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 1rem;
        }
        .status-message.success {
            background-color: rgba(39, 174, 96, 0.2);
            color: #27ae60;
            border: 1px solid #27ae60;
        }
        .status-message.error {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
    </style>
</head>
<body>
    
    <?php include '../components/sidebar.php'; ?>
    
    <div class="main-content">
        
        <?php include '../components/header.php'; ?>
        
        <main class="content">
            <div class="table-container">
                <div class="table-header">
                    <h2 class="dashboard-title" style="margin: 0;"><i class="fas fa-building"></i> Data Supplier</h2>
                    <a href="create_supplier.php" class="btn-tambah"><i class="fas fa-plus"></i> Tambah Supplier</a>
                </div>
                <?php echo $status_message; // TAMPILKAN PESAN DI SINI ?>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Supplier</th>
                            <th>Alamat</th>
                            <th>Kontak</th>
                            <th>Email</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row["id_supplier"]; ?></td>
                                    <td><?php echo htmlspecialchars($row["nama_supplier"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["alamat"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["kontak"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["email"]); ?></td>
                                    <td class="action-buttons">
                                        <a href="update_supplier.php?id=<?php echo $row["id_supplier"]; ?>" class="btn-edit" >Edit</a>
                                        <a href="delete_supplier.php?id=<?php echo $row["id_supplier"]; ?>" class="btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?');">Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Tidak ada data supplier.</td>
                            </tr>
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
// Tutup koneksi
$conn->close();
?>