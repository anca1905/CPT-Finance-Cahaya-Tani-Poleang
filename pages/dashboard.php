<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Dashboard summary data
$kas_query = $conn->query("
    SELECT SUM(CASE WHEN posisi = 'Debit' THEN nominal ELSE -nominal END) as total_kas 
    FROM tb_jurnal_detail jd 
    JOIN tb_jurnal_umum ju ON jd.id_jurnal = ju.id 
    WHERE jd.kode_akun = '111'
");
$kas = $kas_query->fetch_assoc()['total_kas'] ?? 0;

$penjualan_query = $conn->query("
    SELECT SUM(nominal) as total_penjualan 
    FROM tb_jurnal_detail 
    WHERE kode_akun = '411' AND posisi = 'Kredit'
");
$penjualan = $penjualan_query->fetch_assoc()['total_penjualan'] ?? 0;

$pembelian_query = $conn->query("
    SELECT SUM(nominal) as total_pembelian 
    FROM tb_jurnal_detail 
    WHERE kode_akun = '511' AND posisi = 'Debit'
");
$pembelian = $pembelian_query->fetch_assoc()['total_pembelian'] ?? 0;

// Menghitung Laba/Rugi (Sederhana)
// Laba Rugi = Pendapatan - Beban (Pembelian + Biaya Opr + Upah)
$beban_query = $conn->query("
    SELECT SUM(nominal) as total_beban 
    FROM tb_jurnal_detail 
    WHERE kode_akun IN ('511','512','513') AND posisi = 'Debit'
");
$beban = $beban_query->fetch_assoc()['total_beban'] ?? 0;

$laba_rugi = $penjualan - $beban;

?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dasbor</h1>
</div>

<div class="row">

    <!-- Saldo Kas Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="metric-title">Saldo Kas (Aset)</div>
                        <div class="metric-value">Rp <?= number_format($kas, 0, ',', '.') ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="metric-card-icon metric-primary">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Penjualan Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="metric-title">Total Penjualan</div>
                        <div class="metric-value">Rp <?= number_format($penjualan, 0, ',', '.') ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="metric-card-icon metric-success">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pembelian Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="metric-title">Total Pembelian (HPP)</div>
                        <div class="metric-value">Rp <?= number_format($pembelian, 0, ',', '.') ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="metric-card-icon metric-info">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Laba Rugi Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="metric-title">Laba / Rugi Berjalan</div>
                        <div class="metric-value">Rp <?= number_format($laba_rugi, 0, ',', '.') ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="metric-card-icon metric-warning">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Selamat Datang!</h6>
            </div>
            <div class="card-body">
                <p>Halo <b><?= $_SESSION['nama_lengkap'] ?></b>, selamat datang di Sistem Informasi Manajemen Keuangan UMKM Cahaya Tani Poleang. Anda login sebagai <b><?= $_SESSION['role'] ?></b>.</p>
                <p>Aplikasi ini mencatat seluruh transaksi keuangan harian yang berhubungan dengan pengolahan kelapa menjadi kopra putih.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../layouts/footer.php'; ?>
