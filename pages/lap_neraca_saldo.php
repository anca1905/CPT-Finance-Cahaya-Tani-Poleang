<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

$tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : date('Y-m-t');

// Ambil Semua Akun
$q_akun = $conn->query("SELECT * FROM tb_akun ORDER BY kode_akun ASC");
$neraca_saldo = [];

$total_debit_all = 0;
$total_kredit_all = 0;

while ($akun = $q_akun->fetch_assoc()) {
    $kode_akun = $akun['kode_akun'];
    
    // Ambil Total Transaksi s/d Tanggal
    $q_saldo = $conn->query("
        SELECT 
            SUM(CASE WHEN jd.posisi = 'Debit' THEN jd.nominal ELSE 0 END) as tot_debit,
            SUM(CASE WHEN jd.posisi = 'Kredit' THEN jd.nominal ELSE 0 END) as tot_kredit
        FROM tb_jurnal_detail jd
        JOIN tb_jurnal_umum ju ON jd.id_jurnal = ju.id
        WHERE jd.kode_akun = '$kode_akun' AND ju.tanggal <= '$tgl_sampai'
    ");
    
    $row_s = $q_saldo->fetch_assoc();
    $tot_d = $row_s['tot_debit'] ?? 0;
    $tot_k = $row_s['tot_kredit'] ?? 0;
    
    // Jika tidak ada transaksi sama sekali, abaikan (atau bisa tetap ditampilkan 0)
    if ($tot_d == 0 && $tot_k == 0) {
        continue;
    }
    
    $saldo_akhir_debit = 0;
    $saldo_akhir_kredit = 0;
    
    if ($akun['saldo_normal'] == 'Debit') {
        $net = $tot_d - $tot_k;
        if ($net > 0) {
            $saldo_akhir_debit = $net;
        } else {
            $saldo_akhir_kredit = abs($net);
        }
    } else {
        $net = $tot_k - $tot_d;
        if ($net > 0) {
            $saldo_akhir_kredit = $net;
        } else {
            $saldo_akhir_debit = abs($net);
        }
    }
    
    $total_debit_all += $saldo_akhir_debit;
    $total_kredit_all += $saldo_akhir_kredit;
    
    $akun['saldo_debit'] = $saldo_akhir_debit;
    $akun['saldo_kredit'] = $saldo_akhir_kredit;
    $neraca_saldo[] = $akun;
}

?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Neraca Saldo (Trial Balance)</h1>
    <button onclick="window.print()" class="btn btn-sm btn-success shadow-sm d-print-none">
        <i class="fas fa-print fa-sm text-white-50"></i> Cetak Laporan
    </button>
</div>

<div class="card shadow mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" class="form-inline">
            <label class="mr-2">Per Tanggal:</label>
            <input type="date" class="form-control mr-3" name="tgl_sampai" value="<?= $tgl_sampai ?>" required>
            
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
        </form>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Laporan Neraca Saldo per <?= date('d/m/Y', strtotime($tgl_sampai)) ?></h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                <thead class="thead-light text-center">
                    <tr>
                        <th width="120">Kode Akun</th>
                        <th>Nama Akun</th>
                        <th width="200">Debit (Rp)</th>
                        <th width="200">Kredit (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($neraca_saldo) > 0): ?>
                        <?php foreach($neraca_saldo as $row): ?>
                        <tr>
                            <td class="text-center"><?= $row['kode_akun'] ?></td>
                            <td><?= htmlspecialchars($row['nama_akun']) ?></td>
                            <td class="text-right"><?= $row['saldo_debit'] > 0 ? number_format($row['saldo_debit'], 0, ',', '.') : '-' ?></td>
                            <td class="text-right"><?= $row['saldo_kredit'] > 0 ? number_format($row['saldo_kredit'], 0, ',', '.') : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">Belum ada data transaksi s/d tanggal tersebut.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-light font-weight-bold">
                        <td colspan="2" class="text-right text-uppercase">Total Keseluruhan:</td>
                        <td class="text-right text-primary"><?= number_format($total_debit_all, 0, ',', '.') ?></td>
                        <td class="text-right text-danger"><?= number_format($total_kredit_all, 0, ',', '.') ?></td>
                    </tr>
                    <?php if(count($neraca_saldo) > 0): ?>
                    <tr>
                        <td colspan="2" class="text-right">Status:</td>
                        <td colspan="2" class="text-center <?= $total_debit_all == $total_kredit_all ? 'text-success' : 'text-danger' ?> font-weight-bold">
                            <?= $total_debit_all == $total_kredit_all ? 'SEIMBANG (BALANCE)' : 'TIDAK SEIMBANG' ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .card.shadow { box-shadow: none !important; border: none; }
    .card-header, .table-responsive, .table-responsive * { visibility: visible; }
    .table-responsive { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<?php require_once '../layouts/footer.php'; ?>
