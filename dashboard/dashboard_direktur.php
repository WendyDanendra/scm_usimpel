<?php
session_start();
require_once '../config.php';

if ($_SESSION['jabatan'] != 'Direktur Operasional') {
    header("Location: ../login.php");
    exit();
}

// Get current date for welcome message
$current_date = date('d F Y');
$current_time = date('H:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Direktur - PT. Usimpel Inovasi Indonesia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        /* Dashboard Direktur - Full Screen Override */
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
            background: linear-gradient(135deg, #667eea, #764ba2);
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
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .quick-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .quick-action-btn {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255,255,255,0.2);
            color: white;
            padding: 20px 30px;
            text-decoration: none;
            border-radius: 15px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            min-width: 200px;
            max-width: 250px;
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
            margin-top: 20px;
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
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                padding: 20px !important;
                width: 100vw !important;
            }
            
            .welcome-section {
                padding: 30px 20px;
            }
            
            .quick-actions {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            
            .quick-action-btn {
                min-width: 180px;
                max-width: 200px;
            }
        }
        
        @media (max-width: 1200px) {
            .main-content {
                margin-left: 200px !important;
                width: calc(100vw - 200px) !important;
            }
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
                            Dashboard Direktur Operasional
                        </h1>
                        <p style="text-align: center; font-size: 1.1em; margin-bottom: 20px; opacity: 0.9; font-weight: 500;">
                            Executive Management Dashboard
                        </p>
                        
                        <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 15px; backdrop-filter: blur(10px); margin: 20px 0;">
                            <p style="margin: 0; font-size: 1.2em; font-weight: 500;">
                                <i class="fas fa-user-circle" style="margin-right: 8px;"></i>
                                Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['username'] ?? 'Direktur'); ?></strong>! 
                            </p>
                            <p style="margin: 8px 0 0 0; font-size: 1em; opacity: 0.9;">
                                <i class="fas fa-id-badge" style="margin-right: 8px;"></i>
                                Role: <strong>Direktur Operasional</strong>
                            </p>
                            <p style="margin: 8px 0 0 0; opacity: 0.9;">
                                <i class="fas fa-clock" style="margin-right: 8px;"></i> 
                                <?= date('l, d F Y - H:i') ?> WIB
                            </p>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="quick-actions">
                            <a href="../laporan/laporan_scm.php" class="quick-action-btn">
                                <div style="position: relative; z-index: 2;">
                                    <i class="fas fa-chart-line" style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                                    <strong>Laporan SCM</strong>
                                    <small style="display: block; margin-top: 5px; opacity: 0.8;">Analisis & Monitoring</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        
        <script>
            // Simple script for any future functionality
        </script>
    </div>
</body>
</html>
