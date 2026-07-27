<?php
session_start();
require_once '../config.php';

// Proteksi halaman
$jabatan_lower = strtolower(trim($_SESSION['jabatan'] ?? ''));
if (!isset($_SESSION['jabatan']) || !in_array($jabatan_lower, ['inventory & purchasing officer', 'administrator', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

// Pesan status
$status_message = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'upload_success':
            $status_message = '<div class="status-message success">Invoice berhasil diunggah. Silakan periksa kembali sebelum mengirim ke Finance.</div>';
            break;
        case 'update_success':
            $status_message = '<div class="status-message success">Invoice berhasil diperbarui.</div>';
            break;
        case 'sent_to_finance':
            $status_message = '<div class="status-message success">Data berhasil dikirim ke tim Finance untuk proses pembayaran.</div>';
            break;
        case 'error':
            $status_message = '<div class="status-message error">Terjadi kesalahan. Silakan coba lagi.</div>';
            break;
    }
}

// Query untuk mengambil data pengadaan dengan status pembayaran
if (isset($_GET['view']) && $_GET['view'] == 'completed') {
    // Query untuk pengadaan yang sudah selesai (Lunas)
    $sql = "SELECT 
                pb.id_pengajuan, pb.status_pengajuan, pb.tanggal_pengajuan,
                b.produk, b.merek, pb.jumlah_diajukan, 
                s.nama_supplier, s.email AS email_supplier,
                pd.id_pengadaan, pd.file_invoice,
                p.status_pembayaran, p.tanggal_pembayaran
            FROM pengajuan_barang pb
            JOIN barang b ON pb.id_barang = b.id_barang
            JOIN supplier s ON pb.id_supplier = s.id_supplier
            LEFT JOIN pengadaan_barang pd ON pb.id_pengajuan = pd.id_pengajuan
            LEFT JOIN pembayaran p ON pd.id_pengadaan = p.id_pengadaan
            WHERE p.status_pembayaran = 'Lunas'
            ORDER BY p.tanggal_pembayaran DESC";
} else {
    // Query untuk pengadaan yang belum selesai
    $sql = "SELECT 
                pb.id_pengajuan, pb.status_pengajuan, pb.tanggal_pengajuan,
                b.produk, b.merek, pb.jumlah_diajukan, 
                s.nama_supplier, s.email AS email_supplier,
                pd.id_pengadaan, pd.file_invoice,
                p.status_pembayaran, p.tanggal_pembayaran
            FROM pengajuan_barang pb
            JOIN barang b ON pb.id_barang = b.id_barang
            JOIN supplier s ON pb.id_supplier = s.id_supplier
            LEFT JOIN pengadaan_barang pd ON pb.id_pengajuan = pd.id_pengajuan
            LEFT JOIN pembayaran p ON pd.id_pengadaan = p.id_pengadaan
            WHERE pb.status_pengajuan IN ('Disetujui', 'Diproses') 
            AND (p.status_pembayaran IS NULL OR p.status_pembayaran != 'Lunas')
            ORDER BY pb.tanggal_pengajuan DESC, 
                     CAST(SUBSTRING(pb.id_pengajuan, 6) AS UNSIGNED) ASC,
                     CASE 
                         WHEN pb.status_pengajuan = 'Disetujui' THEN 1
                         WHEN pb.status_pengajuan = 'Diproses' THEN 2
                         ELSE 3 
                     END";
}
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengadaan Barang - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .table-container { background: white; padding: 2rem; border-radius: 10px; }
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        .data-table th, .data-table td { 
            padding: 12px 15px; 
            border: 1px solid #e1e1e1; 
            text-align: center; 
            vertical-align: middle; 
        }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            text-align: center;
        }
        .data-table td:nth-child(2), .data-table td:nth-child(4) { text-align: left; } /* Barang dan Supplier columns */
        
        .action-buttons { 
            display: flex; 
            flex-direction: column; 
            gap: 6px; 
            align-items: center;
            min-width: 160px;
        }
        .action-buttons form { 
            display: flex; 
            flex-direction: column; 
            gap: 6px; 
            margin: 0;
            width: 100%;
            align-items: center;
        }
        .btn, button { 
            padding: 7px 12px; 
            border-radius: 4px; 
            text-decoration: none; 
            font-size: 0.85rem; 
            border: none; 
            cursor: pointer; 
            color: white; 
            text-align: center;
            display: block;
            width: 130px;
            box-sizing: border-box;
            white-space: nowrap;
            font-weight: 500;
        }
        .btn-email { 
            background-color: #007bff; 
            margin-bottom: 4px;
        }
        .btn-email:hover { 
            background-color: #0056b3; 
        }
        .btn-upload, .btn-update { 
            background-color: var(--success); 
        }
        .btn-upload:hover, .btn-update:hover { 
            background-color: #27ae60; 
        }
        .btn-send { 
            background-color: var(--secondary); 
            margin-top: 4px; 
        }
        .btn-send:hover { 
            background-color: #2980b9; 
        }
        .btn-view { 
            background-color: var(--gray); 
            margin-bottom: 4px; 
        }
        .btn-view:hover { 
            background-color: #5a6c7d; 
        }
        .status-message { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .status-message.success { background-color: rgba(39, 174, 96, 0.2); color: #27ae60; }
        .status-message.error { background-color: rgba(231, 76, 60, 0.2); color: var(--danger); }
        .status-badge { padding: 5px 12px; border-radius: 15px; font-size: 0.8rem; color: white; }
        .status-disetujui { background-color: var(--success); }
        .status-diproses { background-color: var(--primary); }
        
        /* Styling khusus untuk tombol back */
        .btn-back {
            display: inline-block !important;
            padding: 10px 20px !important;
            background-color: var(--primary) !important;
            color: white !important;
            text-decoration: none !important;
            border-radius: 5px !important;
            font-weight: 500 !important;
            transition: all 0.3s ease !important;
            border: none !important;
            font-size: 0.9rem !important;
            line-height: 1.4 !important;
            white-space: nowrap !important;
        }
        .btn-back:hover {
            background-color: #2980b9 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <div class="table-container">
                <h2 class="dashboard-title"><i class="fas fa-truck-loading"></i> Proses Pengadaan Barang</h2>
                
                <?php echo $status_message; ?>
                
                <?php 
                // Cek pengadaan yang sudah selesai (Lunas)
                $sql_completed = "SELECT COUNT(*) as total FROM pengajuan_barang pb 
                                 LEFT JOIN pengadaan_barang pd ON pb.id_pengajuan = pd.id_pengajuan
                                 LEFT JOIN pembayaran p ON pd.id_pengadaan = p.id_pengadaan
                                 WHERE p.status_pembayaran = 'Lunas'";
                $completed_result = $conn->query($sql_completed);
                $completed_count = $completed_result->fetch_assoc()['total'];
                
                if ($completed_count > 0): ?>
                    <div class="status-message success">
                        <i class="fas fa-check-circle"></i> 
                        <strong><?= $completed_count ?> pengadaan telah selesai</strong> (pembayaran lunas). 
                        <a href="?view=completed" style="color: #27ae60; text-decoration: underline; margin-left: 10px;">
                            <i class="fas fa-eye"></i> Lihat Riwayat
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['view']) && $_GET['view'] == 'completed'): ?>
                    <div style="margin-bottom: 20px; padding: 15px 0; border-bottom: 1px solid #e1e1e1;">
                        <a href="pengadaan_barang.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Kembali ke Pengadaan Aktif
                        </a>
                        <h3 style="margin: 15px 0 0 0; color: #2c3e50; font-size: 1.1rem;">
                            <i class="fas fa-history"></i> Riwayat Pengadaan Selesai
                        </h3>
                    </div>
                <?php endif; ?>
                
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Barang</th><th>Jumlah</th><th>Supplier</th><th>Status</th><th><?= isset($_GET['view']) && $_GET['view'] == 'completed' ? 'Tanggal Selesai' : 'Aksi' ?></th></tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row["id_pengajuan"]; ?></td>
                                    <td><?php echo htmlspecialchars($row["produk"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["jumlah_diajukan"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["nama_supplier"]); ?></td>
                                    <td>
                                        <?php if (isset($_GET['view']) && $_GET['view'] == 'completed'): ?>
                                            <span class="status-badge" style="background-color: var(--success);">Selesai</span>
                                        <?php else: ?>
                                            <span class="status-badge status-<?php echo strtolower($row['status_pengajuan']); ?>"><?php echo htmlspecialchars($row['status_pengajuan']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-buttons">
                                        <?php if (isset($_GET['view']) && $_GET['view'] == 'completed'): ?>
                                            <!-- View untuk pengadaan yang sudah selesai -->
                                            <div style="text-align: center;">
                                                <strong><?php echo date('d/m/Y', strtotime($row['tanggal_pembayaran'])); ?></strong><br>
                                                <small style="color: var(--success);">Pembayaran Lunas</small><br>
                                                <a href="../uploads/invoices/<?php echo htmlspecialchars($row['file_invoice']); ?>" target="_blank" class="btn btn-view" style="margin-top: 5px;">
                                                    <i class="fas fa-eye"></i> Lihat Invoice
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <!-- View untuk pengadaan yang belum selesai (existing logic) -->
                                            <?php if ($row['status_pengajuan'] == 'Diproses'): ?>
                                                <a href="../uploads/invoices/<?php echo htmlspecialchars($row['file_invoice']); ?>" target="_blank" class="btn btn-view">
                                                    <i class="fas fa-eye"></i> Lihat Invoice
                                                </a>
                                            <?php elseif (is_null($row['file_invoice'])): ?>
                                                <button type="button" class="btn btn-email" onclick="openGmail('<?php echo $row['email_supplier']; ?>', '<?php echo addslashes($row['produk']); ?>', '<?php echo addslashes($row['nama_supplier']); ?>', '<?php echo $row['jumlah_diajukan']; ?>', '<?php echo $row['id_pengajuan']; ?>')">
                                                    <i class="fas fa-envelope"></i> Kirim Email PO
                                                </button>
                                                
                                                <!-- Hidden form untuk upload invoice pertama kali -->
                                                <form id="upload-form-<?php echo $row["id_pengajuan"]; ?>" action="proses_pengadaan.php" method="POST" enctype="multipart/form-data" style="display: none;">
                                                    <input type="hidden" name="id_pengajuan" value="<?php echo $row["id_pengajuan"]; ?>">
                                                    <input type="file" id="upload-input-<?php echo $row["id_pengajuan"]; ?>" name="file_invoice" accept=".pdf,.jpg,.jpeg,.png" onchange="submitUploadForm('<?php echo $row["id_pengajuan"]; ?>')">
                                                </form>
                                                
                                                <!-- Button yang langsung membuka file explorer -->
                                                <button type="button" class="btn-upload" onclick="openUploadExplorer('<?php echo $row["id_pengajuan"]; ?>')">
                                                    <i class="fas fa-upload"></i> Unggah Invoice
                                                </button>
                                            <?php else: ?>
                                                <a href="../uploads/invoices/<?php echo htmlspecialchars($row['file_invoice']); ?>" target="_blank" class="btn btn-view">
                                                    <i class="fas fa-eye"></i> Lihat Invoice
                                                </a>
                                                
                                                <!-- Hidden form untuk update invoice -->
                                                <form id="update-form-<?php echo $row["id_pengadaan"]; ?>" action="update_invoice.php" method="POST" enctype="multipart/form-data" style="display: none;">
                                                    <input type="hidden" name="id_pengadaan" value="<?php echo $row["id_pengadaan"]; ?>">
                                                    <input type="file" id="file-input-<?php echo $row["id_pengadaan"]; ?>" name="new_invoice" accept=".pdf,.jpg,.jpeg,.png" onchange="submitUpdateForm('<?php echo $row["id_pengadaan"]; ?>')">
                                                </form>
                                                
                                                <!-- Button yang langsung membuka file explorer -->
                                                <button type="button" class="btn-update" onclick="openFileExplorer('<?php echo $row["id_pengadaan"]; ?>')">
                                                    <i class="fas fa-edit"></i> Ubah Invoice
                                                </button>
                                                
                                                <hr>
                                                <a href="kirim_ke_finance.php?id=<?php echo $row['id_pengajuan']; ?>" class="btn btn-send" onclick="return confirm('Anda yakin invoice ini sudah benar dan akan mengirimnya ke tim Finance?');">
                                                    <i class="fas fa-paper-plane"></i> Kirim ke Finance
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; padding: 30px;">
                                <i class="fas fa-check-circle" style="color: var(--success); font-size: 2rem; margin-bottom: 10px;"></i><br>
                                <strong>Semua pengadaan sudah selesai!</strong><br>
                                <small>Tidak ada pengajuan yang perlu diproses. Semua pembayaran telah lunas.</small>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
    <script>
        function openGmail(email, produk, supplier, jumlah, idPengajuan) {
            const subject = "Purchase Order - " + produk;
            const body = `Kepada Yth. ${supplier},

Dengan hormat,

Kami bermaksud untuk melakukan pemesanan barang dengan detail sebagai berikut:

Nama Barang: ${produk}
Jumlah: ${jumlah} unit
ID Purchase Order: ${idPengajuan}

Mohon untuk dapat mengirimkan invoice dan konfirmasi ketersediaan barang.

Terima kasih atas kerjasamanya.

Hormat kami,
Inventory & Purchasing Officer
PT. USIMPEL INOVASI INDONESIA`;

            // URL Gmail Compose - langsung ke Gmail
            const gmailUrl = `https://mail.google.com/mail/?view=cm&to=${email}&su=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
            window.open(gmailUrl, '_blank');
        }
        
        // Function untuk membuka file explorer (upload pertama kali)
        function openUploadExplorer(idPengajuan) {
            const fileInput = document.getElementById('upload-input-' + idPengajuan);
            fileInput.click(); // Langsung membuka file explorer
        }
        
        // Function untuk auto submit setelah file dipilih (upload pertama kali)
        function submitUploadForm(idPengajuan) {
            const fileInput = document.getElementById('upload-input-' + idPengajuan);
            const form = document.getElementById('upload-form-' + idPengajuan);
            
            if (fileInput.files.length > 0) {
                // Konfirmasi sebelum submit
                const fileName = fileInput.files[0].name;
                const confirmSubmit = confirm(`Anda yakin ingin mengunggah invoice "${fileName}"?`);
                
                if (confirmSubmit) {
                    // Tambahkan loading indicator
                    const button = event.target.closest('td').querySelector('.btn-upload');
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengunggah...';
                    button.disabled = true;
                    
                    form.submit();
                } else {
                    // Reset file input jika user cancel
                    fileInput.value = '';
                }
            }
        }
        
        // Function untuk membuka file explorer (update invoice)
        function openFileExplorer(idPengadaan) {
            const fileInput = document.getElementById('file-input-' + idPengadaan);
            fileInput.click(); // Langsung membuka file explorer
        }
        
        // Function untuk auto submit setelah file dipilih (update invoice)
        function submitUpdateForm(idPengadaan) {
            const fileInput = document.getElementById('file-input-' + idPengadaan);
            const form = document.getElementById('update-form-' + idPengadaan);
            
            if (fileInput.files.length > 0) {
                // Konfirmasi sebelum submit
                const fileName = fileInput.files[0].name;
                const confirmSubmit = confirm(`Anda yakin ingin mengganti invoice dengan file "${fileName}"?`);
                
                if (confirmSubmit) {
                    // Tambahkan loading indicator
                    const button = event.target.closest('td').querySelector('.btn-update');
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengunggah...';
                    button.disabled = true;
                    
                    form.submit();
                } else {
                    // Reset file input jika user cancel
                    fileInput.value = '';
                }
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>