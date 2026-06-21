<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Proses Simpan Penggajian
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_transaksi'])) {
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $id_karyawan = (int)$_POST['id_karyawan'];
    $jenis_pekerjaan = $conn->real_escape_string($_POST['jenis_pekerjaan']);
    $qty = (int)$_POST['qty'];
    $tarif_satuan = (float)$_POST['tarif_satuan'];
    
    $total_upah = $qty * $tarif_satuan;

    if ($total_upah > 0) {
        // Ambil nama karyawan untuk deskripsi
        $qk = $conn->query("SELECT nama_karyawan FROM tb_karyawan WHERE id = $id_karyawan");
        $nama_karyawan = $qk->fetch_assoc()['nama_karyawan'];

        $conn->begin_transaction();
        try {
            // 1. Buat Jurnal Umum (Beban Gaji)
            $deskripsi = "Pembayaran Gaji: $nama_karyawan (Jenis: $jenis_pekerjaan)";
            $no_referensi = 'TRX-G-' . date('YmdHis');
            $conn->query("INSERT INTO tb_jurnal_umum (no_referensi, tanggal, deskripsi, total_debit, total_kredit) VALUES ('$no_referensi', '$tanggal', '$deskripsi', $total_upah, $total_upah)");
            $id_jurnal = $conn->insert_id;
            
            // 2. Buat Detail Jurnal
            // Debit: 513 (Upah Karyawan)
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal, '513', 'Debit', $total_upah)");
            // Kredit: 111 (Kas)
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal, '111', 'Kredit', $total_upah)");

            // 3. Simpan Transaksi Penggajian
            $conn->query("INSERT INTO tb_penggajian (tanggal, id_karyawan, jenis_pekerjaan, qty, tarif_satuan, total_upah, id_jurnal) VALUES ('$tanggal', $id_karyawan, '$jenis_pekerjaan', $qty, $tarif_satuan, $total_upah, $id_jurnal)");
            
            $conn->commit();
            echo "<script>alert('Data Penggajian Berhasil Disimpan!'); window.location.href='trx_penggajian.php';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Gagal menyimpan transaksi: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Total upah tidak boleh kurang dari atau sama dengan nol.');</script>";
    }
}

// Ambil Referensi Data
$karyawan = $conn->query("SELECT * FROM tb_karyawan ORDER BY nama_karyawan ASC");

// Ambil Riwayat Transaksi
$history = $conn->query("
    SELECT t.*, k.nama_karyawan, k.jabatan 
    FROM tb_penggajian t 
    JOIN tb_karyawan k ON t.id_karyawan = k.id 
    ORDER BY t.tanggal DESC, t.id DESC
");
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Transaksi Penggajian / Upah</h1>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTransaksi">
        <i class="fas fa-plus fa-sm text-white-50"></i> Catat Upah Baru
    </button>
</div>

<!-- Tabel Riwayat Penggajian -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Riwayat Pembayaran Upah</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Karyawan</th>
                        <th>Jenis Pekerjaan</th>
                        <th>Qty</th>
                        <th>Tarif Satuan</th>
                        <th>Total Upah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $history->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td>
                            <?= htmlspecialchars($row['nama_karyawan']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($row['jabatan']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($row['jenis_pekerjaan']) ?></td>
                        <td><?= htmlspecialchars($row['qty']) ?></td>
                        <td>Rp <?= number_format($row['tarif_satuan'], 0, ',', '.') ?></td>
                        <td><b>Rp <?= number_format($row['total_upah'], 0, ',', '.') ?></b></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($history->num_rows == 0): ?>
                    <tr><td colspan="6" class="text-center">Belum ada riwayat penggajian</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Penggajian -->
<div class="modal fade" id="modalTransaksi" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Catat Upah Karyawan</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tanggal Pembayaran</label>
                        <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Pilih Karyawan</label>
                        <select class="form-control" name="id_karyawan" required>
                            <option value="">-- Pilih Karyawan --</option>
                            <?php while($k = $karyawan->fetch_assoc()): ?>
                                <option value="<?= $k['id'] ?>"><?= $k['nama_karyawan'] ?> - <?= $k['jabatan'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jenis Pekerjaan</label>
                        <input type="text" class="form-control" name="jenis_pekerjaan" placeholder="Contoh: Bongkar Muat / Kupas Kelapa" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Qty / Hari</label>
                            <input type="number" class="form-control" name="qty" id="qty" value="1" required>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Tarif Satuan (Rp)</label>
                            <input type="number" step="0.01" class="form-control" name="tarif_satuan" id="tarif_satuan" value="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Total Upah</label>
                        <input type="text" class="form-control font-weight-bold" id="total_upah_display" readonly style="font-size: 1.2rem;">
                    </div>
                    <div class="alert alert-info mb-0">
                        <small><i class="fas fa-info-circle"></i> Sistem otomatis mencatat Upah Karyawan pada Jurnal Umum setelah disimpan.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_transaksi" class="btn btn-primary">Simpan Upah</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Script Kalkulasi Otomatis THP
document.addEventListener('DOMContentLoaded', function() {
    const qty = document.getElementById('qty');
    const tarif = document.getElementById('tarif_satuan');
    const display = document.getElementById('total_upah_display');

    function hitungTotal() {
        let q = parseFloat(qty.value) || 0;
        let t = parseFloat(tarif.value) || 0;
        let total = q * t;
        
        display.value = 'Rp ' + total.toLocaleString('id-ID');
    }

    qty.addEventListener('input', hitungTotal);
    tarif.addEventListener('input', hitungTotal);
});
</script>

<?php require_once '../layouts/footer.php'; ?>
