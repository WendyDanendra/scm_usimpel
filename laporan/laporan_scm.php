<?php
session_start();
require_once '../config.php';
require_once '../helpers.php'; // Untuk generate_id

// 1. HAK AKSES
$jabatan = $_SESSION['jabatan'] ?? '';
$allowed_roles = ['Inventory & Purchasing Officer', 'Finance & Billing Officer', 'Kepala Divisi Produk & Pengadaan', 'Direktur Operasional'];
if (!in_array($jabatan, $allowed_roles)) {
    header('Location: ../login.php');
    exit();
}

// Definisikan hak akses untuk setiap tab
$can_view_stok_pengadaan = in_array($jabatan, ['Direktur Operasional', 'Inventory & Purchasing Officer', 'Kepala Divisi Produk & Pengadaan']);
$can_view_pengeluaran = in_array($jabatan, ['Direktur Operasional', 'Finance & Billing Officer']);

// 2. LOGIKA FILTER TANGGAL
$start_date = $_POST['start_date'] ?? date('Y-m-01');
$end_date = $_POST['end_date'] ?? date('Y-m-t');

// 3. QUERY UNTUK RINGKASAN (SUMMARY CARDS)
// Total Produk (tidak terikat tanggal)
$total_produk = $conn->query("SELECT COUNT(id_barang) AS total FROM barang")->fetch_assoc()['total'] ?? 0;

// Total Nilai Pengadaan pada periode terpilih
$stmt_pengadaan = $conn->prepare("SELECT SUM(pb.jumlah_diajukan * b.harga) AS total FROM pengadaan_barang pd JOIN pengajuan_barang pb ON pd.id_pengajuan = pb.id_pengajuan JOIN barang b ON pb.id_barang = b.id_barang WHERE pd.tanggal_pengadaan BETWEEN ? AND ?");
$stmt_pengadaan->bind_param("ss", $start_date, $end_date);
$stmt_pengadaan->execute();
$total_pengadaan = $stmt_pengadaan->get_result()->fetch_assoc()['total'] ?? 0;

// Total Pengeluaran pada periode terpilih
$stmt_pengeluaran = $conn->prepare("SELECT SUM(nominal) AS total FROM pembayaran WHERE tanggal_pembayaran BETWEEN ? AND ?");
$stmt_pengeluaran->bind_param("ss", $start_date, $end_date);
$stmt_pengeluaran->execute();
$total_pengeluaran = $stmt_pengeluaran->get_result()->fetch_assoc()['total'] ?? 0;


// 4. QUERY UNTUK TABEL LAPORAN DENGAN FILTER TANGGAL

// Laporan stok real-time (query langsung dari stok_barang)
$laporan_stok = $conn->query("
    SELECT 
        CONCAT('LPRN-', LPAD(ROW_NUMBER() OVER (ORDER BY 
            CASE 
                WHEN sb.status_stok = 'kritis' THEN 1
                WHEN sb.status_stok = 'rendah' THEN 2
                WHEN sb.status_stok = 'normal' THEN 3
                ELSE 4
            END,
            b.produk ASC
        ), 5, '0')) as id_laporan,
        b.produk, 
        b.merek, 
        s.nama_supplier, 
        sb.stok_tersedia,
        sb.rop,
        sb.safety_stock,
        sb.status_stok,
        sb.permintaan_harian,
        CURDATE() as tanggal_laporan
    FROM stok_barang sb
    JOIN barang b ON sb.id_barang = b.id_barang
    LEFT JOIN supplier s ON b.id_supplier = s.id_supplier
    ORDER BY 
        CASE 
            WHEN sb.status_stok = 'kritis' THEN 1
            WHEN sb.status_stok = 'rendah' THEN 2
            WHEN sb.status_stok = 'normal' THEN 3
            ELSE 4
        END,
        b.produk ASC
");

// Laporan Pengadaan - Kembali ke query original yang sudah terbukti bekerja
$stmt_lap_pengadaan = $conn->prepare("SELECT pd.id_pengadaan, pd.tanggal_pengadaan, s.nama_supplier, b.produk, pb.jumlah_diajukan, b.harga, (pb.jumlah_diajukan * b.harga) AS total, pb.status_pengajuan FROM pengadaan_barang pd JOIN pengajuan_barang pb ON pd.id_pengajuan = pb.id_pengajuan JOIN barang b ON pb.id_barang = b.id_barang JOIN supplier s ON pb.id_supplier = s.id_supplier WHERE pd.tanggal_pengadaan BETWEEN ? AND ? ORDER BY pd.tanggal_pengadaan DESC, pd.id_pengadaan ASC");
$stmt_lap_pengadaan->bind_param("ss", $start_date, $end_date);
$stmt_lap_pengadaan->execute();
$laporan_pengadaan = $stmt_lap_pengadaan->get_result();

// Laporan Pengeluaran - Kembali ke query original yang sudah terbukti bekerja
$stmt_lap_pengeluaran = $conn->prepare("SELECT p.*, s.nama_supplier FROM pembayaran p JOIN pengadaan_barang pd ON p.id_pengadaan = pd.id_pengadaan JOIN pengajuan_barang pb ON pd.id_pengajuan = pb.id_pengajuan JOIN supplier s ON pb.id_supplier = s.id_supplier WHERE p.tanggal_pembayaran BETWEEN ? AND ? ORDER BY p.tanggal_pembayaran DESC, p.id_pembayaran ASC");
$stmt_lap_pengeluaran->bind_param("ss", $start_date, $end_date);
$stmt_lap_pengeluaran->execute();
$laporan_pengeluaran = $stmt_lap_pengeluaran->get_result();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan SCM - PT. Usimpel Inovasi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .main-content .content { padding: 20px; }
        .summary-section { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; } 
        .summary-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; }
        .summary-title { font-size: 1rem; color: var(--gray); margin-bottom: 10px; }
        .summary-value { font-size: 1.8rem; font-weight: 700; color: var(--primary); }
        .filter-section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .filter-row { display: flex; gap: 15px; align-items: flex-end; }
        .filter-group { flex: 1; }
        .filter-group label { display: block; margin-bottom: 5px; }
        .filter-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 20px; border-radius: 5px; cursor: pointer; border: none; }
        .btn-primary { background: var(--secondary); color: white; }
        .report-tabs { display: flex; gap: 5px; margin-bottom: -1px; }
        .tab { padding: 12px 20px; cursor: pointer; background: #f0f4f8; border: 1px solid #e0e6ed; border-bottom: none; border-radius: 8px 8px 0 0; }
        .tab.active { background: white; border-bottom: 1px solid white; position: relative; z-index: 2; }
        .report-content { display: none; background: white; border: 1px solid #e0e6ed; border-radius: 0 8px 8px 8px; }
        .report-content.active { display: block; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th, .report-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: center; }
        .report-table th { background-color: #f8f9fa; font-weight: 600; }
        
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; }
        .status-aman { background: rgba(39, 174, 96, 0.1); color: #27ae60; }
        .status-mendekati-rop { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
        .status-dibawah-rop { background: rgba(231, 76, 60, 0.1); color: #e74c3c; }
        
        /* Styling untuk tombol Lihat yang lebih bagus */
        .btn-view {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-view:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .btn-view i {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <h2 class="dashboard-title"><i class="fas fa-file-contract"></i> Laporan SCM</h2>
            
            <div class="filter-section">
                <form action="laporan_scm.php" method="POST">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="start_date">Dari Tanggal</label>
                            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                        </div>
                        <div class="filter-group">
                            <label for="end_date">Sampai Tanggal</label>
                            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="summary-section">
                <div class="summary-card"><div class="summary-title">Total Jenis Produk</div><div class="summary-value"><?= $total_produk ?></div></div>
                <div class="summary-card"><div class="summary-title">Total Nilai Pengadaan</div><div class="summary-value">Rp <?= number_format($total_pengadaan, 0, ',', '.') ?></div></div>
                <div class="summary-card"><div class="summary-title">Total Pengeluaran</div><div class="summary-value">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></div></div>
            </div>

            <div class="report-tabs">
                <?php if ($can_view_stok_pengadaan): ?><div class="tab active" data-tab="stok">Laporan Stok    <button class="btn btn-outline btn-print" data-type="stok"><i class="fas fa-print"></i> Cetak</button>
</div><?php endif; ?>
                <?php if ($can_view_stok_pengadaan): ?><div class="tab" data-tab="pengadaan">Laporan Pengadaan    <button class="btn btn-outline btn-print" data-type="pengadaan"><i class="fas fa-print"></i> Cetak</button>
</div><?php endif; ?>
                <?php if ($can_view_pengeluaran): ?><div class="tab" data-tab="pengeluaran">Laporan Pengeluaran    <button class="btn btn-outline btn-print" data-type="pengeluaran"><i class="fas fa-print"></i> Cetak</button>
</div><?php endif; ?>
            </div>

            <?php if ($can_view_stok_pengadaan): ?>
            <div id="stok" class="report-content active">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>ID Laporan</th>
                            <th>Produk</th>
                            <th>Merek</th>
                            <th>Supplier</th>
                            <th>Stok</th>
                            <th>ROP</th>
                            <th>Safety Stock</th>
                            <th>Permintaan Harian</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset pointer untuk query
                        $laporan_stok->data_seek(0);
                        while($row = $laporan_stok->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id_laporan']) ?></td>
                                <td><?= htmlspecialchars($row['produk']) ?></td>
                                <td><?= htmlspecialchars($row['merek']) ?></td>
                                <td><?= htmlspecialchars($row['nama_supplier'] ?? 'Tidak Ada') ?></td>
                                <td><?= (int)($row['stok_tersedia'] ?? 0) ?></td>
                                <td><?= (int)($row['rop'] ?? 0) ?></td>
                                <td><?= (int)($row['safety_stock'] ?? 0) ?></td>
                                <td><?= (int)($row['permintaan_harian'] ?? 0) ?></td>
                                <td><span class="status-badge status-<?= strtolower(str_replace(' ', '-', $row['status_stok'] ?? 'normal')) ?>"><?= ucfirst($row['status_stok'] ?? 'Normal') ?></span></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal_laporan'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if ($can_view_stok_pengadaan): ?>
            <div id="pengadaan" class="report-content">
                <table class="report-table">
                     <thead>
                        <tr>
                            <th>ID Pengadaan</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Harga Satuan</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                     <tbody>
                        <?php 
                        // Reset pointer untuk query pengadaan
                        $laporan_pengadaan->data_seek(0);
                        while($row = $laporan_pengadaan->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id_pengadaan']) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal_pengadaan'])) ?></td>
                                <td><?= htmlspecialchars($row['nama_supplier']) ?></td>
                                <td><?= htmlspecialchars($row['produk']) ?></td>
                                <td><?= number_format($row['jumlah_diajukan'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                <td><span class="status-badge"><?= htmlspecialchars($row['status_pengajuan']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($laporan_pengadaan->num_rows == 0): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #666; padding: 20px;">
                                    Tidak ada data pengadaan pada periode yang dipilih
                                </td>
                            </tr>
                        <?php endif; ?>
                     </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if ($can_view_pengeluaran): ?>
            <div id="pengeluaran" class="report-content">
                 <table class="report-table">
                    <thead>
                        <tr>
                            <th>ID Pembayaran</th>
                            <th>ID Pengadaan</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th>Nominal</th>
                            <th>Status</th>
                            <th>Bukti Transfer</th>
                            <th>Kuitansi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset pointer untuk query pengeluaran
                        $laporan_pengeluaran->data_seek(0);
                        while($row = $laporan_pengeluaran->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id_pembayaran']) ?></td>
                                <td><?= htmlspecialchars($row['id_pengadaan']) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal_pembayaran'])) ?></td>
                                <td><?= htmlspecialchars($row['nama_supplier']) ?></td>
                                <td>Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                                <td><span class="status-badge"><?= htmlspecialchars($row['status_pembayaran']) ?></span></td>
                                <td>
                                    <?php if (!empty($row['file_bukti_transfer'])): ?>
                                        <a href="../uploads/transfers/<?= htmlspecialchars($row['file_bukti_transfer']) ?>" target="_blank" class="btn-view">
                                            <i class="fas fa-file-image"></i> Lihat
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['file_kuitansi'])): ?>
                                        <a href="../uploads/receipts/<?= htmlspecialchars($row['file_kuitansi']) ?>" target="_blank" class="btn-view">
                                            <i class="fas fa-receipt"></i> Lihat
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($laporan_pengeluaran->num_rows == 0): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #666; padding: 20px;">
                                    Tidak ada data pengeluaran pada periode yang dipilih
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                 </table>
            </div>
            <?php endif; ?>

        </main>
    </div>
    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.report-content');
            
            // Set tab pertama yang terlihat sebagai aktif
            const firstVisibleTab = document.querySelector('.tab');
            const firstVisibleContent = document.querySelector('.report-content');
            if(firstVisibleTab){
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                firstVisibleTab.classList.add('active');
                firstVisibleContent.classList.add('active');
            }

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));
                    
                    tab.classList.add('active');
                    document.getElementById(tab.dataset.tab).classList.add('active');
                });
            });
        });

        document.querySelectorAll('.btn-print').forEach(button => {
    button.addEventListener('click', function() {
        const reportType = this.dataset.type;
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        // Buat URL dengan parameter
        const printUrl = `cetak_laporan.php?type=${reportType}&start_date=${startDate}&end_date=${endDate}`;

        // Buka di tab baru
        window.open(printUrl, '_blank');
    });
});
    </script>
</body>
</html>