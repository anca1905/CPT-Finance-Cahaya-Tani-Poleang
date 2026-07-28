<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Proses Hapus Data
if (isset($_GET['delete'])) {
    $kode_akun = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM tb_akun WHERE kode_akun = '$kode_akun'");
    echo "<script>window.location.href='master_akun.php';</script>";
    exit;
}

// Proses Tambah / Edit Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_akun = $conn->real_escape_string($_POST['kode_akun']);
    $nama_akun = $conn->real_escape_string($_POST['nama_akun']);
    $tipe = $conn->real_escape_string($_POST['tipe']);
    $saldo_normal = $conn->real_escape_string($_POST['saldo_normal']);

    if (isset($_POST['add'])) {
        $conn->query("INSERT INTO tb_akun (kode_akun, nama_akun, tipe, saldo_normal) VALUES ('$kode_akun', '$nama_akun', '$tipe', '$saldo_normal')");
    } elseif (isset($_POST['edit'])) {
        $old_kode_akun = $conn->real_escape_string($_POST['old_kode_akun']);
        $conn->query("UPDATE tb_akun SET kode_akun='$kode_akun', nama_akun='$nama_akun', tipe='$tipe', saldo_normal='$saldo_normal' WHERE kode_akun='$old_kode_akun'");
    }
    echo "<script>window.location.href='master_akun.php';</script>";
    exit;
}

// Ambil Data Akun
$result = $conn->query("SELECT * FROM tb_akun ORDER BY kode_akun ASC");
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Data Akun</h1>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
        <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Akun
    </button>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Akun (Chart of Accounts)</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Kode Akun</th>
                        <th>Nama Akun</th>
                        <th>Tipe</th>
                        <th>Saldo Normal</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $akun_data = [];
                    while ($row = $result->fetch_assoc()):
                        $akun_data[] = $row;
                        $safe_id = md5($row['kode_akun']);
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['kode_akun']) ?></td>
                            <td><?= htmlspecialchars($row['nama_akun']) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($row['tipe']) ?></span></td>
                            <td><?= htmlspecialchars($row['saldo_normal']) ?></td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-warning mr-1" data-toggle="modal" data-target="#editModal<?= $safe_id ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="?delete=<?= $row['kode_akun'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus akun ini?');">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (empty($akun_data)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data akun</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach ($akun_data as $row): $safe_id = md5($row['kode_akun']); ?>
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal<?= $safe_id ?>" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Data Akun</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="old_kode_akun" value="<?= $row['kode_akun'] ?>">
                        <div class="form-group">
                            <label>Kode Akun</label>
                            <input type="text" class="form-control" name="kode_akun" value="<?= $row['kode_akun'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nama Akun</label>
                            <input type="text" class="form-control" name="nama_akun" value="<?= htmlspecialchars($row['nama_akun']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tipe</label>
                            <select class="form-control" name="tipe" required>
                                <option value="Aset" <?= $row['tipe'] == 'Aset' ? 'selected' : '' ?>>Aset</option>
                                <option value="Kewajiban" <?= $row['tipe'] == 'Kewajiban' ? 'selected' : '' ?>>Kewajiban</option>
                                <option value="Ekuitas" <?= $row['tipe'] == 'Ekuitas' ? 'selected' : '' ?>>Ekuitas</option>
                                <option value="Pendapatan" <?= $row['tipe'] == 'Pendapatan' ? 'selected' : '' ?>>Pendapatan</option>
                                <option value="Beban" <?= $row['tipe'] == 'Beban' ? 'selected' : '' ?>>Beban</option>
                                <option value="HPP" <?= $row['tipe'] == 'HPP' ? 'selected' : '' ?>>HPP</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Saldo Normal</label>
                            <select class="form-control" name="saldo_normal" required>
                                <option value="Debit" <?= $row['saldo_normal'] == 'Debit' ? 'selected' : '' ?>>Debit</option>
                                <option value="Kredit" <?= $row['saldo_normal'] == 'Kredit' ? 'selected' : '' ?>>Kredit</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<!-- Add Modal -->
<div class="modal" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Akun</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Kode Akun</label>
                        <input type="text" class="form-control" name="kode_akun" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Akun</label>
                        <input type="text" class="form-control" name="nama_akun" required>
                    </div>
                    <div class="form-group">
                        <label>Tipe</label>
                        <select class="form-control" name="tipe" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="Aset">Aset</option>
                            <option value="Kewajiban">Kewajiban</option>
                            <option value="Ekuitas">Ekuitas</option>
                            <option value="Pendapatan">Pendapatan</option>
                            <option value="Beban">Beban</option>
                            <option value="HPP">HPP</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Saldo Normal</label>
                        <select class="form-control" name="saldo_normal" required>
                            <option value="">-- Pilih Saldo Normal --</option>
                            <option value="Debit">Debit</option>
                            <option value="Kredit">Kredit</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="add" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once '../layouts/footer.php'; ?>