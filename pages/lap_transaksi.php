<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Filter Tanggal
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : date('Y-m-t');

// Ambil Data Pembelian
$q_beli = $conn->query("
    SELECT t.*, p.nama_pemasok 
    FROM tb_transaksi_pembelian t 
    JOIN tb_pemasok p ON t.id_pemasok = p.id 
    WHERE t.tanggal BETWEEN '$tgl_mulai' AND '$tgl_sampai'
    ORDER BY t.tanggal DESC
");

$total_beli = 0;

// Ambil Data Penjualan
$q_jual = $conn->query("
    SELECT t.*, p.nama_pelanggan 
    FROM tb_transaksi_penjualan t 
    JOIN tb_pelanggan p ON t.id_pelanggan = p.id 
    WHERE t.tanggal BETWEEN '$tgl_mulai' AND '$tgl_sampai'
    ORDER BY t.tanggal DESC
");

$total_jual = 0;
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Laporan Transaksi Pembelian & Penjualan</h1>
    <button onclick="window.print()" class="btn btn-sm btn-info shadow-sm d-none d-sm-inline-block">
        <i class="fas fa-print fa-sm text-white-50"></i> Cetak Laporan
    </button>
</div>

<!-- Filter Box (Hidden during print) -->
<div class="card shadow mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" class="form-inline">
            <label class="mr-2">Dari Tanggal: </label>
            <input type="date" name="tgl_mulai" class="form-control mr-3" value="<?= $tgl_mulai ?>">
            
            <label class="mr-2">Sampai: </label>
            <input type="date" name="tgl_sampai" class="form-control mr-3" value="<?= $tgl_sampai ?>">
            
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div>
</div>

<div class="row">
    <!-- Laporan Penjualan -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-success">
                <h6 class="m-0 font-weight-bold text-white">Laporan Penjualan (Pemasukan)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>No Nota</th>
                                <th>Pelanggan</th>
                                <th class="text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $q_jual->fetch_assoc()): $total_jual += $row['total_harga']; ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td><?= $row['no_transaksi'] ?></td>
                                <td><?= $row['nama_pelanggan'] ?></td>
                                <td class="text-right">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">TOTAL PENJUALAN:</th>
                                <th class="text-right text-success font-weight-bold">Rp <?= number_format($total_jual, 0, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Laporan Pembelian -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-danger">
                <h6 class="m-0 font-weight-bold text-white">Laporan Pembelian (Pengeluaran)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>No Nota</th>
                                <th>Pemasok</th>
                                <th class="text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $q_beli->fetch_assoc()): $total_beli += $row['total_harga']; ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td><?= $row['no_transaksi'] ?></td>
                                <td><?= $row['nama_pemasok'] ?></td>
                                <td class="text-right">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">TOTAL PEMBELIAN:</th>
                                <th class="text-right text-danger font-weight-bold">Rp <?= number_format($total_beli, 0, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body { font-size: 12px; }
    .navbar-nav, .topbar, footer { display: none !important; }
    #content-wrapper { background-color: #fff !important; }
    .card { border: none !important; box-shadow: none !important; }
    .card-header { background-color: transparent !important; border-bottom: 2px solid #000 !important; }
    .card-header h6 { color: #000 !important; }
}
</style>

<?php require_once '../layouts/footer.php'; ?>
