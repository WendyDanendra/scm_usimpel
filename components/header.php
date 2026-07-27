<div class="header">
    <div class="header-left">
        <?php 
        $base = defined('BASE_URL') ? BASE_URL : '/scm_usimpel/';
        $jabatan_raw = trim($_SESSION['jabatan'] ?? '');
        $nama_user = $_SESSION['name'] ?? 'User';
        $jabatan_lower = strtolower($jabatan_raw);

        $dashboard_url = $base . 'dashboard/';
        if (in_array($jabatan_lower, ['admin', 'administrator'])) {
            $dashboard_url .= 'dashboard_admin.php';
        } elseif ($jabatan_lower === strtolower('Inventory & Purchasing Officer')) {
            $dashboard_url .= 'dashboard_inventory.php';
        } elseif ($jabatan_lower === strtolower('Finance & Billing Officer')) {
            $dashboard_url .= 'dashboard_finance.php';
        } elseif ($jabatan_lower === strtolower('Kepala Divisi Produk & Pengadaan')) {
            $dashboard_url .= 'dashboard_kepala_divisi.php';
        } elseif ($jabatan_lower === strtolower('Direktur Operasional')) {
            $dashboard_url .= 'dashboard_direktur.php';
        } else {
            $dashboard_url .= 'dashboard_admin.php';
        }
        ?>
        <a href="<?= $dashboard_url ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 10px;" title="Kembali ke Dashboard">
            <i class="fas fa-home" style="color: var(--primary); font-size: 1.2em; transition: color 0.3s ease;"></i>
            <h2 style="margin: 0;">Dashboard Manajemen SCM</h2>
        </a>
    </div>
    <div class="header-right">
        <div class="user-profile">
            <div class="user-avatar"><?php echo getInitials($nama_user); ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($nama_user); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($jabatan_raw); ?></div>
            </div>
        </div>
    </div>
</div>