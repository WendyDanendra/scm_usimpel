<?php
session_start();
require_once '../config.php';

// Proteksi halaman - izinkan Inventory, Administrator, dan Admin
$jabatan_lower = strtolower(trim($_SESSION['jabatan'] ?? ''));
if (!isset($_SESSION['jabatan']) || !in_array($jabatan_lower, ['inventory & purchasing officer', 'administrator', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

$id_barang = $_GET['id'] ?? null;
if (!$id_barang) {
    header("Location: barang.php");
    exit();
}

// Logika untuk UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_to_update = $_POST['id_barang'];
    $produk = $_POST['produk'];
    $merek = $_POST['merek'];
    $satuan = $_POST['satuan'];
    $harga = $_POST['harga'];
    $lead_time = $_POST['lead_time'];
    $id_supplier = $_POST['id_supplier'];

    $stmt = $conn->prepare("UPDATE barang SET produk=?, merek=?, satuan=?, harga=?, lead_time=?, id_supplier=? WHERE id_barang=?");
    $stmt->bind_param("ssssiss", $produk, $merek, $satuan, $harga, $lead_time, $id_supplier, $id_to_update);
    
    if ($stmt->execute()) {
        header("Location: barang.php?status=success_update");
        exit();
    } else {
        $error = "Gagal memperbarui data: " . $stmt->error;
    }
}

// Ambil data barang yang akan diedit
$stmt = $conn->prepare("SELECT * FROM barang WHERE id_barang = ?");
$stmt->bind_param("s", $id_barang);
$stmt->execute();
$barang = $stmt->get_result()->fetch_assoc();

if (!$barang) {
    echo "Barang tidak ditemukan.";
    exit();
}

// Ambil daftar supplier untuk dropdown
$suppliers_result = $conn->query("SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier");
$selected_supp_id = strtolower(trim($barang['id_supplier'] ?? ''));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ubah Barang - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .form-container { background: white; padding: 2rem; border-radius: 10px; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: .5rem; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: .75rem; border: 1px solid #ccc; border-radius: 5px; }
        .btn-submit { background-color: var(--success); color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-back { background-color: var(--gray); color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <div class="form-container">
                <h2 class="dashboard-title"><i class="fas fa-edit"></i> Ubah Data Barang</h2>
                <?php if (isset($error)): ?><p class="error-message"><?php echo $error; ?></p><?php endif; ?>
                <form action="update_barang.php?id=<?php echo urlencode($id_barang); ?>" method="POST">
                    <input type="hidden" name="id_barang" value="<?php echo htmlspecialchars($barang['id_barang']); ?>">
                    <div class="form-group">
                        <label>Nama Produk</label>
                        <input type="text" name="produk" value="<?php echo htmlspecialchars($barang['produk']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Merek</label>
                        <input type="text" name="merek" value="<?php echo htmlspecialchars($barang['merek']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Satuan</label>
                        <input type="text" name="satuan" value="<?php echo htmlspecialchars($barang['satuan']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Harga</label>
                        <input type="number" name="harga" value="<?php echo htmlspecialchars($barang['harga']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Lead Time (Hari)</label>
                        <input type="number" name="lead_time" value="<?php echo htmlspecialchars($barang['lead_time']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <select name="id_supplier" required>
                            <option value="">-- Pilih Supplier --</option>
                            <?php while($supplier = $suppliers_result->fetch_assoc()): 
                                $curr_supp_id = strtolower(trim($supplier['id_supplier'] ?? ''));
                            ?>
                                <option value="<?php echo htmlspecialchars($supplier['id_supplier']); ?>" <?php echo ($curr_supp_id === $selected_supp_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($supplier['nama_supplier']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit">Perbarui</button>
                    <a href="barang.php" class="btn-back">Batal</a>
                </form>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>