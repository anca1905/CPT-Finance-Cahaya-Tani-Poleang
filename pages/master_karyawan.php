<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Proses Hapus Data
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM tb_karyawan WHERE id = $id");
    echo "<script>window.location.href='master_karyawan.php';</script>";
    exit;
}

// Proses Tambah / Edit Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_karyawan = $conn->real_escape_string($_POST['nama_karyawan']);
    $jabatan = $conn->real_escape_string($_POST['jabatan']);
    $no_hp = $conn->real_escape_string($_POST['no_hp']);

    if (isset($_POST['add'])) {
        $conn->query("INSERT INTO tb_karyawan (nama_karyawan, jabatan, no_hp) VALUES ('$nama_karyawan', '$jabatan', '$no_hp')");
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE tb_karyawan SET nama_karyawan='$nama_karyawan', jabatan='$jabatan', no_hp='$no_hp' WHERE id=$id");
    }
    echo "<script>window.location.href='master_karyawan.php';</script>";
    exit;
}

// Ambil Data Karyawan
$result = $conn->query("SELECT * FROM tb_karyawan ORDER BY id DESC");
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Data Karyawan</h1>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
        <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Karyawan
    </button>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Karyawan (Staff / Pekerja)</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">No</th>
                        <th>Nama Karyawan</th>
                        <th>Jabatan</th>
                        <th>No. Handphone</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $karyawan_data = [];
                    while ($row = $result->fetch_assoc()):
                        $karyawan_data[] = $row;
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($row['jabatan']) ?></span></td>
                            <td><?= htmlspecialchars($row['no_hp']) ?></td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-warning mr-1" data-toggle="modal" data-target="#editModal<?= $row['id'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus karyawan ini?');">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (empty($karyawan_data)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data karyawan</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach ($karyawan_data as $row): ?>
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Data Karyawan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="form-group">
                            <label>Nama Karyawan</label>
                            <input type="text" class="form-control" name="nama_karyawan" value="<?= htmlspecialchars($row['nama_karyawan']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Jabatan</label>
                            <input type="text" class="form-control" name="jabatan" value="<?= htmlspecialchars($row['jabatan']) ?>">
                        </div>
                        <div class="form-group">
                            <label>No. Handphone</label>
                            <input type="text" class="form-control" name="no_hp" value="<?= htmlspecialchars($row['no_hp']) ?>">
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
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Karyawan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Karyawan</label>
                        <input type="text" class="form-control" name="nama_karyawan" required>
                    </div>
                    <div class="form-group">
                        <label>Jabatan</label>
                        <input type="text" class="form-control" name="jabatan" placeholder="Contoh: Supir, Kuli Panggul, dsb.">
                    </div>
                    <div class="form-group">
                        <label>No. Handphone</label>
                        <input type="text" class="form-control" name="no_hp">
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