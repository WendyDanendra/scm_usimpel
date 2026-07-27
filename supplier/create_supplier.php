<?php
session_start();
require_once '../config.php';
require_once '../helpers.php'; // Panggil helper

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Inventory & Purchasing Officer') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Generate ID baru
    $new_id = generate_id('SUPP', 'supplier', 'id_supplier');

    $nama = $_POST['nama_supplier'];
    $alamat = $_POST['alamat'];
    $kontak = $_POST['kontak'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("INSERT INTO supplier (id_supplier, nama_supplier, alamat, kontak, email) VALUES (?, ?, ?, ?, ?)");
    // Tipe data ID sekarang 's' (string)
    $stmt->bind_param("sssss", $new_id, $nama, $alamat, $kontak, $email);
    
    if ($stmt->execute()) {
        header("Location: supplier.php?status=success_create");
        exit();
    } else {
        $error = "Error: " . $stmt->error;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Supplier - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: .5rem;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: .75rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .btn-submit {
            background-color: var(--success);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-back {
            background-color: var(--gray);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
        }
        .error-message {
            color: var(--danger);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <div class="form-container">
                <h2 class="dashboard-title"><i class="fas fa-plus-circle"></i> Tambah Supplier Baru</h2>
                
                <?php if (isset($error)): ?>
                    <p class="error-message"><?php echo $error; ?></p>
                <?php endif; ?>

                <form action="create_supplier.php" method="POST">
                    <div class="form-group">
                        <label for="nama_supplier">Nama Supplier</label>
                        <input type="text" id="nama_supplier" name="nama_supplier" required>
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <input type="text" id="alamat" name="alamat" required>
                    </div>
                    <div class="form-group">
                        <label for="kontak">Kontak (Telepon)</label>
                        <input type="text" id="kontak" name="kontak" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn-submit">Simpan</button>
                    <a href="supplier.php" class="btn-back">Batal</a>
                </form>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>