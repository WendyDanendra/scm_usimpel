<?php
session_start();

// Cek jika user belum login, tendang ke halaman login
if (!isset($_SESSION['jabatan'])) {
    header('Location: ../login.php');
    exit();
}

// Proteksi untuk jabatan kepala divisi
if ($_SESSION['jabatan'] !== 'Kepala Divisi Produk & Pengadaan') {
    header('Location: ../login.php');
    exit();
}

include '../config.php';

// Query statistik untuk Kepala Divisi
// 1. Total pengajuan barang
$result = $conn->query("
    SELECT 
        COUNT(*) as total_pengajuan,
        SUM(CASE WHEN status_pengajuan = 'Diajukan' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status_pengajuan = 'Disetujui' THEN 1 ELSE 0 END) as disetujui,
        SUM(CASE WHEN status_pengajuan = 'Ditolak' THEN 1 ELSE 0 END) as ditolak
    FROM pengajuan_barang
");
$total_pengajuan = $result ? $result->fetch_assoc() : ['total_pengajuan' => 0, 'pending' => 0, 'disetujui' => 0, 'ditolak' => 0];

// 2. Statistik stok barang
$result = $conn->query("
    SELECT 
        COUNT(*) as total_barang,
        SUM(CASE WHEN CAST(stok_tersedia AS UNSIGNED) <= CAST(rop AS UNSIGNED) THEN 1 ELSE 0 END) as perlu_restock,
        SUM(CASE WHEN CAST(stok_tersedia AS UNSIGNED) > CAST(rop AS UNSIGNED) AND CAST(stok_tersedia AS UNSIGNED) <= (CAST(rop AS UNSIGNED) * 2) THEN 1 ELSE 0 END) as stok_aman,
        SUM(CASE WHEN CAST(stok_tersedia AS UNSIGNED) > (CAST(rop AS UNSIGNED) * 2) THEN 1 ELSE 0 END) as stok_berlebih
    FROM stok_barang
");
$stok_barang = $result ? $result->fetch_assoc() : ['total_barang' => 0, 'perlu_restock' => 0, 'stok_aman' => 0, 'stok_berlebih' => 0];

// 3. Aktivitas tim (berdasarkan pengajuan 30 hari terakhir)
$result = $conn->query("
    SELECT 
        COUNT(*) as total_aktivitas,
        SUM(CASE WHEN status_pengajuan = 'Disetujui' THEN 1 ELSE 0 END) as approved_count,
        COUNT(*) as total_count
    FROM pengajuan_barang 
    WHERE tanggal_pengajuan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$aktivitas_data = $result ? $result->fetch_assoc() : ['total_aktivitas' => 0, 'approved_count' => 0, 'total_count' => 0];
$approval_rate = $aktivitas_data['total_count'] > 0 ? ($aktivitas_data['approved_count'] / $aktivitas_data['total_count']) * 100 : 0;
$aktivitas_tim = [
    'anggota_aktif' => 3, // Hardcode untuk inventory team
    'total_aktivitas' => $aktivitas_data['total_aktivitas'],
    'approval_rate' => $approval_rate
];

// 4. Kinerja supplier
$result = $conn->query("
    SELECT 
        COUNT(DISTINCT s.id_supplier) as total_supplier,
        COUNT(p.id_pembayaran) as total_transaksi,
        SUM(CASE WHEN p.status_pembayaran = 'Lunas' THEN 1 ELSE 0 END) as completed_transactions
    FROM supplier s
    LEFT JOIN pengajuan_barang pb ON s.id_supplier = pb.id_supplier
    LEFT JOIN pengadaan_barang pg ON pb.id_pengajuan = pg.id_pengajuan
    LEFT JOIN pembayaran p ON pg.id_pengadaan = p.id_pengadaan
    WHERE pb.tanggal_pengajuan >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
");
$supplier_data = $result ? $result->fetch_assoc() : ['total_supplier' => 0, 'total_transaksi' => 0, 'completed_transactions' => 0];
$supplier_performance = $supplier_data['total_transaksi'] > 0 ? ($supplier_data['completed_transactions'] / $supplier_data['total_transaksi']) * 100 : 0;
$kinerja_supplier = [
    'total_supplier' => $supplier_data['total_supplier'],
    'supplier_performance' => $supplier_performance
];

// 5. Daftar pengajuan pending untuk persetujuan
$pengajuan_pending = $conn->query("
    SELECT pb.*, b.produk as nama_barang, s.nama_supplier
    FROM pengajuan_barang pb
    JOIN barang b ON pb.id_barang = b.id_barang
    JOIN supplier s ON pb.id_supplier = s.id_supplier
    WHERE pb.status_pengajuan = 'Diajukan'
    ORDER BY pb.tanggal_pengajuan DESC
    LIMIT 10
");

// 6. Barang yang perlu restock
$barang_restock = $conn->query("
    SELECT b.produk as nama_barang, sb.stok_tersedia, sb.rop, s.nama_supplier
    FROM stok_barang sb
    JOIN barang b ON sb.id_barang = b.id_barang
    LEFT JOIN supplier s ON b.id_supplier = s.id_supplier
    WHERE CAST(sb.stok_tersedia AS UNSIGNED) <= CAST(sb.rop AS UNSIGNED)
    ORDER BY (CAST(sb.stok_tersedia AS UNSIGNED) / CAST(sb.rop AS UNSIGNED)) ASC
    LIMIT 10
");

// 7. Laporan bulanan divisi
$laporan_divisi = $conn->query("
    SELECT 
        DATE_FORMAT(pb.tanggal_pengajuan, '%Y-%m') as bulan,
        COUNT(*) as jumlah_pengajuan,
        SUM(pb.jumlah_diajukan * b.harga) as total_nilai,
        SUM(CASE WHEN pb.status_pengajuan = 'Disetujui' THEN 1 ELSE 0 END) as approved_count,
        COUNT(*) as total_count
    FROM pengajuan_barang pb
    JOIN barang b ON pb.id_barang = b.id_barang
    WHERE pb.tanggal_pengajuan >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(pb.tanggal_pengajuan, '%Y-%m')
    ORDER BY bulan DESC
    LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kepala Divisi - PT. Usimpel Inovasi Indonesia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        /* Dashboard Kepala Divisi - Full Screen Override */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            display: flex;
            width: 100vw;
            height: 100vh;
        }
        
        .main-content {
            margin-left: 250px !important;
            padding: 30px !important;
            min-height: 100vh !important;
            width: calc(100vw - 250px) !important;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            flex: 1;
        }
        
        .content {
            width: 100%;
            height: 100%;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 25px;
            margin-bottom: 40px;
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .quick-action-btn {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255,255,255,0.2);
            color: white;
            padding: 20px;
            text-decoration: none;
            border-radius: 15px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .quick-action-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transition: all 0.6s ease;
            transform: translate(-50%, -50%);
        }
        
        .quick-action-btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .quick-action-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            color: white;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: linear-gradient(145deg, #ffffff, #f0f0f0);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 
                20px 20px 60px #bebebe,
                -20px -20px 60px #ffffff;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 20px 20px 0 0;
        }
        .stat-card.green::before {
            background: linear-gradient(90deg, #4facfe, #00f2fe);
        }
        .stat-card.blue::before {
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        .stat-card.orange::before {
            background: linear-gradient(90deg, #f093fb, #f5576c);
        }
        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 
                25px 25px 80px #bebebe,
                -25px -25px 80px #ffffff;
        }
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
            color: white;
            background: linear-gradient(135deg, var(--icon-color-1, #667eea), var(--icon-color-2, #764ba2));
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .stat-number {
            font-size: 3em;
            font-weight: 700;
            margin: 15px 0;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-number.green {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-number.blue {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-number.orange {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            color: #555;
            font-size: 1em;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-detail {
            font-size: 0.95em;
            color: #666;
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stat-detail i {
            width: 16px;
            color: #667eea;
        }
        
        .stat-detail.green i {
            color: #4facfe;
        }
        
        .stat-detail.orange i {
            color: #f093fb;
        }
        .stat-icon i {
            font-size: 1.8em;
            color: white;
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: 800;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-number.green {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-number.orange {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-label {
            font-size: 1.1em;
            color: #555;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-detail {
            display: flex;
            align-items: center;
            margin: 8px 0;
            color: #666;
            font-size: 0.95em;
        }
        .stat-detail i {
            margin-right: 8px;
            width: 16px;
            color: #667eea;
        }
        .stat-detail.green i {
            color: #4facfe;
        }
        .stat-detail.orange i {
            color: #f093fb;
        }
        .progress-bar {
            background: #e0e0e0;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin: 15px 0 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--progress-color-1, #667eea), var(--progress-color-2, #764ba2));
            border-radius: 4px;
            transition: width 1.5s ease-in-out;
            animation: fillProgress 2s ease-in-out;
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 25px;
            margin-bottom: 40px;
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .welcome-content {
            position: relative;
            z-index: 2;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .quick-action-btn {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255,255,255,0.2);
            color: white;
            padding: 20px;
            text-decoration: none;
            border-radius: 15px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .quick-action-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transition: all 0.6s ease;
            transform: translate(-50%, -50%);
        }
        
        .quick-action-btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .quick-action-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            color: white;
        }
        
        .data-section {
            background: linear-gradient(145deg, #ffffff, #f0f0f0);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 
                20px 20px 60px #bebebe,
                -20px -20px 60px #ffffff;
            margin-bottom: 30px;
        }
        .data-section h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.3em;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .data-section h3 i {
            margin-right: 10px;
            color: #667eea;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .data-table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: 600;
            font-size: 0.9em;
        }
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-size: 0.9em;
            text-align: center;
        }
        .data-table td:nth-child(3), .data-table td:nth-child(5), .data-table td:nth-child(8) { 
            text-align: left; 
        } /* Nama Barang, Supplier, dan Catatan columns */
        .data-table tbody tr:hover {
            background: #f8f9ff;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            color: white;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { 
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        .status-disetujui { 
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        .status-ditolak { 
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        .performance-badge {
            padding: 6px 12px;
            border-radius: 20px;
            color: white;
            font-size: 0.8em;
            font-weight: 600;
        }
        .performance-high { 
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }
        .performance-medium { 
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }
        .performance-low { 
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        @keyframes fillProgress {
            0% { width: 0%; }
        }
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .currency {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }
        .table-small {
            font-size: 0.85em;
        }
        .table-small th, .table-small td {
            padding: 10px 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../components/header.php'; ?>

            <main class="content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1 style="font-size: 2.5em; margin-bottom: 10px; font-weight: 700; text-align: center;">
                        <i class="fas fa-users-cog" style="margin-right: 15px;"></i> 
                        Dashboard Kepala Divisi
                    </h1>
                    <p style="text-align: center; font-size: 1.1em; margin-bottom: 20px; opacity: 0.9; font-weight: 500;">
                        Manajemen & Pengawasan Divisi SCM
                    </p>
                    
                    <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 15px; backdrop-filter: blur(10px); margin: 20px 0;">
                        <p style="margin: 0; font-size: 1.2em; font-weight: 500;">
                            <i class="fas fa-user-circle" style="margin-right: 8px;"></i>
                            Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>! 
                        </p>
                        <p style="margin: 8px 0 0 0; font-size: 1em; opacity: 0.9;">
                            <i class="fas fa-id-badge" style="margin-right: 8px;"></i>
                            Role: <strong><?php echo htmlspecialchars($_SESSION['jabatan']); ?></strong>
                        </p>
                        <p style="margin: 8px 0 0 0; opacity: 0.9;">
                            <i class="fas fa-clock" style="margin-right: 8px;"></i> 
                            <?= date('l, d F Y - H:i') ?> WIB
                        </p>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <a href="../persetujuan/persetujuan_pengajuan.php" class="quick-action-btn">
                            <div style="position: relative; z-index: 2;">
                                <i class="fas fa-check-circle" style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                                <strong>Persetujuan</strong>
                                <small style="display: block; margin-top: 5px; opacity: 0.8;">Pengajuan barang</small>
                            </div>
                        </a>
                        <a href="../laporan/laporan_scm.php" class="quick-action-btn">
                            <div style="position: relative; z-index: 2;">
                                <i class="fas fa-chart-line" style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                                <strong>Laporan</strong>
                                <small style="display: block; margin-top: 5px; opacity: 0.8;">Analisis kinerja</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistik Cards -->
            <div class="dashboard-grid">
                <!-- Statistik Pengajuan -->
                <div class="stat-card" style="--icon-color-1: #667eea; --icon-color-2: #764ba2; --progress-color-1: #667eea; --progress-color-2: #764ba2;">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number"><?= $total_pengajuan['total_pengajuan'] ?? 0 ?></div>
                            <div class="stat-label">Total Pengajuan</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="stat-detail">
                        <i class="fas fa-clock"></i> Pending: <strong><?= $total_pengajuan['pending'] ?? 0 ?></strong>
                    </div>
                    <div class="stat-detail green">
                        <i class="fas fa-check-circle"></i> Disetujui: <strong><?= $total_pengajuan['disetujui'] ?? 0 ?></strong>
                    </div>
                    <div class="stat-detail orange">
                        <i class="fas fa-times-circle"></i> Ditolak: <strong><?= $total_pengajuan['ditolak'] ?? 0 ?></strong>
                    </div>
                    <?php 
                    $total_req = $total_pengajuan['total_pengajuan'] ?? 0;
                    $approved = $total_pengajuan['disetujui'] ?? 0;
                    $approval_rate = $total_req > 0 ? ($approved / $total_req) * 100 : 0;
                    ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $approval_rate ?>%;"></div>
                    </div>
                    <small style="color: #4facfe; font-weight: 600; margin-top: 8px; display: block;">
                        <i class="fas fa-percentage"></i> Approval Rate: <?= number_format($approval_rate, 1) ?>%
                    </small>
                </div>
            </div>

            <!-- Data Tables section dihapus sesuai permintaan -->
        </div>
        </main>
        
    </div>

    <script src="../assets/js/main.js"></script>
    
</body>
</html>