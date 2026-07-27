<?php
session_start();
require_once '../config.php';
$jabatan_lower = strtolower(trim($_SESSION['jabatan'] ?? ''));
if (!isset($_SESSION['jabatan']) || !in_array($jabatan_lower, ['inventory & purchasing officer', 'administrator', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

$id = $_GET['id'];
$error = '';
$success = '';

// Ambil data pengajuan
$sql = "SELECT * FROM pengajuan_barang WHERE id_pengajuan = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$pengajuan = $result->fetch_assoc();

if (!$pengajuan) {
    header('Location: pengajuan_barang.php');
    exit();
}

// Status yang diizinkan untuk diubah
$allowed_status = ['Diajukan', 'Diproses', 'Disetujui', 'Ditolak'];
if (!in_array($pengajuan['status_pengajuan'], $allowed_status)) {
    header('Location: pengajuan_barang.php?error=Status tidak dapat diubah dari ' . $pengajuan['status_pengajuan']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status_pengajuan'];
    
    $sql = "UPDATE pengajuan_barang 
            SET status_pengajuan = ?
            WHERE id_pengajuan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);
    
    if ($stmt->execute()) {
        $success = "Status pengajuan berhasil diupdate";
        $pengajuan['status_pengajuan'] = $status;
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Status Pengajuan - PT Usimpel Inovasi Indonesia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Semua gaya CSS dari create_pengajuan.php */
        
        .status-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .status-option {
            flex: 1;
            min-width: 120px;
            text-align: center;
            padding: 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .status-option:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .status-option.selected {
            border-color: var(--primary);
        }
        
        .status-option input {
            display: none;
        }
        
        .status-diajukan-option {
            background-color: #3498db;
            color: white;
        }
        
        .status-diproses-option {
            background-color: #f39c12;
            color: white;
        }
        
        .status-disetujui-option {
            background-color: #2ecc71;
            color: white;
        }
        
        .status-ditolak-option {
            background-color: #e74c3c;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        
        <!-- Content -->
        <div class="content">
            <div class="form-container">
                <h2><i class="fas fa-sync"></i> Update Status Pengajuan</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="id_pengajuan">ID Pengajuan</label>
                        <input type="text" id="id_pengajuan" value="PB<?= str_pad($pengajuan['id_pengajuan'], 4, '0', STR_PAD_LEFT) ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Barang</label>
                        <p><?= $pengajuan['id_barang'] ?> (Jumlah: <?= $pengajuan['jumlah_diajukan'] ?>)</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Supplier</label>
                        <p><?= $pengajuan['id_supplier'] ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Status Saat Ini</label>
                        <?php
                            $status_class = 'status-diajukan';
                            if ($pengajuan['status_pengajuan'] === 'Disetujui') $status_class = 'status-disetujui';
                            elseif ($pengajuan['status_pengajuan'] === 'Ditolak') $status_class = 'status-ditolak';
                            elseif ($pengajuan['status_pengajuan'] === 'Diproses') $status_class = 'status-diproses';
                        ?>
                        <div class="status-badge <?= $status_class ?>"><?= $pengajuan['status_pengajuan'] ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status_pengajuan">Status Baru</label>
                        <div class="status-options">
                            <label class="status-option status-diajukan-option <?= $pengajuan['status_pengajuan'] === 'Diajukan' ? 'selected' : '' ?>">
                                <input type="radio" name="status_pengajuan" value="Diajukan" <?= $pengajuan['status_pengajuan'] === 'Diajukan' ? 'checked' : '' ?>>
                                <i class="fas fa-paper-plane"></i><br>
                                Diajukan
                            </label>
                            
                            <label class="status-option status-diproses-option <?= $pengajuan['status_pengajuan'] === 'Diproses' ? 'selected' : '' ?>">
                                <input type="radio" name="status_pengajuan" value="Diproses" <?= $pengajuan['status_pengajuan'] === 'Diproses' ? 'checked' : '' ?>>
                                <i class="fas fa-cogs"></i><br>
                                Diproses
                            </label>
                            
                            <label class="status-option status-disetujui-option <?= $pengajuan['status_pengajuan'] === 'Disetujui' ? 'selected' : '' ?>">
                                <input type="radio" name="status_pengajuan" value="Disetujui" <?= $pengajuan['status_pengajuan'] === 'Disetujui' ? 'checked' : '' ?>>
                                <i class="fas fa-check-circle"></i><br>
                                Disetujui
                            </label>
                            
                            <label class="status-option status-ditolak-option <?= $pengajuan['status_pengajuan'] === 'Ditolak' ? 'selected' : '' ?>">
                                <input type="radio" name="status_pengajuan" value="Ditolak" <?= $pengajuan['status_pengajuan'] === 'Ditolak' ? 'checked' : '' ?>>
                                <i class="fas fa-times-circle"></i><br>
                                Ditolak
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Status</button>
                        <a href="pengajuan_barang.php" class="btn btn-danger"><i class="fas fa-times"></i> Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Mobile sidebar toggle
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('open');
        });
        
        // Status selection
        document.querySelectorAll('.status-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.status-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Check the radio button
                this.querySelector('input').checked = true;
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>