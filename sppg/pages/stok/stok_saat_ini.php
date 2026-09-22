<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once "../../config/database.php";

/// QUERY STOK
$query = mysqli_query(
    $conn,
    "SELECT
        i.id,
        i.item_code,
        i.item_name,
        i.unit,
        i.minimum_stock,

        COALESCE(
            SUM(
                CASE
                    WHEN sm.movement_type = 'IN' THEN sm.quantity
                    WHEN sm.movement_type = 'OUT' THEN -sm.quantity
                    ELSE sm.quantity
                END
            ),
            0
        ) AS current_stock

    FROM items i
    LEFT JOIN stock_movements sm ON i.id = sm.item_id

    GROUP BY
        i.id,
        i.item_code,
        i.item_name,
        i.unit,
        i.minimum_stock

    ORDER BY i.item_name ASC"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Stok Saat Ini</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <!-- TOP NAVIGATION -->
    <div class="top-navbar">

        <!-- LOGO -->
        <div class="top-logo">
            <img src="/sppg/assets/icons/icon.png" alt="Logo">
            <div class="top-logo-text">
                <strong>SPPG</strong>
                <span>Sistem Pengelolaan SPPG</span>
            </div>
        </div>

        <!-- MENU -->
        <nav class="top-menu">

            <!-- DASHBOARD -->
            <a href="/sppg/index.php" class="top-menu-item">
                <img src="/sppg/assets/icons/dashboard.png" alt="">
                <span>Dashboard</span>
            </a>

            <!-- PENGADAAN -->
            <div class="nav-dropdown">
                <button type="button" class="top-menu-item dropdown-toggle">
                    <span>Pengadaan</span>
                    <span class="dropdown-arrow">▼</span>
                </button>

                <div class="dropdown-menu">
                    <a href="/sppg/pages/pengadaan/purchase_order.php">
                        <img src="/sppg/assets/icons/purchase-order.png" alt="">
                        <span>Purchase Order</span>
                    </a>
                </div>
            </div>

            <!-- TRANSAKSI -->
            <div class="nav-dropdown">
                <button type="button" class="top-menu-item dropdown-toggle">
                    <span>Transaksi</span>
                    <span class="dropdown-arrow">▼</span>
                </button>

                <div class="dropdown-menu">
                    <a href="/sppg/pages/transaksi/dana_masuk.php">
                        <img src="/sppg/assets/icons/dana-masuk.png" alt="">
                        <span>Dana Masuk</span>
                    </a>

                    <a href="/sppg/pages/transaksi/pengeluaran.php">
                        <img src="/sppg/assets/icons/pengeluaran.png" alt="">
                        <span>Pengeluaran</span>
                    </a>
                </div>
            </div>

            <!-- STOK -->
            <div class="nav-dropdown">
                <button type="button" class="top-menu-item dropdown-toggle active">
                    <span>Stok</span>
                    <span class="dropdown-arrow">▼</span>
                </button>

                <div class="dropdown-menu">
                    <a href="/sppg/pages/stok/stok_saat_ini.php">
                        <img src="/sppg/assets/icons/stok-saat-ini.png" alt="">
                        <span>Stok Saat Ini</span>
                    </a>

                    <a href="/sppg/pages/stok/pemakaian_barang.php">
                        <img src="/sppg/assets/icons/pemakaian-barang.png" alt="">
                        <span>Pemakaian Barang</span>
                    </a>

                    <a href="/sppg/pages/stok/riwayat_stok.php">
                        <img src="/sppg/assets/icons/riwayat-stok.png" alt="">
                        <span>Riwayat Stok</span>
                    </a>
                </div>
            </div>

            <!-- LAPORAN -->
            <div class="nav-dropdown">
                <button type="button" class="top-menu-item dropdown-toggle">
                    <span>Laporan</span>
                    <span class="dropdown-arrow">▼</span>
                </button>

                <div class="dropdown-menu">
                    <a href="/sppg/pages/laporan/laporan_keuangan.php">
                        <img src="/sppg/assets/icons/laporan-keuangan.png" alt="">
                        <span>Laporan Keuangan</span>
                    </a>

                    <a href="/sppg/pages/laporan/laporan_stok.php">
                        <img src="/sppg/assets/icons/laporan-stok.png" alt="">
                        <span>Laporan Stok</span>
                    </a>
                </div>
            </div>

        </nav>

        <!-- USER -->
        <div class="top-user">
            <div class="profile-icon">
                <img src="/sppg/assets/icons/profil.png" alt="">
            </div>

            <div class="user-detail">
                <strong><?= htmlspecialchars($_SESSION['name']); ?></strong>
                <small><?= htmlspecialchars($_SESSION['role']); ?></small>
            </div>

            <a href="logout.php" class="logout">
                <img src="/sppg/assets/icons/logout.png" alt="">
                <span>Logout</span>
            </a>
        </div>

    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- HEADER -->
        <header>
            <div class="page-title">
                <div class="page-title-icon">
                    <img src="/sppg/assets/icons/stok-saat-ini1.png" alt="">
                </div>

                <div>
                    <h1>Stok Saat Ini</h1>
                    <p>Informasi jumlah stok barang saat ini</p>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <main>
            <div class="dashboard-section">
                <h2>Daftar Stok Barang</h2>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Barang</th>
                                <th>Nama Barang</th>
                                <th>Satuan</th>
                                <th>Stok Saat Ini</th>
                                <th>Minimum Stok</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $no = 1;

                            while ($item = mysqli_fetch_assoc($query)):

                                $current_stock = (float) $item['current_stock'];
                                $minimum_stock = (float) $item['minimum_stock'];

                                if ($current_stock <= 0) {
                                    $status = "Habis";
                                    $status_class = "status-habis";
                                } elseif ($current_stock <= $minimum_stock) {
                                    $status = "Menipis";
                                    $status_class = "status-menipis";
                                } else {
                                    $status = "Aman";
                                    $status_class = "status-aman";
                                }
                            ?>

                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($item['item_code']); ?></td>
                                    <td><?= htmlspecialchars($item['item_name']); ?></td>
                                    <td><?= htmlspecialchars($item['unit']); ?></td>
                                    <td><?= number_format($current_stock, 0, ',', '.'); ?></td>
                                    <td><?= number_format($minimum_stock, 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="status <?= $status_class; ?>"><?= $status; ?></span>
                                    </td>
                                </tr>

                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

    </div>

    <script src="../../assets/js/script.js"></script>

</body>

</html>