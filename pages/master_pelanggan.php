<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Proses Hapus Data
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM tb_pelanggan WHERE id = $id");
    echo "<script>window.location.href='master_pelanggan.php';</script>";
    exit;
}

// Proses Tambah / Edit Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_pelanggan = $conn->real_escape_string($_POST['nama_pelanggan']);
    $no_hp = $conn->real_escape_string($_POST['no_hp']);
    $alamat = $conn->real_escape_string($_POST['alamat']);

    if (isset($_POST['add'])) {
        $conn->query("INSERT INTO tb_pelanggan (nama_pelanggan, no_hp, alamat) VALUES ('$nama_pelanggan', '$no_hp', '$alamat')");
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE tb_pelanggan SET nama_pelanggan='$nama_pelanggan', no_hp='$no_hp', alamat='$alamat' WHERE id=$id");
    }
    echo "<script>window.location.href='master_pelanggan.php';</script>";
    exit;
}

// Ambil Data Pelanggan
$result = $conn->query("SELECT * FROM tb_pelanggan ORDER BY id DESC");
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Data Pelanggan</h1>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
        <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Pelanggan
    </button>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Pelanggan</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">No</th>
                        <th>Nama Pelanggan</th>
                        <th>No. Handphone</th>
                        <th>Alamat</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $pelanggan_data = [];
                    while ($row = $result->fetch_assoc()):
                        $pelanggan_data[] = $row;
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                            <td><?= htmlspecialchars($row['no_hp']) ?></td>
                            <td><?= htmlspecialchars($row['alamat']) ?></td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-warning mr-1" data-toggle="modal" data-target="#editModal<?= $row['id'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus pelanggan ini?');">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (empty($pelanggan_data)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data pelanggan</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach ($pelanggan_data as $row): ?>
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Data Pelanggan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="form-group">
                            <label>Nama Pelanggan</label>
                            <input type="text" class="form-control" name="nama_pelanggan" value="<?= htmlspecialchars($row['nama_pelanggan']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>No. Handphone</label>
                            <input type="text" class="form-control" name="no_hp" value="<?= htmlspecialchars($row['no_hp']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea class="form-control" name="alamat" rows="3"><?= htmlspecialchars($row['alamat']) ?></textarea>
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
                    <h5 class="modal-title">Tambah Data Pelanggan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Pelanggan</label>
                        <input type="text" class="form-control" name="nama_pelanggan" required>
                    </div>
                    <div class="form-group">
                        <label>No. Handphone</label>
                        <input type="text" class="form-control" name="no_hp">
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea class="form-control" name="alamat" rows="3"></textarea>
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