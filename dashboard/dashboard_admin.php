<?php
session_start();
require_once '../config.php';

// Proteksi Halaman Dashboard Admin
$jabatan_lower = strtolower(trim($_SESSION['jabatan'] ?? ''));
if (!isset($_SESSION['jabatan']) || !in_array($jabatan_lower, ['administrator', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

$nama_user = $_SESSION['name'] ?? 'Administrator';

// 1. Metric: Total Users
$res_user = $conn->query("SELECT COUNT(*) as total FROM user");
$total_users = $res_user ? $res_user->fetch_assoc()['total'] : 0;

// 2. Metric: Total Suppliers
$res_supp = $conn->query("SELECT COUNT(*) as total FROM supplier");
$total_suppliers = $res_supp ? $res_supp->fetch_assoc()['total'] : 0;

// 3. Metric: Total Barang
$res_brg = $conn->query("SELECT COUNT(*) as total FROM barang");
$total_barang = $res_brg ? $res_brg->fetch_assoc()['total'] : 0;

// 4. Metric: Total Pengajuan Menunggu Persetujuan
$res_pengajuan = $conn->query("SELECT COUNT(*) as total FROM pengajuan_barang WHERE status_pengajuan = 'Diajukan'");
$total_pending_pengajuan = $res_pengajuan ? $res_pengajuan->fetch_assoc()['total'] : 0;

// 5. Metric: Total Nominal Pembayaran Lunas
$res_bayar = $conn->query("SELECT SUM(nominal) as total FROM pembayaran WHERE status_pembayaran = 'Lunas'");
$total_paid = $res_bayar ? ($res_bayar->fetch_assoc()['total'] ?? 0) : 0;

// Recent Users
$recent_users = $conn->query("SELECT id_user, nama_user, jabatan, username FROM user ORDER BY id_user DESC LIMIT 5");

// Recent Pengajuan Barang
$recent_pengajuan = $conn->query("
    SELECT p.id_pengajuan, b.produk, p.jumlah_diajukan, p.status_pengajuan, p.tanggal_pengajuan 
    FROM pengajuan_barang p
    JOIN barang b ON p.id_barang = b.id_barang
    ORDER BY p.tanggal_pengajuan DESC LIMIT 5
");

// Stok di bawah ROP
$low_stock = $conn->query("
    SELECT s.id_stok, b.produk, s.stok_tersedia, s.rop, s.status_stok 
    FROM stok_barang s
    JOIN barang b ON s.id_barang = b.id_barang
    WHERE s.stok_tersedia <= s.rop OR s.status_stok = 'Dibawah ROP'
    ORDER BY s.stok_tersedia ASC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Overview - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .welcome-banner {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-text h1 { margin: 0 0 8px 0; font-size: 1.8rem; font-weight: 700; }
        .welcome-text p { margin: 0; opacity: 0.9; font-size: 1rem; }
        .welcome-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.9rem;
            backdrop-filter: blur(5px);
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1.2rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: white;
            flex-shrink: 0;
        }
        .icon-users { background: linear-gradient(135deg, #3498db, #2980b9); }
        .icon-supp { background: linear-gradient(135deg, #e67e22, #d35400); }
        .icon-brg { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .icon-pengajuan { background: linear-gradient(135deg, #f1c40f, #f39c12); }
        .icon-paid { background: linear-gradient(135deg, #2ecc71, #27ae60); }

        .metric-info h3 { margin: 0 0 4px 0; font-size: 0.85rem; text-transform: uppercase; color: #7f8c8d; letter-spacing: 0.5px; }
        .metric-info .number { font-size: 1.6rem; font-weight: 700; color: #2c3e50; margin: 0; }

        .grid-two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 992px) {
            .grid-two-columns { grid-template-columns: 1fr; }
        }

        .card-box {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .card-box-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .card-box-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-view-all {
            font-size: 0.85rem;
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
        }
        .btn-view-all:hover { text-decoration: underline; }

        .mini-table {
            width: 100%;
            border-collapse: collapse;
        }
        .mini-table th, .mini-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
            font-size: 0.9rem;
        }
        .mini-table th { background: #f8fafc; color: #475569; font-weight: 600; }

        .badge-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-rejected { background: #fee2e2; color: #b91c1c; }
        .badge-alert { background: #fee2e2; color: #dc2626; }
        .badge-ok { background: #e0e7ff; color: #4338ca; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: white;
            padding: 1.2rem;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: #2c3e50;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .action-card i { font-size: 1.8rem; color: var(--primary); }
        .action-card span { font-weight: 600; font-size: 0.9rem; }
        .action-card:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }
        .action-card:hover i { color: white; }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">

            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1><i class="fas fa-chart-line"></i> Dashboard Overview Administrator</h1>
                    <p>Selamat datang kembali, <strong><?php echo htmlspecialchars($nama_user); ?></strong>! Berikut adalah rangkuman statistik sistem SCM PT Usimpel Inovasi Indonesia.</p>
                </div>
                <div class="welcome-badge">
                    <i class="fas fa-shield-alt"></i> Role: Administrator
                </div>
            </div>

            <!-- Quick Action Shortcuts -->
            <h3 style="margin-bottom: 1rem; color: #2c3e50;"><i class="fas fa-bolt"></i> Aksi Cepat</h3>
            <div class="quick-actions">
                <a href="../user/user.php" class="action-card">
                    <i class="fas fa-users-cog"></i>
                    <span>Kelola Pengguna</span>
                </a>
                <a href="../supplier/supplier.php" class="action-card">
                    <i class="fas fa-building"></i>
                    <span>Data Supplier</span>
                </a>
                <a href="../barang/barang.php" class="action-card">
                    <i class="fas fa-box"></i>
                    <span>Data Barang</span>
                </a>
                <a href="../pengajuan_barang/pengajuan_barang.php" class="action-card">
                    <i class="fas fa-file-signature"></i>
                    <span>Pengajuan Barang</span>
                </a>
                <a href="../pembayaran/pembayaran.php" class="action-card">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Proses Pembayaran</span>
                </a>
            </div>

            <!-- Metrics Cards -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon icon-users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="metric-info">
                        <h3>Total Pengguna</h3>
                        <div class="number"><?php echo number_format($total_users); ?></div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon icon-supp">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="metric-info">
                        <h3>Total Supplier</h3>
                        <div class="number"><?php echo number_format($total_suppliers); ?></div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon icon-brg">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="metric-info">
                        <h3>Total Barang</h3>
                        <div class="number"><?php echo number_format($total_barang); ?></div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon icon-pengajuan">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="metric-info">
                        <h3>Pending Pengajuan</h3>
                        <div class="number"><?php echo number_format($total_pending_pengajuan); ?></div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon icon-paid">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="metric-info">
                        <h3>Pembayaran Lunas</h3>
                        <div class="number">Rp <?php echo number_format($total_paid, 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Content Grids -->
            <div class="grid-two-columns">

                <!-- Recent Registered Users -->
                <div class="card-box">
                    <div class="card-box-header">
                        <h2><i class="fas fa-user-shield"></i> Pengguna Terbaru</h2>
                        <a href="../user/user.php" class="btn-view-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama User</th>
                                <th>Jabatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_users && $recent_users->num_rows > 0): ?>
                                <?php while($u = $recent_users->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($u['id_user']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($u['nama_user']); ?></td>
                                        <td><span class="badge-ok"><?php echo htmlspecialchars($u['jabatan']); ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align: center;">Belum ada data pengguna.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Pengajuan Barang -->
                <div class="card-box">
                    <div class="card-box-header">
                        <h2><i class="fas fa-file-invoice"></i> Pengajuan Terbaru</h2>
                        <a href="../pengajuan_barang/pengajuan_barang.php" class="btn-view-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Barang</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_pengajuan && $recent_pengajuan->num_rows > 0): ?>
                                <?php while($p = $recent_pengajuan->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($p['id_pengajuan']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($p['produk']); ?></td>
                                        <td><?php echo number_format($p['jumlah_diajukan']); ?></td>
                                        <td>
                                            <?php if ($p['status_pengajuan'] == 'Diajukan'): ?>
                                                <span class="badge-status badge-pending">Menunggu</span>
                                            <?php elseif ($p['status_pengajuan'] == 'Disetujui'): ?>
                                                <span class="badge-status badge-approved">Disetujui</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-rejected">Ditolak</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center;">Belum ada pengajuan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Stock ROP Reorder Alert -->
            <div class="card-box" style="margin-bottom: 2rem;">
                <div class="card-box-header">
                    <h2><i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i> Peringatan Stok (Di Bawah Reorder Point / ROP)</h2>
                    <a href="../rop/perhitungan_rop.php" class="btn-view-all">Hitung ROP <i class="fas fa-arrow-right"></i></a>
                </div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>ID Stok</th>
                            <th>Nama Barang</th>
                            <th>Stok Tersedia</th>
                            <th>Reorder Point (ROP)</th>
                            <th>Status Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($low_stock && $low_stock->num_rows > 0): ?>
                            <?php while($s = $low_stock->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($s['id_stok']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($s['produk']); ?></td>
                                    <td><strong style="color: #e74c3c;"><?php echo number_format($s['stok_tersedia']); ?></strong></td>
                                    <td><?php echo number_format($s['rop']); ?></td>
                                    <td><span class="badge-status badge-alert"><?php echo htmlspecialchars($s['status_stok']); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; color: #27ae60;"><i class="fas fa-check-circle"></i> Seluruh stok barang dalam kondisi aman di atas ROP.</td></tr>
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
