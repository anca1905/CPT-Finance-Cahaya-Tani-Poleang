<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Proses Hapus Data
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Prevent deleting the currently logged in user (optional but good practice)
    if ($id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM tb_users WHERE id = $id");
    }
    echo "<script>window.location.href='master_user.php';</script>";
    exit;
}

// Proses Tambah / Edit Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = $conn->real_escape_string($_POST['nama_lengkap']);
    $username = $conn->real_escape_string($_POST['username']);
    $role = $conn->real_escape_string($_POST['role']);
    $password_input = $_POST['password'];

    if (isset($_POST['add'])) {
        $password = password_hash($password_input, PASSWORD_DEFAULT);
        $conn->query("INSERT INTO tb_users (nama_lengkap, username, password, role) VALUES ('$nama_lengkap', '$username', '$password', '$role')");
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        if (!empty($password_input)) {
            $password = password_hash($password_input, PASSWORD_DEFAULT);
            $conn->query("UPDATE tb_users SET nama_lengkap='$nama_lengkap', username='$username', password='$password', role='$role' WHERE id=$id");
        } else {
            $conn->query("UPDATE tb_users SET nama_lengkap='$nama_lengkap', username='$username', role='$role' WHERE id=$id");
        }
    }
    echo "<script>window.location.href='master_user.php';</script>";
    exit;
}

// Ambil Data User
$result = $conn->query("SELECT * FROM tb_users ORDER BY id DESC");
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Data Pengguna</h1>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
        <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Pengguna
    </button>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Pengguna Sistem</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">No</th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Hak Akses (Role)</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $user_data = [];
                    while ($row = $result->fetch_assoc()):
                        $user_data[] = $row;
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td>
                                <?php
                                if ($row['role'] == 'Admin') {
                                    echo '<span class="badge badge-danger">Admin</span>';
                                } elseif ($row['role'] == 'Pimpinan') {
                                    echo '<span class="badge badge-success">Pimpinan</span>';
                                } else {
                                    echo '<span class="badge badge-info">Keuangan</span>';
                                }
                                ?>
                            </td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-warning mr-1" data-toggle="modal" data-target="#editModal<?= $row['id'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus user ini?');">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (empty($user_data)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data user</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach ($user_data as $row): ?>
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Data Pengguna</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_lengkap" value="<?= htmlspecialchars($row['nama_lengkap']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($row['username']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Password <small class="text-danger">(Kosongkan jika tidak ingin diubah)</small></label>
                            <input type="password" class="form-control" name="password" placeholder="Password baru">
                        </div>
                        <div class="form-group">
                            <label>Role / Hak Akses</label>
                            <select class="form-control" name="role" required>
                                <option value="Admin" <?= $row['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="Keuangan" <?= $row['role'] == 'Keuangan' ? 'selected' : '' ?>>Keuangan</option>
                                <option value="Pimpinan" <?= $row['role'] == 'Pimpinan' ? 'selected' : '' ?>>Pimpinan</option>
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
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Pengguna</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Role / Hak Akses</label>
                        <select class="form-control" name="role" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="Admin">Admin</option>
                            <option value="Keuangan">Keuangan</option>
                            <option value="Pimpinan">Pimpinan</option>
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