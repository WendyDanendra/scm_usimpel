<?php
session_start();
require_once '../config.php';
require_once '../helpers.php'; // Panggil helper

// Proteksi halaman
if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Inventory & Purchasing Officer') {
    header('Location: ../login.php');
    exit();
}

// Query untuk mengambil data barang
$barangs = $conn->query("SELECT id_barang, produk, merek, id_supplier FROM barang ORDER BY produk ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Generate ID baru
    $new_id = generate_id('PNJN', 'pengajuan_barang', 'id_pengajuan');

    $tanggal_pengajuan = date("Y-m-d");
    $id_barang = $_POST['id_barang'];
    $jumlah_diajukan = $_POST['jumlah_diajukan'];
    $id_supplier = $_POST['id_supplier'];
    $status_pengajuan = 'Diajukan';

    $stmt = $conn->prepare("INSERT INTO pengajuan_barang (id_pengajuan, tanggal_pengajuan, id_barang, jumlah_diajukan, id_supplier, status_pengajuan) VALUES (?, ?, ?, ?, ?, ?)");
    // Tipe data ID pengajuan 's', id_barang 's', id_supplier 's'
    $stmt->bind_param("ssssss", $new_id, $tanggal_pengajuan, $id_barang, $jumlah_diajukan, $id_supplier, $status_pengajuan);
    
    if ($stmt->execute()) {
        header("Location: pengajuan_barang.php?status=success_create");
        exit();
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Pengajuan Barang - SCM</title>
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
                <h2 class="dashboard-title"><i class="fas fa-plus-circle"></i> Buat Pengajuan Barang</h2>
                <form action="create_pengajuan.php" method="POST">
                    <div class="form-group">
                        <label for="id_barang">Pilih Barang</label>
                        <select id="id_barang" name="id_barang" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php while($row = $barangs->fetch_assoc()): ?>
                                <option value="<?php echo $row['id_barang']; ?>"><?php echo htmlspecialchars($row['produk'] . ' - ' . $row['merek']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jumlah_diajukan">Jumlah Diajukan</label>
                        <input type="number" id="jumlah_diajukan" name="jumlah_diajukan" required min="1">
                    </div>
                    <div class="form-group">
                        <label for="id_supplier">Supplier (Otomatis Terpilih)</label>
                        <select id="id_supplier" name="id_supplier" required>
                            <option value="">-- Menunggu Barang Dipilih --</option>
                        </select>
                    </div>
                    <div class="button-group">
                        <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Ajukan</button>
                        <a href="pengajuan_barang.php" class="btn-back"><i class="fas fa-times"></i> Batal</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
    <script>
    // Script untuk mengambil supplier terkait saat barang dipilih
    document.getElementById('id_barang').addEventListener('change', function() {
        const idBarang = this.value;
        const supplierSelect = document.getElementById('id_supplier');
        supplierSelect.innerHTML = '<option value="">Memuat...</option>';

        if (!idBarang) {
            supplierSelect.innerHTML = '<option value="">-- Menunggu Barang Dipilih --</option>';
            return;
        }

        // Kita butuh file PHP terpisah untuk mengambil data ini
        fetch('get_supplier_by_barang.php?id_barang=' + idBarang)
            .then(response => response.json())
            .then(data => {
                if (data.id_supplier) {
                    supplierSelect.innerHTML = `<option value="${data.id_supplier}" selected>${data.nama_supplier}</option>`;
                } else {
                    supplierSelect.innerHTML = '<option value="">Supplier tidak ditemukan</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                supplierSelect.innerHTML = '<option value="">Gagal memuat supplier</option>';
            });
    });
    </script>
</body>
</html>