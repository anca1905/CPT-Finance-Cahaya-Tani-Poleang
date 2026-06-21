<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Cek Role (Hanya Admin)
if ($_SESSION['role'] != 'Admin') {
    echo "<script>alert('Anda tidak memiliki akses!'); window.location.href='dashboard.php';</script>";
    exit;
}

$backup_dir = '../backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Proses Backup
if (isset($_POST['backup'])) {
    $filename = 'backup_ctp_' . date('Ymd_His') . '.sql';
    $filepath = $backup_dir . $filename;
    
    // Command untuk mysqldump (Pastikan mysqldump ada di PATH/Environment variable server)
    $host = 'localhost';
    $user = 'root'; // Sesuaikan username DB
    $pass = '';     // Sesuaikan password DB
    $dbname = 'db_ctp_finance';
    
    // Ini command standar untuk Laragon / XAMPP di windows
    $command = "mysqldump --user={$user} --password={$pass} --host={$host} {$dbname} > {$filepath}";
    
    // Execute command
    exec($command, $output, $return_var);
    
    if ($return_var === 0) {
        $conn->query("INSERT INTO tb_backup_db (nama_file, path_file) VALUES ('$filename', '$filepath')");
        $msg = "<div class='alert alert-success'>Backup berhasil! File: $filename</div>";
    } else {
        // Fallback jika mysqldump gagal (misal tidak ada di PATH)
        file_put_contents($filepath, "-- Backup manual gagal dijalankan via exec(). Harap set PATH mysqldump.");
        $conn->query("INSERT INTO tb_backup_db (nama_file, path_file) VALUES ('$filename', '$filepath')");
        $msg = "<div class='alert alert-warning'>Exec mysqldump gagal (kemungkinan masalah PATH). File dummy dibuat.</div>";
    }
}

// Proses Hapus Backup
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $q = $conn->query("SELECT * FROM tb_backup_db WHERE id = $id");
    if ($q->num_rows > 0) {
        $row = $q->fetch_assoc();
        if (file_exists($row['path_file'])) {
            unlink($row['path_file']);
        }
        $conn->query("DELETE FROM tb_backup_db WHERE id = $id");
    }
    echo "<script>window.location.href='maintenance.php';</script>";
    exit;
}

// Ambil Riwayat Backup
$result = $conn->query("SELECT * FROM tb_backup_db ORDER BY id DESC");
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Maintenance Database</h1>
</div>

<?php if(isset($msg)) echo $msg; ?>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Backup & Restore</h6>
            </div>
            <div class="card-body text-center">
                <div style="font-size: 5rem; color: #1fa299; margin-bottom: 20px;">
                    <i class="fas fa-database"></i>
                </div>
                <p>Amankan data keuangan Anda secara berkala. Proses ini akan menyimpan seluruh isi database ke dalam sebuah file SQL.</p>
                <form method="POST">
                    <button type="submit" name="backup" class="btn btn-primary btn-block shadow-sm">
                        <i class="fas fa-download fa-sm text-white-50"></i> Lakukan Backup Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Riwayat Backup</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th width="50">No</th>
                                <th>Nama File</th>
                                <th>Waktu Backup</th>
                                <th width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while ($row = $result->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nama_file']) ?></td>
                                <td><?= date('d M Y, H:i', strtotime($row['tanggal_backup'])) ?></td>
                                <td>
                                    <a href="<?= base_url('backups/' . $row['nama_file']) ?>" class="btn btn-sm btn-info" download>
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus riwayat backup ini?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($result->num_rows == 0): ?>
                            <tr>
                                <td colspan="4" class="text-center">Belum ada riwayat backup</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../layouts/footer.php'; ?>
