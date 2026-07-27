<?php
// Pastikan session sudah dimulai di halaman utama yang memanggil file ini
$jabatan = $_SESSION['jabatan'] ?? '';
$nama_user = $_SESSION['name'] ?? 'User';

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
        <?php 
        // Tentukan URL dashboard berdasarkan jabatan
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
        <a href="<?= $dashboard_url ?>" style="text-decoration: none; display: block;" title="Kembali ke Dashboard">
            <img src="/scm_usimpel/assets/logo-usimpel.png" alt="Logo PT. Usimpel Inovasi" class="company-logo">
        </a>
    </div>

    
    <div class="sidebar-menu">

        <?php // Menu untuk Inventory & Purchasing Officer
        if ($jabatan == 'Inventory & Purchasing Officer') : ?>
            <div class="menu-item" data-menu="data-master">
                <i class="fas fa-database"></i><span class="menu-text">Data Master</span><i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="submenu" id="data-master">
                <a href="/scm_usimpel/supplier/supplier.php" class="submenu-item"><i class="fas fa-building"></i><span>Supplier</span></a>
                <a href="/scm_usimpel/barang/barang.php" class="submenu-item"><i class="fas fa-box"></i><span>Barang</span></a>
            </div>

            <a href="/scm_usimpel/rop/perhitungan_rop.php" class="menu-item"><i class="fas fa-calculator"></i><span class="menu-text">Perhitungan ROP</span></a>
            <a href="/scm_usimpel/pengajuan_barang/pengajuan_barang.php" class="menu-item"><i class="fas fa-file-signature"></i><span class="menu-text">Pengajuan Barang</span></a>
            <a href="/scm_usimpel/persetujuan/view_persetujuan.php" class="menu-item"><i class="fas fa-eye"></i><span class="menu-text">Lihat Persetujuan</span></a>
            <a href="/scm_usimpel/pengadaan_barang/pengadaan_barang.php" class="menu-item"><i class="fas fa-truck-loading"></i><span class="menu-text">Pengadaan Barang</span></a>
            <a href="/scm_usimpel/stok_barang/pengelolaan_stok.php" class="menu-item"><i class="fas fa-warehouse"></i><span class="menu-text">Pengelolaan Stok</span></a>
        <?php endif; ?>

        <?php // Menu untuk Inventory & Kepala Divisi
        if ($jabatan == 'Kepala Divisi Produk & Pengadaan') : ?>
            <a href="/scm_usimpel/persetujuan/persetujuan_pengajuan.php" class="menu-item"><i class="fas fa-check-double"></i><span class="menu-text">Persetujuan Pengajuan</span></a>
        <?php endif; ?>

        <?php // --- MENU BARU UNTUK FINANCE ---
        if ($jabatan == 'Finance & Billing Officer') : ?>
            <a href="/scm_usimpel/pembayaran/pembayaran.php" class="menu-item"><i class="fas fa-money-bill-wave"></i><span class="menu-text">Proses Pembayaran</span></a>
        <?php endif; ?>

        <?php // Menu Laporan untuk semua
        if (in_array($jabatan, ['Inventory & Purchasing Officer', 'Finance & Billing Officer', 'Kepala Divisi Produk & Pengadaan', 'Direktur Operasional'])) : ?>
             <a href="/scm_usimpel/laporan/laporan_scm.php" class="menu-item"><i class="fas fa-file-contract"></i><span class="menu-text">Laporan SCM</span></a>
        <?php endif; ?>

        <a href="/scm_usimpel/logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i><span class="menu-text">Logout</span>
        </a>
    </div>
</div>