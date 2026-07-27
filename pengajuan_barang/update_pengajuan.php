<?php
session_start();
require_once '../config.php';

// Proteksi halaman
$jabatan_lower = strtolower(trim($_SESSION['jabatan'] ?? ''));
if (!isset($_SESSION['jabatan']) || !in_array($jabatan_lower, ['inventory & purchasing officer', 'administrator', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

$id_pengajuan = $_GET['id'] ?? null;
if (!$id_pengajuan) {
    header("Location: pengajuan_barang.php");
    exit();
}

// Logika untuk UPDATE data saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_to_update = $_POST['id_pengajuan'];
    $id_barang = $_POST['id_barang'];
    $jumlah_diajukan = $_POST['jumlah_diajukan'];
    $id_supplier = $_POST['id_supplier'];

    $stmt = $conn->prepare("UPDATE pengajuan_barang SET id_barang = ?, jumlah_diajukan = ?, id_supplier = ? WHERE id_pengajuan = ? AND status_pengajuan = 'Diajukan'");
    $stmt->bind_param("ssss", $id_barang, $jumlah_diajukan, $id_supplier, $id_to_update);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        header("Location: pengajuan_barang.php?status=success_update");
        exit();
    } else {
        header("Location: pengajuan_barang.php?status=error_approved");
        exit();
    }
}

// Ambil data pengajuan yang akan di-edit
$stmt = $conn->prepare("SELECT * FROM pengajuan_barang WHERE id_pengajuan = ?");
$stmt->bind_param("s", $id_pengajuan);
$stmt->execute();
$pengajuan = $stmt->get_result()->fetch_assoc();

// Jika pengajuan tidak ditemukan atau statusnya bukan 'Diajukan', redirect
if (!$pengajuan || $pengajuan['status_pengajuan'] != 'Diajukan') {
    header("Location: pengajuan_barang.php?status=error_approved");
    exit();
}

// Ambil data supplier untuk pengajuan ini secara server-side
$supplier_info = null;
if (!empty($pengajuan['id_supplier'])) {
    $stmt_supp = $conn->prepare("SELECT id_supplier, nama_supplier FROM supplier WHERE id_supplier = ?");
    $stmt_supp->bind_param("s", $pengajuan['id_supplier']);
    $stmt_supp->execute();
    $supplier_info = $stmt_supp->get_result()->fetch_assoc();
    $stmt_supp->close();
}

// Ambil daftar barang untuk dropdown
$barangs = $conn->query("SELECT id_barang, produk, merek FROM barang ORDER BY produk");
$selected_brg_id = strtolower(trim($pengajuan['id_barang'] ?? ''));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ubah Pengajuan Barang - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .form-container { background: white; padding: 2rem; border-radius: 10px; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: .5rem; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: .75rem; border: 1px solid #ccc; border-radius: 5px; }
        .button-group { display: flex; gap: 15px; margin-top: 20px; justify-content: flex-start; }
        .btn-submit, .btn-back { 
            padding: 12px 24px; 
            border: none; 
            border-radius: 8px; 
            text-decoration: none; 
            text-align: center; 
            font-size: 14px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease;
            min-width: 120px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-submit { 
            background-color: var(--success); 
            color: white; 
        }
        .btn-submit:hover { 
            background-color: #27ae60; 
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .btn-back { 
            background-color: #6c757d; 
            color: white; 
            display: inline-block; 
        }
        .btn-back:hover { 
            background-color: #5a6268; 
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <div class="form-container">
                <h2 class="dashboard-title"><i class="fas fa-edit"></i> Ubah Pengajuan Barang</h2>
                <form action="update_pengajuan.php?id=<?php echo urlencode($id_pengajuan); ?>" method="POST">
                    <input type="hidden" name="id_pengajuan" value="<?php echo htmlspecialchars($pengajuan['id_pengajuan']); ?>">
                    <div class="form-group">
                        <label for="id_barang">Pilih Barang</label>
                        <select id="id_barang" name="id_barang" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php while($row = $barangs->fetch_assoc()): 
                                $curr_brg_id = strtolower(trim($row['id_barang'] ?? ''));
                            ?>
                                <option value="<?php echo htmlspecialchars($row['id_barang']); ?>" <?php echo ($curr_brg_id === $selected_brg_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['produk'] . ' - ' . $row['merek']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jumlah_diajukan">Jumlah Diajukan</label>
                        <input type="number" id="jumlah_diajukan" name="jumlah_diajukan" required min="1" value="<?php echo htmlspecialchars($pengajuan['jumlah_diajukan']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="id_supplier">Supplier (Otomatis Terpilih)</label>
                        <select id="id_supplier" name="id_supplier" required>
                            <?php if ($supplier_info): ?>
                                <option value="<?php echo htmlspecialchars($supplier_info['id_supplier']); ?>" selected><?php echo htmlspecialchars($supplier_info['nama_supplier']); ?></option>
                            <?php else: ?>
                                <option value="">-- Menunggu Barang Dipilih --</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="button-group">
                        <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Perbarui</button>
                        <a href="pengajuan_barang.php" class="btn-back"><i class="fas fa-times"></i> Batal</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
    <script>
    // Memuat supplier berdasarkan barang yang dipilih
    function loadSupplier(idBarang, preserveExisting) {
        const supplierSelect = document.getElementById('id_supplier');
        if (!idBarang) {
            supplierSelect.innerHTML = '<option value="">-- Menunggu Barang Dipilih --</option>';
            return;
        }

        // Jika preserveExisting true dan sudah ada option terpilih (dari server-side PHP), jangan timpa!
        if (preserveExisting && supplierSelect.value && supplierSelect.value !== "") {
            return;
        }

        fetch('get_supplier_by_barang.php?id_barang=' + encodeURIComponent(idBarang))
            .then(response => response.json())
            .then(data => {
                if (data.id_supplier) {
                    supplierSelect.innerHTML = `<option value="${data.id_supplier}" selected>${data.nama_supplier}</option>`;
                } else {
                    supplierSelect.innerHTML = '<option value="">Supplier tidak ditemukan</option>';
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Panggil saat pertama kali dimuat dengan mempertahankan data server-side
    document.addEventListener('DOMContentLoaded', function() {
        const initialIdBarang = document.getElementById('id_barang').value;
        loadSupplier(initialIdBarang, true);
    });

    // Panggil saat barang diubah manual oleh pengguna
    document.getElementById('id_barang').addEventListener('change', function() {
        loadSupplier(this.value, false);
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>