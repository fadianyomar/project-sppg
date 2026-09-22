<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";

// Total dana masuk
$query_income = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) AS total FROM income_transactions");

$data_income = mysqli_fetch_assoc($query_income);
$total_income = $data_income['total'];

// Total pengeluaran
$query_expense = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) AS total FROM expense_transactions");

$data_expense = mysqli_fetch_assoc($query_expense);
$total_expense = $data_expense['total'];

// Saldo
$balance = $total_income - $total_expense;

// Jumlah barang
$query_items = mysqli_query($conn, "SELECT COUNT(*) AS total FROM items");

$data_items = mysqli_fetch_assoc($query_items);
$total_items = $data_items['total'];

// Transaksi terbaru
$query_transactions = mysqli_query(
    $conn,
    "SELECT
        transaction_date,
        transaction_number,
        description,
        amount,
        'Dana Masuk' AS transaction_type
     FROM income_transactions

     UNION ALL

     SELECT
        transaction_date,
        transaction_number,
        description,
        amount,
        'Pengeluaran' AS transaction_type
     FROM expense_transactions

     ORDER BY transaction_date DESC
     LIMIT 5"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            <a href="/sppg/index.php" class="top-menu-item active">
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

    <div class="main-content">
        <header>
            <div class="page-title">
                <div class="page-title-icon">
                    <img src="/sppg/assets/icons/dashboard1.png" alt="">
                </div>

                <div>
                    <h1>Dashboard</h1>
                    <p>Ringkasan keuangan dan persediaan SPPG</p>
                </div>
            </div>
        </header>

        <main>
            <div class="cards">
                <div class="card">
                    <p>Total Dana Masuk</p>
                    <h2>Rp<?= number_format($total_income, 0, ',', '.'); ?></h2>
                </div>

                <div class="card">
                    <p>Total Pengeluaran</p>
                    <h2>Rp<?= number_format($total_expense, 0, ',', '.'); ?></h2>
                </div>

                <div class="card">
                    <p>Saldo Saat Ini</p>
                    <h2>Rp<?= number_format($balance, 0, ',', '.'); ?></h2>
                </div>

                <div class="card">
                    <p>Jumlah Barang</p>
                    <h2><?= $total_items; ?></h2>
                </div>
            </div>

            <div class="dashboard-section">
                <h2>Transaksi Terbaru</h2>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Transaksi</th>
                                <th>Keterangan</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php while ($transaction = mysqli_fetch_assoc($query_transactions)): ?>
                                <tr>
                                    <td><?= date('d-m-Y', strtotime($transaction['transaction_date'])); ?></td>
                                    <td><?= htmlspecialchars($transaction['transaction_number']); ?></td>
                                    <td><?= htmlspecialchars($transaction['description']); ?></td>
                                    <td><?= htmlspecialchars($transaction['transaction_type']); ?></td>
                                    <td>Rp<?= number_format($transaction['amount'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="/sppg/assets/js/script.js"></script>

</body>

</html>