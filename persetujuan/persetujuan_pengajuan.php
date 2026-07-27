<?php
session_start();
require_once '../config.php';

// Proteksi halaman, hanya Kepala Divisi dan Inventory yang bisa melihat
if (!in_array($_SESSION['jabatan'], ['Kepala Divisi Produk & Pengadaan', 'Inventory & Purchasing Officer'])) {
    header('Location: ../login.php');
    exit();
}

// Logika untuk menampilkan pesan status
$status_message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $status_message = '<div class="status-message success">Keputusan berhasil disimpan.</div>';
    } else {
        $status_message = '<div class="status-message error">Terjadi kesalahan saat memproses keputusan.</div>';
    }
}

// Cek apakah user adalah inventory (hanya bisa melihat)
$is_inventory = ($_SESSION['jabatan'] == 'Inventory & Purchasing Officer');

// Cek mode tampilan untuk Kepala Divisi
$show_history = isset($_GET['mode']) && $_GET['mode'] == 'history';

// Filter tanggal untuk inventory dan kepala divisi (jika mode histori)
$date_filter = '';
if (($is_inventory || $show_history) && (isset($_GET['start_date']) || isset($_GET['end_date']))) {
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    
    if ($start_date && $end_date) {
        $date_filter = " AND DATE(pb.tanggal_pengajuan) BETWEEN '$start_date' AND '$end_date'";
    } elseif ($start_date) {
        $date_filter = " AND DATE(pb.tanggal_pengajuan) >= '$start_date'";
    } elseif ($end_date) {
        $date_filter = " AND DATE(pb.tanggal_pengajuan) <= '$end_date'";
    }
}

// Query berbeda berdasarkan role
if ($is_inventory) {
    // Untuk inventory: tampilkan semua pengajuan (termasuk yang sudah diproses)
    $sql = "SELECT pb.id_pengajuan, pb.tanggal_pengajuan, b.produk, b.merek, pb.jumlah_diajukan, s.nama_supplier, pb.status_pengajuan, pb.catatan_keputusan as catatan_persetujuan
            FROM pengajuan_barang pb
            JOIN barang b ON pb.id_barang = b.id_barang
            JOIN supplier s ON pb.id_supplier = s.id_supplier
            WHERE 1=1 $date_filter
            ORDER BY 
                CASE 
                    WHEN pb.status_pengajuan = 'Diajukan' THEN 1
                    WHEN pb.status_pengajuan = 'Disetujui' THEN 2
                    WHEN pb.status_pengajuan = 'Diproses' THEN 3
                    WHEN pb.status_pengajuan = 'Ditolak' THEN 4
                    ELSE 5
                END,
                pb.tanggal_pengajuan DESC,
                CAST(SUBSTRING(pb.id_pengajuan, 6) AS UNSIGNED) ASC";
} elseif ($show_history) {
    // Untuk kepala divisi mode histori: tampilkan semua pengajuan yang sudah diputuskan
    $sql = "SELECT pb.id_pengajuan, pb.tanggal_pengajuan, b.produk, b.merek, pb.jumlah_diajukan, s.nama_supplier, pb.status_pengajuan, pb.catatan_keputusan as catatan_persetujuan, pb.tanggal_keputusan
            FROM pengajuan_barang pb
            JOIN barang b ON pb.id_barang = b.id_barang
            JOIN supplier s ON pb.id_supplier = s.id_supplier
            WHERE pb.status_pengajuan IN ('Disetujui', 'Ditolak') $date_filter
            ORDER BY pb.tanggal_keputusan DESC, pb.tanggal_pengajuan DESC,
                CAST(SUBSTRING(pb.id_pengajuan, 6) AS UNSIGNED) ASC";
} else {
    // Untuk kepala divisi: hanya tampilkan yang butuh persetujuan
    $sql = "SELECT pb.id_pengajuan, pb.tanggal_pengajuan, b.produk, b.merek, pb.jumlah_diajukan, s.nama_supplier
            FROM pengajuan_barang pb
            JOIN barang b ON pb.id_barang = b.id_barang
            JOIN supplier s ON pb.id_supplier = s.id_supplier
            WHERE pb.status_pengajuan = 'Diajukan'
            ORDER BY pb.tanggal_pengajuan ASC, CAST(SUBSTRING(pb.id_pengajuan, 6) AS UNSIGNED) ASC";
}
$result = $conn->query($sql);

// Debug: Hitung total data untuk inventory dan kepala divisi histori
if ($is_inventory || $show_history) {
    // Total tanpa filter
    if ($is_inventory) {
        $count_all_sql = "SELECT COUNT(*) as total FROM pengajuan_barang pb WHERE 1=1";
    } else {
        $count_all_sql = "SELECT COUNT(*) as total FROM pengajuan_barang pb WHERE pb.status_pengajuan IN ('Disetujui', 'Ditolak')";
    }
    $count_all_result = $conn->query($count_all_sql);
    $total_all_data = $count_all_result->fetch_assoc()['total'];
    
    // Total dengan filter (jika ada)
    if ($is_inventory) {
        $count_filtered_sql = "SELECT COUNT(*) as total FROM pengajuan_barang pb WHERE 1=1 $date_filter";
    } else {
        $count_filtered_sql = "SELECT COUNT(*) as total FROM pengajuan_barang pb WHERE pb.status_pengajuan IN ('Disetujui', 'Ditolak') $date_filter";
    }
    $count_filtered_result = $conn->query($count_filtered_sql);
    $total_filtered_data = $count_filtered_result->fetch_assoc()['total'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Persetujuan Pengajuan - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .table-container { background: white; padding: 2rem; border-radius: 10px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px 15px; border: 1px solid #e1e1e1; text-align: center; }
        .data-table th { background-color: #f8f9fa; font-weight: 600; text-align: center; }
        .data-table td:nth-child(3), .data-table td:nth-child(5), .data-table td:nth-child(8) { text-align: left; } /* Nama Barang, Supplier, dan Catatan columns */
        .action-buttons { display: flex; gap: 8px; }
        .action-buttons form { display: inline-block; margin-right: 5px; }
        .btn-approve, .btn-reject { 
            border: none; 
            padding: 8px 16px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 13px; 
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 80px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-approve { 
            background-color: var(--success); 
            color: white; 
        }
        .btn-approve:hover { 
            background-color: #27ae60; 
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .btn-reject { 
            background-color: var(--danger); 
            color: white; 
        }
        .btn-reject:hover { 
            background-color: #c0392b; 
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .status-message { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .status-message.success { background-color: rgba(39, 174, 96, 0.2); color: #27ae60; border: 1px solid #27ae60; }
        .status-message.error { background-color: rgba(231, 76, 60, 0.2); color: var(--danger); border: 1px solid var(--danger); }
        .notes-input { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd; font-size: 13px; }
        
        /* Toggle button styling */
        .toggle-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
        }
        
        /* Status badges untuk inventory */
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
            display: inline-block;
        }
        .status-approved {
            background-color: var(--success);
        }
        .status-rejected {
            background-color: var(--danger);
        }
        .status-pending {
            background-color: var(--warning);
            color: #333;
        }
        .status-processed {
            background-color: var(--info);
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <div class="table-container">
                <h2 class="dashboard-title"><i class="fas fa-check-double"></i> 
                    <?php if ($is_inventory): ?>
                        Lihat Pengajuan Barang
                        <small style="font-size: 0.7rem; color: #666; font-weight: normal;">
                            <?php if ($date_filter != ''): ?>
                                (Filter Aktif: <?= $total_filtered_data ?? 0 ?> dari <?= $total_all_data ?? 0 ?> data | Ditampilkan: <?= $result ? $result->num_rows : 0 ?>) 🔄 v2.1
                            <?php else: ?>
                                (Total: <?= $total_all_data ?? 0 ?> data | Ditampilkan: <?= $result ? $result->num_rows : 0 ?>) ✅ v2.1
                            <?php endif; ?>
                        </small>
                    <?php elseif ($show_history): ?>
                        Histori Keputusan Persetujuan
                        <small style="font-size: 0.7rem; color: #666; font-weight: normal;">
                            <?php if ($date_filter != ''): ?>
                                (Filter Aktif: <?= $total_filtered_data ?? 0 ?> dari <?= $total_all_data ?? 0 ?> data | Ditampilkan: <?= $result ? $result->num_rows : 0 ?>)
                            <?php else: ?>
                                (Total: <?= $total_all_data ?? 0 ?> data | Ditampilkan: <?= $result ? $result->num_rows : 0 ?>)
                            <?php endif; ?>
                        </small>
                    <?php else: ?>
                        Persetujuan Pengajuan Barang
                    <?php endif; ?>
                </h2>
                
                <?php if (!$is_inventory): ?>
                    <!-- Toggle Mode untuk Kepala Divisi -->
                    <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 20px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #dee2e6;">
                        <div style="display: flex; justify-content: center; gap: 0; max-width: 500px; margin: 0 auto;">
                            <?php if (!$show_history): ?>
                                <a href="?mode=pending" class="toggle-btn active" style="
                                    background: linear-gradient(135deg, #007bff, #0056b3);
                                    color: white;
                                    padding: 12px 24px;
                                    text-decoration: none;
                                    border-radius: 8px 0 0 8px;
                                    font-weight: 600;
                                    transition: all 0.3s ease;
                                    box-shadow: 0 2px 8px rgba(0,123,255,0.3);
                                    flex: 1;
                                    text-align: center;
                                    border: 2px solid #007bff;
                                ">
                                    <i class="fas fa-clock"></i> Menunggu Persetujuan
                                </a>
                                <a href="?mode=history" class="toggle-btn" style="
                                    background: white;
                                    color: #6c757d;
                                    padding: 12px 24px;
                                    text-decoration: none;
                                    border-radius: 0 8px 8px 0;
                                    font-weight: 500;
                                    transition: all 0.3s ease;
                                    flex: 1;
                                    text-align: center;
                                    border: 2px solid #dee2e6;
                                    border-left: none;
                                ">
                                    <i class="fas fa-history"></i> Histori Keputusan
                                </a>
                            <?php else: ?>
                                <a href="?" class="toggle-btn" style="
                                    background: white;
                                    color: #6c757d;
                                    padding: 12px 24px;
                                    text-decoration: none;
                                    border-radius: 8px 0 0 8px;
                                    font-weight: 500;
                                    transition: all 0.3s ease;
                                    flex: 1;
                                    text-align: center;
                                    border: 2px solid #dee2e6;
                                ">
                                    <i class="fas fa-clock"></i> Menunggu Persetujuan
                                </a>
                                <a href="?mode=history" class="toggle-btn active" style="
                                    background: linear-gradient(135deg, #28a745, #1e7e34);
                                    color: white;
                                    padding: 12px 24px;
                                    text-decoration: none;
                                    border-radius: 0 8px 8px 0;
                                    font-weight: 600;
                                    transition: all 0.3s ease;
                                    box-shadow: 0 2px 8px rgba(40,167,69,0.3);
                                    flex: 1;
                                    text-align: center;
                                    border: 2px solid #28a745;
                                    border-left: none;
                                ">
                                    <i class="fas fa-history"></i> Histori Keputusan
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($is_inventory): ?>
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
                <?php elseif ($show_history): ?>
                    <div style="background-color: #e8f5e8; border: 1px solid #4caf50; border-radius: 5px; padding: 15px; margin-bottom: 20px; color: #2e7d32;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Informasi:</strong> Menampilkan histori keputusan persetujuan yang telah Anda buat (disetujui/ditolak). 
                        <?php if (isset($date_filter) && $date_filter != ''): ?>
                            <br><strong>Filter Aktif:</strong> Data difilter berdasarkan tanggal pengajuan yang dipilih.
                        <?php else: ?>
                            <br><strong>Tampilan:</strong> Semua histori keputusan tanpa filter tanggal.
                        <?php endif ?>
                        Data diurutkan berdasarkan tanggal keputusan terbaru.
                    </div>
                <?php endif; ?>
                    
                    <!-- Filter Tanggal untuk Inventory dan Kepala Divisi (mode histori) -->
                    <?php if ($is_inventory || $show_history): ?>
                    <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e9ecef;">
                        <h4 style="margin: 0 0 15px 0; color: #495057; font-size: 1.1rem; font-weight: 600;">
                            <i class="fas fa-calendar-alt" style="margin-right: 8px; color: var(--primary);"></i>
                            Filter Berdasarkan Periode
                        </h4>
                        <form method="GET" style="display: flex; gap: 20px; align-items: end; flex-wrap: wrap;">
                            <?php if ($show_history): ?>
                                <input type="hidden" name="mode" value="history">
                            <?php endif; ?>
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
                                    font-size: 0.95rem;
                                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                                    box-shadow: 0 2px 8px rgba(0,123,255,0.3);
                                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,123,255,0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,123,255,0.3)'">
                                    <i class="fas fa-search"></i> Tampilkan Data
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                
                <?php echo $status_message; ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tanggal Diajukan</th>
                            <th>Nama Barang</th>
                            <th>Jumlah</th>
                            <th>Supplier</th>
                            <?php if ($is_inventory || $show_history): ?>
                                <th>Status</th>
                                <?php if ($show_history): ?>
                                    <th>Tanggal Keputusan</th>
                                <?php endif; ?>
                                <th>Catatan</th>
                            <?php else: ?>
                                <th>Catatan (Opsional)</th>
                                <th>Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row["id_pengajuan"]; ?></td>
                                    <td><?php echo date("d-m-Y", strtotime($row["tanggal_pengajuan"])); ?></td>
                                    <td><?php echo htmlspecialchars($row["produk"] . ' - ' . $row["merek"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["jumlah_diajukan"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["nama_supplier"]); ?></td>
                                    
                                    <?php if ($is_inventory || $show_history): ?>
                                        <!-- Tampilan untuk Inventory dan Histori Kepala Divisi -->
                                        <td>
                                            <?php 
                                            $status = $row["status_pengajuan"];
                                            $status_class = '';
                                            $display_status = $status;
                                            
                                            if ($status == 'Disetujui') {
                                                $status_class = 'status-approved';
                                                $display_status = 'Disetujui';
                                            } elseif ($status == 'Diproses') {
                                                $status_class = 'status-approved';
                                                $display_status = 'Disetujui (Sedang Diproses)';
                                            } elseif ($status == 'Ditolak') {
                                                $status_class = 'status-rejected';
                                                $display_status = 'Ditolak';
                                            } elseif ($status == 'Diajukan') {
                                                $status_class = 'status-pending';
                                                $display_status = 'Menunggu Persetujuan';
                                            } else {
                                                $status_class = 'status-processed';
                                                $display_status = $status;
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $display_status; ?>
                                            </span>
                                        </td>
                                        <?php if ($show_history): ?>
                                            <td><?php echo isset($row["tanggal_keputusan"]) ? date("d-m-Y H:i", strtotime($row["tanggal_keputusan"])) : '-'; ?></td>
                                        <?php endif; ?>
                                        <td><?php 
                                            $catatan = $row["catatan_persetujuan"] ?? '';
                                            if (empty(trim($catatan))) {
                                                echo "Tidak ada catatan";
                                            } else {
                                                echo htmlspecialchars($catatan);
                                            }
                                        ?></td>
                                    <?php else: ?>
                                        <!-- Tampilan untuk Kepala Divisi (Mode Persetujuan) -->
                                        <form action="proses_persetujuan.php" method="POST">
                                            <input type="hidden" name="id_pengajuan" value="<?php echo $row["id_pengajuan"]; ?>">
                                            <td>
                                                <input type="text" name="catatan" class="notes-input" placeholder="Misal: Kebutuhan mendesak">
                                            </td>
                                            <td class="action-buttons">
                                                <button type="submit" name="keputusan" value="Disetujui" class="btn-approve">Setujui</button>
                                                <button type="submit" name="keputusan" value="Ditolak" class="btn-reject">Tolak</button>
                                            </td>
                                        </form>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $is_inventory ? '7' : '7'; ?>" style="text-align: center;">
                                    <?php if ($is_inventory): ?>
                                        Tidak ada data pengajuan barang.
                                    <?php else: ?>
                                        Tidak ada pengajuan yang memerlukan persetujuan.
                                    <?php endif; ?>
                                </td>
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
<?php $conn->close(); ?>