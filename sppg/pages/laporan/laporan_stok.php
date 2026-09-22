<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once "../../config/database.php";

// FILTER LAPORAN
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'semua';

// DATA LAPORAN STOK
$stmt_stock = mysqli_prepare(
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
                    WHEN sm.movement_type = 'IN'
                    THEN sm.quantity
                    ELSE 0
                END
            ),
            0
        ) AS stock_in,

        COALESCE(
            SUM(
                CASE
                    WHEN sm.movement_type = 'OUT'
                    THEN sm.quantity
                    ELSE 0
                END
            ),
            0
        ) AS stock_out

     FROM items i

     LEFT JOIN stock_movements sm
        ON i.id = sm.item_id
        AND sm.movement_date <= ?

     GROUP BY
        i.id,
        i.item_code,
        i.item_name,
        i.unit,
        i.minimum_stock

     ORDER BY
        i.item_name ASC"
);

mysqli_stmt_bind_param($stmt_stock, "s", $tanggal_selesai);
mysqli_stmt_execute($stmt_stock);

$result_stock = mysqli_stmt_get_result($stmt_stock);

// TOTAL PERGERAKAN PERIODE
$stmt_period = mysqli_prepare(
    $conn,
    "SELECT
        item_id,

        COALESCE(
            SUM(
                CASE
                    WHEN movement_type = 'IN'
                    THEN quantity
                    ELSE 0
                END
            ),
            0
        ) AS period_in,

        COALESCE(
            SUM(
                CASE
                    WHEN movement_type = 'OUT'
                    THEN quantity
                    ELSE 0
                END
            ),
            0
        ) AS period_out

     FROM stock_movements

     WHERE movement_date
     BETWEEN ? AND ?

     GROUP BY item_id"
);

mysqli_stmt_bind_param($stmt_period, "ss", $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt_period);

$result_period = mysqli_stmt_get_result($stmt_period);

// SIMPAN DATA PERIODE
$period_data = [];

while ($period = mysqli_fetch_assoc($result_period)) {
    $period_data[$period['item_id']] = [
        'period_in' => $period['period_in'],
        'period_out' => $period['period_out']
    ];
}

mysqli_stmt_close($stmt_period);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Stok</title>
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
                <button type="button" class="top-menu-item dropdown-toggle">
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
                <button type="button" class="top-menu-item dropdown-toggle active">
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
                    <img src="/sppg/assets/icons/laporan-stok1.png" alt="">
                </div>

                <div>
                    <h1>Laporan Stok</h1>
                    <p>Rekap pergerakan dan kondisi stok</p>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <main>

            <!-- FILTER -->
            <div class="form-container">
                <form method="GET">
                    <div class="report-filter-row">

                        <div class="form-group">
                            <label>Dari Tanggal</label>
                            <input
                                type="date"
                                name="tanggal_mulai"
                                value="<?= htmlspecialchars($tanggal_mulai); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Sampai Tanggal</label>
                            <input
                                type="date"
                                name="tanggal_selesai"
                                value="<?= htmlspecialchars($tanggal_selesai); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Status Stok</label>
                            <select name="status">
                                <option value="semua" <?= $status_filter === 'semua' ? 'selected' : ''; ?>>
                                    Semua Status
                                </option>

                                <option value="aman" <?= $status_filter === 'aman' ? 'selected' : ''; ?>>
                                    Stok Aman
                                </option>

                                <option value="menipis" <?= $status_filter === 'menipis' ? 'selected' : ''; ?>>
                                    Stok Menipis
                                </option>

                                <option value="habis" <?= $status_filter === 'habis' ? 'selected' : ''; ?>>
                                    Stok Kosong
                                </option>
                            </select>
                        </div>

                    </div>

                    <div class="report-filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <img src="/sppg/assets/icons/tampilkan.png" alt="" class="btn-icon">
                            Tampilkan
                        </button>

                        <a
                            href="export_laporan_keuangan.php?tanggal_mulai=<?= urlencode($tanggal_mulai); ?>&tanggal_selesai=<?= urlencode($tanggal_selesai); ?>&kategori=<?= urlencode($kategori); ?>"
                            class="btn btn-success"
                        >
                            <img src="/sppg/assets/icons/excel.png" alt="" class="btn-icon">
                            Export Excel
                        </a>
                    </div>
                </form>
            </div>

            <!-- LAPORAN -->
            <div class="dashboard-section">
                <h2>Rekap Stok</h2>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Barang</th>
                                <th>Nama Barang</th>
                                <th>Satuan</th>
                                <th>Masuk Periode</th>
                                <th>Keluar Periode</th>
                                <th>Stok Akhir</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php $no = 1; ?>

                            <?php while ($row = mysqli_fetch_assoc($result_stock)): ?>

                                <?php
                                $period_in = 0;
                                $period_out = 0;

                                if (isset($period_data[$row['id']])) {
                                    $period_in = $period_data[$row['id']]['period_in'];
                                    $period_out = $period_data[$row['id']]['period_out'];
                                }

                                $stock_akhir = $row['stock_in'] - $row['stock_out'];

                                if ($stock_akhir <= 0) {
                                    $status = "Habis";
                                    $status_class = "status-habis";
                                    $status_value = "habis";
                                } elseif ($stock_akhir <= $row['minimum_stock']) {
                                    $status = "Menipis";
                                    $status_class = "status-menipis";
                                    $status_value = "menipis";
                                } else {
                                    $status = "Aman";
                                    $status_class = "status-aman";
                                    $status_value = "aman";
                                }

                                // Filter status
                                if (
                                    $status_filter !== 'semua'
                                    &&
                                    $status_filter !== $status_value
                                ) {
                                    continue;
                                }
                                ?>

                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['item_code']); ?></td>
                                    <td><?= htmlspecialchars($row['item_name']); ?></td>
                                    <td><?= htmlspecialchars($row['unit']); ?></td>
                                    <td><?= number_format($period_in, 2, ',', '.'); ?></td>
                                    <td><?= number_format($period_out, 2, ',', '.'); ?></td>
                                    <td><?= number_format($stock_akhir, 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="status <?= $status_class; ?>">
                                            <?= $status; ?>
                                        </span>
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