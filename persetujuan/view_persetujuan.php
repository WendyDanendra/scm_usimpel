<?php
session_start();
require_once '../config.php';

// Proteksi halaman - hanya untuk Inventory & Purchasing Officer
if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Inventory & Purchasing Officer') {
    header('Location: ../login.php');
    exit();
}

// Filter tanggal
$date_filter = '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

if ($start_date && $end_date) {
    $date_filter = " AND DATE(pb.tanggal_pengajuan) BETWEEN '$start_date' AND '$end_date'";
} elseif ($start_date) {
    $date_filter = " AND DATE(pb.tanggal_pengajuan) >= '$start_date'";
} elseif ($end_date) {
    $date_filter = " AND DATE(pb.tanggal_pengajuan) <= '$end_date'";
}

// Query untuk mengambil data persetujuan
$sql = "SELECT pb.id_pengajuan, pb.tanggal_pengajuan, b.produk, b.merek, 
               pb.jumlah_diajukan, s.nama_supplier, pb.status_pengajuan, 
               pb.catatan_keputusan as catatan_persetujuan
        FROM pengajuan_barang pb
        JOIN barang b ON pb.id_barang = b.id_barang
        JOIN supplier s ON pb.id_supplier = s.id_supplier
        WHERE 1=1 $date_filter
        ORDER BY 
            pb.tanggal_pengajuan DESC,
            CAST(SUBSTRING(pb.id_pengajuan, 6) AS UNSIGNED) DESC";

$result = $conn->query($sql);

// Hitung total data
$total_count_sql = "SELECT COUNT(*) as total FROM pengajuan_barang";
$total_count_result = $conn->query($total_count_sql);
$total_all_data = $total_count_result ? $total_count_result->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Persetujuan - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .status-diajukan {
            background-color: var(--warning);
            color: #333;
        }
        .status-disetujui {
            background-color: var(--success);
            color: white;
        }
        .status-ditolak {
            background-color: var(--danger);
            color: white;
        }
        .status-pending {
            background-color: var(--warning);
            color: #333;
        }
        .status-processed {
            background-color: var(--info);
            color: white;
        }
        .table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }
        .table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #dee2e6;
            padding: 15px 10px;
            white-space: nowrap;
        }
        .table td {
            vertical-align: middle;
            border: 1px solid #dee2e6;
            padding: 12px 10px;
            word-wrap: break-word;
        }
        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .table tbody tr:hover {
            background-color: #e9ecef;
        }
        .table td:first-child {
            text-align: center;
            font-weight: 600;
            color: var(--primary);
            white-space: nowrap;
        }
        .table td:nth-child(2) {
            text-align: center;
            white-space: nowrap;
        }
        .table td:nth-child(5) {
            text-align: center;
            white-space: nowrap;
        }
        .table td:nth-child(6) {
            text-align: center;
        }
        .product-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
            line-height: 1.2;
        }
        .product-brand {
            font-size: 0.85rem;
            color: #666;
            font-style: italic;
        }
        .supplier-name {
            font-weight: 500;
            color: #444;
            line-height: 1.3;
        }
        .quantity {
            font-weight: 600;
            color: var(--primary);
        }
        .notes-cell {
            max-width: 250px;
            word-wrap: break-word;
            line-height: 1.4;
        }
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .table-container {
            overflow-x: auto;
            margin: 0 -20px;
            padding: 0 20px;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        @media (max-width: 768px) {
            .table {
                font-size: 0.9rem;
            }
            .table th, .table td {
                padding: 8px 6px;
            }
            .table-container {
                margin: 0 -10px;
                padding: 0 10px;
            }
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <div class="table-container">
                <h2 class="dashboard-title"><i class="fas fa-eye"></i> 
                    Lihat Pengajuan Barang
                    <small style="font-size: 0.7rem; color: #666; font-weight: normal;">
                        <?php if ($date_filter != ''): ?>
                            (Filter Aktif: <?= $result ? $result->num_rows : 0 ?> dari <?= $total_all_data ?? 0 ?> data | Ditampilkan: <?= $result ? $result->num_rows : 0 ?>) 🔄 v2.1
                        <?php else: ?>
                            (Total: <?= $total_all_data ?? 0 ?> data | Ditampilkan: <?= $result ? $result->num_rows : 0 ?>) ✅ v2.1
                        <?php endif; ?>
                    </small>
                </h2>
                
                <div style="background-color: #e3f2fd; border: 1px solid #2196f3; border-radius: 5px; padding: 15px; margin-bottom: 20px; color: #1976d2;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Informasi:</strong> Menampilkan semua pengajuan barang beserta status persetujuannya. 
                    <?php if (isset($date_filter) && $date_filter != ''): ?>
                        <br><strong>Filter Aktif:</strong> Data difilter berdasarkan tanggal yang dipilih.
                    <?php else: ?>
                        <br><strong>Tampilan:</strong> Semua data tanpa filter tanggal.
                    <?php endif ?>
                    Data diurutkan berdasarkan prioritas: menunggu persetujuan, disetujui, sedang diproses, dan ditolak.
                    <br><small style="color: #0d47a1;"><strong>Debug:</strong> Query berhasil mengambil <?= $result ? $result->num_rows : 0 ?> dari <?= $total_all_data ?? 0 ?> total data. Jika tidak melihat semua data, coba refresh halaman dengan Ctrl+F5.</small>
                </div>
                
                <!-- Filter Tanggal -->
                <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e9ecef;">
                    <h4 style="margin: 0 0 15px 0; color: #495057; font-size: 1.1rem; font-weight: 600;">
                        <i class="fas fa-calendar-alt" style="margin-right: 8px; color: var(--primary);"></i>
                        Filter Berdasarkan Periode
                    </h4>
                    <form method="GET" style="display: flex; gap: 20px; align-items: end; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057; font-size: 0.9rem;">Dari Tanggal:</label>
                            <input type="date" name="start_date" value="<?php echo $_GET['start_date'] ?? ''; ?>" style="
                                width: 100%;
                                padding: 12px 15px;
                                border: 2px solid #e9ecef;
                                border-radius: 8px;
                                font-size: 0.95rem;
                                transition: border-color 0.3s ease;
                                box-sizing: border-box;
                            " onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e9ecef'">
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057; font-size: 0.9rem;">Sampai Tanggal:</label>
                            <input type="date" name="end_date" value="<?php echo $_GET['end_date'] ?? ''; ?>" style="
                                width: 100%;
                                padding: 12px 15px;
                                border: 2px solid #e9ecef;
                                border-radius: 8px;
                                font-size: 0.95rem;
                                transition: border-color 0.3s ease;
                                box-sizing: border-box;
                            " onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e9ecef'">
                        </div>
                        <div style="flex: 0 0 auto;">
                            <button type="submit" style="
                                background: linear-gradient(135deg, var(--primary), #0056b3);
                                color: white;
                                border: none;
                                padding: 12px 24px;
                                border-radius: 8px;
                                cursor: pointer;
                                font-weight: 600;
                                transition: all 0.3s ease;
                                box-shadow: 0 2px 6px rgba(0,0,0,0.15);
                            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)'" onmouseout="this.style.transform='translateY(0px)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.15)'">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID Pengajuan</th>
                                    <th>Tanggal</th>
                                    <th>Barang</th>
                                    <th>Supplier</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id_pengajuan']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                        <td>
                                            <div class="product-name"><?php echo htmlspecialchars($row['produk']); ?></div>
                                            <?php if($row['merek']): ?>
                                                <div class="product-brand"><?php echo htmlspecialchars($row['merek']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="supplier-name"><?php echo htmlspecialchars($row['nama_supplier']); ?></td>
                                        <td class="quantity"><?php echo number_format($row['jumlah_diajukan']); ?> unit</td>
                                        <td>
                                            <span class="badge status-<?php echo strtolower($row['status_pengajuan']); ?>">
                                                <?php echo htmlspecialchars($row['status_pengajuan']); ?>
                                            </span>
                                        </td>
                                        <td class="notes-cell">
                                            <?php 
                                            if($row['catatan_persetujuan']) {
                                                echo htmlspecialchars($row['catatan_persetujuan']);
                                            } else {
                                                echo '<span style="color: #999; font-style: italic;">Tidak ada catatan</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: #6c757d;">
                        <i class="fas fa-inbox" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h4>Tidak ada data pengajuan ditemukan</h4>
                        <?php if($start_date || $end_date): ?>
                            <p>Coba ubah filter tanggal untuk melihat data lain</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
