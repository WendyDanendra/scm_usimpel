<?php
session_start();
require_once '../config.php';

// Check login & permissions
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$nama_user = $_SESSION['name'] ?? 'Administrator';
$jabatan = $_SESSION['jabatan'] ?? 'Administrator';
$current_date = date('d F Y');
$current_time = date('H:i');

// 1. Fetch Metrics Data
// Total Users
$res_user = $conn->query("SELECT COUNT(*) AS total FROM user");
$total_users = $res_user ? $res_user->fetch_assoc()['total'] : 0;

// Total Suppliers
$res_supp = $conn->query("SELECT COUNT(*) AS total FROM supplier");
$total_suppliers = $res_supp ? $res_supp->fetch_assoc()['total'] : 0;

// Total Barang
$res_brg = $conn->query("SELECT COUNT(*) AS total FROM barang");
$total_barang = $res_brg ? $res_brg->fetch_assoc()['total'] : 0;

// Pengajuan Pending
$res_pnjn = $conn->query("SELECT COUNT(*) AS total FROM pengajuan_barang WHERE status_pengajuan = 'Diajukan'");
$pengajuan_pending = $res_pnjn ? $res_pnjn->fetch_assoc()['total'] : 0;

// Total Pembayaran Lunas
$res_bayar = $conn->query("SELECT SUM(nominal) AS total FROM pembayaran WHERE status_pembayaran = 'Lunas'");
$total_pembayaran = $res_bayar ? ($res_bayar->fetch_assoc()['total'] ?? 0) : 0;

// 2. Fetch Recent Users (5 Terbaru)
$recent_users = $conn->query("SELECT id_user, nama_user, jabatan, username FROM user ORDER BY id_user DESC LIMIT 5");

// 3. Fetch Recent Pengajuan (5 Terbaru)
$sql_pengajuan = "SELECT p.*, b.produk, s.nama_supplier 
                  FROM pengajuan_barang p 
                  LEFT JOIN barang b ON p.id_barang = b.id_barang 
                  LEFT JOIN supplier s ON p.id_supplier = s.id_supplier 
                  ORDER BY p.tanggal_pengajuan DESC, p.id_pengajuan DESC LIMIT 5";
$recent_pengajuan = $conn->query($sql_pengajuan);

// 4. Fetch Stock Alert Items (Dibawah atau mendekati ROP)
$sql_stok_alert = "SELECT sb.*, b.produk 
                   FROM stok_barang sb 
                   LEFT JOIN barang b ON sb.id_barang = b.id_barang 
                   WHERE sb.stok_tersedia <= (sb.rop + sb.safety_stock) 
                   ORDER BY sb.stok_tersedia ASC LIMIT 5";
$stok_alerts = $conn->query($sql_stok_alert);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Overview - SCM Usimpel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        * { box-sizing: border-box; }
        
        .welcome-banner {
            background: linear-gradient(135deg, #1e293b 0%, #3b82f6 50%, #1d4ed8 100%);
            color: white;
            padding: 35px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(29, 78, 216, 0.25);
            position: relative;
            overflow: hidden;
        }

        .welcome-banner::after {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .role-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .banner-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .banner-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid #f1f5f9;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.1);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .icon-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .icon-emerald { background: linear-gradient(135deg, #10b981, #047857); }
        .icon-amber { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .icon-purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
        .icon-rose { background: linear-gradient(135deg, #f43f5e, #be123c); }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }

        .quick-actions-section {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .action-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 18px;
            border-radius: 12px;
            text-decoration: none;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.2s ease;
        }

        .action-card:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            transform: translateY(-2px);
        }

        .action-card i {
            font-size: 1.4rem;
        }

        .action-card span {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .dashboard-main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        @media (max-width: 1024px) {
            .dashboard-main-grid { grid-template-columns: 1fr; }
        }

        .content-box {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .simple-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .simple-table th {
            text-align: left;
            padding: 10px 12px;
            background: #f8fafc;
            color: #475569;
            font-size: 0.85rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .simple-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #334155;
        }

        .badge-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .badge-diajukan { background: #fef3c7; color: #b45309; }
        .badge-diproses { background: #dbeafe; color: #1d4ed8; }
        .badge-disetujui { background: #dcfce7; color: #15803d; }
        .badge-danger { background: #ffe4e6; color: #be123c; }

        .view-all-link {
            font-size: 0.88rem;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            float: right;
        }

        .view-all-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <span class="role-badge"><i class="fas fa-shield-alt"></i> System Administrator</span>
                <h1 class="banner-title">Selamat Datang, <?php echo htmlspecialchars($nama_user); ?>!</h1>
                <p class="banner-subtitle">Ringkasan Eksekutif Sistem SCM & Pengelolaan Pengguna - Hari ini: <?php echo $current_date; ?></p>
            </div>

            <!-- Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon icon-blue">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_users); ?></div>
                    <div class="stat-label">Total Pengguna</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon icon-emerald">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_suppliers); ?></div>
                    <div class="stat-label">Total Supplier</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon icon-purple">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_barang); ?></div>
                    <div class="stat-label">Item Barang</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon icon-amber">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($pengajuan_pending); ?></div>
                    <div class="stat-label">Pengajuan Pending</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon icon-rose">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                    </div>
                    <div class="stat-value" style="font-size: 1.4rem; padding-top: 10px;">Rp <?php echo number_format($total_pembayaran, 0, ',', '.'); ?></div>
                    <div class="stat-label">Total Pembayaran Lunas</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions-section">
                <div class="section-title">
                    <i class="fas fa-rocket" style="color: #3b82f6;"></i> Akses Cepat Navigasi Admin
                </div>
                <div class="actions-grid">
                    <a href="../user/user.php" class="action-card">
                        <i class="fas fa-users-cog" style="color: #3b82f6;"></i>
                        <span>Kelola Pengguna</span>
                    </a>
                    <a href="../supplier/supplier.php" class="action-card">
                        <i class="fas fa-building" style="color: #10b981;"></i>
                        <span>Data Supplier</span>
                    </a>
                    <a href="../barang/barang.php" class="action-card">
                        <i class="fas fa-box" style="color: #8b5cf6;"></i>
                        <span>Data Barang</span>
                    </a>
                    <a href="../stok_barang/pengelolaan_stok.php" class="action-card">
                        <i class="fas fa-warehouse" style="color: #f59e0b;"></i>
                        <span>Pengelolaan Stok</span>
                    </a>
                    <a href="../laporan/laporan_scm.php" class="action-card">
                        <i class="fas fa-file-contract" style="color: #f43f5e;"></i>
                        <span>Laporan SCM</span>
                    </a>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="dashboard-main-grid">
                <!-- Recent Users Box -->
                <div class="content-box">
                    <a href="../user/user.php" class="view-all-link">Kelola Semua <i class="fas fa-arrow-right"></i></a>
                    <div class="section-title">
                        <i class="fas fa-user-shield" style="color: #3b82f6;"></i> Pengguna Terdaftar Terbaru
                    </div>
                    <table class="simple-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Pengguna</th>
                                <th>Jabatan</th>
                                <th>Username</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_users && $recent_users->num_rows > 0): ?>
                                <?php while($u = $recent_users->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($u['id_user']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($u['nama_user']); ?></td>
                                        <td><span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;"><?php echo htmlspecialchars($u['jabatan']); ?></span></td>
                                        <td><code><?php echo htmlspecialchars($u['username']); ?></code></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center;">Belum ada data pengguna.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Pengajuan Box -->
                <div class="content-box">
                    <a href="../pengajuan_barang/pengajuan_barang.php" class="view-all-link">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    <div class="section-title">
                        <i class="fas fa-file-signature" style="color: #10b981;"></i> Aktivitas Pengajuan Terbaru
                    </div>
                    <table class="simple-table">
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
                                        <td><?php echo htmlspecialchars($p['produk'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($p['jumlah_diajukan']); ?></td>
                                        <td>
                                            <?php 
                                            $st = $p['status_pengajuan'];
                                            $badge = 'badge-diajukan';
                                            if ($st == 'Diproses') $badge = 'badge-diproses';
                                            elseif ($st == 'Disetujui') $badge = 'badge-disetujui';
                                            elseif ($st == 'Ditolak') $badge = 'badge-danger';
                                            ?>
                                            <span class="badge-status <?php echo $badge; ?>"><?php echo htmlspecialchars($st); ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center;">Belum ada data pengajuan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
