<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$id_user = $_GET['id'] ?? null;
if (!$id_user) {
    header("Location: user.php");
    exit();
}

$error = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_to_update = $_POST['id_user'];
    $nama_user = trim($_POST['nama_user']);
    $jabatan = trim($_POST['jabatan']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($nama_user) && !empty($jabatan) && !empty($username)) {
        // Check if username is used by another user
        $check_stmt = $conn->prepare("SELECT id_user FROM user WHERE username = ? AND id_user != ?");
        $check_stmt->bind_param("ss", $username, $id_to_update);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Username '$username' sudah digunakan oleh pengguna lain.";
        } else {
            if (!empty($password)) {
                // Update with new password
                $stmt = $conn->prepare("UPDATE user SET nama_user = ?, jabatan = ?, username = ?, password = ? WHERE id_user = ?");
                $stmt->bind_param("sssss", $nama_user, $jabatan, $username, $password, $id_to_update);
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE user SET nama_user = ?, jabatan = ?, username = ? WHERE id_user = ?");
                $stmt->bind_param("ssss", $nama_user, $jabatan, $username, $id_to_update);
            }

            if ($stmt->execute()) {
                // Update active session name/jabatan if current user edited their own profile
                if ($_SESSION['user_id'] == $id_to_update) {
                    $_SESSION['name'] = $nama_user;
                    $_SESSION['jabatan'] = $jabatan;
                }
                header("Location: user.php?status=success_update");
                exit();
            } else {
                $error = "Gagal memperbarui pengguna: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $error = "Nama, Jabatan, dan Username wajib diisi.";
    }
}

// Fetch user data for form pre-population
$stmt = $conn->prepare("SELECT * FROM user WHERE id_user = ?");
$stmt->bind_param("s", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "Pengguna tidak ditemukan.";
    exit();
}
$stmt->close();

$current_jabatan_lower = strtolower(trim($user['jabatan'] ?? ''));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Data Pengguna - SCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .form-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: .5rem; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: .75rem; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn-submit { background-color: var(--warning); color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; font-weight: 600; }
        .btn-submit:hover { background-color: #f39c12; }
        .btn-back { background-color: var(--gray); color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; font-size: 1rem; }
        .error-message { color: var(--danger); margin-bottom: 1rem; padding: 10px; background: rgba(231, 76, 60, 0.1); border-radius: 5px; border: 1px solid var(--danger); }
        .help-text { font-size: 0.85rem; color: #666; margin-top: 4px; }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <main class="content">
            <div class="form-container">
                <h2 class="dashboard-title"><i class="fas fa-user-edit"></i> Ubah Data Pengguna</h2>
                
                <?php if (!empty($error)): ?>
                    <p class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>

                <form action="update_user.php?id=<?php echo urlencode($id_user); ?>" method="POST">
                    <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($user['id_user']); ?>">

                    <div class="form-group">
                        <label>ID Pengguna</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['id_user']); ?>" disabled style="background-color: #e9ecef;">
                    </div>

                    <div class="form-group">
                        <label for="nama_user">Nama Lengkap</label>
                        <input type="text" id="nama_user" name="nama_user" value="<?php echo htmlspecialchars($user['nama_user']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="jabatan">Jabatan</label>
                        <select id="jabatan" name="jabatan" required>
                            <option value="Administrator" <?php echo in_array($current_jabatan_lower, ['administrator', 'admin']) ? 'selected' : ''; ?>>Administrator</option>
                            <option value="Inventory & Purchasing Officer" <?php echo ($current_jabatan_lower === strtolower('Inventory & Purchasing Officer')) ? 'selected' : ''; ?>>Inventory & Purchasing Officer</option>
                            <option value="Finance & Billing Officer" <?php echo ($current_jabatan_lower === strtolower('Finance & Billing Officer')) ? 'selected' : ''; ?>>Finance & Billing Officer</option>
                            <option value="Kepala Divisi Produk & Pengadaan" <?php echo ($current_jabatan_lower === strtolower('Kepala Divisi Produk & Pengadaan')) ? 'selected' : ''; ?>>Kepala Divisi Produk & Pengadaan</option>
                            <option value="Direktur Operasional" <?php echo ($current_jabatan_lower === strtolower('Direktur Operasional')) ? 'selected' : ''; ?>>Direktur Operasional</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password Baru (Opsional)</label>
                        <input type="password" id="password" name="password" placeholder="Biarkan kosong jika tidak ingin mengubah password">
                        <div class="help-text">Kosongkan jika password tidak diubah.</div>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Perbarui</button>
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
