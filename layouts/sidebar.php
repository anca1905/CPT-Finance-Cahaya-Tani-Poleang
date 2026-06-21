<?php
$role = $_SESSION['role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
?>
        <!-- Sidebar -->
        <ul class="navbar-nav sidebar accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= base_url('pages/dashboard.php') ?>">
                <div class="sidebar-brand-icon">
                    <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: white;">
                </div>
                <div class="sidebar-brand-text mx-3" style="font-size: 0.8rem;">Cahaya Tani Poleang</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <a class="nav-link" href="<?= base_url('pages/dashboard.php') ?>">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <?php if($role == 'Admin'): ?>
            <!-- Heading -->
            <div class="sidebar-heading">
                Admin Area
            </div>
            <!-- Nav Item - Data Master Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMaster"
                    aria-expanded="true" aria-controls="collapseMaster">
                    <i class="fas fa-fw fa-database"></i>
                    <span>Data Master</span>
                </a>
                <div id="collapseMaster" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?= base_url('pages/master_akun.php') ?>">Data Akun</a>
                        <a class="collapse-item" href="<?= base_url('pages/master_pemasok.php') ?>">Data Pemasok</a>
                        <a class="collapse-item" href="<?= base_url('pages/master_pelanggan.php') ?>">Data Pelanggan</a>
                        <a class="collapse-item" href="<?= base_url('pages/master_karyawan.php') ?>">Data Karyawan</a>
                        <a class="collapse-item" href="<?= base_url('pages/master_barang.php') ?>">Data Barang</a>
                        <a class="collapse-item" href="<?= base_url('pages/master_user.php') ?>">Data User</a>
                    </div>
                </div>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('pages/maintenance.php') ?>">
                    <i class="fas fa-fw fa-cogs"></i>
                    <span>Maintenance DB</span></a>
            </li>
            <hr class="sidebar-divider">
            <?php endif; ?>

            <?php if($role == 'Keuangan' || $role == 'Admin'): ?>
            <!-- Heading -->
            <div class="sidebar-heading">
                Operasional
            </div>
            <!-- Nav Item - Transaksi -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTransaksi"
                    aria-expanded="true" aria-controls="collapseTransaksi">
                    <i class="fas fa-fw fa-money-bill-wave"></i>
                    <span>Transaksi</span>
                </a>
                <div id="collapseTransaksi" class="collapse" aria-labelledby="headingUtilities"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?= base_url('pages/trx_pembelian.php') ?>">Pembelian</a>
                        <a class="collapse-item" href="<?= base_url('pages/trx_penjualan.php') ?>">Penjualan</a>
                        <a class="collapse-item" href="<?= base_url('pages/trx_piutang.php') ?>">Peminjaman / Piutang</a>
                        <a class="collapse-item" href="<?= base_url('pages/trx_penggajian.php') ?>">Penggajian</a>
                        <a class="collapse-item" href="<?= base_url('pages/trx_operasional.php') ?>">Biaya Operasional</a>
                    </div>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('pages/jurnal_umum.php') ?>">
                    <i class="fas fa-fw fa-book"></i>
                    <span>Jurnal Umum</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('pages/jurnal_penyesuaian.php') ?>">
                    <i class="fas fa-fw fa-edit"></i>
                    <span>Jurnal Penyesuaian</span></a>
            </li>
            <hr class="sidebar-divider">
            <?php endif; ?>

            <!-- Heading -->
            <div class="sidebar-heading">
                Reporting
            </div>
            <!-- Nav Item - Laporan -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLaporan"
                    aria-expanded="true" aria-controls="collapseLaporan">
                    <i class="fas fa-fw fa-chart-area"></i>
                    <span>Laporan</span>
                </a>
                <div id="collapseLaporan" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?= base_url('pages/lap_bukubesar.php') ?>">Buku Besar</a>
                        <a class="collapse-item" href="<?= base_url('pages/lap_neraca_saldo.php') ?>">Neraca Saldo</a>
                        <a class="collapse-item" href="<?= base_url('pages/lap_transaksi.php') ?>">Pembelian & Penjualan</a>
                        <a class="collapse-item" href="<?= base_url('pages/lap_stok.php') ?>">Stok Barang</a>
                        <a class="collapse-item" href="<?= base_url('pages/lap_labarugi.php') ?>">Laba Rugi</a>
                        <a class="collapse-item" href="<?= base_url('pages/lap_neraca.php') ?>">Neraca</a>
                        <a class="collapse-item" href="<?= base_url('pages/lap_aruskas.php') ?>">Arus Kas</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= $_SESSION['nama_lengkap'] ?> (<?= $_SESSION['role'] ?>)</span>
                                <img class="img-profile rounded-circle"
                                    src="<?= base_url('assets/img/undraw_profile.svg') ?>">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
