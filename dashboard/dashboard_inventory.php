<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['jabatan'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SESSION['jabatan'] !== 'Inventory & Purchasing Officer') {
    header('Location: ../login.php');
    exit();
}

// Get current date for welcome message
$current_date = date('d F Y');
$current_time = date('H:i');

// Statistik Pengajuan Barang
$result = $conn->query("
    SELECT 
        COUNT(*) as total_pengajuan,
        SUM(CASE WHEN status_pengajuan = 'Diajukan' THEN 1 ELSE 0 END) as menunggu_persetujuan,
        SUM(CASE WHEN status_pengajuan = 'Disetujui' THEN 1 ELSE 0 END) as disetujui,
        SUM(CASE WHEN status_pengajuan = 'Ditolak' THEN 1 ELSE 0 END) as ditolak
    FROM pengajuan_barang
");
$stats_pengajuan = $result ? $result->fetch_assoc() : 
    ['total_pengajuan' => 0, 'menunggu_persetujuan' => 0, 'disetujui' => 0, 'ditolak' => 0];

// Statistik Pengadaan Barang dengan status pembayaran
$result = $conn->query("
    SELECT 
        COUNT(DISTINCT pb.id_pengajuan) as total_pengajuan_disetujui,
        SUM(CASE WHEN pd.id_pengadaan IS NULL THEN 1 ELSE 0 END) as belum_ada_pengadaan,
        SUM(CASE WHEN pd.id_pengadaan IS NOT NULL AND p.status_pembayaran IS NULL THEN 1 ELSE 0 END) as proses_pengadaan,
        SUM(CASE WHEN p.status_pembayaran = 'Lunas' THEN 1 ELSE 0 END) as pengadaan_selesai
    FROM pengajuan_barang pb
    LEFT JOIN pengadaan_barang pd ON pb.id_pengajuan = pd.id_pengajuan
    LEFT JOIN pembayaran p ON pd.id_pengadaan = p.id_pengadaan
    WHERE pb.status_pengajuan = 'Disetujui'
");
$stats_pengadaan = $result ? $result->fetch_assoc() : 
    ['total_pengajuan_disetujui' => 0, 'belum_ada_pengadaan' => 0, 'proses_pengadaan' => 0, 'pengadaan_selesai' => 0];

// Statistik Stok Barang
$result = $conn->query("
    SELECT 
        COUNT(*) as total_item_stok,
        COALESCE(SUM(CAST(stok_tersedia AS UNSIGNED)), 0) as total_stok_tersedia
    FROM stok_barang
");
$stats_stok = $result ? $result->fetch_assoc() : 
    ['total_item_stok' => 0, 'total_stok_tersedia' => 0];

// Barang Masuk dari log_stok
$result = $conn->query("
    SELECT COALESCE(SUM(CAST(jumlah AS UNSIGNED)), 0) as total_masuk 
    FROM log_stok 
    WHERE jenis_log = 'masuk'
");
$barang_masuk = $result ? $result->fetch_assoc() : ['total_masuk' => 0];

// Barang Keluar dari log_stok
$result = $conn->query("
    SELECT COALESCE(SUM(CAST(jumlah AS UNSIGNED)), 0) as total_keluar 
    FROM log_stok 
    WHERE jenis_log = 'keluar'
");
$barang_keluar = $result ? $result->fetch_assoc() : ['total_keluar' => 0];

// Pengajuan 7 hari terakhir
$pengajuan_terbaru = $conn->query("
    SELECT pb.id_pengajuan, pb.tanggal_pengajuan, b.produk, pb.status_pengajuan, pb.jumlah_diajukan
    FROM pengajuan_barang pb
    LEFT JOIN barang b ON pb.id_barang = b.id_barang
    WHERE pb.tanggal_pengajuan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY pb.tanggal_pengajuan DESC
    LIMIT 5
");

// Stok yang perlu perhatian
$stok_perhatian = $conn->query("
    SELECT sb.id_stok, b.produk, b.merek, sb.stok_tersedia, sb.status_stok
    FROM stok_barang sb
    LEFT JOIN barang b ON sb.id_barang = b.id_barang
    WHERE sb.status_stok IN ('Dibawah ROP', 'Belum Ada Data')
    ORDER BY 
        CASE WHEN sb.status_stok = 'Dibawah ROP' THEN 1 ELSE 2 END,
        CAST(COALESCE(sb.stok_tersedia, '0') AS UNSIGNED) ASC
    LIMIT 5
");

// Supplier aktif
$supplier_aktif = $conn->query("
    SELECT s.nama_supplier, COUNT(pb.id_pengajuan) as jumlah_pengajuan
    FROM supplier s
    LEFT JOIN pengajuan_barang pb ON s.id_supplier = pb.id_supplier
    WHERE pb.tanggal_pengajuan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY s.id_supplier, s.nama_supplier
    ORDER BY jumlah_pengajuan DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Inventory - PT. Usimpel Inovasi Indonesia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        /* Dashboard Inventory - Full Screen Override */
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
            background: linear-gradient(135deg, var(--icon-color-1), var(--icon-color-2));
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
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
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            color: #555;
            font-size: 1em;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .quick-action-btn {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .quick-action-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        
        .progress-bar {
            width: 100%;
            height: 12px;
            background: #e8e8e8;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 15px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        .progress-fill {
            height: 100%;
            transition: width 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            background: linear-gradient(90deg, var(--progress-color-1), var(--progress-color-2));
            position: relative;
        }
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .table-small {
            font-size: 0.9em;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .table-small th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8em;
        }
        .table-small td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.3s ease;
        }
        .table-small tr:hover td {
            background-color: #f8f9ff;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            color: white;
            font-size: 0.75em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .status-kritis { 
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            animation: blink 2s infinite;
        }
        .status-rendah { 
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        .status-diajukan { 
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        .status-disetujui { 
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        .status-ditolak { 
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.7; }
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
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
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
        .data-section {
            background: linear-gradient(145deg, #ffffff, #f0f0f0);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 
                15px 15px 35px #d1d1d1,
                -15px -15px 35px #ffffff;
            transition: transform 0.3s ease;
        }
        .data-section:hover {
            transform: translateY(-5px);
        }
        .data-section h3 {
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }
        .data-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
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
        .stat-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            color: #667eea;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .stat-link:hover {
            color: #764ba2;
            transform: translateX(5px);
        }
        .loading-animation {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                            <i class="fas fa-tachometer-alt" style="margin-right: 15px;"></i> 
                            Dashboard Inventory & Purchasing
                        </h1>
                        <p style="text-align: center; font-size: 1.1em; margin-bottom: 20px; opacity: 0.9; font-weight: 500;">
                            Inventory Management Dashboard
                        </p>
                        
                        <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 15px; backdrop-filter: blur(10px); margin: 20px 0;">
                            <p style="margin: 0; font-size: 1.2em; font-weight: 500;">
                                <i class="fas fa-user-circle" style="margin-right: 8px;"></i>
                                Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['username'] ?? 'Inventory Officer'); ?></strong>! 
                            </p>
                            <p style="margin: 8px 0 0 0; font-size: 1em; opacity: 0.9;">
                                <i class="fas fa-id-badge" style="margin-right: 8px;"></i>
                                Role: <strong>Inventory & Purchasing Officer</strong>
                            </p>
                            <p style="margin: 8px 0 0 0; opacity: 0.9;">
                                <i class="fas fa-clock" style="margin-right: 8px;"></i> 
                                <?= date('l, d F Y - H:i') ?> WIB
                            </p>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="quick-actions">
                            <a href="../pengajuan_barang/pengajuan_barang.php" class="quick-action-btn">
                                <div style="position: relative; z-index: 2;">
                                    <i class="fas fa-plus-circle" style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                                    <strong>Buat Pengajuan</strong>
                                    <small style="display: block; margin-top: 5px; opacity: 0.8;">Ajukan barang baru</small>
                                </div>
                            </a>
                            <a href="../persetujuan/persetujuan_pengajuan.php" class="quick-action-btn">
                                <div style="position: relative; z-index: 2;">
                                    <i class="fas fa-check-circle" style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                                    <strong>Lihat Persetujuan</strong>
                                    <small style="display: block; margin-top: 5px; opacity: 0.8;">Status pengajuan</small>
                                </div>
                            </a>
                        <a href="../stok_barang/pengelolaan_stok.php" class="quick-action-btn">
                            <div style="position: relative; z-index: 2;">
                                <i class="fas fa-warehouse" style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                                <strong>Kelola Stok</strong>
                                <small style="display: block; margin-top: 5px; opacity: 0.8;">Update inventory</small>
                            </div>
                        </a>
                        <a href="../pengadaan_barang/pengadaan_barang.php" class="quick-action-btn">
                            <div style="position: relative; z-index: 2;">
                                <i class="fas fa-truck" style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                                <strong>Pengadaan</strong>
                                <small style="display: block; margin-top: 5px; opacity: 0.8;">Proses pembelian</small>
                            </div>
                        </a>
                        <a href="../laporan/laporan_scm.php" class="quick-action-btn">
                            <div style="position: relative; z-index: 2;">
                                <i class="fas fa-chart-line" style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                                <strong>Laporan</strong>
                                <small style="display: block; margin-top: 5px; opacity: 0.8;">Analisis data</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistik Cards -->
            <div class="dashboard-grid">
                <!-- Statistik Pengajuan -->
                <div class="stat-card" style="--icon-color-1: #3498db; --icon-color-2: #2980b9; --progress-color-1: #3498db; --progress-color-2: #2980b9;">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number"><?= $stats_pengajuan['total_pengajuan'] ?? 0 ?></div>
                            <div class="stat-label">Total Pengajuan</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-file-signature"></i>
                        </div>
                    </div>
                    <div class="stat-detail">
                        <i class="fas fa-clock"></i> Menunggu: <strong><?= $stats_pengajuan['menunggu_persetujuan'] ?? 0 ?></strong>
                    </div>
                    <div class="stat-detail">
                        <i class="fas fa-check"></i> Disetujui: <strong><?= $stats_pengajuan['disetujui'] ?? 0 ?></strong>
                    </div>
                    <div class="stat-detail">
                        <i class="fas fa-times"></i> Ditolak: <strong><?= $stats_pengajuan['ditolak'] ?? 0 ?></strong>
                    </div>
                    <?php 
                    $total = $stats_pengajuan['total_pengajuan'] ?? 0;
                    $disetujui = $stats_pengajuan['disetujui'] ?? 0;
                    $approval_rate = $total > 0 ? ($disetujui / $total) * 100 : 0;
                    ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $approval_rate ?>%;"></div>
                    </div>
                    <small style="color: #27ae60; font-weight: 600; margin-top: 8px; display: block;">
                        <i class="fas fa-chart-line"></i> Tingkat Persetujuan: <?= number_format($approval_rate, 1) ?>%
                    </small>
                </div>

                <!-- Statistik Pengadaan -->
                <div class="stat-card" style="--icon-color-1: #e67e22; --icon-color-2: #d35400; --progress-color-1: #e67e22; --progress-color-2: #d35400;">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number"><?= $stats_pengadaan['total_pengajuan_disetujui'] ?? 0 ?></div>
                            <div class="stat-label">Pengajuan Disetujui</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                    </div>
                    <div class="stat-detail">
                        <i class="fas fa-hourglass-start"></i> Perlu Proses: <strong><?= ($stats_pengadaan['belum_ada_pengadaan'] + $stats_pengadaan['proses_pengadaan']) ?? 0 ?></strong>
                    </div>
                    <div class="stat-detail">
                        <i class="fas fa-check-circle" style="color: #27ae60;"></i> Selesai: <strong><?= $stats_pengadaan['pengadaan_selesai'] ?? 0 ?></strong>
                    </div>
                    <a href="../pengadaan_barang/pengadaan_barang.php" class="stat-link">
                        <i class="fas fa-arrow-right"></i> Detail Pengadaan
                    </a>
                </div>

                <!-- Statistik Stok -->
                <div class="stat-card" style="--icon-color-1: #9b59b6; --icon-color-2: #8e44ad; --progress-color-1: #9b59b6; --progress-color-2: #8e44ad;">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number"><?= number_format($stats_stok['total_stok_tersedia'] ?? 0) ?></div>
                            <div class="stat-label">Total Unit Stok</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-warehouse"></i>
                        </div>
                    </div>
                    <div class="stat-detail">
                        <i class="fas fa-boxes"></i> Item: <strong><?= $stats_stok['total_item_stok'] ?? 0 ?></strong>
                    </div>
                    <div class="stat-detail">
                        <i class="fas fa-arrow-up" style="color: #27ae60;"></i> Masuk: <strong style="color: #27ae60;"><?= number_format($barang_masuk['total_masuk'] ?? 0) ?></strong> unit
                    </div>
                    <div class="stat-detail">
                        <i class="fas fa-arrow-down" style="color: #e74c3c;"></i> Keluar: <strong style="color: #e74c3c;"><?= number_format($barang_keluar['total_keluar'] ?? 0) ?></strong> unit
                    </div>
                    <a href="../stok_barang/pengelolaan_stok.php" class="stat-link">
                        <i class="fas fa-arrow-right"></i> Kelola Stok
                    </a>
                </div>
            </div>

            <!-- Data Tables -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 40px;">
                <!-- Pengajuan Terbaru -->
                <div class="data-section">
                    <h3><i class="fas fa-clock"></i> Pengajuan Terbaru (7 Hari)</h3>
                    <table class="data-table table-small">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> ID</th>
                                <th><i class="fas fa-box"></i> Produk</th>
                                <th><i class="fas fa-sort-numeric-up"></i> Jumlah</th>
                                <th><i class="fas fa-flag"></i> Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pengajuan_terbaru && $pengajuan_terbaru->num_rows > 0): ?>
                                <?php while($row = $pengajuan_terbaru->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $row['id_pengajuan'] ?></strong></td>
                                    <td>
                                        <div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($row['produk']) ?>">
                                            <?= htmlspecialchars($row['produk']) ?>
                                        </div>
                                    </td>
                                    <td><span style="font-weight: 600;"><?= $row['jumlah_diajukan'] ?></span></td>
                                    <td><span class="status-badge status-<?= strtolower($row['status_pengajuan']) ?>"><?= $row['status_pengajuan'] ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center; color: #999; padding: 20px; font-style: italic;">
                                    <i class="fas fa-inbox"></i> Tidak ada data pengajuan terbaru
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Stok Perlu Perhatian -->
                <div class="data-section">
                    <h3><i class="fas fa-exclamation-triangle" style="color: #f39c12;"></i> Stok Perlu Perhatian</h3>
                    <table class="data-table table-small">
                        <thead>
                            <tr>
                                <th><i class="fas fa-box"></i> Produk</th>
                                <th><i class="fas fa-tag"></i> Merek</th>
                                <th><i class="fas fa-layer-group"></i> Stok</th>
                                <th><i class="fas fa-warning"></i> Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($stok_perhatian && $stok_perhatian->num_rows > 0): ?>
                                <?php while($row = $stok_perhatian->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="max-width: 120px; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($row['produk']) ?>">
                                            <?= htmlspecialchars($row['produk']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($row['merek']) ?></td>
                                    <td><span style="font-weight: 600; color: #e74c3c;"><?= $row['stok_tersedia'] ?></span></td>
                                    <td><span class="status-badge status-<?= $row['status_stok'] == 'Dibawah ROP' ? 'kritis' : 'rendah' ?>"><?= $row['status_stok'] ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center; color: #27ae60; padding: 20px; font-weight: 600;">
                                    <i class="fas fa-check-circle"></i> Semua stok dalam kondisi normal
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Supplier Aktif -->
            <div class="data-section" style="margin-top: 30px;">
                <h3><i class="fas fa-handshake" style="color: #3498db;"></i> Supplier Aktif (30 Hari)</h3>
                <table class="data-table table-small">
                    <thead>
                        <tr>
                            <th><i class="fas fa-building"></i> Nama Supplier</th>
                            <th><i class="fas fa-chart-bar"></i> Jumlah Pengajuan</th>
                            <th><i class="fas fa-activity"></i> Aktivitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($supplier_aktif && $supplier_aktif->num_rows > 0): ?>
                            <?php while($row = $supplier_aktif->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['nama_supplier']) ?></strong></td>
                                <td>
                                    <span style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 4px 8px; border-radius: 10px; font-size: 0.8em; font-weight: 600;">
                                        <?= $row['jumlah_pengajuan'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress-bar" style="width: 120px; height: 8px;">
                                        <div class="progress-fill" style="width: <?= min(($row['jumlah_pengajuan'] / 10) * 100, 100) ?>%; --progress-color-1: #3498db; --progress-color-2: #2980b9;"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center; color: #999; padding: 20px; font-style: italic;">
                                <i class="fas fa-users-slash"></i> Tidak ada aktivitas supplier bulan ini
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
