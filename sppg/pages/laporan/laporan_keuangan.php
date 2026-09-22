<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once "../../config/database.php";

// FILTER
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : date('Y-m-d');
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';

// TOTAL DANA MASUK
$total_income = 0;

if ($kategori === 'semua' || $kategori === 'masuk') {
    $stmt_income = mysqli_prepare(
        $conn,
        "SELECT COALESCE(SUM(amount), 0) AS total_income
         FROM income_transactions
         WHERE transaction_date BETWEEN ? AND ?"
    );

    mysqli_stmt_bind_param($stmt_income, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_income);
    $result_income = mysqli_stmt_get_result($stmt_income);
    $data_income = mysqli_fetch_assoc($result_income);
    $total_income = $data_income['total_income'];
    mysqli_stmt_close($stmt_income);
}

// TOTAL PENGELUARAN
$total_expense = 0;

if ($kategori === 'semua' || $kategori === 'pengeluaran') {
    $stmt_expense = mysqli_prepare(
        $conn,
        "SELECT COALESCE(SUM(amount), 0) AS total_expense
         FROM expense_transactions
         WHERE transaction_date BETWEEN ? AND ?"
    );

    mysqli_stmt_bind_param($stmt_expense, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_expense);
    $result_expense = mysqli_stmt_get_result($stmt_expense);
    $data_expense = mysqli_fetch_assoc($result_expense);
    $total_expense = $data_expense['total_expense'];
    mysqli_stmt_close($stmt_expense);
}

// SALDO
$saldo = $total_income - $total_expense;

// DATA TRANSAKSI
if ($kategori === 'masuk') {
    $stmt_transactions = mysqli_prepare(
        $conn,
        "SELECT
            transaction_date,
            transaction_number,
            'Dana Masuk' AS transaction_type,
            category,
            amount,
            description
         FROM income_transactions
         WHERE transaction_date BETWEEN ? AND ?
         ORDER BY transaction_date DESC, id DESC"
    );

    mysqli_stmt_bind_param($stmt_transactions, "ss", $tanggal_mulai, $tanggal_selesai);

} elseif ($kategori === 'pengeluaran') {
    $stmt_transactions = mysqli_prepare(
        $conn,
        "SELECT
            et.transaction_date,
            et.transaction_number,
            'Pengeluaran' AS transaction_type,
            ec.category_name AS category,
            et.amount,
            et.description
         FROM expense_transactions et
         INNER JOIN expense_categories ec
            ON et.category_id = ec.id
         WHERE et.transaction_date BETWEEN ? AND ?
         ORDER BY et.transaction_date DESC, et.id DESC"
    );

    mysqli_stmt_bind_param($stmt_transactions, "ss", $tanggal_mulai, $tanggal_selesai);

} else {
    $stmt_transactions = mysqli_prepare(
        $conn,
        "SELECT
            transaction_date,
            transaction_number,
            transaction_type,
            category,
            amount,
            description
         FROM
         (
            SELECT
                id,
                transaction_date,
                transaction_number,
                'Dana Masuk' AS transaction_type,
                category,
                amount,
                description
            FROM income_transactions
            WHERE transaction_date BETWEEN ? AND ?

            UNION ALL

            SELECT
                et.id,
                et.transaction_date,
                et.transaction_number,
                'Pengeluaran' AS transaction_type,
                ec.category_name AS category,
                et.amount,
                et.description
            FROM expense_transactions et
            INNER JOIN expense_categories ec
                ON et.category_id = ec.id
            WHERE et.transaction_date BETWEEN ? AND ?
         ) AS transactions
         ORDER BY transaction_date DESC, id DESC"
    );

    mysqli_stmt_bind_param(
        $stmt_transactions,
        "ssss",
        $tanggal_mulai,
        $tanggal_selesai,
        $tanggal_mulai,
        $tanggal_selesai
    );
}

mysqli_stmt_execute($stmt_transactions);
$query_transactions = mysqli_stmt_get_result($stmt_transactions);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan</title>
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
                <img src="/sppg/assets/icons/laporan-keuangan1.png" alt="">
            </div>

            <div>
                <h1>Laporan Keuangan</h1>
                <p>Rekap dana masuk dan pengeluaran</p>
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
                        <label>Pilih Kategori</label>

                        <select name="kategori">
                            <option
                                value="semua"
                                <?= $kategori === 'semua' ? 'selected' : ''; ?>
                            >Semua Kategori</option>

                            <option
                                value="masuk"
                                <?= $kategori === 'masuk' ? 'selected' : ''; ?>
                            >Dana Masuk</option>

                            <option
                                value="pengeluaran"
                                <?= $kategori === 'pengeluaran' ? 'selected' : ''; ?>
                            >Pengeluaran</option>
                        </select>
                    </div>
                </div>

                <!-- TOMBOL -->
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

        <!-- RINGKASAN -->
        <div class="dashboard-section">
            <h2>Ringkasan Keuangan</h2>

            <table>
                <tbody>
                    <tr>
                        <td>Total Dana Masuk</td>
                        <td>Rp <?= number_format($total_income, 0, ',', '.'); ?></td>
                    </tr>

                    <tr>
                        <td>Total Pengeluaran</td>
                        <td>Rp <?= number_format($total_expense, 0, ',', '.'); ?></td>
                    </tr>

                    <tr>
                        <td><strong>Saldo</strong></td>
                        <td><strong>Rp <?= number_format($saldo, 0, ',', '.'); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <br>

        <!-- RIWAYAT TRANSAKSI -->
        <div class="dashboard-section">
            <h2>Riwayat Transaksi</h2>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>No. Transaksi</th>
                            <th>Jenis</th>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $no = 1; ?>

                        <?php while ($row = mysqli_fetch_assoc($query_transactions)): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= date('d-m-Y', strtotime($row['transaction_date'])); ?></td>
                                <td><?= htmlspecialchars($row['transaction_number']); ?></td>
                                <td><?= htmlspecialchars($row['transaction_type']); ?></td>
                                <td><?= htmlspecialchars($row['category']); ?></td>
                                <td>Rp <?= number_format($row['amount'], 0, ',', '.'); ?></td>
                                <td><?= htmlspecialchars($row['description']); ?></td>
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