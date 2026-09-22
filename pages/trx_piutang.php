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

// Proses Pelunasan Piutang (Sebagian / Penuh)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bayar_piutang'])) {
    $id_piutang = (int)$_POST['id_piutang'];
    $nominal_bayar = (float)$_POST['nominal_bayar'];

    if ($nominal_bayar > 0) {
        $q = $conn->query("
            SELECT pt.*, p.nama_pemasok 
            FROM tb_piutang_petani pt 
            JOIN tb_pemasok p ON pt.id_pemasok = p.id 
            WHERE pt.id = $id_piutang AND pt.status = 'Belum Lunas'
        ");

        if ($q->num_rows > 0) {
            $row = $q->fetch_assoc();

            // Cek jika bayar melebihi sisa
            if ($nominal_bayar > $row['sisa_piutang']) {
                echo "<script>alert('Nominal bayar melebihi sisa piutang!'); window.history.back();</script>";
                exit;
            }

            $no_transaksi = 'LUNAS-P-' . date('YmdHis');
            $tanggal_lunas = date('Y-m-d');
            $nama_pemasok = $row['nama_pemasok'];

            $sisa_baru = $row['sisa_piutang'] - $nominal_bayar;
            $status_baru = ($sisa_baru <= 0) ? 'Lunas' : 'Belum Lunas';

            $conn->begin_transaction();
            try {
                // Jurnal Pelunasan Piutang
                $deskripsi = "Pembayaran Piutang dari Pemasok: $nama_pemasok (Ref: $no_transaksi)";
                $conn->query("INSERT INTO tb_jurnal_umum (no_referensi, tanggal, deskripsi, total_debit, total_kredit) VALUES ('$no_transaksi', '$tanggal_lunas', '$deskripsi', $nominal_bayar, $nominal_bayar)");
                $id_jurnal_lunas = $conn->insert_id;

                // Debit: 111 (Kas)
                $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal_lunas, '111', 'Debit', $nominal_bayar)");
                // Kredit: 112 (Piutang CTP)
                $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal_lunas, '112', 'Kredit', $nominal_bayar)");

                // Update Sisa & Status
                $conn->query("UPDATE tb_piutang_petani SET sisa_piutang = $sisa_baru, status = '$status_baru' WHERE id = $id_piutang");

                $conn->commit();
                echo "<script>alert('Pembayaran piutang berhasil dicatat!'); window.location.href='trx_piutang.php';</script>";
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                echo "<script>alert('Gagal melunasi: " . $e->getMessage() . "');</script>";
            }
        }
    } else {
        echo "<script>alert('Nominal harus lebih dari 0');</script>";
    }
}

// Proses Pelunasan Piutang (Rekap)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bayar_rekap_piutang'])) {
    $id_pemasok = (int)$_POST['id_pemasok'];
    $nominal_bayar = (float)$_POST['nominal_bayar'];

    if ($nominal_bayar > 0) {
        $q = $conn->query("
            SELECT pt.*, p.nama_pemasok 
            FROM tb_piutang_petani pt 
            JOIN tb_pemasok p ON pt.id_pemasok = p.id 
            WHERE pt.id_pemasok = $id_pemasok AND pt.status = 'Belum Lunas'
            ORDER BY pt.tanggal ASC, pt.id ASC
        ");

        if ($q->num_rows > 0) {
            $conn->begin_transaction();
            try {
                $sisa_bayar = $nominal_bayar;
                $nama_pemasok = '';
                
                while($row = $q->fetch_assoc()) {
                    if($sisa_bayar <= 0) break;
                    
                    $nama_pemasok = $row['nama_pemasok'];
                    $bayar_untuk_ini = min($sisa_bayar, $row['sisa_piutang']);
                    
                    $sisa_baru = $row['sisa_piutang'] - $bayar_untuk_ini;
                    $status_baru = ($sisa_baru <= 0) ? 'Lunas' : 'Belum Lunas';
                    
                    $conn->query("UPDATE tb_piutang_petani SET sisa_piutang = $sisa_baru, status = '$status_baru' WHERE id = " . $row['id']);
                    
                    $sisa_bayar -= $bayar_untuk_ini;
                }
                
                // Jurnal Pelunasan
                $no_transaksi = 'LUNAS-P-REKAP-' . date('YmdHis');
                $tanggal_lunas = date('Y-m-d');
                $deskripsi = "Pembayaran Piutang Rekap dari Pemasok: $nama_pemasok (Ref: $no_transaksi)";
                
                $conn->query("INSERT INTO tb_jurnal_umum (no_referensi, tanggal, deskripsi, total_debit, total_kredit) VALUES ('$no_transaksi', '$tanggal_lunas', '$deskripsi', $nominal_bayar, $nominal_bayar)");
                $id_jurnal_lunas = $conn->insert_id;

                $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal_lunas, '111', 'Debit', $nominal_bayar)");
                $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal_lunas, '112', 'Kredit', $nominal_bayar)");

                $conn->commit();
                echo "<script>alert('Pembayaran piutang berhasil dicatat!'); window.location.href='trx_piutang.php';</script>";
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                echo "<script>alert('Gagal melunasi: " . $e->getMessage() . "');</script>";
            }
        }
    } else {
        echo "<script>alert('Nominal harus lebih dari 0');</script>";
    }
}


// Ambil Referensi Data
$pemasok = $conn->query("SELECT * FROM tb_pemasok ORDER BY nama_pemasok ASC");

// Rekapitulasi Total Panjar / Piutang Per Pemasok (menjumlahkan semua transaksi yg sama)
$rekap_panjar = $conn->query("
    SELECT 
        p.id as id_pemasok,
        p.nama_pemasok,
        COUNT(pt.id) as jumlah_transaksi,
        SUM(pt.nominal_panjar) as total_panjar,
        SUM(pt.sisa_piutang) as total_sisa_piutang,
        SUM(CASE WHEN pt.status = 'Belum Lunas' THEN 1 ELSE 0 END) as jml_belum_lunas
    FROM tb_piutang_petani pt
    JOIN tb_pemasok p ON pt.id_pemasok = p.id
    WHERE pt.status = 'Belum Lunas'
    GROUP BY p.id, p.nama_pemasok
    HAVING total_sisa_piutang > 0
    ORDER BY total_sisa_piutang DESC
");

// Hitung grand total piutang belum lunas
$gt_piutang_q = $conn->query("SELECT SUM(sisa_piutang) as grand_total FROM tb_piutang_petani WHERE status = 'Belum Lunas'");
$grand_total_piutang = $gt_piutang_q->fetch_assoc()['grand_total'] ?? 0;

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

<?php if ($rekap_panjar && $rekap_panjar->num_rows > 0): ?>
<!-- NOTIFIKASI: Rekapitulasi Total Panjar Per Pemasok -->
<div class="card border-left-warning shadow mb-4">
    <div class="card-header py-3 d-flex align-items-center" style="background: linear-gradient(135deg, #d35400 0%, #f39c12 100%);">
        <i class="fas fa-clipboard-list text-white mr-2"></i>
        <h6 class="m-0 font-weight-bold text-white">
            <i class="fas fa-hand-holding-usd mr-1"></i>
            Rekap Total Panjar / Piutang Pemasok Belum Lunas
        </h6>
        <span class="badge badge-light ml-auto" style="font-size:0.9rem;">
            Total: <strong>Rp <?= number_format($grand_total_piutang, 0, ',', '.') ?></strong>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead style="background:#fef9e7;">
                    <tr>
                        <th class="pl-3" style="width:40px;">#</th>
                        <th><i class="fas fa-user mr-1 text-warning"></i> Nama Pemasok / Petani</th>
                        <th class="text-center">Jml. Transaksi Panjar</th>
                        <th class="text-right">Total Panjar Diberikan</th>
                        <th class="text-right">Total Sisa Piutang</th>
                        <th class="text-center pr-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no_r = 1;
                    $modals_rekap = '';
                    while ($rp = $rekap_panjar->fetch_assoc()):
                        // Modal detail riwayat panjar per pemasok
                        $modals_rekap .= '
                        <div class="modal fade" id="modalDetailRekap' . $rp['id_pemasok'] . '" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header bg-info text-white">
                                        <h5 class="modal-title">Detail Panjar Pemasok: ' . htmlspecialchars($rp['nama_pemasok']) . '</h5>
                                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Tanggal</th>
                                                        <th>Nominal Panjar</th>
                                                        <th>Sisa Piutang</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                        
                        // Fetch details for this pemasok
                        $q_det = $conn->query("SELECT * FROM tb_piutang_petani WHERE id_pemasok = " . $rp['id_pemasok'] . " ORDER BY tanggal DESC, id DESC");
                        while($det = $q_det->fetch_assoc()) {
                            $modals_rekap .= '<tr>
                                <td>' . date('d/m/Y', strtotime($det['tanggal'])) . '</td>
                                <td>Rp ' . number_format($det['nominal_panjar'], 0, ',', '.') . '</td>
                                <td>Rp ' . number_format($det['sisa_piutang'], 0, ',', '.') . '</td>
                                <td><span class="badge badge-' . ($det['status'] == 'Lunas' ? 'success' : 'danger') . '">' . htmlspecialchars($det['status']) . '</span></td>
                            </tr>';
                        }
                        
                        $modals_rekap .= '      </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>';

                        // Modal Bayar Rekap
                        $modals_rekap .= '
                        <div class="modal fade" id="modalBayarRekap' . $rp['id_pemasok'] . '" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <form method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">Pembayaran Total Piutang / Panjar</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id_pemasok" value="' . $rp['id_pemasok'] . '">
                                            <div class="form-group">
                                                <label>Nama Pemasok</label>
                                                <input type="text" class="form-control" value="' . htmlspecialchars($rp['nama_pemasok']) . '" readonly>
                                            </div>
                                            <div class="form-group">
                                                <label>Total Sisa Piutang Saat Ini</label>
                                                <input type="text" class="form-control" value="Rp ' . number_format($rp['total_sisa_piutang'], 0, ',', '.') . '" readonly>
                                            </div>
                                            <div class="form-group">
                                                <label>Jumlah Bayar (Rp)</label>
                                                <input type="number" class="form-control" name="nominal_bayar" max="' . $rp['total_sisa_piutang'] . '" required>
                                                <small class="text-muted">Masukkan nominal yang dibayar. Akan mengurangi sisa piutang secara berurutan dari yang paling awal.</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                            <button type="submit" name="bayar_rekap_piutang" class="btn btn-success">Simpan Pembayaran</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>';

                    ?>
                    <tr>
                        <td class="pl-3"><?= $no_r++ ?></td>
                        <td>
                            <span class="font-weight-bold"><?= htmlspecialchars($rp['nama_pemasok']) ?></span>
                            <?php if ($rp['jumlah_transaksi'] > 1): ?>
                            <span class="badge badge-info ml-1" title="Pemasok ini memiliki <?= $rp['jumlah_transaksi'] ?> catatan panjar">
                                <i class="fas fa-layer-group"></i> <?= $rp['jumlah_transaksi'] ?>x panjar
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-<?= $rp['jml_belum_lunas'] > 0 ? 'danger' : 'success' ?>">
                                <?= $rp['jml_belum_lunas'] ?> belum lunas
                            </span>
                        </td>
                        <td class="text-right">
                            Rp <?= number_format($rp['total_panjar'], 0, ',', '.') ?>
                        </td>
                        <td class="text-right">
                            <strong class="text-danger">Rp <?= number_format($rp['total_sisa_piutang'], 0, ',', '.') ?></strong>
                        </td>
                        <td class="text-center pr-3">
                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#modalDetailRekap<?= $rp['id_pemasok'] ?>" title="Detail"><i class="fas fa-list"></i> Detail</button>
                            <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalBayarRekap<?= $rp['id_pemasok'] ?>" title="Bayar"><i class="fas fa-money-bill-wave"></i> Bayar</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot style="background:#fef9e7;">
                    <tr>
                        <td colspan="4" class="text-right font-weight-bold pl-3">Grand Total Sisa Piutang Semua Pemasok:</td>
                        <td class="text-right font-weight-bold text-danger" style="font-size:1.05rem;">
                            Rp <?= number_format($grand_total_piutang, 0, ',', '.') ?>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-success shadow mb-4">
    <i class="fas fa-check-circle mr-2"></i>
    <strong>Tidak ada piutang aktif!</strong> Semua panjar pemasok sudah lunas.
</div>
<?php endif; ?>

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
                    <?php
                    $modals = '';
                    while ($row = $history->fetch_assoc()):
                    ?>
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
                                <?php if ($row['status'] == 'Belum Lunas'):
                                    $modals .= '
                                <div class="modal fade" id="modalBayar' . $row['id'] . '" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <form method="POST">
                                            <div class="modal-content">
                                                <div class="modal-header bg-success text-white">
                                                    <h5 class="modal-title">Pembayaran Piutang / Panjar</h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_piutang" value="' . $row['id'] . '">
                                                    <div class="form-group">
                                                        <label>Nama Pemasok</label>
                                                        <input type="text" class="form-control" value="' . htmlspecialchars($row['nama_pemasok']) . '" readonly>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Sisa Piutang Saat Ini</label>
                                                        <input type="text" class="form-control" value="Rp ' . number_format($row['sisa_piutang'], 0, ',', '.') . '" readonly>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Jumlah Bayar (Rp)</label>
                                                        <input type="number" class="form-control" name="nominal_bayar" max="' . $row['sisa_piutang'] . '" required>
                                                        <small class="text-muted">Masukkan nominal yang dibayar. Otomatis lunas jika dibayar penuh.</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                    <button type="submit" name="bayar_piutang" class="btn btn-success">Simpan Pembayaran</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>';
                                ?>
                                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalBayar<?= $row['id'] ?>">
                                        <i class="fas fa-hand-holding-usd"></i> Bayar
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>Selesai</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($history->num_rows == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center">Belum ada data panjar / piutang</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $modals ?? '' ?>
<?= $modals_rekap ?? '' ?>

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
                            <?php while ($p = $pemasok->fetch_assoc()): ?>
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