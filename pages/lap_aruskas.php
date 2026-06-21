<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Filter Tanggal
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : date('Y-m-t');

// Ambil Saldo Kas Awal (Sebelum tanggal mulai)
$q_saldo_awal = $conn->query("
    SELECT 
        SUM(CASE WHEN jd.posisi = 'Debit' THEN jd.nominal ELSE 0 END) as debit_awal,
        SUM(CASE WHEN jd.posisi = 'Kredit' THEN jd.nominal ELSE 0 END) as kredit_awal
    FROM tb_jurnal_detail jd
    JOIN tb_jurnal_umum ju ON jd.id_jurnal = ju.id
    WHERE jd.kode_akun = '111' AND ju.tanggal < '$tgl_mulai'
");
$row_awal = $q_saldo_awal->fetch_assoc();
$saldo_awal = $row_awal['debit_awal'] - $row_awal['kredit_awal'];

// Ambil Mutasi Kas Masuk (Debit Akun 111) dalam periode
$q_masuk = $conn->query("
    SELECT ju.tanggal, ju.no_referensi, ju.deskripsi, jd.nominal
    FROM tb_jurnal_detail jd
    JOIN tb_jurnal_umum ju ON jd.id_jurnal = ju.id
    WHERE jd.kode_akun = '111' AND jd.posisi = 'Debit' 
    AND ju.tanggal BETWEEN '$tgl_mulai' AND '$tgl_sampai'
    ORDER BY ju.tanggal ASC, ju.id ASC
");
$total_masuk = 0;

// Ambil Mutasi Kas Keluar (Kredit Akun 111) dalam periode
$q_keluar = $conn->query("
    SELECT ju.tanggal, ju.no_referensi, ju.deskripsi, jd.nominal
    FROM tb_jurnal_detail jd
    JOIN tb_jurnal_umum ju ON jd.id_jurnal = ju.id
    WHERE jd.kode_akun = '111' AND jd.posisi = 'Kredit' 
    AND ju.tanggal BETWEEN '$tgl_mulai' AND '$tgl_sampai'
    ORDER BY ju.tanggal ASC, ju.id ASC
");
$total_keluar = 0;
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Laporan Arus Kas (Buku Kas Umum)</h1>
    <button onclick="window.print()" class="btn btn-sm btn-info shadow-sm d-none d-sm-inline-block">
        <i class="fas fa-print fa-sm text-white-50"></i> Cetak Laporan
    </button>
</div>

<!-- Filter Box -->
<div class="card shadow mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" class="form-inline">
            <label class="mr-2">Dari Tanggal: </label>
            <input type="date" name="tgl_mulai" class="form-control mr-3" value="<?= $tgl_mulai ?>">
            
            <label class="mr-2">Sampai: </label>
            <input type="date" name="tgl_sampai" class="form-control mr-3" value="<?= $tgl_sampai ?>">
            
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Tampilkan</button>
        </form>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo UMKM Cahaya Tani Poleang" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; margin-bottom: 10px;">
                    <h4 class="font-weight-bold text-dark mb-0">UMKM CAHAYA TANI POLEANG</h4>
                    <h5 class="text-dark mb-0">LAPORAN ARUS KAS (CASH FLOW)</h5>
                    <p class="text-muted">Periode: <?= date('d/m/Y', strtotime($tgl_mulai)) ?> s.d <?= date('d/m/Y', strtotime($tgl_sampai)) ?></p>
                </div>

                <div class="alert alert-secondary d-flex justify-content-between mb-4">
                    <h5 class="mb-0 font-weight-bold text-dark">SALDO AWAL KAS (Per <?= date('d/m/Y', strtotime($tgl_mulai . ' -1 day')) ?>)</h5>
                    <h5 class="mb-0 font-weight-bold text-dark">Rp <?= number_format($saldo_awal, 0, ',', '.') ?></h5>
                </div>

                <div class="row">
                    <!-- ARUS KAS MASUK -->
                    <div class="col-md-6 mb-4">
                        <h6 class="font-weight-bold text-success border-bottom pb-2">PENERIMAAN KAS (CASH INFLOW)</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th class="text-right">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $q_masuk->fetch_assoc()): $total_masuk += $row['nominal']; ?>
                                    <tr>
                                        <td><?= date('d/m/y', strtotime($row['tanggal'])) ?></td>
                                        <td><?= $row['deskripsi'] ?></td>
                                        <td class="text-right">Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <th colspan="2" class="text-right text-dark">Total Penerimaan:</th>
                                        <th class="text-right text-success">Rp <?= number_format($total_masuk, 0, ',', '.') ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- ARUS KAS KELUAR -->
                    <div class="col-md-6 mb-4">
                        <h6 class="font-weight-bold text-danger border-bottom pb-2">PENGELUARAN KAS (CASH OUTFLOW)</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th class="text-right">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $q_keluar->fetch_assoc()): $total_keluar += $row['nominal']; ?>
                                    <tr>
                                        <td><?= date('d/m/y', strtotime($row['tanggal'])) ?></td>
                                        <td><?= $row['deskripsi'] ?></td>
                                        <td class="text-right">Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <th colspan="2" class="text-right text-dark">Total Pengeluaran:</th>
                                        <th class="text-right text-danger">Rp <?= number_format($total_keluar, 0, ',', '.') ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <?php 
                $surplus_defisit = $total_masuk - $total_keluar; 
                $saldo_akhir = $saldo_awal + $surplus_defisit;
                ?>

                <!-- KENAIKAN / PENURUNAN KAS -->
                <div class="alert <?= $surplus_defisit >= 0 ? 'alert-success' : 'alert-danger' ?> d-flex justify-content-between mt-2">
                    <h6 class="mb-0 font-weight-bold">Kenaikan (Penurunan) Kas Bersih:</h6>
                    <h6 class="mb-0 font-weight-bold">Rp <?= number_format($surplus_defisit, 0, ',', '.') ?></h6>
                </div>

                <!-- SALDO AKHIR KAS -->
                <div class="alert alert-primary d-flex justify-content-between mt-2">
                    <h4 class="mb-0 font-weight-bold text-dark">SALDO AKHIR KAS (Per <?= date('d/m/Y', strtotime($tgl_sampai)) ?>)</h4>
                    <h4 class="mb-0 font-weight-bold text-dark">Rp <?= number_format($saldo_akhir, 0, ',', '.') ?></h4>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body { font-size: 12px; background-color: #fff !important; }
    .navbar-nav, .topbar, footer { display: none !important; }
    #content-wrapper { background-color: #fff !important; }
    .card { border: none !important; box-shadow: none !important; }
    .alert-secondary, .alert-primary { background-color: transparent !important; border: 2px solid #000 !important; color: #000 !important; }
    .alert-success, .alert-danger { background-color: transparent !important; border: 1px dashed #000 !important; color: #000 !important; }
    .text-dark, .text-success, .text-danger { color: #000 !important; }
}
</style>

<?php require_once '../layouts/footer.php'; ?>
