<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Proses Hapus Data
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM tb_barang WHERE id = $id");
    echo "<script>window.location.href='master_barang.php';</script>";
    exit;
}

// Proses Tambah / Edit Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_barang = $conn->real_escape_string($_POST['kode_barang']);
    $nama_barang = $conn->real_escape_string($_POST['nama_barang']);
    $kategori = $conn->real_escape_string($_POST['kategori']);
    $satuan = $conn->real_escape_string($_POST['satuan']);
    $stok_tersedia = (int)$_POST['stok_tersedia'];
    $harga_satuan = (float)$_POST['harga_satuan'];

    if (isset($_POST['add'])) {
        $conn->query("INSERT INTO tb_barang (kode_barang, nama_barang, kategori, satuan, stok_tersedia, harga_satuan) VALUES ('$kode_barang', '$nama_barang', '$kategori', '$satuan', $stok_tersedia, $harga_satuan)");
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE tb_barang SET kode_barang='$kode_barang', nama_barang='$nama_barang', kategori='$kategori', satuan='$satuan', stok_tersedia=$stok_tersedia, harga_satuan=$harga_satuan WHERE id=$id");
    }
    echo "<script>window.location.href='master_barang.php';</script>";
    exit;
}

// Ambil Data Barang
$result = $conn->query("SELECT * FROM tb_barang ORDER BY id DESC");
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Data Barang</h1>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
        <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Barang
    </button>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Barang (Item Master)</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">No</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th>Stok</th>
                        <th>Harga Satuan</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    while ($row = $result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['kode_barang']) ?></td>
                        <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td>
                            <span class="badge badge-<?= $row['kategori'] == 'Bahan Baku' ? 'info' : 'success' ?>">
                                <?= htmlspecialchars($row['kategori']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['satuan']) ?></td>
                        <td><b><?= $row['stok_tersedia'] ?></b></td>
                        <td>Rp <?= number_format($row['harga_satuan'], 0, ',', '.') ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editModal<?= $row['id'] ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus barang ini?');">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <form method="POST">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Data Barang</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label>Kode Barang</label>
                                                <input type="text" class="form-control" name="kode_barang" value="<?= htmlspecialchars($row['kode_barang']) ?>" required>
                                            </div>
                                            <div class="form-group col-md-8">
                                                <label>Nama Barang</label>
                                                <input type="text" class="form-control" name="nama_barang" value="<?= htmlspecialchars($row['nama_barang']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Kategori</label>
                                                <select class="form-control" name="kategori" required>
                                                    <option value="Bahan Baku" <?= $row['kategori'] == 'Bahan Baku' ? 'selected' : '' ?>>Bahan Baku</option>
                                                    <option value="Barang Jadi" <?= $row['kategori'] == 'Barang Jadi' ? 'selected' : '' ?>>Barang Jadi</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Satuan</label>
                                                <input type="text" class="form-control" name="satuan" value="<?= htmlspecialchars($row['satuan']) ?>" placeholder="Pcs, Kg, dsb" required>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Stok Tersedia</label>
                                                <input type="number" class="form-control" name="stok_tersedia" value="<?= $row['stok_tersedia'] ?>" required>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Harga Satuan</label>
                                                <input type="number" step="0.01" class="form-control" name="harga_satuan" value="<?= $row['harga_satuan'] ?>" required>
                                            </div>
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
                    <?php endwhile; ?>
                    <?php if($result->num_rows == 0): ?>
                    <tr>
                        <td colspan="8" class="text-center">Belum ada data barang</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Barang</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Kode Barang</label>
                            <input type="text" class="form-control" name="kode_barang" required>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Nama Barang</label>
                            <input type="text" class="form-control" name="nama_barang" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Kategori</label>
                            <select class="form-control" name="kategori" required>
                                <option value="">-- Pilih --</option>
                                <option value="Bahan Baku">Bahan Baku</option>
                                <option value="Barang Jadi">Barang Jadi</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Satuan</label>
                            <input type="text" class="form-control" name="satuan" placeholder="Pcs, Kg, Karung" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Stok Awal</label>
                            <input type="number" class="form-control" name="stok_tersedia" value="0" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Harga Satuan</label>
                            <input type="number" step="0.01" class="form-control" name="harga_satuan" value="0" required>
                        </div>
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
