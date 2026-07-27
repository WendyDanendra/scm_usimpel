<?php
require_once 'config.php';

echo "<pre style='font-family: monospace; line-height: 1.5;'>";
echo "<h1>Memulai Proses Seeding Database...</h1>";

// Matikan sementara foreign key checks untuk menghindari error urutan
$conn->query("SET FOREIGN_key_checks=0");
echo "✅ Foreign key checks dinonaktifkan.<br>";

// Daftar tabel untuk dikosongkan (urutan dari anak ke induk)
$tables = [
    'log_stok', 'pembayaran', 'persetujuan', 'pengadaan_barang', 
    'pengajuan_barang', 'stok_barang', 'laporan_scm', 'barang', 'supplier', 'user'
];

foreach ($tables as $table) {
    $conn->query("TRUNCATE TABLE `$table`");
    echo "🧹 Tabel `$table` berhasil dikosongkan.<br>";
}
echo "--------------------------------------------------<br>";

// 1. Seeding Tabel User
echo "👤 **Seeding User...**<br>";
$users = [
    ['USER-00001', 'Budi Santoso', 'Inventory & Purchasing Officer', 'inventory', 'password'],
    ['USER-00002', 'Siti Rahayu', 'Finance & Billing Officer', 'finance', 'password'],
    ['USER-00003', 'Agus Wijaya', 'Kepala Divisi Produk & Pengadaan', 'kadiv', 'password'],
    ['USER-00004', 'Rina Marlina', 'Direktur Operasional', 'direktur', 'password']
];
$stmt_user = $conn->prepare("INSERT INTO user (id_user, nama_user, jabatan, username, password) VALUES (?, ?, ?, ?, ?)");
foreach ($users as $user) {
    $stmt_user->bind_param("sssss", $user[0], $user[1], $user[2], $user[3], $user[4]); // Password di-hash
    $stmt_user->execute();
    echo "   -> User '$user[1]' dibuat.<br>";
}
echo "--------------------------------------------------<br>";

// 2. Seeding Tabel Supplier
echo "🚚 **Seeding Supplier...**<br>";
$suppliers = [
    ['SUPP-00001', 'PT. TPLink Indonesia', 'Jl. Gatot Subroto No. 12, Jakarta', '021-555-001', 'sales@tplink.co.id'],
    ['SUPP-00002', 'PT. Virtus Tech Indonesia', 'Jl. M.H. Thamrin No. 8, Jakarta', '021-555-002', 'order@virtus.co.id'],
    ['SUPP-00003', 'PT. Kreasi Utama Mandiri', 'Jl. Sudirman Kav. 5, Jakarta', '021-555-003', 'contact@kum.co.id']
];
$stmt_supplier = $conn->prepare("INSERT INTO supplier (id_supplier, nama_supplier, alamat, kontak, email) VALUES (?, ?, ?, ?, ?)");
foreach ($suppliers as $supplier) {
    $stmt_supplier->bind_param("sssss", $supplier[0], $supplier[1], $supplier[2], $supplier[3], $supplier[4]);
    $stmt_supplier->execute();
    echo "   -> Supplier '$supplier[1]' dibuat.<br>";
}
echo "--------------------------------------------------<br>";

// 3. Seeding Tabel Barang
echo "📦 **Seeding Barang...**<br>";
$barangs = [
    ['BRNG-00001', 'TP-Link Archer AX23', 'TP-Link', 'Pcs', '850000', '7', 'SUPP-00001'],
    ['BRNG-00002', 'Huawei AX3 Dual-Core', 'Huawei', 'Pcs', '750000', '10', 'SUPP-00002'],
    ['BRNG-00003', 'Tenda Nova MW6 Mesh', 'Tenda', 'Set', '1200000', '8', 'SUPP-00003'],
    ['BRNG-00004', 'TP-Link Deco X20 Mesh', 'TP-Link', 'Set', '1500000', '9', 'SUPP-00001'],
    ['BRNG-00005', 'Huawei Wifi Mesh 3', 'Huawei', 'Set', '1350000', '10', 'SUPP-00002']
];
$stmt_barang = $conn->prepare("INSERT INTO barang (id_barang, produk, merek, satuan, harga, lead_time, id_supplier) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($barangs as $barang) {
    $stmt_barang->bind_param("ssssiss", $barang[0], $barang[1], $barang[2], $barang[3], $barang[4], $barang[5], $barang[6]);
    $stmt_barang->execute();
    echo "   -> Barang '$barang[1]' dibuat.<br>";
}
echo "--------------------------------------------------<br>";

// 4. Seeding Tabel Stok Barang
echo "📊 **Seeding Stok Barang...**<br>";
$stoks = [
    ['STOK-00001', 'BRNG-00001', '120', '36', '6', 'Aman', '0.5'],
    ['STOK-00002', 'BRNG-00002', '50', '48', '8', 'Mendekati ROP', '0.8'],
    ['STOK-00003', 'BRNG-00003', '30', '34', '6', 'Dibawah ROP', '0.7'],
    ['STOK-00004', 'BRNG-00004', '85', '40', '8', 'Aman', '0.6'],
    ['STOK-00005', 'BRNG-00005', '42', '45', '9', 'Mendekati ROP', '0.75']
];
$stmt_stok = $conn->prepare("INSERT INTO stok_barang (id_stok, id_barang, stok_tersedia, rop, safety_stock, status_stok, permintaan_harian) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($stoks as $stok) {
    $stmt_stok->bind_param("ssiiisd", $stok[0], $stok[1], $stok[2], $stok[3], $stok[4], $stok[5], $stok[6]);
    $stmt_stok->execute();
    echo "   -> Stok untuk '$stok[1]' diinisialisasi.<br>";
}
echo "--------------------------------------------------<br>";

// 5. Seeding Alur Pengadaan
echo "🔄 **Seeding Alur Pengajuan, Persetujuan, Pengadaan & Pembayaran...**<br>";
$pengadaans = [
    ['PNJN-00015', '2025-04-01', 'BRNG-00001', '30', 'SUPP-00001', 'Diproses', 'PRTJ-00001', '2025-04-02', 'PGDN-00015', '2025-04-05', 'BYRN-00025', '2025-04-10', '25500000', 'Lunas'],
    ['PNJN-00017', '2025-04-15', 'BRNG-00003', '20', 'SUPP-00003', 'Diproses', 'PRTJ-00002', '2025-04-16', 'PGDN-00017', '2025-04-18', 'BYRN-00026', '2025-04-20', '24000000', 'Lunas'],
    ['PNJN-00016', '2025-04-10', 'BRNG-00002', '25', 'SUPP-00002', 'Diproses', 'PRTJ-00003', '2025-04-11', 'PGDN-00016', '2025-04-12', null, null, null, null],
    ['PNJN-00018', '2025-04-20', 'BRNG-00004', '15', 'SUPP-00001', 'Diajukan', null, null, null, null, null, null, null, null] // Contoh yang masih 'Diajukan'
];
$stmt_pengajuan = $conn->prepare("INSERT INTO pengajuan_barang (id_pengajuan, tanggal_pengajuan, id_barang, jumlah_diajukan, id_supplier, status_pengajuan) VALUES (?, ?, ?, ?, ?, ?)");
$stmt_persetujuan = $conn->prepare("INSERT INTO persetujuan (id_persetujuan, id_pengajuan, tanggal_persetujuan, keputusan, catatan, id_user_penyetuju) VALUES (?, ?, ?, 'Disetujui', 'OK', 'USER-00003')");
$stmt_pengadaan = $conn->prepare("INSERT INTO pengadaan_barang (id_pengadaan, id_pengajuan, tanggal_pengadaan, file_invoice, status_pengadaan) VALUES (?, ?, ?, 'dummy_invoice.pdf', 'Menunggu Pembayaran')");
$stmt_pembayaran = $conn->prepare("INSERT INTO pembayaran (id_pembayaran, id_pengadaan, tanggal_pembayaran, nominal, file_bukti_transfer, file_kuitansi, status_pembayaran) VALUES (?, ?, ?, ?, 'dummy_transfer.pdf', 'dummy_receipt.pdf', ?)");

foreach ($pengadaans as $p) {
    // Insert Pengajuan
    $stmt_pengajuan->bind_param("ssisss", $p[0], $p[1], $p[2], $p[3], $p[4], $p[5]);
    $stmt_pengajuan->execute();
    echo "   -> Pengajuan '$p[0]' dibuat.<br>";

    if ($p[5] == 'Diproses') { // Jika statusnya 'Diproses', buat data persetujuan & pengadaan
        // Insert Persetujuan
        $stmt_persetujuan->bind_param("sss", $p[6], $p[0], $p[7]);
        $stmt_persetujuan->execute();
        echo "      -> Persetujuan '$p[6]' dicatat.<br>";
        
        // Insert Pengadaan
        $stmt_pengadaan->bind_param("sss", $p[8], $p[0], $p[9]);
        $stmt_pengadaan->execute();
        echo "         -> Pengadaan '$p[8]' dicatat.<br>";

        // Insert Pembayaran (jika ada)
        if ($p[10] !== null) {
            $stmt_pembayaran->bind_param("sssis", $p[10], $p[8], $p[11], $p[12], $p[13]);
            $stmt_pembayaran->execute();
            echo "            -> Pembayaran '$p[10]' dicatat sebagai Lunas.<br>";
        }
    }
}
echo "--------------------------------------------------<br>";

// 6. Seeding Log Stok
echo "📜 **Seeding Log Stok...**<br>";
$logs = [
    ['LOGS-00001', 'BRNG-00001', '2025-04-11', 'masuk', '30', 'Penerimaan barang dari pengadaan ID: PGDN-00015', 'PGDN-00015'],
    ['LOGS-00002', 'BRNG-00003', '2025-04-21', 'masuk', '20', 'Penerimaan barang dari pengadaan ID: PGDN-00017', 'PGDN-00017'],
    ['LOGS-00003', 'BRNG-00001', '2025-04-15', 'keluar', '5', 'Untuk Proyek A', null],
    ['LOGS-00004', 'BRNG-00001', '2025-04-25', 'keluar', '10', 'Untuk Proyek B', null],
    ['LOGS-00005', 'BRNG-00002', '2025-04-18', 'keluar', '14', 'Untuk Kebutuhan Internal', null],
    ['LOGS-00006', 'BRNG-00002', '2025-04-28', 'keluar', '10', 'Untuk Proyek C', null],
    ['LOGS-00007', 'BRNG-00003', '2025-04-22', 'keluar', '21', 'Untuk Tim Lapangan', null],
];
$stmt_log = $conn->prepare("INSERT INTO log_stok (id_log, id_barang, tanggal, jenis_log, jumlah, keterangan, id_pengadaan) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($logs as $log) {
    $stmt_log->bind_param("ssssiss", $log[0], $log[1], $log[2], $log[3], $log[4], $log[5], $log[6]);
    $stmt_log->execute();
    echo "   -> Log Stok '$log[0]' ('$log[3]') dibuat.<br>";
}
echo "--------------------------------------------------<br>";


// Nyalakan kembali foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=1");
echo "✅ Foreign key checks diaktifkan kembali.<br><br>";
echo "<h2>🎉 Proses Seeding Selesai! 🎉</h2>";
echo "</pre>";

$conn->close();
?>