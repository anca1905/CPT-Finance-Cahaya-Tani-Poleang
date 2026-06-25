<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Proses Simpan Transaksi Penjualan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_transaksi'])) {
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $id_pelanggan = (int)$_POST['id_pelanggan'];
    $status_bayar = $conn->real_escape_string($_POST['status_bayar']);
    $no_transaksi = 'TRX-J-' . date('YmdHis');
    
    $id_barangs = $_POST['id_barang'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $hargas = $_POST['harga_satuan'] ?? [];
    
    // Kalkulasi Total
    $total_harga = 0;
    for ($i = 0; $i < count($id_barangs); $i++) {
        $total_harga += ((int)$qtys[$i] * (float)$hargas[$i]);
    }

    if ($total_harga > 0 && count($id_barangs) > 0) {
        $conn->begin_transaction();
        try {
            // 1. Buat Jurnal Umum
            $deskripsi = "Penjualan " . ($status_bayar == 'Lunas' ? 'Tunai' : 'Kredit') . " (Ref: $no_transaksi)";
            $conn->query("INSERT INTO tb_jurnal_umum (no_referensi, tanggal, deskripsi, total_debit, total_kredit) VALUES ('$no_transaksi', '$tanggal', '$deskripsi', $total_harga, $total_harga)");
            $id_jurnal = $conn->insert_id;
            
            // 2. Buat Detail Jurnal
            // Kredit: 411 (Penjualan)
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal, '411', 'Kredit', $total_harga)");
            // Debit: 111 (Kas) jika Lunas, 112 (Piutang CTP) jika Hutang
            $akun_debit = ($status_bayar == 'Lunas') ? '111' : '112';
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal, '$akun_debit', 'Debit', $total_harga)");

            // 3. Simpan Transaksi Penjualan
            $conn->query("INSERT INTO tb_transaksi_penjualan (no_transaksi, tanggal, id_pelanggan, total_harga, status_bayar, id_jurnal) VALUES ('$no_transaksi', '$tanggal', $id_pelanggan, $total_harga, '$status_bayar', $id_jurnal)");
            $id_penjualan = $conn->insert_id;

            // 4. Simpan Detail Penjualan & Update Stok
            for ($i = 0; $i < count($id_barangs); $i++) {
                $id_b = (int)$id_barangs[$i];
                $q = (int)$qtys[$i];
                $h = (float)$hargas[$i];
                $subtotal = $q * $h;
                
                // Insert detail
                $conn->query("INSERT INTO tb_transaksi_penjualan_detail (id_penjualan, id_barang, qty, harga_satuan, subtotal) VALUES ($id_penjualan, $id_b, $q, $h, $subtotal)");
                
                // Update Stok (dikurangi karena penjualan)
                $conn->query("UPDATE tb_barang SET stok_tersedia = stok_tersedia - $q WHERE id = $id_b");
            }
            
            $conn->commit();
            echo "<script>alert('Transaksi Penjualan Berhasil Disimpan!'); window.location.href='trx_penjualan.php';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Gagal menyimpan transaksi: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Item barang tidak boleh kosong!');</script>";
    }
}

// Proses Hapus Transaksi (Opsional/Admin)
if (isset($_GET['delete'])) {
    if ($_SESSION['role'] == 'Admin') {
        $id_hapus = (int)$_GET['delete'];
        // Mengembalikan stok dan menghapus transaksi
        $dtl = $conn->query("SELECT id_barang, qty FROM tb_transaksi_penjualan_detail WHERE id_penjualan = $id_hapus");
        while($r = $dtl->fetch_assoc()) {
            $conn->query("UPDATE tb_barang SET stok_tersedia = stok_tersedia + " . $r['qty'] . " WHERE id = " . $r['id_barang']);
        }
        $conn->query("DELETE FROM tb_transaksi_penjualan WHERE id = $id_hapus");
        echo "<script>alert('Transaksi berhasil dihapus dan stok dikembalikan.'); window.location.href='trx_penjualan.php';</script>";
        exit;
    }
}

// Ambil Referensi Data
$pelanggan = $conn->query("SELECT * FROM tb_pelanggan ORDER BY nama_pelanggan ASC");
$barang = $conn->query("SELECT * FROM tb_barang WHERE nama_barang LIKE '%Kopra Putih%' OR nama_barang LIKE '%Kopra Edibel%' OR nama_barang LIKE '%Kopra Hitam%' OR nama_barang LIKE '%Arang%' ORDER BY nama_barang ASC");

// Ambil Riwayat Transaksi
$history = $conn->query("
    SELECT t.*, p.nama_pelanggan,
           (SELECT qty FROM tb_transaksi_penjualan_detail WHERE id_penjualan = t.id LIMIT 1) as qty,
           (SELECT harga_satuan FROM tb_transaksi_penjualan_detail WHERE id_penjualan = t.id LIMIT 1) as harga_satuan,
           (SELECT b.nama_barang FROM tb_transaksi_penjualan_detail d JOIN tb_barang b ON d.id_barang = b.id WHERE d.id_penjualan = t.id LIMIT 1) as nama_barang
    FROM tb_transaksi_penjualan t 
    JOIN tb_pelanggan p ON t.id_pelanggan = p.id 
    ORDER BY t.tanggal DESC, t.id DESC
");
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Transaksi Penjualan</h1>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTransaksi">
        <i class="fas fa-plus fa-sm text-white-50"></i> Buat Transaksi Baru
    </button>
</div>

<!-- Tabel Riwayat Penjualan -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Riwayat Penjualan</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>No Transaksi</th>
                        <th>Pelanggan</th>
                        <th>Jenis Barang</th>
                        <th>Qty (Kg)</th>
                        <th>Harga/Kg</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $history->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($row['no_transaksi']) ?></td>
                        <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                        <td><?= htmlspecialchars($row['nama_barang'] ?? '') ?></td>
                        <td><?= number_format($row['qty'] ?? 0, 0, ',', '.') ?></td>
                        <td>Rp <?= number_format($row['harga_satuan'] ?? 0, 0, ',', '.') ?></td>
                        <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                        <td>
                            <span class="badge badge-<?= $row['status_bayar'] == 'Lunas' ? 'success' : 'warning' ?>">
                                <?= htmlspecialchars($row['status_bayar']) ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="alert('Fitur cetak nota dalam pengembangan')">
                                <i class="fas fa-print"></i>
                            </button>
                            <?php if($_SESSION['role'] == 'Admin'): ?>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus transaksi ini? Stok akan dikembalikan.');">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Transaksi -->
<div class="modal fade" id="modalTransaksi" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Buat Transaksi Penjualan</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="font-weight-bold">Informasi Penjualan</h6>
                            <hr>
                            <div class="form-group">
                                <label>Tanggal Transaksi</label>
                                <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Pelanggan (Customer)</label>
                                <select class="form-control" name="id_pelanggan" required>
                                    <option value="">-- Pilih Pelanggan --</option>
                                    <?php while($p = $pelanggan->fetch_assoc()): ?>
                                        <option value="<?= $p['id'] ?>"><?= $p['nama_pelanggan'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status Pembayaran</label>
                                <select class="form-control" name="status_bayar" required>
                                    <option value="Lunas">Tunai / Lunas</option>
                                    <option value="Piutang">Piutang / Kredit</option>
                                </select>
                            </div>
                            <div class="alert alert-info">
                                <small><i class="fas fa-info-circle"></i> Stok barang akan otomatis berkurang setelah disimpan. Jurnal Pendapatan akan otomatis terbentuk.</small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6 class="font-weight-bold">Daftar Barang</h6>
                            <hr>
                            <table class="table table-bordered" id="tabelBarang">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Barang (Stok Tersedia)</th>
                                        <th width="100">Qty (Kg)</th>
                                        <th width="150">Harga/Kg</th>
                                        <th width="50">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select class="form-control" name="id_barang[]" required>
                                                <option value="">-- Pilih Barang --</option>
                                                <?php 
                                                // Reset pointer barang
                                                $barang->data_seek(0);
                                                while($b = $barang->fetch_assoc()): 
                                                ?>
                                                    <option value="<?= $b['id'] ?>"><?= $b['kode_barang'] ?> - <?= $b['nama_barang'] ?> (Stok: <?= $b['stok_tersedia'] ?>)</option>
                                                <?php endwhile; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" name="qty[]" min="1" value="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control" name="harga_satuan[]" required>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm hapus-baris"><i class="fas fa-times"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-success btn-sm" id="tambahBaris">
                                <i class="fas fa-plus"></i> Tambah Item
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_transaksi" class="btn btn-primary">Simpan Transaksi</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Script untuk menambah dan menghapus baris dinamis
document.addEventListener('DOMContentLoaded', function() {
    const tabelBody = document.querySelector('#tabelBarang tbody');
    const tambahBtn = document.getElementById('tambahBaris');
    
    // Copy baris pertama sebagai template
    const barisTemplate = tabelBody.querySelector('tr').cloneNode(true);
    barisTemplate.querySelector('input[name="qty[]"]').value = 1;
    barisTemplate.querySelector('input[name="harga_satuan[]"]').value = '';
    
    tambahBtn.addEventListener('click', function() {
        const trBaru = barisTemplate.cloneNode(true);
        tabelBody.appendChild(trBaru);
        attachHapusEvent();
    });
    
    function attachHapusEvent() {
        const hapusBtns = document.querySelectorAll('.hapus-baris');
        hapusBtns.forEach(btn => {
            btn.onclick = function() {
                if(tabelBody.children.length > 1) {
                    this.closest('tr').remove();
                } else {
                    alert('Minimal 1 barang harus dimasukkan.');
                }
            };
        });
    }
    
    attachHapusEvent();
});
</script>

<?php require_once '../layouts/footer.php'; ?>
