<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Proses Simpan Pelunasan Hutang
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_pelunasan'])) {
    $id_pembelian = (int)$_POST['id_pembelian'];
    $tanggal_pelunasan = $conn->real_escape_string($_POST['tanggal_pelunasan']);
    $kode_akun_sumber = $conn->real_escape_string($_POST['kode_akun']);
    
    // Ambil data transaksi pembelian
    $q_trx = $conn->query("SELECT no_transaksi, total_harga, id_pemasok FROM tb_transaksi_pembelian WHERE id = $id_pembelian AND status_bayar = 'Hutang'");
    
    if ($q_trx && $q_trx->num_rows > 0) {
        $trx = $q_trx->fetch_assoc();
        $total_harga = $trx['total_harga'];
        $no_transaksi = $trx['no_transaksi'];
        
        $conn->begin_transaction();
        try {
            // 1. Buat Jurnal Pelunasan
            $no_ref_jurnal = 'BYR-B-' . date('YmdHis');
            $deskripsi = "Pelunasan Hutang Pembelian (Ref: $no_transaksi)";
            
            $conn->query("INSERT INTO tb_jurnal_umum (no_referensi, tanggal, deskripsi, total_debit, total_kredit) VALUES ('$no_ref_jurnal', '$tanggal_pelunasan', '$deskripsi', $total_harga, $total_harga)");
            $id_jurnal = $conn->insert_id;
            
            // 2. Detail Jurnal
            // Debit: 211 (Utang Usaha)
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal, '211', 'Debit', $total_harga)");
            // Kredit: Akun sumber dana yang dipilih pengguna
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal, '$kode_akun_sumber', 'Kredit', $total_harga)");
            
            // 3. Update status_bayar di transaksi pembelian
            $conn->query("UPDATE tb_transaksi_pembelian SET status_bayar = 'Lunas' WHERE id = $id_pembelian");
            
            $conn->commit();
            echo "<script>alert('Pelunasan hutang berhasil dicatat!'); window.location.href='trx_bayar_hutang.php';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Gagal mencatat pelunasan: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Transaksi tidak valid atau sudah lunas.');</script>";
    }
}

// Ambil Referensi Akun Sumber Dana (Hanya Aset, misal Kas/Bank)
$akun_sumber = $conn->query("SELECT * FROM tb_akun WHERE tipe = 'Aset' ORDER BY kode_akun ASC");

// Ambil Daftar Hutang Belum Lunas
$hutang = $conn->query("
    SELECT t.id, t.no_transaksi, t.tanggal, t.total_harga, p.nama_pemasok 
    FROM tb_transaksi_pembelian t 
    JOIN tb_pemasok p ON t.id_pemasok = p.id 
    WHERE t.status_bayar = 'Hutang' 
    ORDER BY t.tanggal ASC
");
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Pelunasan Hutang</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Hutang Pembelian Belum Lunas</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Tanggal Transaksi</th>
                        <th>No Transaksi</th>
                        <th>Pemasok</th>
                        <th>Total Hutang</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $hutang->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($row['no_transaksi']) ?></td>
                        <td><?= htmlspecialchars($row['nama_pemasok']) ?></td>
                        <td class="text-danger font-weight-bold">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                        <td><span class="badge badge-warning">Hutang</span></td>
                        <td>
                            <button class="btn btn-sm btn-success btn-lunasi" 
                                data-id="<?= $row['id'] ?>"
                                data-no="<?= htmlspecialchars($row['no_transaksi']) ?>"
                                data-nominal="Rp <?= number_format($row['total_harga'], 0, ',', '.') ?>"
                                data-toggle="modal" data-target="#modalPelunasan">
                                <i class="fas fa-check"></i> Lunasi
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($hutang->num_rows == 0): ?>
                    <tr><td colspan="6" class="text-center">Tidak ada tagihan hutang yang belum lunas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Pelunasan -->
<div class="modal fade" id="modalPelunasan" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Konfirmasi Pelunasan Hutang</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_pembelian" id="modal_id_pembelian" required>
                    
                    <div class="form-group">
                        <label>No Transaksi</label>
                        <input type="text" class="form-control" id="modal_no_transaksi" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Total Nominal</label>
                        <input type="text" class="form-control text-danger font-weight-bold" id="modal_nominal" readonly>
                    </div>
                    
                    <hr>
                    
                    <div class="form-group">
                        <label>Tanggal Pelunasan</label>
                        <input type="date" class="form-control" name="tanggal_pelunasan" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Sumber Dana (Pembayaran dari Akun)</label>
                        <select class="form-control" name="kode_akun" required>
                            <option value="">-- Pilih Akun --</option>
                            <?php while($a = $akun_sumber->fetch_assoc()): ?>
                                <!-- Pilih default 111 (Kas) -->
                                <option value="<?= $a['kode_akun'] ?>" <?= $a['kode_akun'] == '111' ? 'selected' : '' ?>>
                                    [<?= $a['kode_akun'] ?>] <?= htmlspecialchars($a['nama_akun']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle"></i> Sistem akan otomatis menjurnal pembayaran ini.</small>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_pelunasan" class="btn btn-success">Proses Pelunasan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btns = document.querySelectorAll('.btn-lunasi');
    btns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modal_id_pembelian').value = this.getAttribute('data-id');
            document.getElementById('modal_no_transaksi').value = this.getAttribute('data-no');
            document.getElementById('modal_nominal').value = this.getAttribute('data-nominal');
        });
    });
});
</script>

<?php require_once '../layouts/footer.php'; ?>
