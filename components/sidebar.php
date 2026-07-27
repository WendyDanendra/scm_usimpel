<?php
// Pastikan session sudah dimulai di halaman utama yang memanggil file ini
$jabatan_raw = trim($_SESSION['jabatan'] ?? '');
$nama_user = $_SESSION['name'] ?? 'User';
$base = defined('BASE_URL') ? BASE_URL : '/scm_usimpel/';

$jabatan_lower = strtolower($jabatan_raw);
$is_admin = in_array($jabatan_lower, ['admin', 'administrator']);
$is_inventory = ($jabatan_lower === strtolower('Inventory & Purchasing Officer'));
$is_finance = ($jabatan_lower === strtolower('Finance & Billing Officer'));
$is_kadiv = ($jabatan_lower === strtolower('Kepala Divisi Produk & Pengadaan'));
$is_direktur = ($jabatan_lower === strtolower('Direktur Operasional'));

// Tentukan URL dashboard berdasarkan jabatan
$dashboard_url = $base . 'dashboard/';
if ($is_admin) {
    $dashboard_url .= 'dashboard_admin.php';
} elseif ($is_inventory) {
    $dashboard_url .= 'dashboard_inventory.php';
} elseif ($is_finance) {
    $dashboard_url .= 'dashboard_finance.php';
} elseif ($is_kadiv) {
    $dashboard_url .= 'dashboard_kepala_divisi.php';
} elseif ($is_direktur) {
    $dashboard_url .= 'dashboard_direktur.php';
} else {
    $dashboard_url .= 'dashboard_admin.php';
}

// Fungsi untuk membuat inisial dari nama (dengan pengecekan untuk mencegah redeclaration)
if (!function_exists('getInitials')) {
    function getInitials($name) {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $w) {
            $initials .= mb_substr($w, 0, 1);
        }
        return strtoupper($initials);
    }
}
?>
<button class="toggle-sidebar">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar">
    <div class="logo-container">
        <a href="<?= $dashboard_url ?>" style="text-decoration: none; display: block;" title="Kembali ke Dashboard">
            <img src="<?= $base ?>assets/logo-usimpel.png" alt="Logo PT. Usimpel Inovasi" class="company-logo">
        </a>
    </div>

    
    <div class="sidebar-menu">

        <a href="<?= $dashboard_url ?>" class="menu-item">
            <i class="fas fa-chart-line"></i><span class="menu-text">Dashboard Overview</span>
        </a>

        <?php // Menu untuk Inventory & Purchasing Officer
        if ($is_inventory) : ?>
            <div class="menu-item" data-menu="data-master">
                <i class="fas fa-database"></i><span class="menu-text">Data Master</span><i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="submenu" id="data-master">
                <a href="<?= $base ?>supplier/supplier.php" class="submenu-item"><i class="fas fa-building"></i><span>Supplier</span></a>
                <a href="<?= $base ?>barang/barang.php" class="submenu-item"><i class="fas fa-box"></i><span>Barang</span></a>
            </div>

            <a href="<?= $base ?>rop/perhitungan_rop.php" class="menu-item"><i class="fas fa-calculator"></i><span class="menu-text">Perhitungan ROP</span></a>
            <a href="<?= $base ?>pengajuan_barang/pengajuan_barang.php" class="menu-item"><i class="fas fa-file-signature"></i><span class="menu-text">Pengajuan Barang</span></a>
            <a href="<?= $base ?>persetujuan/view_persetujuan.php" class="menu-item"><i class="fas fa-eye"></i><span class="menu-text">Lihat Persetujuan</span></a>
            <a href="<?= $base ?>pengadaan_barang/pengadaan_barang.php" class="menu-item"><i class="fas fa-truck-loading"></i><span class="menu-text">Pengadaan Barang</span></a>
            <a href="<?= $base ?>stok_barang/pengelolaan_stok.php" class="menu-item"><i class="fas fa-warehouse"></i><span class="menu-text">Pengelolaan Stok</span></a>
        <?php endif; ?>

        <?php // Menu untuk Kepala Divisi Produk & Pengadaan
        if ($is_kadiv) : ?>
            <a href="<?= $base ?>persetujuan/persetujuan_pengajuan.php" class="menu-item"><i class="fas fa-check-double"></i><span class="menu-text">Persetujuan Pengajuan</span></a>
        <?php endif; ?>

        <?php // Menu untuk Finance & Billing Officer
        if ($is_finance) : ?>
            <a href="<?= $base ?>pembayaran/pembayaran.php" class="menu-item"><i class="fas fa-money-bill-wave"></i><span class="menu-text">Proses Pembayaran</span></a>
        <?php endif; ?>

        <?php // Menu Kelola Pengguna untuk Admin dan semua role
        if ($is_admin || $is_inventory || $is_finance || $is_kadiv || $is_direktur || !empty($jabatan_raw)) : ?>
            <a href="<?= $base ?>user/user.php" class="menu-item"><i class="fas fa-users-cog"></i><span class="menu-text">Kelola Pengguna</span></a>
        <?php endif; ?>

        <?php // Menu Laporan untuk role selain Admin
        if ($is_inventory || $is_finance || $is_kadiv || $is_direktur) : ?>
            <a href="<?= $base ?>laporan/laporan_scm.php" class="menu-item"><i class="fas fa-file-contract"></i><span class="menu-text">Laporan SCM</span></a>
        <?php endif; ?>

        <a href="<?= $base ?>logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i><span class="menu-text">Logout</span>
        </a>
    </div>
</div>