<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Ambil Data Barang
$q_barang = $conn->query("SELECT * FROM tb_barang ORDER BY kategori ASC, nama_barang ASC");

$total_nilai_aset = 0;
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Laporan Stok Barang (Persediaan)</h1>
    <button onclick="window.print()" class="btn btn-sm btn-info shadow-sm d-none d-sm-inline-block">
        <i class="fas fa-print fa-sm text-white-50"></i> Cetak Laporan
    </button>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 bg-primary">
        <h6 class="m-0 font-weight-bold text-white">Data Stok & Nilai Persediaan per Tanggal: <?= date('d/m/Y') ?></h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                <thead class="thead-light text-center">
                    <tr>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Stok Tersedia</th>
                        <th>Harga Satuan (HPP/Jual)</th>
                        <th>Total Nilai Aset (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    while($row = $q_barang->fetch_assoc()): 
                        $nilai_aset = $row['stok_tersedia'] * $row['harga_satuan'];
                        $total_nilai_aset += $nilai_aset;
                    ?>
                    <tr>
                        <td class="text-center"><?= $row['kode_barang'] ?></td>
                        <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td class="text-center"><?= $row['kategori'] ?></td>
                        <td class="text-center font-weight-bold <?= $row['stok_tersedia'] <= 5 ? 'text-danger' : 'text-success' ?>">
                            <?= $row['stok_tersedia'] ?> <?= $row['satuan'] ?>
                        </td>
                        <td class="text-right">Rp <?= number_format($row['harga_satuan'], 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format($nilai_aset, 0, ',', '.') ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-light">
                        <th colspan="5" class="text-right h5 font-weight-bold">TOTAL NILAI PERSEDIAAN (ASET):</th>
                        <th class="text-right h5 font-weight-bold text-primary">Rp <?= number_format($total_nilai_aset, 0, ',', '.') ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="mt-4 d-print-none alert alert-info">
            <i class="fas fa-info-circle"></i> <b>Keterangan:</b> Baris dengan teks stok berwarna merah menunjukkan stok kritis (<= 5). Nilai total persediaan ini akan sinkron dengan nilai Aset Lancar (Persediaan) di laporan Neraca.
        </div>
    </div>
</div>

<style>
@media print {
    body { font-size: 14px; }
    .navbar-nav, .topbar, footer { display: none !important; }
    #content-wrapper { background-color: #fff !important; }
    .card { border: none !important; box-shadow: none !important; }
    .card-header { background-color: transparent !important; border-bottom: 2px solid #000 !important; }
    .card-header h6 { color: #000 !important; }
}
</style>

<?php require_once '../layouts/footer.php'; ?>
