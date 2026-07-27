<?php
session_start();
require_once '../config.php';

// Proteksi halaman
$jabatan_lower = strtolower(trim($_SESSION['jabatan'] ?? ''));
if (!isset($_SESSION['jabatan']) || !in_array($jabatan_lower, ['inventory & purchasing officer', 'administrator', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

$id_supplier = $_GET['id'] ?? null;
if (!$id_supplier) {
    header("Location: supplier.php");
    exit();
}

// Logika untuk UPDATE data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_to_update = $_POST['id_supplier'];
    $nama = $_POST['nama_supplier'];
    $alamat = $_POST['alamat'];
    $kontak = $_POST['kontak'];
    $email = $_POST['email'];

    if (!empty($nama) && !empty($alamat) && !empty($kontak) && !empty($email)) {
        $stmt = $conn->prepare("UPDATE supplier SET nama_supplier = ?, alamat = ?, kontak = ?, email = ? WHERE id_supplier = ?");
        $stmt->bind_param("sssss", $nama, $alamat, $kontak, $email, $id_to_update);
        
        if ($stmt->execute()) {
            header("Location: supplier.php?status=success_update");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Semua field wajib diisi.";
    }
}

// Logika untuk SELECT data yang akan di-edit
$stmt = $conn->prepare("SELECT * FROM supplier WHERE id_supplier = ?");
$stmt->bind_param("s", $id_supplier);
$stmt->execute();
$result = $stmt->get_result();
$supplier = $result->fetch_assoc();

if (!$supplier) {
    echo "Supplier tidak ditemukan.";
    exit();
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Data Supplier - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .form-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: .5rem; font-weight: 600; }
        .form-group input { width: 100%; padding: .75rem; border: 1px solid #ccc; border-radius: 5px; }
        .btn-submit { background-color: var(--success); color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; }
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
                <h2 class="dashboard-title"><i class="fas fa-edit"></i> Ubah Data Supplier</h2>
                
                <?php if (isset($error)): ?>
                    <p class="error-message"><?php echo $error; ?></p>
                <?php endif; ?>

                <form action="update_supplier.php?id=<?php echo $id_supplier; ?>" method="POST">
                    <input type="hidden" name="id_supplier" value="<?php echo $supplier['id_supplier']; ?>">
                    <div class="form-group">
                        <label for="nama_supplier">Nama Supplier</label>
                        <input type="text" id="nama_supplier" name="nama_supplier" value="<?php echo htmlspecialchars($supplier['nama_supplier']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <input type="text" id="alamat" name="alamat" value="<?php echo htmlspecialchars($supplier['alamat']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="kontak">Kontak (Telepon)</label>
                        <input type="text" id="kontak" name="kontak" value="<?php echo htmlspecialchars($supplier['kontak']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($supplier['email']); ?>" required>
                    </div>
                    <button type="submit" class="btn-submit">Perbarui</button>
                    <a href="supplier.php" class="btn-back">Batal</a>
                </form>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>