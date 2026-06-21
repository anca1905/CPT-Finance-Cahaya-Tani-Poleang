<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Filter Tanggal
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : date('Y-m-t');

// Fungsi untuk mengambil saldo berdasarkan tipe akun dan tanggal
function getSaldoAkunByTipe($conn, $tipe, $tgl_mulai, $tgl_sampai) {
    $q = $conn->query("
        SELECT a.kode_akun, a.nama_akun, a.saldo_normal,
               SUM(CASE WHEN jd.posisi = 'Debit' THEN jd.nominal ELSE 0 END) as total_debit,
               SUM(CASE WHEN jd.posisi = 'Kredit' THEN jd.nominal ELSE 0 END) as total_kredit
        FROM tb_akun a
        LEFT JOIN tb_jurnal_detail jd ON a.kode_akun = jd.kode_akun
        LEFT JOIN tb_jurnal_umum ju ON jd.id_jurnal = ju.id AND ju.tanggal BETWEEN '$tgl_mulai' AND '$tgl_sampai'
        WHERE a.tipe = '$tipe'
        GROUP BY a.kode_akun
    ");
    $result = [];
    while($row = $q->fetch_assoc()) {
        $net = 0;
        if ($row['saldo_normal'] == 'Debit') {
            $net = $row['total_debit'] - $row['total_kredit'];
        } else {
            $net = $row['total_kredit'] - $row['total_debit'];
        }
        if ($net != 0) {
            $row['saldo_akhir'] = $net;
            $result[] = $row;
        }
    }
    return $result;
}

$pendapatan = getSaldoAkunByTipe($conn, 'Pendapatan', $tgl_mulai, $tgl_sampai);
$hpp = getSaldoAkunByTipe($conn, 'HPP', $tgl_mulai, $tgl_sampai);
$beban = getSaldoAkunByTipe($conn, 'Beban', $tgl_mulai, $tgl_sampai);

// Hitung Total
$total_pendapatan = array_sum(array_column($pendapatan, 'saldo_akhir'));
$total_hpp = array_sum(array_column($hpp, 'saldo_akhir'));
$laba_kotor = $total_pendapatan - $total_hpp;

$total_beban = array_sum(array_column($beban, 'saldo_akhir'));
$laba_bersih = $laba_kotor - $total_beban;
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Laporan Laba Rugi</h1>
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
    <div class="col-lg-10">
        <div class="card shadow mb-4">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo UMKM Cahaya Tani Poleang" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; margin-bottom: 10px;">
                    <h4 class="font-weight-bold text-dark mb-0">UMKM CAHAYA TANI POLEANG</h4>
                    <h5 class="text-dark mb-0">LAPORAN LABA RUGI</h5>
                    <p class="text-muted">Periode: <?= date('d/m/Y', strtotime($tgl_mulai)) ?> s.d <?= date('d/m/Y', strtotime($tgl_sampai)) ?></p>
                </div>

                <table class="table table-borderless table-sm">
                    <tbody>
                        <!-- PENDAPATAN -->
                        <tr>
                            <td colspan="2"><h6 class="font-weight-bold text-dark mt-3">PENDAPATAN USAHA</h6></td>
                        </tr>
                        <?php foreach($pendapatan as $p): ?>
                        <tr>
                            <td class="pl-4"><?= $p['nama_akun'] ?></td>
                            <td class="text-right">Rp <?= number_format($p['saldo_akhir'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="border-top">
                            <td class="pl-4 font-weight-bold text-dark">Total Pendapatan</td>
                            <td class="text-right font-weight-bold text-dark">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                        </tr>

                        <!-- HPP -->
                        <tr>
                            <td colspan="2"><h6 class="font-weight-bold text-dark mt-4">HARGA POKOK PENJUALAN (HPP)</h6></td>
                        </tr>
                        <?php foreach($hpp as $h): ?>
                        <tr>
                            <td class="pl-4"><?= $h['nama_akun'] ?></td>
                            <td class="text-right">Rp <?= number_format($h['saldo_akhir'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="border-top">
                            <td class="pl-4 font-weight-bold text-dark">Total Harga Pokok Penjualan</td>
                            <td class="text-right font-weight-bold text-danger">(Rp <?= number_format($total_hpp, 0, ',', '.') ?>)</td>
                        </tr>

                        <!-- LABA KOTOR -->
                        <tr class="border-top border-bottom bg-light">
                            <td class="font-weight-bold text-dark py-2"><h6 class="m-0 font-weight-bold">LABA KOTOR</h6></td>
                            <td class="text-right font-weight-bold text-dark py-2"><h6 class="m-0 font-weight-bold">Rp <?= number_format($laba_kotor, 0, ',', '.') ?></h6></td>
                        </tr>

                        <!-- BEBAN OPERASIONAL -->
                        <tr>
                            <td colspan="2"><h6 class="font-weight-bold text-dark mt-4">BEBAN OPERASIONAL</h6></td>
                        </tr>
                        <?php foreach($beban as $b): ?>
                        <tr>
                            <td class="pl-4"><?= $b['nama_akun'] ?></td>
                            <td class="text-right">Rp <?= number_format($b['saldo_akhir'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="border-top">
                            <td class="pl-4 font-weight-bold text-dark">Total Beban Operasional</td>
                            <td class="text-right font-weight-bold text-danger">(Rp <?= number_format($total_beban, 0, ',', '.') ?>)</td>
                        </tr>

                        <!-- LABA BERSIH -->
                        <tr class="border-top border-bottom <?= $laba_bersih >= 0 ? 'bg-success' : 'bg-danger' ?> text-white mt-4">
                            <td class="font-weight-bold py-3"><h5 class="m-0 font-weight-bold">LABA (RUGI) BERSIH</h5></td>
                            <td class="text-right font-weight-bold py-3"><h5 class="m-0 font-weight-bold">Rp <?= number_format($laba_bersih, 0, ',', '.') ?></h5></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body { font-size: 14px; background-color: #fff !important; }
    .navbar-nav, .topbar, footer { display: none !important; }
    #content-wrapper { background-color: #fff !important; }
    .card { border: none !important; box-shadow: none !important; }
    .table-borderless td, .table-borderless th { border: none !important; }
    .border-top { border-top: 1px solid #000 !important; }
    .border-bottom { border-bottom: 1px solid #000 !important; }
    .bg-light { background-color: transparent !important; }
    .bg-success, .bg-danger { background-color: transparent !important; color: #000 !important; border-top: 3px double #000 !important; border-bottom: 3px double #000 !important; }
    .text-white { color: #000 !important; }
}
</style>

<?php require_once '../layouts/footer.php'; ?>
