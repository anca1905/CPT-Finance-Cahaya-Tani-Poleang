<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Proses Simpan Transaksi Piutang Petani (Memberi Panjar/Pinjaman)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_transaksi'])) {
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $id_pemasok = (int)$_POST['id_pemasok'];
    $nominal_panjar = (float)$_POST['nominal_panjar'];
    $sisa_piutang = $nominal_panjar; // Awalnya sisa piutang sama dengan panjar
    $no_transaksi = 'TRX-P-' . date('YmdHis');

    if ($nominal_panjar > 0) {
        // Ambil nama pemasok
        $qp = $conn->query("SELECT nama_pemasok FROM tb_pemasok WHERE id = $id_pemasok");
        $nama_pemasok = $qp->fetch_assoc()['nama_pemasok'];

        $conn->begin_transaction();
        try {
            // 1. Buat Jurnal Umum (Pengeluaran Kas untuk Panjar/Piutang)
            $deskripsi = "Pemberian Panjar/Piutang kepada Pemasok: $nama_pemasok (Ref: $no_transaksi)";
            $conn->query("INSERT INTO tb_jurnal_umum (no_referensi, tanggal, deskripsi, total_debit, total_kredit) VALUES ('$no_transaksi', '$tanggal', '$deskripsi', $nominal_panjar, $nominal_panjar)");
            $id_jurnal = $conn->insert_id;
            
            // 2. Buat Detail Jurnal
            // Debit: 112 (Piutang CTP / Piutang Usaha)
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal, '112', 'Debit', $nominal_panjar)");
            // Kredit: 111 (Kas)
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal, '111', 'Kredit', $nominal_panjar)");

            // 3. Simpan Transaksi Piutang
            $conn->query("INSERT INTO tb_piutang_petani (tanggal, id_pemasok, nominal_panjar, sisa_piutang, status, id_jurnal) VALUES ('$tanggal', $id_pemasok, $nominal_panjar, $sisa_piutang, 'Belum Lunas', $id_jurnal)");
            
            $conn->commit();
            echo "<script>alert('Data Piutang / Panjar Berhasil Disimpan!'); window.location.href='trx_piutang.php';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Gagal menyimpan transaksi: " . $e->getMessage() . "');</script>";
        }
    }
}

// Proses Pelunasan Piutang (Sederhana - Bayar Penuh)
if (isset($_GET['lunas'])) {
    $id_piutang = (int)$_GET['lunas'];
    
    $q = $conn->query("
        SELECT pt.*, p.nama_pemasok 
        FROM tb_piutang_petani pt 
        JOIN tb_pemasok p ON pt.id_pemasok = p.id 
        WHERE pt.id = $id_piutang AND pt.status = 'Belum Lunas'
    ");
    
    if ($q->num_rows > 0) {
        $row = $q->fetch_assoc();
        $no_transaksi = 'LUNAS-P-' . date('YmdHis');
        $tanggal_lunas = date('Y-m-d');
        $nominal = $row['sisa_piutang'];
        $nama_pemasok = $row['nama_pemasok'];

        $conn->begin_transaction();
        try {
            // Jurnal Pelunasan Piutang
            $deskripsi = "Pelunasan Piutang/Panjar dari Pemasok: $nama_pemasok (Ref: $no_transaksi)";
            $conn->query("INSERT INTO tb_jurnal_umum (no_referensi, tanggal, deskripsi, total_debit, total_kredit) VALUES ('$no_transaksi', '$tanggal_lunas', '$deskripsi', $nominal, $nominal)");
            $id_jurnal_lunas = $conn->insert_id;
            
            // Debit: 111 (Kas)
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal_lunas, '111', 'Debit', $nominal)");
            // Kredit: 112 (Piutang CTP)
            $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal_lunas, '112', 'Kredit', $nominal)");

            // Update Status
            $conn->query("UPDATE tb_piutang_petani SET sisa_piutang = 0, status = 'Lunas' WHERE id = $id_piutang");
            
            $conn->commit();
            echo "<script>alert('Piutang berhasil dilunasi!'); window.location.href='trx_piutang.php';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Gagal melunasi: " . $e->getMessage() . "');</script>";
        }
    }
}

// Ambil Referensi Data
$pemasok = $conn->query("SELECT * FROM tb_pemasok ORDER BY nama_pemasok ASC");

// Ambil Riwayat Transaksi
$history = $conn->query("
    SELECT pt.*, p.nama_pemasok 
    FROM tb_piutang_petani pt 
    JOIN tb_pemasok p ON pt.id_pemasok = p.id 
    ORDER BY pt.tanggal DESC, pt.id DESC
");
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Piutang / Panjar Petani</h1>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTransaksi">
        <i class="fas fa-plus fa-sm text-white-50"></i> Catat Panjar Baru
    </button>
</div>

<!-- Tabel Riwayat Piutang -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Panjar Pemasok (Petani)</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Pemasok / Petani</th>
                        <th>Nominal Panjar</th>
                        <th>Sisa Piutang</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $history->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($row['nama_pemasok']) ?></td>
                        <td>Rp <?= number_format($row['nominal_panjar'], 0, ',', '.') ?></td>
                        <td><b>Rp <?= number_format($row['sisa_piutang'], 0, ',', '.') ?></b></td>
                        <td>
                            <span class="badge badge-<?= $row['status'] == 'Lunas' ? 'success' : 'danger' ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if($row['status'] == 'Belum Lunas'): ?>
                            <a href="?lunas=<?= $row['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Yakin ingin menandai piutang ini sebagai LUNAS SEPENUHNYA? (Sistem akan membuat jurnal kas masuk)');">
                                <i class="fas fa-check"></i> Pelunasan Penuh
                            </a>
                            <?php else: ?>
                            <button class="btn btn-sm btn-secondary" disabled>Selesai</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($history->num_rows == 0): ?>
                    <tr><td colspan="6" class="text-center">Belum ada data panjar / piutang</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Piutang -->
<div class="modal fade" id="modalTransaksi" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Catat Panjar (Piutang) Pemasok</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tanggal Pemberian Panjar</label>
                        <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Pilih Pemasok / Petani</label>
                        <select class="form-control" name="id_pemasok" required>
                            <option value="">-- Pilih Pemasok --</option>
                            <?php while($p = $pemasok->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>"><?= $p['nama_pemasok'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nominal Panjar (Rp)</label>
                        <input type="number" step="0.01" class="form-control font-weight-bold text-danger" name="nominal_panjar" required>
                    </div>
                    <div class="alert alert-warning mb-0">
                        <small><i class="fas fa-exclamation-triangle"></i> Jurnal pengeluaran Kas akan otomatis terbentuk setelah disimpan.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_transaksi" class="btn btn-primary">Simpan Panjar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once '../layouts/footer.php'; ?>
