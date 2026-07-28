<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Proses Simpan Transaksi Pembelian
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_transaksi'])) {
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $id_pemasok = (int)$_POST['id_pemasok'];
    $status_bayar = $conn->real_escape_string($_POST['status_bayar']);
    $no_transaksi = 'TRX-B-' . date('YmdHis');

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
            $deskripsi = "Pembelian " . ($status_bayar == 'Lunas' ? 'Tunai' : 'Kredit') . " (Ref: $no_transaksi)";
            $conn->query("INSERT INTO tb_jurnal_umum (no_referensi, tanggal, deskripsi, total_debit, total_kredit) VALUES ('$no_transaksi', '$tanggal', '$deskripsi', $total_harga, $total_harga)");
            $id_jurnal = $conn->insert_id;

            // 2. Buat Detail Jurnal
            // Debit: 511 (Pembelian) atau 113 (Persediaan). Kita pakai 511 Pembelian
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal, '511', 'Debit', $total_harga)");
            // Kredit: 111 (Kas) jika Lunas, 211 (Utang Usaha) jika Hutang
            $akun_kredit = ($status_bayar == 'Lunas') ? '111' : '211';
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal, '$akun_kredit', 'Kredit', $total_harga)");

            // 3. Simpan Transaksi Pembelian
            $conn->query("INSERT INTO tb_transaksi_pembelian (no_transaksi, tanggal, id_pemasok, total_harga, status_bayar, id_jurnal) VALUES ('$no_transaksi', '$tanggal', $id_pemasok, $total_harga, '$status_bayar', $id_jurnal)");
            $id_pembelian = $conn->insert_id;

            // 4. Simpan Detail Pembelian & Update Stok
            for ($i = 0; $i < count($id_barangs); $i++) {
                $id_b = (int)$id_barangs[$i];
                $q = (int)$qtys[$i];
                $h = (float)$hargas[$i];
                $subtotal = $q * $h;

                // Insert detail
                $conn->query("INSERT INTO tb_transaksi_pembelian_detail (id_pembelian, id_barang, qty, harga_satuan, subtotal) VALUES ($id_pembelian, $id_b, $q, $h, $subtotal)");
            }

            $conn->commit();
            echo "<script>alert('Transaksi Pembelian Berhasil Disimpan!'); window.location.href='trx_pembelian.php';</script>";
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

        // Ambil id_jurnal
        $q_jurnal = $conn->query("SELECT id_jurnal FROM tb_transaksi_pembelian WHERE id = $id_hapus");
        $id_jurnal = ($q_jurnal && $q_jurnal->num_rows > 0) ? $q_jurnal->fetch_assoc()['id_jurnal'] : null;

        // Kembalikan stok (kurangi karena pembelian dihapus)
        $dtl = $conn->query("SELECT id_barang, qty FROM tb_transaksi_pembelian_detail WHERE id_pembelian = $id_hapus");
        if ($dtl) {
            while ($r = $dtl->fetch_assoc()) {
                $conn->query("UPDATE tb_barang SET stok_tersedia = stok_tersedia - " . $r['qty'] . " WHERE id = " . $r['id_barang']);
            }
        }

        // Menghapus transaksi
        $conn->query("DELETE FROM tb_transaksi_pembelian WHERE id = $id_hapus");

        // Menghapus jurnal terkait
        if ($id_jurnal) {
            $conn->query("DELETE FROM tb_jurnal_umum WHERE id = $id_jurnal");
        }

        echo "<script>alert('Transaksi berhasil dihapus dan stok disesuaikan.'); window.location.href='trx_pembelian.php';</script>";
        exit;
    }
}

// Ambil Referensi Data
$pemasok = $conn->query("SELECT * FROM tb_pemasok ORDER BY nama_pemasok ASC");
$barang = $conn->query("SELECT * FROM tb_barang WHERE nama_barang LIKE '%Kelapa Utuh%' ORDER BY nama_barang ASC");

// Ambil Riwayat Transaksi
$history = $conn->query("
    SELECT t.id as id_transaksi, t.no_transaksi, t.tanggal, t.total_harga, t.status_bayar, p.nama_pemasok, d.qty, d.harga_satuan, d.subtotal, b.nama_barang, b.satuan
    FROM tb_transaksi_pembelian t
    JOIN tb_pemasok p ON t.id_pemasok = p.id
    JOIN tb_transaksi_pembelian_detail d ON d.id_pembelian = t.id
    JOIN tb_barang b ON d.id_barang = b.id
    ORDER BY t.tanggal DESC, t.id DESC, d.id ASC
");
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Transaksi Pembelian</h1>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTransaksi">
        <i class="fas fa-plus fa-sm text-white-50"></i> Buat Transaksi Baru
    </button>
</div>

<!-- Tabel Riwayat Pembelian -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Riwayat Pembelian</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>No Transaksi</th>
                        <th>Pemasok</th>
                        <th>Jenis Barang</th>
                        <th>Qty</th>
                        <th>Harga Satuan</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $history->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($row['no_transaksi']) ?></td>
                            <td><?= htmlspecialchars($row['nama_pemasok']) ?></td>
                            <td><?= htmlspecialchars($row['nama_barang'] ?? '') ?> (<?= htmlspecialchars($row['satuan'] ?? '') ?>)</td>
                            <td><?= number_format($row['qty'] ?? 0, 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($row['harga_satuan'] ?? 0, 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                            <td>
                                <span class="badge badge-<?= $row['status_bayar'] == 'Lunas' ? 'success' : 'warning' ?>">
                                    <?= htmlspecialchars($row['status_bayar']) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="window.open('cetak_nota_pembelian.php?id=<?= $row['id_transaksi'] ?>', '_blank', 'width=800,height=600')">
                                    <i class="fas fa-print"></i>
                                </button>
                                <?php if ($_SESSION['role'] == 'Admin'): ?>
                                    <a href="?delete=<?= $row['id_transaksi'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus transaksi ini? Stok akan disesuaikan.');">
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
                    <h5 class="modal-title">Buat Transaksi Pembelian</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="font-weight-bold">Informasi Pembelian</h6>
                            <hr>
                            <div class="form-group">
                                <label>Tanggal Transaksi</label>
                                <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Pemasok (Supplier)</label>
                                <select class="form-control" name="id_pemasok" required>
                                    <option value="">-- Pilih Pemasok --</option>
                                    <?php while ($p = $pemasok->fetch_assoc()): ?>
                                        <option value="<?= $p['id'] ?>"><?= $p['nama_pemasok'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status Pembayaran</label>
                                <select class="form-control" name="status_bayar" required>
                                    <option value="Lunas">Tunai / Lunas</option>
                                    <option value="Hutang">Hutang / Kredit</option>
                                </select>
                            </div>
                            <div class="alert alert-info">
                                <small><i class="fas fa-info-circle"></i> Jurnal akan otomatis terbentuk setelah disimpan.</small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6 class="font-weight-bold">Daftar Barang</h6>
                            <hr>
                            <table class="table table-bordered" id="tabelBarang">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Barang</th>
                                        <th width="100">Qty</th>
                                        <th width="150">Harga Satuan</th>
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
                                                while ($b = $barang->fetch_assoc()):
                                                ?>
                                                    <option value="<?= $b['id'] ?>"><?= $b['kode_barang'] ?> - <?= $b['nama_barang'] ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" name="qty[]" min="1" value="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control" name="harga_satuan[]" required>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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



<?php require_once '../layouts/footer.php'; ?>