<?php
session_start();
require_once '../config.php';

// Proteksi halaman - pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$status_message = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success_create':
            $status_message = '<div class="status-message success"><i class="fas fa-check-circle"></i> Data pengguna berhasil ditambahkan.</div>';
            break;
        case 'success_update':
            $status_message = '<div class="status-message success"><i class="fas fa-check-circle"></i> Data pengguna berhasil diperbarui.</div>';
            break;
        case 'success_delete':
            $status_message = '<div class="status-message success"><i class="fas fa-check-circle"></i> Data pengguna berhasil dihapus.</div>';
            break;
        case 'error_self_delete':
            $status_message = '<div class="status-message error"><i class="fas fa-exclamation-triangle"></i> Gagal! Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.</div>';
            break;
        case 'error_delete':
            $status_message = '<div class="status-message error"><i class="fas fa-exclamation-circle"></i> Gagal menghapus data pengguna.</div>';
            break;
    }
}

// Query mengambil data pengguna
$sql = "SELECT id_user, nama_user, jabatan, username FROM user ORDER BY id_user ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .table-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px 15px; border: 1px solid #e1e1e1; text-align: center; vertical-align: middle; }
        .data-table thead th { background-color: #f8f9fa; font-weight: 600; text-align: center; }
        .data-table td:nth-child(2), .data-table td:nth-child(3) { text-align: left; }
        .action-buttons a { color: white; padding: 6px 12px; border-radius: 5px; text-decoration: none; margin-right: 5px; font-size: 0.85rem; display: inline-block; }
        .btn-edit { background-color: var(--warning); }
        .btn-edit:hover { background-color: #f39c12; }
        .btn-delete { background-color: var(--danger); }
        .btn-delete:hover { background-color: #c0392b; }
        .btn-tambah { background-color: var(--secondary); color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; font-weight: 600; }
        .btn-tambah:hover { background-color: #2980b9; }
        .status-message { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .status-message.success { background-color: rgba(39, 174, 96, 0.2); color: #27ae60; border: 1px solid #27ae60; }
        .status-message.error { background-color: rgba(231, 76, 60, 0.2); color: var(--danger); border: 1px solid var(--danger); }
        .badge-jabatan { background: #eef2f7; color: #334155; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: 500; }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <div class="table-container">
                <div class="table-header">
                    <h2 class="dashboard-title" style="margin: 0;"><i class="fas fa-users-cog"></i> Kelola Pengguna</h2>
                    <a href="create_user.php" class="btn-tambah"><i class="fas fa-user-plus"></i> Tambah Pengguna</a>
                </div>
                <?php echo $status_message; ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID User</th>
                            <th>Nama Pengguna</th>
                            <th>Jabatan</th>
                            <th>Username</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row["id_user"]); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row["nama_user"]); ?></td>
                                    <td><span class="badge-jabatan"><?php echo htmlspecialchars($row["jabatan"]); ?></span></td>
                                    <td><code><?php echo htmlspecialchars($row["username"]); ?></code></td>
                                    <td class="action-buttons">
                                        <a href="update_user.php?id=<?php echo urlencode($row["id_user"]); ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="delete_user.php?id=<?php echo urlencode($row["id_user"]); ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus pengguna <?php echo htmlspecialchars($row["nama_user"]); ?>?');"><i class="fas fa-trash"></i> Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center;">Tidak ada data pengguna.</td></tr>
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
