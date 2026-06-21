<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Proses Simpan Jurnal Manual
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_jurnal'])) {
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $deskripsi = $conn->real_escape_string($_POST['deskripsi']);
    $no_referensi = 'MAN-' . date('YmdHis');
    
    $kode_akuns = $_POST['kode_akun'] ?? [];
    $posisis = $_POST['posisi'] ?? [];
    $nominals = $_POST['nominal'] ?? [];
    
    $total_debit = 0;
    $total_kredit = 0;
    
    // Validasi Debit dan Kredit
    for ($i = 0; $i < count($kode_akuns); $i++) {
        $n = (float)$nominals[$i];
        if ($posisis[$i] == 'Debit') $total_debit += $n;
        else $total_kredit += $n;
    }

    if (count($kode_akuns) < 2) {
        echo "<script>alert('Jurnal minimal harus memiliki 2 akun (Debit & Kredit).');</script>";
    } elseif ($total_debit != $total_kredit) {
        echo "<script>alert('Gagal! Total Debit (Rp ".number_format($total_debit).") dan Kredit (Rp ".number_format($total_kredit).") tidak balance.');</script>";
    } else {
        $conn->begin_transaction();
        try {
            $conn->query("INSERT INTO tb_jurnal_umum (no_referensi, tanggal, deskripsi, total_debit, total_kredit) VALUES ('$no_referensi', '$tanggal', '$deskripsi', $total_debit, $total_kredit)");
            $id_jurnal = $conn->insert_id;
            
            for ($i = 0; $i < count($kode_akuns); $i++) {
                $akun = $conn->real_escape_string($kode_akuns[$i]);
                $pos = $conn->real_escape_string($posisis[$i]);
                $nom = (float)$nominals[$i];
                $conn->query("INSERT INTO tb_jurnal_detail (id_jurnal, kode_akun, posisi, nominal) VALUES ($id_jurnal, '$akun', '$pos', $nom)");
            }
            
            $conn->commit();
            echo "<script>alert('Jurnal Manual Berhasil Disimpan!'); window.location.href='jurnal_umum.php';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Gagal menyimpan jurnal: " . $e->getMessage() . "');</script>";
        }
    }
}

// Ambil Referensi Akun
$akun_list = $conn->query("SELECT * FROM tb_akun ORDER BY kode_akun ASC");

// Pagination dan Filter Jurnal
$limit = 20; // 20 transaksi jurnal per halaman
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $limit;

$total_jurnal_q = $conn->query("SELECT COUNT(*) as t FROM tb_jurnal_umum WHERE jenis_jurnal = 'Umum'");
$total_jurnal = $total_jurnal_q->fetch_assoc()['t'];
$total_pages = ceil($total_jurnal / $limit);

$jurnal_umum = $conn->query("SELECT * FROM tb_jurnal_umum WHERE jenis_jurnal = 'Umum' ORDER BY tanggal DESC, id DESC LIMIT $limit OFFSET $offset");
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Jurnal Umum</h1>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalJurnal">
        <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Jurnal Manual
    </button>
</div>

<!-- Tabel Jurnal -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Riwayat Jurnal Umum (Semua Transaksi)</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                <thead class="thead-light text-center">
                    <tr>
                        <th width="100">Tanggal</th>
                        <th width="150">No. Ref</th>
                        <th>Keterangan / Nama Akun</th>
                        <th width="150">Ref (Akun)</th>
                        <th width="150">Debit</th>
                        <th width="150">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    while($ju = $jurnal_umum->fetch_assoc()): 
                        $id_j = $ju['id'];
                        $details = $conn->query("SELECT jd.*, a.nama_akun FROM tb_jurnal_detail jd JOIN tb_akun a ON jd.kode_akun = a.kode_akun WHERE jd.id_jurnal = $id_j ORDER BY jd.posisi ASC"); // Debit dulu baru Kredit
                    ?>
                    <!-- Baris Header Transaksi -->
                    <tr class="bg-light font-weight-bold">
                        <td class="text-center align-middle" rowspan="<?= $details->num_rows + 1 ?>"><?= date('d/m/Y', strtotime($ju['tanggal'])) ?></td>
                        <td class="text-center align-middle" rowspan="<?= $details->num_rows + 1 ?>">
                            <span class="text-primary"><?= $ju['no_referensi'] ?></span>
                        </td>
                        <td colspan="4" class="text-muted"><i><?= htmlspecialchars($ju['deskripsi']) ?></i></td>
                    </tr>
                    
                    <!-- Baris Detail Akun -->
                    <?php while($d = $details->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php 
                                if($d['posisi'] == 'Debit') {
                                    echo "<b>" . htmlspecialchars($d['nama_akun']) . "</b>";
                                } else {
                                    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i>" . htmlspecialchars($d['nama_akun']) . "</i>";
                                }
                            ?>
                        </td>
                        <td class="text-center"><?= $d['kode_akun'] ?></td>
                        <td class="text-right"><?= $d['posisi'] == 'Debit' ? 'Rp ' . number_format($d['nominal'], 0, ',', '.') : '-' ?></td>
                        <td class="text-right"><?= $d['posisi'] == 'Kredit' ? 'Rp ' . number_format($d['nominal'], 0, ',', '.') : '-' ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endwhile; ?>
                    
                    <?php if($jurnal_umum->num_rows == 0): ?>
                    <tr><td colspan="6" class="text-center">Belum ada transaksi di jurnal umum</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $page-1 ?>" tabindex="-1">Sebelumnya</a>
                </li>
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                    <a class="page-link" href="?p=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $page+1 ?>">Selanjutnya</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Tambah Jurnal Manual -->
<div class="modal fade" id="modalJurnal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <form method="POST" id="formJurnal">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Input Jurnal Manual</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Tanggal Transaksi</label>
                            <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group col-md-9">
                            <label>Keterangan Jurnal / Deskripsi</label>
                            <input type="text" class="form-control" name="deskripsi" placeholder="Contoh: Setoran Modal Awal Pemilik" required>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="font-weight-bold">Rincian Akun (Debit & Kredit Harus Seimbang)</h6>
                    
                    <table class="table table-bordered" id="tabelAkun">
                        <thead class="thead-light">
                            <tr>
                                <th>Pilih Akun</th>
                                <th width="150">Posisi</th>
                                <th width="200">Nominal (Rp)</th>
                                <th width="50">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select class="form-control" name="kode_akun[]" required>
                                        <option value="">-- Pilih Akun --</option>
                                        <?php 
                                        $akun_list->data_seek(0);
                                        while($a = $akun_list->fetch_assoc()): 
                                        ?>
                                            <option value="<?= $a['kode_akun'] ?>">[<?= $a['kode_akun'] ?>] <?= $a['nama_akun'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control posisi" name="posisi[]" required>
                                        <option value="Debit">Debit</option>
                                        <option value="Kredit">Kredit</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" class="form-control nominal" name="nominal[]" value="0" required>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm hapus-baris"><i class="fas fa-times"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <select class="form-control" name="kode_akun[]" required>
                                        <option value="">-- Pilih Akun --</option>
                                        <?php 
                                        $akun_list->data_seek(0);
                                        while($a = $akun_list->fetch_assoc()): 
                                        ?>
                                            <option value="<?= $a['kode_akun'] ?>">[<?= $a['kode_akun'] ?>] <?= $a['nama_akun'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control posisi" name="posisi[]" required>
                                        <option value="Kredit" selected>Kredit</option>
                                        <option value="Debit">Debit</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" class="form-control nominal" name="nominal[]" value="0" required>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm hapus-baris"><i class="fas fa-times"></i></button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-right">Total Debit:</th>
                                <th colspan="2" id="sumDebit" class="text-primary font-weight-bold">0</th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-right">Total Kredit:</th>
                                <th colspan="2" id="sumKredit" class="text-danger font-weight-bold">0</th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-right">Status:</th>
                                <th colspan="2" id="statusBalance" class="text-danger font-weight-bold">TIDAK BALANCE</th>
                            </tr>
                        </tfoot>
                    </table>
                    <button type="button" class="btn btn-success btn-sm" id="tambahBaris">
                        <i class="fas fa-plus"></i> Tambah Akun
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_jurnal" id="btnSimpan" class="btn btn-primary" disabled>Simpan Jurnal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabelBody = document.querySelector('#tabelAkun tbody');
    const tambahBtn = document.getElementById('tambahBaris');
    
    const barisTemplate = tabelBody.querySelector('tr').cloneNode(true);
    barisTemplate.querySelector('input[name="nominal[]"]').value = 0;
    barisTemplate.querySelector('select[name="kode_akun[]"]').value = '';
    barisTemplate.querySelector('select[name="posisi[]"]').value = 'Debit';
    
    function hitungTotal() {
        let sumD = 0;
        let sumK = 0;
        
        const baris = tabelBody.querySelectorAll('tr');
        baris.forEach(tr => {
            const pos = tr.querySelector('.posisi').value;
            const nom = parseFloat(tr.querySelector('.nominal').value) || 0;
            if(pos === 'Debit') sumD += nom;
            else sumK += nom;
        });
        
        document.getElementById('sumDebit').innerText = 'Rp ' + sumD.toLocaleString('id-ID');
        document.getElementById('sumKredit').innerText = 'Rp ' + sumK.toLocaleString('id-ID');
        
        const stat = document.getElementById('statusBalance');
        const btn = document.getElementById('btnSimpan');
        
        if (sumD === sumK && sumD > 0) {
            stat.innerText = 'BALANCE';
            stat.className = 'text-success font-weight-bold';
            btn.disabled = false;
        } else {
            stat.innerText = 'TIDAK BALANCE';
            stat.className = 'text-danger font-weight-bold';
            btn.disabled = true;
        }
    }
    
    tambahBtn.addEventListener('click', function() {
        const trBaru = barisTemplate.cloneNode(true);
        tabelBody.appendChild(trBaru);
        attachEvents();
    });
    
    function attachEvents() {
        const hapusBtns = document.querySelectorAll('.hapus-baris');
        hapusBtns.forEach(btn => {
            btn.onclick = function() {
                if(tabelBody.children.length > 2) {
                    this.closest('tr').remove();
                    hitungTotal();
                } else {
                    alert('Minimal 2 akun (Debit & Kredit) harus ada.');
                }
            };
        });
        
        const inputs = document.querySelectorAll('.posisi, .nominal');
        inputs.forEach(inp => {
            inp.removeEventListener('input', hitungTotal);
            inp.addEventListener('input', hitungTotal);
            inp.removeEventListener('change', hitungTotal);
            inp.addEventListener('change', hitungTotal);
        });
    }
    
    attachEvents();
    hitungTotal();
});
</script>

<?php require_once '../layouts/footer.php'; ?>
