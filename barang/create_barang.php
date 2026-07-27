<?php
session_start();
require_once '../config.php';
require_once '../helpers.php'; // Panggil helper

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Inventory & Purchasing Officer') {
    header('Location: ../login.php');
    exit();
}

$suppliers_result = $conn->query("SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Generate ID baru
    $new_id = generate_id('BRNG', 'barang', 'id_barang');

    $produk = $_POST['produk'];
    $merek = $_POST['merek'];
    $satuan = $_POST['satuan'];
    $harga = $_POST['harga'];
    $lead_time = $_POST['lead_time'];
    $id_supplier = $_POST['id_supplier'];

    $stmt = $conn->prepare("INSERT INTO barang (id_barang, produk, merek, satuan, harga, lead_time, id_supplier) VALUES (?, ?, ?, ?, ?, ?, ?)");
    // Tipe data ID barang 's', id_supplier 's'
    $stmt->bind_param("ssssiss", $new_id, $produk, $merek, $satuan, $harga, $lead_time, $id_supplier);
    
    if ($stmt->execute()) {
        header("Location: barang.php?status=success_create");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Barang - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .form-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: .5rem; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: .75rem; border: 1px solid #ccc; border-radius: 5px; }
        .btn-submit { background-color: var(--success); color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-back { background-color: var(--gray); color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; }
        .error-message { color: var(--danger); margin-bottom: 1rem; }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <div class="form-container">
                <h2 class="dashboard-title"><i class="fas fa-plus-circle"></i> Tambah Barang Baru</h2>
                <?php if (isset($error)): ?><p class="error-message"><?php echo $error; ?></p><?php endif; ?>
                <form action="create_barang.php" method="POST">
                    <div class="form-group">
                        <label for="produk">Nama Produk</label>
                        <input type="text" id="produk" name="produk" required>
                    </div>
                    <div class="form-group">
                        <label for="merek">Merek</label>
                        <input type="text" id="merek" name="merek">
                    </div>
                    <div class="form-group">
                        <label for="satuan">Satuan (e.g., Pcs, Box, Kg)</label>
                        <input type="text" id="satuan" name="satuan">
                    </div>
                    <div class="form-group">
                        <label for="harga">Harga</label>
                        <input type="number" id="harga" name="harga" required>
                    </div>
                    <div class="form-group">
                        <label for="lead_time">Lead Time (Hari)</label>
                        <input type="number" id="lead_time" name="lead_time">
                    </div>
                    <div class="form-group">
                        <label for="id_supplier">Supplier</label>
                        <select id="id_supplier" name="id_supplier" required>
                            <option value="">-- Pilih Supplier --</option>
                            <?php while($supplier = $suppliers_result->fetch_assoc()): ?>
                                <option value="<?php echo $supplier['id_supplier']; ?>"><?php echo htmlspecialchars($supplier['nama_supplier']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit">Simpan</button>
                    <a href="barang.php" class="btn-back">Batal</a>
                </form>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>