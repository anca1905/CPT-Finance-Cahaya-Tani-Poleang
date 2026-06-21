CREATE DATABASE IF NOT EXISTS db_ctp_finance;
USE db_ctp_finance;

-- 1. Tabel Users
CREATE TABLE tb_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Keuangan', 'Pimpinan') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default Users (password: 123456 - MD5 for simplicity in PHP Native, or BCRYPT. We will use password_hash() later, but for now let's insert MD5 or plaintext for test. Let's not insert users here, we'll create a seeder in PHP or insert a default admin now)
INSERT INTO tb_users (nama_lengkap, username, password, role) VALUES 
('Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin'), -- password
('Staff Keuangan', 'keuangan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Keuangan'),
('Pimpinan CTP', 'pimpinan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pimpinan');

-- 2. Tabel Akun (Chart of Accounts)
CREATE TABLE tb_akun (
    kode_akun VARCHAR(10) PRIMARY KEY,
    nama_akun VARCHAR(100) NOT NULL,
    tipe ENUM('Aset', 'Kewajiban', 'Ekuitas', 'Pendapatan', 'Beban', 'HPP') NOT NULL,
    saldo_normal ENUM('Debit', 'Kredit') NOT NULL
);

-- Insert Data Akun Wajib
INSERT INTO tb_akun (kode_akun, nama_akun, tipe, saldo_normal) VALUES
('111', 'Kas', 'Aset', 'Debit'),
('112', 'Piutang CTP', 'Aset', 'Debit'),
('113', 'Persediaan Kelapa', 'Aset', 'Debit'),
('211', 'Utang Usaha', 'Kewajiban', 'Kredit'),
('311', 'Modal', 'Ekuitas', 'Kredit'),
('411', 'Penjualan', 'Pendapatan', 'Kredit'),
('511', 'Pembelian', 'HPP', 'Debit'),
('512', 'Biaya Operasional', 'Beban', 'Debit'),
('513', 'Upah Karyawan', 'Beban', 'Debit');

-- 3. Tabel Data Master Lainnya
CREATE TABLE tb_pemasok (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pemasok VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20),
    alamat TEXT
);

CREATE TABLE tb_pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pelanggan VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20),
    alamat TEXT
);

CREATE TABLE tb_karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_karyawan VARCHAR(100) NOT NULL,
    jabatan VARCHAR(50),
    no_hp VARCHAR(20)
);

CREATE TABLE tb_barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_barang VARCHAR(20) UNIQUE,
    nama_barang VARCHAR(100) NOT NULL,
    kategori ENUM('Bahan Baku', 'Barang Jadi') NOT NULL,
    satuan VARCHAR(20) NOT NULL, 
    stok_tersedia INT DEFAULT 0,
    harga_satuan DECIMAL(15,2) DEFAULT 0
);

-- Insert Default Barang
INSERT INTO tb_barang (kode_barang, nama_barang, kategori, satuan, stok_tersedia, harga_satuan) VALUES
('BRG-001', 'Kelapa Utuh', 'Bahan Baku', 'Biji', 0, 1500),
('BRG-002', 'Kopra Putih', 'Barang Jadi', 'Kg', 0, 12000),
('BRG-003', 'Arang Batok', 'Barang Jadi', 'Karung', 0, 35000);

-- 4. Tabel Jurnal Akuntansi (Core)
CREATE TABLE tb_jurnal_umum (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_referensi VARCHAR(50) UNIQUE NOT NULL,
    tanggal DATE NOT NULL,
    deskripsi TEXT,
    total_debit DECIMAL(15,2) DEFAULT 0,
    total_kredit DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tb_jurnal_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_jurnal INT NOT NULL,
    kode_akun VARCHAR(10) NOT NULL,
    posisi ENUM('Debit', 'Kredit') NOT NULL,
    nominal DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (id_jurnal) REFERENCES tb_jurnal_umum(id) ON DELETE CASCADE,
    FOREIGN KEY (kode_akun) REFERENCES tb_akun(kode_akun) ON DELETE CASCADE
);

-- 5. Tabel Modul Transaksi & Operasional
CREATE TABLE tb_transaksi_pembelian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_transaksi VARCHAR(50) UNIQUE NOT NULL,
    tanggal DATE NOT NULL,
    id_pemasok INT NOT NULL,
    total_harga DECIMAL(15,2) NOT NULL,
    status_bayar ENUM('Lunas', 'Hutang') DEFAULT 'Lunas',
    id_jurnal INT,
    FOREIGN KEY (id_pemasok) REFERENCES tb_pemasok(id),
    FOREIGN KEY (id_jurnal) REFERENCES tb_jurnal_umum(id) ON DELETE SET NULL
);

CREATE TABLE tb_transaksi_pembelian_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pembelian INT NOT NULL,
    id_barang INT NOT NULL,
    qty INT NOT NULL,
    harga_satuan DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (id_pembelian) REFERENCES tb_transaksi_pembelian(id) ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES tb_barang(id)
);

CREATE TABLE tb_transaksi_penjualan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_transaksi VARCHAR(50) UNIQUE NOT NULL,
    tanggal DATE NOT NULL,
    id_pelanggan INT NOT NULL,
    total_harga DECIMAL(15,2) NOT NULL,
    status_bayar ENUM('Lunas', 'Piutang') DEFAULT 'Lunas',
    id_jurnal INT,
    FOREIGN KEY (id_pelanggan) REFERENCES tb_pelanggan(id),
    FOREIGN KEY (id_jurnal) REFERENCES tb_jurnal_umum(id) ON DELETE SET NULL
);

CREATE TABLE tb_transaksi_penjualan_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_penjualan INT NOT NULL,
    id_barang INT NOT NULL,
    qty INT NOT NULL,
    harga_satuan DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (id_penjualan) REFERENCES tb_transaksi_penjualan(id) ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES tb_barang(id)
);

CREATE TABLE tb_piutang_petani (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    id_pemasok INT NOT NULL,
    nominal_panjar DECIMAL(15,2) NOT NULL,
    sisa_piutang DECIMAL(15,2) NOT NULL,
    status ENUM('Belum Lunas', 'Lunas') DEFAULT 'Belum Lunas',
    id_jurnal INT,
    FOREIGN KEY (id_pemasok) REFERENCES tb_pemasok(id),
    FOREIGN KEY (id_jurnal) REFERENCES tb_jurnal_umum(id) ON DELETE SET NULL
);

CREATE TABLE tb_penggajian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    id_karyawan INT NOT NULL,
    jenis_pekerjaan VARCHAR(100), 
    qty INT DEFAULT 1,
    tarif_satuan DECIMAL(15,2) DEFAULT 0,
    total_upah DECIMAL(15,2) NOT NULL,
    id_jurnal INT,
    FOREIGN KEY (id_karyawan) REFERENCES tb_karyawan(id),
    FOREIGN KEY (id_jurnal) REFERENCES tb_jurnal_umum(id) ON DELETE SET NULL
);

CREATE TABLE tb_biaya_operasional (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    jenis_biaya VARCHAR(100) NOT NULL, 
    nominal DECIMAL(15,2) NOT NULL,
    keterangan TEXT,
    id_jurnal INT,
    FOREIGN KEY (id_jurnal) REFERENCES tb_jurnal_umum(id) ON DELETE SET NULL
);

-- 6. Tabel Backup Database
CREATE TABLE tb_backup_db (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_file VARCHAR(255) NOT NULL,
    tanggal_backup DATETIME DEFAULT CURRENT_TIMESTAMP,
    path_file VARCHAR(255) NOT NULL
);
