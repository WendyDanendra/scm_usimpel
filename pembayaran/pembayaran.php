<?php
session_start();
require_once '../config.php';

// Proteksi halaman
if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Finance & Billing Officer') {
    header('Location: ../login.php');
    exit();
}

// Pesan status
$status_message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'transfer_success') {
        $status_message = '<div class="status-message success"><i class="fas fa-check-circle"></i> Bukti transfer berhasil diunggah dan pembayaran sedang menunggu kuitansi.</div>';
    } elseif ($_GET['status'] == 'receipt_success') {
        $status_message = '<div class="status-message success"><i class="fas fa-check-circle"></i> Kuitansi berhasil diunggah. Pembayaran telah selesai dan ditandai sebagai lunas.</div>';
    } elseif ($_GET['status'] == 'error') {
        $status_message = '<div class="status-message error"><i class="fas fa-exclamation-circle"></i> Terjadi kesalahan. Silakan coba lagi.</div>';
    }
}

// Query untuk mengambil data pembayaran, DITAMBAH p.nominal
$sql = "SELECT 
            pd.id_pengadaan, 
            pb.id_pengajuan,
            b.produk,
            s.nama_supplier, s.email AS email_supplier,
            pd.file_invoice,
            p.id_pembayaran, p.status_pembayaran, p.file_bukti_transfer, p.file_kuitansi, p.nominal
        FROM pengadaan_barang pd
        JOIN pengajuan_barang pb ON pd.id_pengajuan = pb.id_pengajuan
        JOIN barang b ON pb.id_barang = b.id_barang
        JOIN supplier s ON pb.id_supplier = s.id_supplier
        LEFT JOIN pembayaran p ON pd.id_pengadaan = p.id_pengadaan
        WHERE pb.status_pengajuan = 'Disetujui' AND pd.file_invoice IS NOT NULL
        ORDER BY 
            CASE 
                WHEN p.status_pembayaran IS NULL OR p.status_pembayaran = '' THEN 1
                WHEN p.status_pembayaran = 'Belum Bayar' THEN 2
                WHEN p.status_pembayaran = 'Menunggu Kuitansi' THEN 3
                WHEN p.status_pembayaran = 'Lunas' THEN 4
                ELSE 5
            END,
            CAST(SUBSTRING(pd.id_pengadaan, 6) AS UNSIGNED) ASC,
            CAST(SUBSTRING(pb.id_pengajuan, 6) AS UNSIGNED) ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pembayaran - SCM</title>
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
        .data-table td:nth-child(2) { text-align: left; } /* Barang column */
        .action-buttons { 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
            min-width: 200px;
        }
        .action-buttons form { 
            display: flex; 
            flex-direction: column; 
            gap: 6px; 
            margin: 0;
            padding: 8px;
            border: 1px solid #e1e1e1;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .btn, button { 
            padding: 8px 12px; 
            border-radius: 5px; 
            text-decoration: none; 
            font-size: 0.85rem; 
            border: none; 
            cursor: pointer; 
            color: white; 
            display: inline-block;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-view { 
            background-color: var(--gray); 
            margin-bottom: 4px;
        }
        .btn-view:hover { 
            background-color: #5a6c7d; 
        }
        .btn-upload, button[type="submit"] { 
            background-color: var(--success); 
        }
        .btn-upload:hover, button[type="submit"]:hover { 
            background-color: #27ae60; 
        }
        .btn-email { 
            background-color: #007bff; 
            margin-bottom: 4px;
        }
        .btn-email:hover { 
            background-color: #0056b3; 
        }
        .form-input {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 0.85rem;
            margin-bottom: 4px;
        }
        .form-label {
            font-size: 0.8rem;
            color: #555;
            margin-bottom: 2px;
            display: block;
        }
        .divider {
            margin: 8px 0;
            border: none;
            border-top: 1px solid #ddd;
        }
        .status-badge { 
            padding: 5px 12px; 
            border-radius: 15px; 
            font-size: 0.8rem; 
            color: white; 
        }
        .status-lunas { background-color: var(--success); }
        .status-belum-bayar { background-color: #dc3545; }
        .status-menunggu-pembayaran { background-color: #dc3545; }
        .status-menunggu-kuitansi { background-color: var(--warning); }
        .status-message { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .status-message.success { background-color: rgba(39, 174, 96, 0.2); color: #27ae60; }
        .status-message.error { background-color: rgba(231, 76, 60, 0.2); color: var(--danger); }
        
        /* Smooth scrolling untuk anchor */
        html {
            scroll-behavior: smooth;
        }
        
        /* Highlight row yang baru diproses */
        tr[id^="row_"]:target {
            background-color: rgba(255, 193, 7, 0.2);
            animation: highlightFade 3s ease-out;
        }
        
        @keyframes highlightFade {
            0% { background-color: rgba(255, 193, 7, 0.5); }
            100% { background-color: transparent; }
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <div class="table-container">
                <h2 class="dashboard-title"><i class="fas fa-money-bill-wave"></i> Proses Pembayaran</h2>
                <?php echo $status_message ?? ''; ?>
                <table class="data-table">
                    <thead>
                        <tr><th>ID Pengadaan</th><th>Barang</th><th>Invoice</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0):
                            while($row = $result->fetch_assoc()): 
                                $status = $row['status_pembayaran'] ?? 'Belum Bayar';
                                // Normalisasi status untuk class CSS
                                $status_class = 'status-' . str_replace([' ', '_'], '-', strtolower($status));
                        ?>
                                <tr id="row_<?php echo $row["id_pengadaan"]; ?>">
                                    <td><?php echo $row["id_pengadaan"]; ?></td>
                                    <td><?php echo htmlspecialchars($row["produk"]); ?></td>
                                    <td><a href="../uploads/invoices/<?php echo htmlspecialchars($row['file_invoice']); ?>" target="_blank" class="btn btn-view">Lihat</a></td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                                    <td class="action-buttons">
                                        <?php if ($status == 'Belum Bayar' || $status == 'Menunggu Pembayaran' || $status == '' || is_null($status)): ?>
                                            <form action="proses_pembayaran.php" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="id_pengadaan" value="<?php echo $row["id_pengadaan"]; ?>">
                                                
                                                <label class="form-label">Nominal Pembayaran:</label>
                                                <input type="number" name="nominal" class="form-input" placeholder="Masukkan nominal" required step="0.01" min="0">
                                                
                                                <label class="form-label">Bukti Transfer:</label>
                                                <input type="file" name="file_bukti_transfer" class="form-input" required accept=".jpg,.jpeg,.png,.pdf">
                                                
                                                <button type="submit">💰 Simpan & Kirim</button>
                                            </form>
                                        <?php elseif ($status == 'Menunggu Kuitansi'): ?>
                                            <?php
                                                // --- KODE BARU UNTUK MEMBUAT EMAIL GMAIL COMPOSE ---
                                                $nama_pengirim = $_SESSION['nama'] ?? 'Tim Finance PT. Usimpel Inovasi';
                                                $subject = "Bukti Pembayaran Invoice Pengadaan ID " . $row['id_pengadaan'];
                                                
                                                $body = "Yth. " . htmlspecialchars($row["nama_supplier"]) . ",\n\n";
                                                $body .= "Dengan hormat,\n\n";
                                                $body .= "Bersama ini kami sampaikan bukti pembayaran untuk pengadaan barang dengan detail sebagai berikut:\n";
                                                $body .= "- Produk: " . htmlspecialchars($row["produk"]) . "\n";
                                                $body .= "- Nominal: Rp " . number_format($row["nominal"] ?? 0, 0, ',', '.') . "\n";
                                                $body .= "- No. Pengajuan: " . $row['id_pengajuan'] . "\n\n";
                                                $body .= "Bukti transfer terlampir bersama email ini. Mohon dapat segera dikirimkan kuitansi resmi atas pembayaran ini agar dapat kami proses lebih lanjut.\n\n";
                                                $body .= "Terima kasih atas perhatian dan kerja samanya.\n\n";
                                                $body .= "Hormat kami,\n";
                                                $body .= $nama_pengirim;

                                                // Gmail Compose URL
                                                $gmail_link = "https://mail.google.com/mail/?view=cm&fs=1&to=" . urlencode($row['email_supplier']) . "&su=" . urlencode($subject) . "&body=" . urlencode($body);
                                            ?>
                                            <a href="../uploads/transfers/<?php echo htmlspecialchars($row['file_bukti_transfer']); ?>" target="_blank" class="btn btn-view">📄 Lihat Bukti Transfer</a>
                                            <button 
                                                class="btn btn-email email-btn" 
                                                data-email="<?php echo htmlspecialchars($row['email_supplier']); ?>" 
                                                data-subject="<?php echo htmlspecialchars($subject); ?>" 
                                                data-body="<?php echo htmlspecialchars($body); ?>">
                                                📧 Kirim Email Konfirmasi
                                            </button>
                                            
                                            <hr class="divider">
                                            
                                            <form action="upload_kuitansi.php" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="id_pembayaran" value="<?php echo $row["id_pembayaran"]; ?>">
                                                <label class="form-label">Upload Kuitansi:</label>
                                                <input type="file" name="file_kuitansi" class="form-input" required accept=".jpg,.jpeg,.png,.pdf">
                                                <button type="submit">✅ Tandai Lunas</button>
                                            </form>
                                        <?php else: ?>
                                            <a href="../uploads/transfers/<?php echo htmlspecialchars($row['file_bukti_transfer']); ?>" target="_blank" class="btn btn-view">📄 Lihat Bukti Transfer</a>
                                            <a href="../uploads/receipts/<?php echo htmlspecialchars($row['file_kuitansi']); ?>" target="_blank" class="btn btn-view">🧾 Lihat Kuitansi</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center;">Tidak ada pengadaan yang perlu diproses.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
    <script>
        // Event listener untuk semua tombol email
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.email-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const email = this.getAttribute('data-email');
                    const subject = this.getAttribute('data-subject');
                    const body = this.getAttribute('data-body');
                    
                    // Buat URL Gmail compose
                    const gmailURL = `https://mail.google.com/mail/?view=cm&fs=1&to=${encodeURIComponent(email)}&su=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
                    
                    // Buka di tab baru
                    window.open(gmailURL, '_blank');
                });
            });
        });
        
        // Auto scroll ke row yang baru diproses jika ada fragment di URL
        window.addEventListener('load', function() {
            if (window.location.hash && !sessionStorage.getItem('skipScroll')) {
                const targetRow = document.querySelector(window.location.hash);
                if (targetRow) {
                    setTimeout(() => {
                        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }
            }
            // Clear skip scroll flag
            sessionStorage.removeItem('skipScroll');
        });

        // Prevent auto scroll untuk upload forms
        document.querySelectorAll('form[action*="proses_pembayaran.php"], form[action*="upload_kuitansi.php"]').forEach(form => {
            form.addEventListener('submit', function() {
                // Save current scroll position
                sessionStorage.setItem('scrollPosition', window.pageYOffset);
                sessionStorage.setItem('skipScroll', 'true');
            });
        });

        // Restore scroll position after upload
        if (sessionStorage.getItem('scrollPosition') && sessionStorage.getItem('skipScroll')) {
            setTimeout(() => {
                window.scrollTo({
                    top: parseInt(sessionStorage.getItem('scrollPosition')),
                    behavior: 'smooth'
                });
                sessionStorage.removeItem('scrollPosition');
            }, 100);
        }
    </script>
</body>
</html>