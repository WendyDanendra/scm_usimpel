<?php
session_start();
require_once '../config.php';

// Ambil parameter dari URL
$report_type = $_GET['type'] ?? 'stok';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_title = '';
$data = null;

// ======================= KODE BARU UNTUK MENGAMBIL NAMA =======================
// Fungsi helper untuk mengambil nama user berdasarkan jabatan
function get_user_name_by_role($conn, $role) {
    $stmt = $conn->prepare("SELECT nama_user FROM user WHERE jabatan = ? LIMIT 1");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['nama_user'] ?? '.........................'; // Default jika tidak ditemukan
}

// Ambil nama untuk setiap jabatan
$nama_inventory = get_user_name_by_role($conn, 'Inventory & Purchasing Officer');
$nama_finance = get_user_name_by_role($conn, 'Finance & Billing Officer');
$nama_kadiv = get_user_name_by_role($conn, 'Kepala Divisi Produk & Pengadaan');
$nama_direktur = get_user_name_by_role($conn, 'Direktur Operasional');
// =============================================================================

// Query data berdasarkan jenis laporan (logika switch Anda sudah benar)
switch ($report_type) {
    case 'pengadaan':
        $report_title = 'Laporan Pengadaan Barang';
        $stmt = $conn->prepare("SELECT pd.id_pengadaan, pd.tanggal_pengadaan, s.nama_supplier, b.produk, pb.jumlah_diajukan, b.harga, (pb.jumlah_diajukan * b.harga) AS total FROM pengadaan_barang pd JOIN pengajuan_barang pb ON pd.id_pengajuan = pb.id_pengajuan JOIN barang b ON pb.id_barang = b.id_barang JOIN supplier s ON pb.id_supplier = s.id_supplier WHERE pd.tanggal_pengadaan BETWEEN ? AND ? ORDER BY pd.tanggal_pengadaan DESC");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $data = $stmt->get_result();
        $stmt->close();
        break;
    case 'pengeluaran':
        $report_title = 'Laporan Pengeluaran';
        $stmt = $conn->prepare("SELECT p.*, s.nama_supplier FROM pembayaran p JOIN pengadaan_barang pd ON p.id_pengadaan = pd.id_pengadaan JOIN pengajuan_barang pb ON pd.id_pengajuan = pb.id_pengajuan JOIN supplier s ON pb.id_supplier = s.id_supplier WHERE p.tanggal_pembayaran BETWEEN ? AND ? ORDER BY p.tanggal_pembayaran DESC");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $data = $stmt->get_result();
        $stmt->close();
        break;
    default:
        $report_title = 'Laporan Stok Barang';
        $data = $conn->query("SELECT b.produk, b.merek, sb.* FROM barang b LEFT JOIN stok_barang sb ON b.id_barang = sb.id_barang ORDER BY b.produk ASC");
        break;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $report_title ?></title>
    <link rel="stylesheet" href="../assets/css/print.css">
    <style>
        /* Reset dan styling dasar */
        body { 
            font-family: 'Times New Roman', serif; 
            font-size: 12pt; 
            line-height: 1.5;
            margin: 0;
            padding: 20px;
            color: #000;
        }
        
        .container { 
            width: 100%; 
            max-width: 21cm;
            margin: 0 auto; 
        }
        
        /* Header Kop Surat - Simple & Clean */
        .kop-surat { 
            text-align: center; 
            border-bottom: 3px double #000; 
            padding-bottom: 20px; 
            margin-bottom: 30px;
        }
        .kop-surat h1 { 
            margin: 0 0 8px 0; 
            font-size: 20pt; 
            font-weight: bold;
            color: #000;
        }
        .kop-surat p { 
            margin: 0; 
            font-size: 12pt; 
            color: #333;
        }
        
        /* Report Header */
        .report-details { 
            text-align: center; 
            margin-bottom: 25px;
        }
        .report-details h2 { 
            margin: 0 0 10px 0; 
            text-transform: uppercase; 
            font-size: 16pt;
            font-weight: bold;
            color: #000;
        }
        .report-details p { 
            margin: 0; 
            font-size: 12pt;
            color: #333;
        }
        
        /* Table Styling - Clean & Professional */
        .report-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 25px 0;
            font-size: 11pt;
        }
        .report-table th { 
            background-color: #f5f5f5;
            border: 1px solid #000;
            padding: 10px 6px;
            font-weight: bold;
            text-align: center;
            font-size: 11pt;
        }
        .report-table td { 
            border: 1px solid #000;
            padding: 8px 6px;
            text-align: center;
            vertical-align: middle;
        }
        .report-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }
        
        /* Text alignment */
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        
        /* Number formatting */
        .currency { 
            font-weight: bold; 
            color: #000;
        }
        
        /* Status colors - subtle */
        .status-aman { color: #006600; font-weight: bold; }
        .status-warning { color: #cc6600; font-weight: bold; }
        .status-danger { color: #cc0000; font-weight: bold; }
        
        /* Footer Signatures */
        .report-footer { 
            margin-top: 50px; 
            display: flex; 
            justify-content: space-between;
        }
        .signature { 
            width: 30%;
            text-align: center;
            font-size: 11pt;
        }
        .signature p { 
            margin: 5px 0;
        }
        .signature .line { 
            border-bottom: 1px solid #000; 
            margin: 60px auto 10px auto;
            width: 150px;
        }
        .signature .name {
            font-weight: bold;
        }
        .signature .position {
            font-style: italic;
            font-size: 10pt;
        }
        
        /* Print optimization */
        @media print {
            body { margin: 0; padding: 15px; }
            .container { width: 100%; }
        }
        
        /* Summary row styling */
        .summary-row {
            background-color: #f0f0f0 !important;
            font-weight: bold;
            border-top: 2px solid #000 !important;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="kop-surat">
            <h1>PT. USIMPEL INOVASI INDONESIA</h1>
            <p>Penyedia Layanan Internet Broadband Nirkabel</p>
        </div>

        <div class="report-details">
            <h2><?= $report_title ?></h2>
            <p>Periode: <?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?></p>
        </div>

        <table class="report-table">
            <?php if ($report_type == 'stok'): ?>
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="35%">Produk</th>
                        <th width="12%">Stok</th>
                        <th width="12%">ROP</th>
                        <th width="12%">Safety Stock</th>
                        <th width="12%">Permintaan Harian</th>
                        <th width="12%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    while($row = $data->fetch_assoc()): 
                        $status_class = 'status-aman';
                        if (stripos($row['status_stok'], 'mendekati') !== false || stripos($row['status_stok'], 'rendah') !== false) {
                            $status_class = 'status-warning';
                        } elseif (stripos($row['status_stok'], 'kritis') !== false || stripos($row['status_stok'], 'dibawah') !== false) {
                            $status_class = 'status-danger';
                        }
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td class="text-left"><?= htmlspecialchars($row['produk'].' - '.$row['merek']) ?></td>
                        <td><?= number_format((int)$row['stok_tersedia']) ?></td>
                        <td><?= number_format((int)$row['rop']) ?></td>
                        <td><?= number_format((int)$row['safety_stock']) ?></td>
                        <td><?= number_format($row['permintaan_harian'], 1) ?></td>
                        <td class="<?= $status_class ?>"><?= htmlspecialchars($row['status_stok']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            <?php elseif ($report_type == 'pengadaan'): ?>
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="12%">Tanggal</th>
                        <th width="25%">Supplier</th>
                        <th width="28%">Produk</th>
                        <th width="10%">Jumlah</th>
                        <th width="20%">Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $grand_total = 0;
                    while($row = $data->fetch_assoc()): 
                        $grand_total += $row['total'];
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal_pengadaan'])) ?></td>
                        <td class="text-left"><?= htmlspecialchars($row['nama_supplier']) ?></td>
                        <td class="text-left"><?= htmlspecialchars($row['produk']) ?></td>
                        <td><?= number_format($row['jumlah_diajukan']) ?></td>
                        <td class="text-right currency">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <tr class="summary-row">
                        <td colspan="5" class="text-right"><strong>TOTAL KESELURUHAN:</strong></td>
                        <td class="text-right currency"><strong>Rp <?= number_format($grand_total, 0, ',', '.') ?></strong></td>
                    </tr>
                </tbody>
            <?php else: // Pengeluaran ?>
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="12%">Tanggal</th>
                        <th width="25%">Supplier</th>
                        <th width="18%">ID Pengadaan</th>
                        <th width="20%">Nominal</th>
                        <th width="20%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $grand_total = 0;
                    while($row = $data->fetch_assoc()): 
                        $grand_total += $row['nominal'];
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal_pembayaran'])) ?></td>
                        <td class="text-left"><?= htmlspecialchars($row['nama_supplier']) ?></td>
                        <td><?= $row['id_pengadaan'] ?></td>
                        <td class="text-right currency">Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                        <td class="status-aman"><?= $row['status_pembayaran'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <tr class="summary-row">
                        <td colspan="4" class="text-right"><strong>TOTAL PENGELUARAN:</strong></td>
                        <td class="text-right currency"><strong>Rp <?= number_format($grand_total, 0, ',', '.') ?></strong></td>
                        <td></td>
                    </tr>
                </tbody>
            <?php endif; ?>
        </table>

        <div class="report-footer">
            <div class="signature">
                <p>Disusun Oleh,</p>
                <div class="line"></div>
                <p class="name">
                    <?php
                        if ($report_type == 'pengeluaran') {
                            echo htmlspecialchars($nama_finance);
                        } else {
                            echo htmlspecialchars($nama_inventory);
                        }
                    ?>
                </p>
                <p class="position">
                    <?php
                        if ($report_type == 'pengeluaran') {
                            echo 'Finance & Billing Officer';
                        } else {
                            echo 'Inventory & Purchasing Officer';
                        }
                    ?>
                </p>
            </div>
            
            <div class="signature">
                <p>Diperiksa Oleh,</p>
                <div class="line"></div>
                <p class="name"><?= htmlspecialchars($nama_kadiv) ?></p>
                <p class="position">Kepala Divisi Produk & Pengadaan</p>
            </div>
            
            <div class="signature">
                <p>Disetujui Oleh,</p>
                <div class="line"></div>
                <p class="name"><?= htmlspecialchars($nama_direktur) ?></p>
                <p class="position">Direktur Operasional</p>
            </div>
        </div>
    </div>
</body>
</html>