<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['jabatan'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SESSION['jabatan'] !== 'Finance & Billing Officer') {
    header('Location: ../login.php');
    exit();
}

// Statistik Pembayaran
$stats_pembayaran = $conn->query("
    SELECT 
        COUNT(*) as total_pembayaran,
        SUM(CASE WHEN status_pembayaran = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status_pembayaran = 'Lunas' THEN 1 ELSE 0 END) as lunas,
        SUM(CASE WHEN status_pembayaran = 'Overdue' THEN 1 ELSE 0 END) as overdue
    FROM pembayaran
");
$stats_pembayaran = $stats_pembayaran ? $stats_pembayaran->fetch_assoc() : 
    ['total_pembayaran' => 0, 'pending' => 0, 'lunas' => 0, 'overdue' => 0];

// Total Nilai Transaksi
$total_nilai = $conn->query("
    SELECT 
        COALESCE(SUM(CAST(nominal AS DECIMAL(15,2))), 0) as total_transaksi,
        COALESCE(SUM(CASE WHEN status_pembayaran = 'Lunas' THEN CAST(nominal AS DECIMAL(15,2)) ELSE 0 END), 0) as total_lunas,
        COALESCE(SUM(CASE WHEN status_pembayaran = 'Pending' THEN CAST(nominal AS DECIMAL(15,2)) ELSE 0 END), 0) as total_pending
    FROM pembayaran
");
$total_nilai = $total_nilai ? $total_nilai->fetch_assoc() : 
    ['total_transaksi' => 0, 'total_lunas' => 0, 'total_pending' => 0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Finance - PT. Usimpel Inovasi Indonesia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        /* Dashboard Finance - Full Screen Override */
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
            background: linear-gradient(90deg, #e74c3c, #c0392b);
            border-radius: 20px 20px 0 0;
        }
        .stat-card.green::before {
            background: linear-gradient(90deg, #27ae60, #229954);
        }
        .stat-card.blue::before {
            background: linear-gradient(90deg, #3498db, #2980b9);
        }
        .stat-card.orange::before {
            background: linear-gradient(90deg, #f39c12, #e67e22);
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
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-number.green {
            background: linear-gradient(135deg, #27ae60, #229954);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-number.blue {
            background: linear-gradient(135deg, #3498db, #2980b9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-number.orange {
            background: linear-gradient(135deg, #f39c12, #e67e22);
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
            color: #e74c3c;
        }
        .stat-detail.green i {
            color: #27ae60;
        }
        .stat-detail.blue i {
            color: #3498db;
        }
        .stat-detail.orange i {
            color: #f39c12;
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
        }
        .welcome-section {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 40px;
            border-radius: 25px;
            margin-bottom: 40px;
            box-shadow: 0 20px 40px rgba(231, 76, 60, 0.3);
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
            background: linear-gradient(90deg, #e74c3c, #c0392b);
            border-radius: 2px;
        }
        .table-small {
            font-size: 0.9em;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .table-small th {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
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
            background-color: #fff5f5;
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
        .status-pending { 
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        .status-lunas { 
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        .status-overdue { 
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            animation: blink 2s infinite;
        }
        .status-draft { 
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }
        .status-sent { 
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        .status-paid { 
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        .status-diproses { 
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.7; }
        }
        .currency {
            font-family: 'Courier New', monospace;
            font-weight: 600;
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
                <div style="position: relative; z-index: 2;">
                    <h1 style="font-size: 2.5em; margin-bottom: 10px; font-weight: 700; text-align: center;">
                        <i class="fas fa-chart-line" style="margin-right: 15px;"></i> 
                        Dashboard Finance & Billing
                    </h1>
                    <p style="text-align: center; font-size: 1.1em; margin-bottom: 20px; opacity: 0.9; font-weight: 500;">
                        Manajemen Keuangan & Pembayaran
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
                        <a href="../pembayaran/pembayaran.php" class="quick-action-btn">
                            <div style="position: relative; z-index: 2;">
                                <i class="fas fa-credit-card" style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                                <strong>Kelola Pembayaran</strong>
                                <small style="display: block; margin-top: 5px; opacity: 0.8;">Proses pembayaran</small>
                            </div>
                        </a>
                        <a href="../laporan/laporan_scm.php" class="quick-action-btn">
                            <div style="position: relative; z-index: 2;">
                                <i class="fas fa-chart-bar" style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                                <strong>Laporan</strong>
                                <small style="display: block; margin-top: 5px; opacity: 0.8;">Analisis keuangan</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistik Cards -->
            <div class="dashboard-grid">
                <!-- Statistik Pembayaran -->
                <div class="stat-card" style="--icon-color-1: #e74c3c; --icon-color-2: #c0392b; --progress-color-1: #e74c3c; --progress-color-2: #c0392b;">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number"><?= $stats_pembayaran['total_pembayaran'] ?? 0 ?></div>
                            <div class="stat-label">Total Pembayaran</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                    </div>
                    <div class="stat-detail green">
                        <i class="fas fa-check-circle"></i> Pembayaran Lunas: <strong><?= $stats_pembayaran['lunas'] ?? 0 ?></strong>
                    </div>
                    <div class="stat-detail">
                        <i class="fas fa-chart-line"></i> Total Transaksi Keuangan
                    </div>
                    <?php 
                    $total_pay = $stats_pembayaran['total_pembayaran'] ?? 0;
                    $lunas = $stats_pembayaran['lunas'] ?? 0;
                    $success_rate = $total_pay > 0 ? ($lunas / $total_pay) * 100 : 0;
                    ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $success_rate ?>%;"></div>
                    </div>
                    <small style="color: #27ae60; font-weight: 600; margin-top: 8px; display: block;">
                        <i class="fas fa-percentage"></i> Success Rate: <?= number_format($success_rate, 1) ?>%
                    </small>
                </div>

                <!-- Total Nilai Transaksi -->
                <div class="stat-card green" style="--icon-color-1: #27ae60; --icon-color-2: #229954; --progress-color-1: #27ae60; --progress-color-2: #229954;">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number green">Rp <?= number_format($total_nilai['total_transaksi'] ?? 0, 0, ',', '.') ?></div>
                            <div class="stat-label">Total Transaksi</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="stat-detail green">
                        <i class="fas fa-check-double"></i> Lunas: <strong class="currency">Rp <?= number_format($total_nilai['total_lunas'] ?? 0, 0, ',', '.') ?></strong>
                    </div>
                    <div class="stat-detail orange">
                        <i class="fas fa-hourglass-half"></i> Pending: <strong class="currency">Rp <?= number_format($total_nilai['total_pending'] ?? 0, 0, ',', '.') ?></strong>
                    </div>
                </div>
            </div>
        </main>
        
    </div>

    <script src="../assets/js/main.js"></script>
    
</body>
</html>