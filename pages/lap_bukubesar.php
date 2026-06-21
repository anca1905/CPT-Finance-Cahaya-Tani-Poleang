<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : date('Y-m-t');
$selected_akun = isset($_GET['kode_akun']) ? $_GET['kode_akun'] : '';

// Ambil List Akun
$akun_list = $conn->query("SELECT * FROM tb_akun ORDER BY kode_akun ASC");

$saldo_awal = 0;
$saldo_normal = 'Debit';
$nama_akun_dipilih = '';

// Ambil Data Akun yang Dipilih
if ($selected_akun != '') {
    $q_akun = $conn->query("SELECT * FROM tb_akun WHERE kode_akun = '$selected_akun'");
    if ($q_akun->num_rows > 0) {
        $row_akun = $q_akun->fetch_assoc();
        $saldo_normal = $row_akun['saldo_normal'];
        $nama_akun_dipilih = $row_akun['nama_akun'];
    }

    // Ambil Saldo Awal (sebelum tgl_mulai)
    $q_saldo_awal = $conn->query("
        SELECT 
            SUM(CASE WHEN jd.posisi = 'Debit' THEN jd.nominal ELSE 0 END) as tot_debit,
            SUM(CASE WHEN jd.posisi = 'Kredit' THEN jd.nominal ELSE 0 END) as tot_kredit
        FROM tb_jurnal_detail jd
        JOIN tb_jurnal_umum ju ON jd.id_jurnal = ju.id
        WHERE jd.kode_akun = '$selected_akun' AND ju.tanggal < '$tgl_mulai'
    ");
    
    $row_sa = $q_saldo_awal->fetch_assoc();
    $tot_deb_awal = $row_sa['tot_debit'] ?? 0;
    $tot_kre_awal = $row_sa['tot_kredit'] ?? 0;

    if ($saldo_normal == 'Debit') {
        $saldo_awal = $tot_deb_awal - $tot_kre_awal;
    } else {
        $saldo_awal = $tot_kre_awal - $tot_deb_awal;
    }

    // Ambil Mutasi Transaksi
    $mutasi = $conn->query("
        SELECT ju.tanggal, ju.no_referensi, ju.deskripsi, jd.posisi, jd.nominal, ju.jenis_jurnal
        FROM tb_jurnal_detail jd
        JOIN tb_jurnal_umum ju ON jd.id_jurnal = ju.id
        WHERE jd.kode_akun = '$selected_akun' 
          AND ju.tanggal BETWEEN '$tgl_mulai' AND '$tgl_sampai'
        ORDER BY ju.tanggal ASC, ju.id ASC
    ");
}

?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Buku Besar (General Ledger)</h1>
    <?php if($selected_akun != ''): ?>
    <button onclick="window.print()" class="btn btn-sm btn-success shadow-sm d-print-none">
        <i class="fas fa-print fa-sm text-white-50"></i> Cetak Laporan
    </button>
    <?php endif; ?>
</div>

<div class="card shadow mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" class="form-inline">
            <label class="mr-2">Dari Tanggal:</label>
            <input type="date" class="form-control mr-3" name="tgl_mulai" value="<?= $tgl_mulai ?>" required>
            
            <label class="mr-2">Sampai Tanggal:</label>
            <input type="date" class="form-control mr-3" name="tgl_sampai" value="<?= $tgl_sampai ?>" required>
            
            <label class="mr-2">Akun:</label>
            <select class="form-control mr-3" name="kode_akun" required>
                <option value="">-- Pilih Akun --</option>
                <?php while($a = $akun_list->fetch_assoc()): ?>
                <option value="<?= $a['kode_akun'] ?>" <?= $selected_akun == $a['kode_akun'] ? 'selected' : '' ?>>
                    [<?= $a['kode_akun'] ?>] <?= htmlspecialchars($a['nama_akun']) ?>
                </option>
                <?php endwhile; ?>
            </select>
            
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
        </form>
    </div>
</div>

<?php if ($selected_akun != ''): ?>
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Rincian Buku Besar</h6>
        <span class="badge badge-info p-2" style="font-size: 14px;">Akun: [<?= $selected_akun ?>] <?= htmlspecialchars($nama_akun_dipilih) ?></span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                <thead class="thead-light text-center">
                    <tr>
                        <th width="100">Tanggal</th>
                        <th width="150">No. Bukti / Ref</th>
                        <th>Keterangan</th>
                        <th width="150">Debit (Rp)</th>
                        <th width="150">Kredit (Rp)</th>
                        <th width="150">Saldo (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-light">
                        <td colspan="3" class="text-right font-weight-bold">Saldo Awal per <?= date('d/m/Y', strtotime($tgl_mulai)) ?></td>
                        <td></td>
                        <td></td>
                        <td class="text-right font-weight-bold"><?= number_format($saldo_awal, 0, ',', '.') ?></td>
                    </tr>
                    
                    <?php 
                    $saldo_berjalan = $saldo_awal;
                    $total_mutasi_debit = 0;
                    $total_mutasi_kredit = 0;
                    
                    if(isset($mutasi) && $mutasi->num_rows > 0):
                        while($row = $mutasi->fetch_assoc()):
                            $debit = $row['posisi'] == 'Debit' ? $row['nominal'] : 0;
                            $kredit = $row['posisi'] == 'Kredit' ? $row['nominal'] : 0;
                            
                            $total_mutasi_debit += $debit;
                            $total_mutasi_kredit += $kredit;
                            
                            if($saldo_normal == 'Debit') {
                                $saldo_berjalan = $saldo_berjalan + $debit - $kredit;
                            } else {
                                $saldo_berjalan = $saldo_berjalan - $debit + $kredit;
                            }
                    ?>
                    <tr>
                        <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td class="text-center">
                            <?= htmlspecialchars($row['no_referensi']) ?>
                            <?php if($row['jenis_jurnal'] == 'Penyesuaian'): ?>
                                <br><small class="text-danger">(Penyesuaian)</small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['deskripsi']) ?></td>
                        <td class="text-right"><?= $debit > 0 ? number_format($debit, 0, ',', '.') : '-' ?></td>
                        <td class="text-right"><?= $kredit > 0 ? number_format($kredit, 0, ',', '.') : '-' ?></td>
                        <td class="text-right"><?= number_format($saldo_berjalan, 0, ',', '.') ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada mutasi transaksi pada periode ini</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-light font-weight-bold">
                        <td colspan="3" class="text-right">Total Mutasi:</td>
                        <td class="text-right text-primary"><?= number_format($total_mutasi_debit, 0, ',', '.') ?></td>
                        <td class="text-right text-danger"><?= number_format($total_mutasi_kredit, 0, ',', '.') ?></td>
                        <td></td>
                    </tr>
                    <tr class="bg-dark text-white font-weight-bold">
                        <td colspan="5" class="text-right">Saldo Akhir:</td>
                        <td class="text-right"><?= number_format($saldo_berjalan, 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
@media print {
    body * { visibility: hidden; }
    .card.shadow { box-shadow: none !important; border: none; }
    .card-header, .table-responsive, .table-responsive * { visibility: visible; }
    .table-responsive { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<?php require_once '../layouts/footer.php'; ?>
