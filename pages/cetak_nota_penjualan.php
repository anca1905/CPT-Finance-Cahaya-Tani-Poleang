<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak.");
}

if (!isset($_GET['id'])) {
    die("ID Transaksi tidak valid.");
}

$id = (int)$_GET['id'];

// Ambil Header
$q_header = $conn->query("
    SELECT t.*, p.nama_pelanggan, p.alamat, p.no_hp 
    FROM tb_transaksi_penjualan t 
    JOIN tb_pelanggan p ON t.id_pelanggan = p.id 
    WHERE t.id = $id
");

if ($q_header->num_rows == 0) {
    die("Data transaksi tidak ditemukan.");
}
$header = $q_header->fetch_assoc();

// Ambil Detail
$q_detail = $conn->query("
    SELECT d.*, b.nama_barang, b.satuan 
    FROM tb_transaksi_penjualan_detail d 
    JOIN tb_barang b ON d.id_barang = b.id 
    WHERE d.id_penjualan = $id
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Penjualan - <?= $header['no_transaksi'] ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 14px; margin: 0; padding: 20px; }
        .nota-container { width: 100%; max-width: 800px; margin: 0 auto; border: 1px solid #000; padding: 20px; }
        .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 20px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; }
        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .item-table th, .item-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .item-table th { background-color: #f2f2f2; }
        .total-row td { font-weight: bold; }
        .footer { margin-top: 40px; text-align: center; }
        .sign-area { display: flex; justify-content: space-between; margin-top: 50px; }
        .sign-box { width: 200px; text-align: center; }
        .sign-box p { margin: 0 0 80px 0; }
        @media print {
            body { padding: 0; }
            .nota-container { border: none; padding: 0; }
            .d-print-none { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="nota-container">
        <div class="header">
            <h2>CTP FINANCE</h2>
            <p>Nota Penjualan</p>
        </div>
        
        <table class="info-table">
            <tr>
                <td width="15%">No Transaksi</td>
                <td width="2%">:</td>
                <td width="33%"><?= $header['no_transaksi'] ?></td>
                <td width="15%">Kepada</td>
                <td width="2%">:</td>
                <td width="33%"><b><?= $header['nama_pelanggan'] ?></b></td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>:</td>
                <td><?= date('d/m/Y', strtotime($header['tanggal'])) ?></td>
                <td>Alamat</td>
                <td>:</td>
                <td><?= $header['alamat'] ?></td>
            </tr>
            <tr>
                <td>Status Bayar</td>
                <td>:</td>
                <td><?= $header['status_bayar'] ?></td>
                <td>No HP</td>
                <td>:</td>
                <td><?= $header['no_hp'] ?></td>
            </tr>
        </table>
        
        <table class="item-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="35%">Nama Barang</th>
                    <th width="15%">Qty</th>
                    <th width="20%">Harga Satuan</th>
                    <th width="25%">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($item = $q_detail->fetch_assoc()): 
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $item['nama_barang'] ?></td>
                    <td><?= $item['qty'] ?> <?= $item['satuan'] ?></td>
                    <td>Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
                <tr class="total-row">
                    <td colspan="4" align="right">TOTAL KESELURUHAN</td>
                    <td>Rp <?= number_format($header['total_harga'], 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="sign-area">
            <div class="sign-box">
                <p>Hormat Kami,</p>
                <br>
                <span>( ....................... )</span>
            </div>
            <div class="sign-box">
                <p>Penerima / Pelanggan,</p>
                <br>
                <span>( <?= $header['nama_pelanggan'] ?> )</span>
            </div>
        </div>
        
        <div class="footer d-print-none">
            <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Cetak Ulang</button>
            <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; margin-left: 10px;">Tutup</button>
        </div>
    </div>
</body>
</html>
