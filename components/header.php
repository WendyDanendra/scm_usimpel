<div class="header">
    <div class="header-left">
        <?php 
        // Tentukan URL dashboard berdasarkan jabatan (sama seperti di sidebar)
        $dashboard_url = '/scm_usimpel/dashboard/';
        switch($jabatan) {
            case 'Inventory & Purchasing Officer':
                $dashboard_url .= 'dashboard_inventory.php';
                break;
            case 'Finance & Billing Officer':
                $dashboard_url .= 'dashboard_finance.php';
                break;
            case 'Kepala Divisi Produk & Pengadaan':
                $dashboard_url .= 'dashboard_kepala_divisi.php';
                break;
            case 'Direktur Operasional':
                $dashboard_url .= 'dashboard_direktur.php';
                break;
            default:
                $dashboard_url .= 'dashboard_inventory.php';
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
                <div class="user-role"><?php echo htmlspecialchars($jabatan); ?></div>
            </div>
        </div>
    </div>
</div>