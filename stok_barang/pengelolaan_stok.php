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
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $status_message = '<div class="status-message success"><i class="fas fa-check-circle"></i> <strong>Berhasil!</strong> Stok berhasil diperbarui dan log telah tercatat.</div>';
    } else {
        $error_msg = isset($_GET['msg']) ? $_GET['msg'] : 'Terjadi kesalahan.';
        $status_message = '<div class="status-message error"><i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> ' . htmlspecialchars($error_msg) . '</div>';
    }
}

// Query untuk mengambil pengadaan yang sudah LUNAS tapi belum dicatat stoknya
$sql_masuk = "SELECT 
            pd.id_pengadaan,
            pb.id_pengajuan, pb.id_barang, pb.jumlah_diajukan,
            b.produk, b.merek
        FROM pembayaran p
        JOIN pengadaan_barang pd ON p.id_pengadaan = pd.id_pengadaan
        JOIN pengajuan_barang pb ON pd.id_pengajuan = pb.id_pengajuan
        JOIN barang b ON pb.id_barang = b.id_barang
        WHERE p.status_pembayaran = 'Lunas'
        AND NOT EXISTS (
            SELECT 1 
            FROM log_stok l 
            WHERE l.id_pengadaan = pd.id_pengadaan
        )
        ORDER BY p.tanggal_pembayaran ASC";
$result_masuk = $conn->query($sql_masuk);

// Query untuk mengambil daftar semua barang (untuk stok keluar) DENGAN STOK SAAT INI
$sql_barang_all = "SELECT b.id_barang, b.produk, b.merek, 
                      COALESCE(s.stok_tersedia, 0) as stok_tersedia
                   FROM barang b
                   LEFT JOIN stok_barang s ON b.id_barang = s.id_barang
                   ORDER BY b.produk ASC";
$result_barang_all = $conn->query($sql_barang_all);

// Filter untuk log stok
$jenis_log_filter = $_GET['jenis_log'] ?? 'semua';
$log_where = "";
if ($jenis_log_filter == 'masuk') {
    $log_where = "WHERE l.jenis_log = 'masuk'";
} elseif ($jenis_log_filter == 'keluar') {
    $log_where = "WHERE l.jenis_log = 'keluar'";
}

// Query untuk mengambil log stok
$sql_log = "SELECT l.id_log, l.tanggal, l.jenis_log, l.jumlah, l.keterangan,
                   b.produk, b.merek
            FROM log_stok l
            JOIN barang b ON l.id_barang = b.id_barang
            $log_where
            ORDER BY l.tanggal DESC, l.id_log DESC
            LIMIT 50";
$result_log = $conn->query($sql_log);

// Query untuk menghitung total record per kategori
$sql_count_all = "SELECT COUNT(*) as total FROM log_stok";
$sql_count_masuk = "SELECT COUNT(*) as total FROM log_stok WHERE jenis_log = 'masuk'";
$sql_count_keluar = "SELECT COUNT(*) as total FROM log_stok WHERE jenis_log = 'keluar'";

$count_all = $conn->query($sql_count_all)->fetch_assoc()['total'];
$count_masuk = $conn->query($sql_count_masuk)->fetch_assoc()['total'];
$count_keluar = $conn->query($sql_count_keluar)->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Pengelolaan Stok - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .container-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .log-section { background: white; padding: 2rem; border-radius: 10px; margin-top: 2rem; }
        .form-container { background: white; padding: 2rem; border-radius: 10px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .data-table th, .data-table td { padding: 10px; border: 1px solid #e1e1e1; text-align: center; }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-align: center;
        }
        /* Khusus untuk tabel log stok (6 kolom) - kolom nama barang dan keterangan */
        .log-section .data-table td:nth-child(3), 
        .log-section .data-table td:nth-child(6) { text-align: left; }
        
        /* Pastikan kolom Aksi pada tabel Catat Barang Masuk tetap center */
        .form-container .data-table td:nth-child(3) { text-align: center; }
        
        /* Perbaikan untuk kolom keterangan */
        .data-table th:nth-child(6), .data-table td:nth-child(6) { 
            width: 35%; 
            max-width: 300px; 
            word-wrap: break-word; 
            white-space: normal;
        }
        .data-table td:nth-child(6) {
            font-size: 0.85rem;
            line-height: 1.3;
        }
        .btn-log { background-color: var(--success); color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: .5rem; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: .75rem; border: 1px solid #ccc; border-radius: 5px; }
        .status-message { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .status-message.success { background-color: rgba(39, 174, 96, 0.2); color: #27ae60; }
        .status-message.error { background-color: rgba(231, 76, 60, 0.2); color: var(--danger); }
        
        /* Responsive table */
        .data-table {
            font-size: 0.9rem;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .data-table tr:hover {
            background-color: #e3f2fd;
        }
        
        /* Filter untuk log */
        .filter-section { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem; }
        .filter-buttons { display: flex; gap: 10px; }
        .filter-btn { 
            padding: 8px 16px; 
            border: 1px solid #ddd; 
            background: white; 
            border-radius: 5px; 
            text-decoration: none; 
            color: #333; 
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .filter-btn.active { 
            background: var(--primary); 
            color: white; 
            border-color: var(--primary);
        }
        .filter-btn:hover { 
            background: var(--primary); 
            color: white; 
        }
        
        /* Badge untuk jenis log */
        .log-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        .log-masuk { background-color: var(--success); }
        .log-keluar { background-color: var(--danger); }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <h2 class="dashboard-title"><i class="fas fa-warehouse"></i> Pengelolaan Stok Barang</h2>
            <?php echo $status_message; ?>
            
            <div class="container-grid">
                <div class="form-container">
                    <h3><i class="fas fa-arrow-down"></i> Catat Barang Masuk</h3>
                    <p>Daftar barang dari pengadaan yang sudah lunas dan siap dimasukkan ke stok.</p>
                    <table class="data-table">
                        <thead><tr><th>Barang</th><th>Jumlah</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php if ($result_masuk && $result_masuk->num_rows > 0): ?>
                                <?php while($row = $result_masuk->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['produk']); ?></td>
                                        <td><?php echo htmlspecialchars($row['jumlah_diajukan']); ?></td>
                                        <td>
                                            <form action="proses_stok.php" method="POST" style="margin:0;">
                                                <input type="hidden" name="jenis_log" value="masuk">
                                                <input type="hidden" name="id_barang" value="<?php echo $row['id_barang']; ?>">
                                                <input type="hidden" name="jumlah" value="<?php echo $row['jumlah_diajukan']; ?>">
                                                <input type="hidden" name="id_pengadaan" value="<?php echo $row['id_pengadaan']; ?>">
                                                <button type="submit" class="btn-log">Catat Masuk</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align:center;">Tidak ada barang masuk yang perlu dicatat.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-container">
                    <h3><i class="fas fa-arrow-up"></i> Catat Barang Keluar</h3>
                    <p>Gunakan form ini untuk mencatat pemakaian barang internal (produksi, ATK, dll).</p>
                    
                    <!-- Alert untuk stok kosong -->
                    <div id="stok-warning" class="status-message error" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Peringatan:</strong> Barang yang dipilih saat ini tidak memiliki stok atau stok habis. Silakan pilih barang lain.
                    </div>
                    
                    <form action="proses_stok.php" method="POST" id="form-keluar">
                         <input type="hidden" name="jenis_log" value="keluar">
                         <div class="form-group">
                            <label for="id_barang_keluar">Pilih Barang</label>
                            <select id="id_barang_keluar" name="id_barang" required onchange="checkStok()">
                                <option value="">-- Pilih Barang --</option>
                                <?php while($row = $result_barang_all->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id_barang']; ?>" data-stok="<?php echo $row['stok_tersedia']; ?>">
                                        <?php echo htmlspecialchars($row['produk'] . ' - ' . $row['merek']); ?> 
                                        (Stok: <?php echo $row['stok_tersedia']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="jumlah_keluar">Jumlah Keluar</label>
                            <input type="number" id="jumlah_keluar" name="jumlah" required min="1" onchange="validateJumlah()">
                            <small id="stok-info" style="color: #666; font-size: 0.9em;"></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="keterangan_keluar">Keterangan</label>
                            <input type="text" id="keterangan_keluar" name="keterangan" placeholder="Contoh: Untuk produksi batch A" required>
                        </div>
                        
                        <button type="submit" id="btn-submit" class="btn-log" style="background-color: var(--danger);">Catat Keluar</button>
                    </form>
                </div>
            </div>
            
            <!-- Section Log Stok -->
            <div class="log-section">
                <h3><i class="fas fa-history"></i> Riwayat Log Stok Barang</h3>
                <p>Riwayat transaksi barang masuk dan keluar dari gudang.</p>
                
                <!-- Filter Jenis Log -->
                <div class="filter-section">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Filter Berdasarkan Jenis Log:</label>
                    <div class="filter-buttons">
                        <a href="?jenis_log=semua" class="filter-btn <?php echo ($jenis_log_filter == 'semua') ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> Semua (<?php echo $count_all; ?>)
                        </a>
                        <a href="?jenis_log=masuk" class="filter-btn <?php echo ($jenis_log_filter == 'masuk') ? 'active' : ''; ?>">
                            <i class="fas fa-arrow-down"></i> Barang Masuk (<?php echo $count_masuk; ?>)
                        </a>
                        <a href="?jenis_log=keluar" class="filter-btn <?php echo ($jenis_log_filter == 'keluar') ? 'active' : ''; ?>">
                            <i class="fas fa-arrow-up"></i> Barang Keluar (<?php echo $count_keluar; ?>)
                        </a>
                    </div>
                    <small style="color: #666; font-style: italic;">Menampilkan maksimal 50 record terbaru</small>
                </div>
                
                <!-- Tabel Log -->
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID Log</th>
                            <th>Tanggal</th>
                            <th>Nama Barang</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_log && $result_log->num_rows > 0): ?>
                            <?php while($log = $result_log->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['id_log']); ?></td>
                                    <td><?php echo date("d-m-Y", strtotime($log['tanggal'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['produk'] . ' - ' . $log['merek']); ?></td>
                                    <td>
                                        <span class="log-badge log-<?php echo $log['jenis_log']; ?>">
                                            <?php if ($log['jenis_log'] == 'masuk'): ?>
                                                <i class="fas fa-arrow-down"></i> Masuk
                                            <?php else: ?>
                                                <i class="fas fa-arrow-up"></i> Keluar
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['jumlah']); ?></td>
                                    <td>
                                        <?php 
                                        $keterangan = htmlspecialchars($log['keterangan']);
                                        // Format keterangan yang lebih baik
                                        if (strlen($keterangan) > 60) {
                                            $keterangan_short = substr($keterangan, 0, 57) . '...';
                                            echo "<span title='$keterangan' style='cursor: help;'>$keterangan_short</span>";
                                        } else {
                                            echo $keterangan;
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #666;">
                                    <?php if ($jenis_log_filter == 'masuk'): ?>
                                        Belum ada riwayat barang masuk.
                                    <?php elseif ($jenis_log_filter == 'keluar'): ?>
                                        Belum ada riwayat barang keluar.
                                    <?php else: ?>
                                        Belum ada riwayat log stok.
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
    <script>
        function checkStok() {
            const select = document.getElementById('id_barang_keluar');
            const selectedOption = select.selectedOptions[0];
            const warning = document.getElementById('stok-warning');
            const stokInfo = document.getElementById('stok-info');
            const jumlahInput = document.getElementById('jumlah_keluar');
            const btnSubmit = document.getElementById('btn-submit');
            
            if (selectedOption && selectedOption.value) {
                const stok = parseInt(selectedOption.getAttribute('data-stok'));
                
                if (stok <= 0) {
                    warning.style.display = 'block';
                    stokInfo.innerHTML = `<span style="color: red;">⚠️ Stok tidak tersedia (${stok})</span>`;
                    jumlahInput.disabled = true;
                    btnSubmit.disabled = true;
                    btnSubmit.style.opacity = '0.5';
                    btnSubmit.style.cursor = 'not-allowed';
                } else {
                    warning.style.display = 'none';
                    stokInfo.innerHTML = `<span style="color: green;">✅ Stok tersedia: ${stok} unit</span>`;
                    jumlahInput.disabled = false;
                    jumlahInput.max = stok;
                    btnSubmit.disabled = false;
                    btnSubmit.style.opacity = '1';
                    btnSubmit.style.cursor = 'pointer';
                }
                
                validateJumlah();
            } else {
                warning.style.display = 'none';
                stokInfo.innerHTML = '';
                jumlahInput.disabled = false;
                btnSubmit.disabled = false;
                btnSubmit.style.opacity = '1';
                btnSubmit.style.cursor = 'pointer';
            }
        }
        
        function validateJumlah() {
            const select = document.getElementById('id_barang_keluar');
            const selectedOption = select.selectedOptions[0];
            const jumlahInput = document.getElementById('jumlah_keluar');
            const stokInfo = document.getElementById('stok-info');
            const btnSubmit = document.getElementById('btn-submit');
            
            if (selectedOption && selectedOption.value && jumlahInput.value) {
                const stok = parseInt(selectedOption.getAttribute('data-stok'));
                const jumlah = parseInt(jumlahInput.value);
                
                if (jumlah > stok) {
                    stokInfo.innerHTML = `<span style="color: red;">❌ Jumlah melebihi stok tersedia (${stok})</span>`;
                    btnSubmit.disabled = true;
                    btnSubmit.style.opacity = '0.5';
                    btnSubmit.style.cursor = 'not-allowed';
                } else if (stok > 0) {
                    stokInfo.innerHTML = `<span style="color: green;">✅ Stok tersedia: ${stok} unit</span>`;
                    btnSubmit.disabled = false;
                    btnSubmit.style.opacity = '1';
                    btnSubmit.style.cursor = 'pointer';
                }
            }
        }
        
        // Tambahkan event listener untuk form submit
        document.getElementById('form-keluar').addEventListener('submit', function(e) {
            const select = document.getElementById('id_barang_keluar');
            const selectedOption = select.selectedOptions[0];
            
            if (selectedOption && selectedOption.value) {
                const stok = parseInt(selectedOption.getAttribute('data-stok'));
                const jumlah = parseInt(document.getElementById('jumlah_keluar').value);
                
                if (stok <= 0) {
                    e.preventDefault();
                    alert('Barang yang dipilih tidak memiliki stok. Silakan pilih barang lain.');
                    return false;
                }
                
                if (jumlah > stok) {
                    e.preventDefault();
                    alert(`Jumlah yang diminta (${jumlah}) melebihi stok tersedia (${stok}). Silakan kurangi jumlahnya.`);
                    return false;
                }
            }
        });
        
        // Auto-scroll ke riwayat log jika ada status success
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status') === 'success') {
                // Scroll ke section riwayat log
                setTimeout(function() {
                    const logSection = document.querySelector('.log-section');
                    if (logSection) {
                        logSection.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'start' 
                        });
                        
                        // Highlight section untuk beberapa detik
                        logSection.style.background = 'rgba(39, 174, 96, 0.1)';
                        logSection.style.transition = 'background 0.5s ease';
                        setTimeout(function() {
                            logSection.style.background = '';
                        }, 3000);
                    }
                }, 500);
            }
        });
        
        // Tambahkan loading indicator untuk form submissions
        document.querySelectorAll('form[action="proses_stok.php"]').forEach(function(form) {
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    submitBtn.disabled = true;
                }
            });
        });
    </script>
</body>
</html>