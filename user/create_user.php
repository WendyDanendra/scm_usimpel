<?php
session_start();
require_once '../config.php';
require_once '../helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_user = trim($_POST['nama_user']);
    $jabatan = trim($_POST['jabatan']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($nama_user) && !empty($jabatan) && !empty($username) && !empty($password)) {
        // Cekapakah username sudah digunakan
        $check_stmt = $conn->prepare("SELECT id_user FROM user WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Username '$username' sudah digunakan, silakan pilih username lain.";
        } else {
            // Generate ID baru
            $new_id = generate_id('USER', 'user', 'id_user');

            $stmt = $conn->prepare("INSERT INTO user (id_user, nama_user, jabatan, username, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $new_id, $nama_user, $jabatan, $username, $password);

            if ($stmt->execute()) {
                header("Location: user.php?status=success_create");
                exit();
            } else {
                $error = "Gagal menambah pengguna: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $error = "Semua bidang wajib diisi.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengguna - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .form-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: .5rem; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: .75rem; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn-submit { background-color: var(--success); color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; font-weight: 600; }
        .btn-submit:hover { background-color: #27ae60; }
        .btn-back { background-color: var(--gray); color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; font-size: 1rem; }
        .error-message { color: var(--danger); margin-bottom: 1rem; padding: 10px; background: rgba(231, 76, 60, 0.1); border-radius: 5px; border: 1px solid var(--danger); }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <div class="form-container">
                <h2 class="dashboard-title"><i class="fas fa-user-plus"></i> Tambah Pengguna Baru</h2>
                
                <?php if (!empty($error)): ?>
                    <p class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>

                <form action="create_user.php" method="POST">
                    <div class="form-group">
                        <label for="nama_user">Nama Lengkap</label>
                        <input type="text" id="nama_user" name="nama_user" placeholder="Contoh: Ahmad Yani" value="<?php echo htmlspecialchars($_POST['nama_user'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="jabatan">Jabatan</label>
                        <select id="jabatan" name="jabatan" required>
                            <option value="">-- Pilih Jabatan --</option>
                            <option value="Administrator" <?php echo (($_POST['jabatan'] ?? '') == 'Administrator') ? 'selected' : ''; ?>>Administrator</option>
                            <option value="Inventory & Purchasing Officer" <?php echo (($_POST['jabatan'] ?? '') == 'Inventory & Purchasing Officer') ? 'selected' : ''; ?>>Inventory & Purchasing Officer</option>
                            <option value="Finance & Billing Officer" <?php echo (($_POST['jabatan'] ?? '') == 'Finance & Billing Officer') ? 'selected' : ''; ?>>Finance & Billing Officer</option>
                            <option value="Kepala Divisi Produk & Pengadaan" <?php echo (($_POST['jabatan'] ?? '') == 'Kepala Divisi Produk & Pengadaan') ? 'selected' : ''; ?>>Kepala Divisi Produk & Pengadaan</option>
                            <option value="Direktur Operasional" <?php echo (($_POST['jabatan'] ?? '') == 'Direktur Operasional') ? 'selected' : ''; ?>>Direktur Operasional</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Contoh: ahmadyani" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Simpan</button>
                        <a href="user.php" class="btn-back"><i class="fas fa-arrow-left"></i> Batal</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
