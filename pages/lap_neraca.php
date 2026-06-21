<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Filter Tanggal (As of date)
$tgl_as_of = isset($_GET['tgl_as_of']) ? $_GET['tgl_as_of'] : date('Y-m-t');

// Fungsi untuk mengambil saldo berdasarkan tipe akun sampai tanggal tertentu (Kumulatif)
function getSaldoAkunKumulatif($conn, $tipe, $tgl_as_of) {
    $q = $conn->query("
        SELECT a.kode_akun, a.nama_akun, a.saldo_normal,
               SUM(CASE WHEN jd.posisi = 'Debit' THEN jd.nominal ELSE 0 END) as total_debit,
               SUM(CASE WHEN jd.posisi = 'Kredit' THEN jd.nominal ELSE 0 END) as total_kredit
        FROM tb_akun a
        LEFT JOIN tb_jurnal_detail jd ON a.kode_akun = jd.kode_akun
        LEFT JOIN tb_jurnal_umum ju ON jd.id_jurnal = ju.id AND ju.tanggal <= '$tgl_as_of'
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
        $row['saldo_akhir'] = $net;
        $result[] = $row;
    }
    return $result;
}

$aset = getSaldoAkunKumulatif($conn, 'Aset', $tgl_as_of);
$kewajiban = getSaldoAkunKumulatif($conn, 'Kewajiban', $tgl_as_of);
$ekuitas = getSaldoAkunKumulatif($conn, 'Ekuitas', $tgl_as_of);

// Hitung Laba Ditahan / Laba Berjalan (Pendapatan - HPP - Beban sampai tanggal tersebut)
$pendapatan = getSaldoAkunKumulatif($conn, 'Pendapatan', $tgl_as_of);
$hpp = getSaldoAkunKumulatif($conn, 'HPP', $tgl_as_of);
$beban = getSaldoAkunKumulatif($conn, 'Beban', $tgl_as_of);

$tot_pendapatan = array_sum(array_column($pendapatan, 'saldo_akhir'));
$tot_hpp = array_sum(array_column($hpp, 'saldo_akhir'));
$tot_beban = array_sum(array_column($beban, 'saldo_akhir'));

$laba_berjalan = $tot_pendapatan - $tot_hpp - $tot_beban;

// Hitung Total Neraca
$total_aset = array_sum(array_column($aset, 'saldo_akhir'));
$total_kewajiban = array_sum(array_column($kewajiban, 'saldo_akhir'));
$total_ekuitas_awal = array_sum(array_column($ekuitas, 'saldo_akhir'));

$total_ekuitas = $total_ekuitas_awal + $laba_berjalan;
$total_pasiva = $total_kewajiban + $total_ekuitas;

$is_balance = ($total_aset == $total_pasiva);
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Laporan Neraca (Balance Sheet)</h1>
    <button onclick="window.print()" class="btn btn-sm btn-info shadow-sm d-none d-sm-inline-block">
        <i class="fas fa-print fa-sm text-white-50"></i> Cetak Laporan
    </button>
</div>

<!-- Filter Box -->
<div class="card shadow mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" class="form-inline">
            <label class="mr-2">Per Tanggal (As of): </label>
            <input type="date" name="tgl_as_of" class="form-control mr-3" value="<?= $tgl_as_of ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Tampilkan</button>
        </form>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-body p-5">
                <div class="text-center mb-5">
                    <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo UMKM Cahaya Tani Poleang" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; margin-bottom: 10px;">
                    <h4 class="font-weight-bold text-dark mb-0">UMKM CAHAYA TANI POLEANG</h4>
                    <h5 class="text-dark mb-0">LAPORAN NERACA</h5>
                    <p class="text-muted">Per Tanggal: <?= date('d/m/Y', strtotime($tgl_as_of)) ?></p>
                </div>

                <div class="row">
                    <!-- AKTIVA (ASET) -->
                    <div class="col-md-6 border-right">
                        <h5 class="font-weight-bold text-dark border-bottom pb-2">AKTIVA (ASET)</h5>
                        <table class="table table-borderless table-sm mt-3">
                            <tbody>
                                <?php foreach($aset as $a): ?>
                                <tr>
                                    <td><?= $a['nama_akun'] ?></td>
                                    <td class="text-right">Rp <?= number_format($a['saldo_akhir'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PASIVA (KEWAJIBAN & EKUITAS) -->
                    <div class="col-md-6">
                        <h5 class="font-weight-bold text-dark border-bottom pb-2">PASIVA (KEWAJIBAN & EKUITAS)</h5>
                        
                        <!-- Kewajiban -->
                        <h6 class="font-weight-bold mt-3">Kewajiban</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                <?php foreach($kewajiban as $k): ?>
                                <tr>
                                    <td class="pl-3"><?= $k['nama_akun'] ?></td>
                                    <td class="text-right">Rp <?= number_format($k['saldo_akhir'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Ekuitas -->
                        <h6 class="font-weight-bold mt-3">Ekuitas</h6>
                        <table class="table table-borderless table-sm">
                            <tbody>
                                <?php foreach($ekuitas as $e): ?>
                                <tr>
                                    <td class="pl-3"><?= $e['nama_akun'] ?></td>
                                    <td class="text-right">Rp <?= number_format($e['saldo_akhir'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td class="pl-3 font-italic">Laba (Rugi) Berjalan</td>
                                    <td class="text-right font-italic">Rp <?= number_format($laba_berjalan, 0, ',', '.') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TOTAL ROW -->
                <div class="row mt-4 border-top border-bottom py-3">
                    <div class="col-md-6 text-dark">
                        <div class="d-flex justify-content-between">
                            <h5 class="font-weight-bold mb-0">TOTAL AKTIVA</h5>
                            <h5 class="font-weight-bold mb-0">Rp <?= number_format($total_aset, 0, ',', '.') ?></h5>
                        </div>
                    </div>
                    <div class="col-md-6 text-dark border-left">
                        <div class="d-flex justify-content-between">
                            <h5 class="font-weight-bold mb-0">TOTAL PASIVA</h5>
                            <h5 class="font-weight-bold mb-0">Rp <?= number_format($total_pasiva, 0, ',', '.') ?></h5>
                        </div>
                    </div>
                </div>

                <!-- Validation Status -->
                <div class="mt-4 text-center d-print-none">
                    <?php if($is_balance): ?>
                        <div class="alert alert-success d-inline-block px-5">
                            <i class="fas fa-check-circle fa-2x align-middle mr-2"></i> 
                            <span class="h5 align-middle font-weight-bold">BALANCE (SEIMBANG)</span>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger d-inline-block px-5">
                            <i class="fas fa-exclamation-triangle fa-2x align-middle mr-2"></i> 
                            <span class="h5 align-middle font-weight-bold">TIDAK BALANCE! Terdapat selisih Rp <?= number_format(abs($total_aset - $total_pasiva), 0, ',', '.') ?></span>
                        </div>
                    <?php endif; ?>
                </div>

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
    .table-borderless td, .table-borderless th { border: none !important; padding: .25rem !important; }
    .border-top { border-top: 2px solid #000 !important; }
    .border-bottom { border-bottom: 2px solid #000 !important; }
    .text-dark { color: #000 !important; }
}
</style>

<?php require_once '../layouts/footer.php'; ?>
