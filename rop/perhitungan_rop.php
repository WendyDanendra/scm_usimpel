<?php
session_start();
require_once '../config.php';

// Proteksi halaman
if ($_SESSION['jabatan'] != 'Inventory & Purchasing Officer') {
    header('Location: ../login.php');
    exit();
}

// Pesan status
$status_message = '';
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $status_message = '<div class="status-message success">Perhitungan ROP & Safety Stock berhasil diperbarui untuk semua barang.</div>';
} elseif (isset($_GET['status']) && $_GET['status'] == 'error') {
    $status_message = '<div class="status-message error">Terjadi kesalahan saat menghitung ROP. Silakan coba lagi.</div>';
}

// Query untuk menampilkan data stok terkini
$sql = "SELECT 
            b.id_barang, b.produk, b.merek, b.lead_time,
            sb.stok_tersedia, sb.permintaan_harian, sb.safety_stock, sb.rop, sb.status_stok
        FROM barang b
        LEFT JOIN stok_barang sb ON b.id_barang = sb.id_barang
        ORDER BY b.produk ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perhitungan ROP - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .table-container { 
            background: white; 
            padding: 2rem; 
            border-radius: 10px; 
        }
        .table-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 1.5rem; 
        }
        .btn-recalc { 
            background-color: var(--secondary); 
            color: white; 
            border: none; 
            padding: 10px 15px; 
            border-radius: 5px; 
            cursor: pointer; 
            text-decoration: none; 
        }
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        .data-table th, .data-table td { 
            padding: 10px 15px; 
            border: 1px solid #e1e1e1; 
            text-align: center;
        }
        .data-table th:first-child, .data-table td:first-child { 
            text-align: left; 
        }
        .status-badge { 
            padding: 5px 12px; 
            border-radius: 15px; 
            font-size: 0.8rem; 
            color: white; 
        }
        .status-aman { 
            background-color: var(--success); 
        }
        .status-mendekati-rop { 
            background-color: var(--warning); 
        }
        .status-dibawah-rop { 
            background-color: var(--danger); 
        }
        .status-belum-ada-data { 
            background-color: var(--gray); 
        }
        .status-message { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 5px; 
        }
        .status-message.success {
            background-color: rgba(39, 174, 96, 0.2); 
            color: #27ae60; 
        }
        .status-message.error {
            background-color: rgba(231, 76, 60, 0.2); 
            color: #e74c3c; 
        }
        
        /* Button Detail */
        .btn-detail {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .btn-detail:hover {
            background-color: #2980b9;
        }
        
        /* Modal untuk Detail Perhitungan */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        .calculation-step {
            margin: 10px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
        }
        .formula {
            font-family: monospace;
            background-color: #e9ecef;
            padding: 5px;
            border-radius: 3px;
        }
        .status-message.success {
            background-color: rgba(39, 174, 96, 0.2); 
            color: #27ae60; 
        }
        .status-message.error {
            background-color: rgba(231, 76, 60, 0.2); 
            color: #e74c3c; 
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <div class="table-container">
                <div class="table-header">
                    <h2 class="dashboard-title" style="margin: 0;"><i class="fas fa-calculator"></i> Perhitungan ROP & Safety Stock</h2>
                    <a href="proses_hitung_rop.php" class="btn-recalc" onclick="return confirm('Proses ini akan menghitung ulang ROP untuk semua barang. Lanjutkan?');">
                        <i class="fas fa-sync-alt"></i> Hitung Ulang Sekarang
                    </a>
                </div>
                <?php echo $status_message; ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th>Stok Saat Ini</th>
                            <th>Permintaan Harian (d)</th>
                            <th>Lead Time (L)</th>
                            <th>Safety Stock (SS)</th>
                            <th>Reorder Point (ROP)</th>
                            <th>Status</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0):
                            while($row = $result->fetch_assoc()):
                                // Logika untuk membuat class CSS dari nama status
                                $status_text = $row['status_stok'] ?? '';
                                $status_class = 'status-' . strtolower(str_replace(' ', '-', $status_text));
                                
                                // Data untuk perhitungan detail
                                $id_barang = $row['id_barang'] ?? '';
                                $permintaan_harian = (float)($row['permintaan_harian'] ?? 0);
                                $lead_time = (int)($row['lead_time'] ?? 0);
                                $safety_stock = (int)($row['safety_stock'] ?? 0);
                                $rop = (int)($row['rop'] ?? 0);
                                $stok_tersedia = (int)($row['stok_tersedia'] ?? 0);
                                $nama_barang = htmlspecialchars($row['produk'] . ' - ' . $row['merek']);
                        ?>
                            <tr>
                                <td><?php echo $nama_barang; ?></td>
                                <td><?php echo $stok_tersedia; ?></td>
                                <td><?php echo number_format($permintaan_harian, 0); ?></td>
                                <td><?php echo $lead_time; ?> hari</td>
                                <td><?php echo $safety_stock; ?></td>
                                <td><?php echo $rop; ?></td>
                                <td>
                                    <?php if(!empty($status_text)): ?>
                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_text); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn-detail" onclick="showDetail('<?php echo $nama_barang; ?>', '<?php echo $id_barang; ?>', <?php echo $permintaan_harian; ?>, <?php echo $lead_time; ?>, <?php echo $safety_stock; ?>, <?php echo $rop; ?>, <?php echo $stok_tersedia; ?>, '<?php echo $status_text; ?>')">
                                        <i class="fas fa-info-circle"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="8" style="text-align: center;">Data barang tidak ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Modal Detail Perhitungan -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle">Detail Perhitungan ROP</h3>
            <div id="modalContent">
                <!-- Content akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        function showDetail(namaBarang, idBarang, permintaanHarian, leadTime, safetyStock, rop, stokTersedia, status) {
            document.getElementById('modalTitle').innerHTML = 'Detail Perhitungan ROP: ' + namaBarang;
            
            // Ambil data transaksi barang keluar untuk perhitungan permintaan harian
            fetch('get_transaksi_keluar.php?id_barang=' + encodeURIComponent(idBarang))
                .then(response => response.json())
                .then(data => {
                    let content = `
                        <div style="margin-bottom: 20px;">
                            <h4>Data Barang:</h4>
                            <p><strong>Nama:</strong> ${namaBarang}</p>
                            <p><strong>Stok Tersedia:</strong> ${stokTersedia} unit</p>
                            <p><strong>Lead Time (L):</strong> ${leadTime} hari</p>
                            <p><strong>Status Stok:</strong> ${status}</p>
                        </div>`;
                    
                    // Bagian perhitungan permintaan harian dengan tabel
                    content += `
                        <div class="calculation-step">
                            <h4>1. Perhitungan Permintaan Harian (d):</h4>
                            <p><strong>Data Transaksi Barang Keluar (30 hari terakhir):</strong></p>`;
                    
                    if (data.success && data.transaksi && data.transaksi.length > 0) {
                        content += `
                            <table border="1" style="width: 100%; border-collapse: collapse; margin: 10px 0;">
                                <tr style="background-color: #f8f9fa;">
                                    <th style="padding: 8px;">Tanggal</th>
                                    <th style="padding: 8px;">Total Keluar per Hari</th>
                                </tr>`;
                        
                        let totalKeluar = 0;
                        let tanggalUnik = new Set();
                        
                        // Kelompokkan berdasarkan tanggal dan jumlahkan per hari
                        let dailyData = {};
                        data.transaksi.forEach(function(transaksi) {
                            let tanggal = transaksi.tanggal;
                            if (!dailyData[tanggal]) {
                                dailyData[tanggal] = 0;
                            }
                            dailyData[tanggal] += parseInt(transaksi.jumlah);
                            tanggalUnik.add(tanggal);
                        });
                        
                        // Tampilkan data per hari
                        Object.keys(dailyData).sort().forEach(function(tanggal) {
                            content += `
                                <tr>
                                    <td style="padding: 8px;">${tanggal}</td>
                                    <td style="padding: 8px; text-align: right;">${dailyData[tanggal]} unit</td>
                                </tr>`;
                            totalKeluar += dailyData[tanggal];
                        });
                        
                        content += `</table>`;
                        
                        let jumlahHari = tanggalUnik.size;
                        
                        content += `
                            <p><strong>Perhitungan (Formula BENAR):</strong></p>
                            <p><span class="formula">Permintaan Harian (d) = Total Keluar ÷ Jumlah Hari (unik)</span></p>
                            <p><strong>Total Keluar dalam ${jumlahHari} hari:</strong> ${totalKeluar} unit</p>
                            <p><strong>Jumlah hari unik:</strong> ${jumlahHari} hari</p>
                            <p><strong>Perhitungan:</strong> ${totalKeluar} ÷ ${jumlahHari} = ${(totalKeluar / jumlahHari).toFixed(2)}</p>
                            <p><strong>Hasil Permintaan Harian (d):</strong> <strong>${permintaanHarian} unit/hari</strong></p>`;
                    } else {
                        content += `
                            <div style="background-color: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;">
                                <p style="color: #856404; margin: 0;"><strong>⚠️ Tidak ada data transaksi barang keluar dalam 30 hari terakhir.</strong></p>
                            </div>
                            <p><strong>Hasil Permintaan Harian (d):</strong> <strong>${permintaanHarian} unit/hari</strong></p>
                            <p style="color: #6c757d; font-size: 0.9em;"><em>Nilai ini mungkin diambil dari perhitungan periode sebelumnya atau estimasi</em></p>`;
                    }
                    
                    content += `</div>`;
                    
                    // Bagian perhitungan Safety Stock
                    content += `
                        <div class="calculation-step">
                            <h4>2. Perhitungan Safety Stock (SS):</h4>
                            <p><strong>Formula:</strong> <span class="formula">Safety Stock = ceil(20% × (d × L))</span></p>
                            <p><strong>Substitusi:</strong> Safety Stock = ceil(0.20 × (${permintaanHarian} × ${leadTime}))</p>
                            <p><strong>Hitung:</strong> Safety Stock = ceil(0.20 × ${permintaanHarian * leadTime})</p>
                            <p><strong>Hitung:</strong> Safety Stock = ceil(${0.20 * (permintaanHarian * leadTime)})</p>
                            <p><strong>Hasil:</strong> Safety Stock = <strong>${safetyStock} unit</strong></p>
                        </div>`;
                    
                    // Bagian perhitungan ROP
                    content += `
                        <div class="calculation-step">
                            <h4>3. Perhitungan Reorder Point (ROP):</h4>
                            <p><strong>Formula:</strong> <span class="formula">ROP = ceil((d × L) + Safety Stock)</span></p>
                            <p><strong>Substitusi:</strong> ROP = ceil((${permintaanHarian} × ${leadTime}) + ${safetyStock})</p>
                            <p><strong>Hitung:</strong> ROP = ceil(${permintaanHarian * leadTime} + ${safetyStock})</p>
                            <p><strong>Hitung:</strong> ROP = ceil(${(permintaanHarian * leadTime) + safetyStock})</p>
                            <p><strong>Hasil:</strong> ROP = <strong>${rop} unit</strong></p>
                        </div>`;
                    
                    // Bagian penentuan status
                    if (permintaanHarian > 0) {
                        content += `
                            <div class="calculation-step">
                                <h4>4. Penentuan Status Stok:</h4>
                                <p><strong>Kondisi:</strong></p>
                                <ul>
                                    <li>Stok Tersedia: ${stokTersedia} unit</li>
                                    <li>ROP: ${rop} unit</li>
                                </ul>
                                <p><strong>Logika Penentuan Status:</strong></p>
                                <ul>`;
                        
                        if (stokTersedia <= rop) {
                            content += `<li style="color: #e74c3c;"><strong>🔴 Dibawah ROP:</strong> ${stokTersedia} ≤ ${rop} ✓</li>`;
                        } else {
                            content += `<li style="color: #27ae60;"><strong>🟢 Aman:</strong> ${stokTersedia} > ${rop} ✓</li>`;
                        }
                        
                        content += `</ul>
                                <p><strong>Status Final:</strong> <strong>${status}</strong></p>
                            </div>`;
                    } else {
                        content += `
                            <div class="calculation-step">
                                <h4>4. Status Barang:</h4>
                                <p style="color: #6c757d;">Karena permintaan harian = 0 (tidak ada data transaksi keluar), maka:</p>
                                <ul>
                                    <li>Safety Stock = 0</li>
                                    <li>ROP = 0</li>
                                    <li>Status = "Belum Ada Data"</li>
                                </ul>
                            </div>`;
                    }
                    
                    document.getElementById('modalContent').innerHTML = content;
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Tampilkan konten dasar jika ada error
                    let content = `
                        <div style="margin-bottom: 20px;">
                            <h4>Data Barang:</h4>
                            <p><strong>Nama:</strong> ${namaBarang}</p>
                            <p><strong>Permintaan Harian (d):</strong> ${permintaanHarian} unit/hari</p>
                            <p><strong>Lead Time (L):</strong> ${leadTime} hari</p>
                            <p><strong>Safety Stock:</strong> ${safetyStock} unit</p>
                            <p><strong>ROP:</strong> ${rop} unit</p>
                            <p><strong>Status:</strong> ${status}</p>
                        </div>
                        <div style="background-color: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;">
                            <p style="color: #721c24; margin: 0;"><strong>❌ Error loading transaction data.</strong></p>
                            <p style="color: #721c24; margin: 5px 0 0 0; font-size: 0.9em;">Menampilkan data perhitungan yang tersimpan.</p>
                        </div>`;
                    document.getElementById('modalContent').innerHTML = content;
                });
            
            document.getElementById('detailModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('detailModal').style.display = 'none';
        }
        
        // Tutup modal jika user klik di luar modal
        window.onclick = function(event) {
            let modal = document.getElementById('detailModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
