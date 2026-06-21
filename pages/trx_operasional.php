<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Proses Simpan Biaya Operasional
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_transaksi'])) {
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $jenis_biaya = $conn->real_escape_string($_POST['jenis_biaya']);
    if ($jenis_biaya == 'Lainnya') {
        $jenis_biaya = $conn->real_escape_string($_POST['jenis_biaya_lain']);
    }
    $nominal = (float)$_POST['nominal'];
    $keterangan = $conn->real_escape_string($_POST['keterangan']);
    
    $no_transaksi = 'TRX-O-' . date('YmdHis');

    if ($nominal > 0) {
        $conn->begin_transaction();
        try {
            // 1. Buat Jurnal Umum (Biaya Operasional)
            $deskripsi = "Biaya $jenis_biaya: $keterangan (Ref: $no_transaksi)";
            $conn->query("INSERT INTO tb_jurnal_umum (no_referensi, tanggal, deskripsi, total_debit, total_kredit) VALUES ('$no_transaksi', '$tanggal', '$deskripsi', $nominal, $nominal)");
            $id_jurnal = $conn->insert_id;
            
            // 2. Buat Detail Jurnal
            // Debit: 512 (Beban Operasional) 
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal, '512', 'Debit', $nominal)");
            // Kredit: 111 (Kas)
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal, '111', 'Kredit', $nominal)");

            // 3. Simpan Transaksi Operasional
            $conn->query("INSERT INTO tb_biaya_operasional (tanggal, jenis_biaya, nominal, keterangan, id_jurnal) VALUES ('$tanggal', '$jenis_biaya', $nominal, '$keterangan', $id_jurnal)");
            
            $conn->commit();
            echo "<script>alert('Biaya Operasional Berhasil Disimpan!'); window.location.href='trx_operasional.php';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Gagal menyimpan transaksi: " . $e->getMessage() . "');</script>";
        }
    }
}

// Proses Hapus (Opsional)
if (isset($_GET['delete'])) {
    if ($_SESSION['role'] == 'Admin') {
        $id = (int)$_GET['delete'];
        $conn->query("DELETE FROM tb_biaya_operasional WHERE id = $id");
        echo "<script>alert('Transaksi dihapus'); window.location.href='trx_operasional.php';</script>";
        exit;
    }
}

// Ambil Riwayat Transaksi
$history = $conn->query("SELECT * FROM tb_biaya_operasional ORDER BY tanggal DESC, id DESC");
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Biaya Operasional</h1>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTransaksi">
        <i class="fas fa-plus fa-sm text-white-50"></i> Catat Pengeluaran
    </button>
</div>

<!-- Tabel Riwayat Biaya -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Riwayat Biaya Operasional</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis Biaya</th>
                        <th>Keterangan</th>
                        <th>Nominal</th>
                        <?php if($_SESSION['role'] == 'Admin'): ?>
                        <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $history->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($row['jenis_biaya']) ?></span></td>
                        <td><?= htmlspecialchars($row['keterangan']) ?></td>
                        <td><b>Rp <?= number_format($row['nominal'], 0, ',', '.') ?></b></td>
                        <?php if($_SESSION['role'] == 'Admin'): ?>
                        <td>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus biaya ini?');"><i class="fas fa-trash"></i></a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($history->num_rows == 0): ?>
                    <tr><td colspan="5" class="text-center">Belum ada riwayat pengeluaran</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Biaya -->
<div class="modal fade" id="modalTransaksi" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Catat Pengeluaran Operasional</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tanggal Pengeluaran</label>
                        <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Jenis Biaya</label>
                        <select class="form-control" name="jenis_biaya" id="jenis_biaya" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="Listrik">Listrik</option>
                            <option value="Air">Air</option>
                            <option value="Internet/Telepon">Internet / Telepon</option>
                            <option value="Transportasi">BBM / Transportasi</option>
                            <option value="Pemeliharaan">Pemeliharaan Bangunan / Aset</option>
                            <option value="Konsumsi">Konsumsi Karyawan</option>
                            <option value="Lainnya">Lainnya...</option>
                        </select>
                    </div>
                    <div class="form-group" id="divLainnya" style="display:none;">
                        <label>Tuliskan Jenis Biaya</label>
                        <input type="text" class="form-control" name="jenis_biaya_lain" id="jenis_biaya_lain">
                    </div>
                    <div class="form-group">
                        <label>Nominal Pengeluaran (Rp)</label>
                        <input type="number" step="0.01" class="form-control text-danger font-weight-bold" name="nominal" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan Lengkap</label>
                        <textarea class="form-control" name="keterangan" rows="3" required></textarea>
                    </div>
                    <div class="alert alert-warning mb-0">
                        <small><i class="fas fa-exclamation-triangle"></i> Jurnal akan otomatis dibuat saat Anda menyimpan form ini.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_transaksi" class="btn btn-primary">Simpan Biaya</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('jenis_biaya').addEventListener('change', function() {
    if(this.value === 'Lainnya') {
        document.getElementById('divLainnya').style.display = 'block';
        document.getElementById('jenis_biaya_lain').required = true;
    } else {
        document.getElementById('divLainnya').style.display = 'none';
        document.getElementById('jenis_biaya_lain').required = false;
    }
});
</script>

<?php require_once '../layouts/footer.php'; ?>
