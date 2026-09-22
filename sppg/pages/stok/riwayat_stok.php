<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once "../../config/database.php";

/// FILTER
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : date('Y-m-d');
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'semua';

/// AMBIL RIWAYAT STOK
$sql = "
    SELECT
        sm.id,
        sm.movement_date,
        sm.movement_type,
        sm.quantity,
        sm.reference_type,
        sm.reference_id,
        sm.description,
        i.item_code,
        i.item_name,
        i.unit,
        po.po_number
    FROM stock_movements sm
    INNER JOIN items i
        ON sm.item_id = i.id
    LEFT JOIN purchase_orders po
        ON sm.reference_type = 'PURCHASE_ORDER'
        AND sm.reference_id = po.id
    WHERE sm.movement_date BETWEEN ? AND ?
";

if ($jenis === 'masuk') {
    $sql .= " AND sm.movement_type = 'IN'";
} elseif ($jenis === 'keluar') {
    $sql .= " AND sm.movement_type = 'OUT'";
}

$sql .= "
    ORDER BY
        sm.movement_date DESC,
        sm.id DESC
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_selesai);

mysqli_stmt_execute($stmt);

$query = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Riwayat Stok</title>
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
                    <img src="/sppg/assets/icons/riwayat-stok1.png" alt="">
                </div>

                <div>
                    <h1>Riwayat Stok</h1>
                    <p>Riwayat seluruh pergerakan stok barang</p>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <main>
            <div class="dashboard-section">
                <h2>Riwayat Pergerakan Stok</h2>

                <div class="form-container">
                    <form method="GET">
                        <div class="report-filter-row">
                            <div class="form-group">
                                <label>Tanggal Mulai</label>
                                <input
                                    type="date"
                                    name="tanggal_mulai"
                                    value="<?= htmlspecialchars($tanggal_mulai); ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label>Tanggal Selesai</label>
                                <input
                                    type="date"
                                    name="tanggal_selesai"
                                    value="<?= htmlspecialchars($tanggal_selesai); ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label>Jenis Pergerakan</label>
                                <select name="jenis">
                                    <option value="semua" <?= $jenis === 'semua' ? 'selected' : ''; ?>>Semua</option>
                                    <option value="masuk" <?= $jenis === 'masuk' ? 'selected' : ''; ?>>Masuk</option>
                                    <option value="keluar" <?= $jenis === 'keluar' ? 'selected' : ''; ?>>Keluar</option>
                                </select>
                            </div>
                        </div>

                        <div class="report-filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <img src="/sppg/assets/icons/tampilkan.png" alt="" class="btn-icon">
                                Tampilkan
                            </button>
                            <a href="riwayat_stok.php" class="btn">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Kode Barang</th>
                                <th>Nama Barang</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                                <th>Referensi</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($query)):
                            ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['movement_date'])); ?></td>
                                    <td><?= htmlspecialchars($row['item_code']); ?></td>
                                    <td><?= htmlspecialchars($row['item_name']); ?></td>

                                    <td>
                                        <?php if ($row['movement_type'] === 'IN'): ?>
                                            <span class="status status-diterima">Masuk</span>
                                        <?php else: ?>
                                            <span class="status status-dibatalkan">Keluar</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= number_format($row['quantity'], 0, ',', '.'); ?>
                                        <?= htmlspecialchars($row['unit']); ?>
                                    </td>

                                    <td>
                                        <?php
                                        if ($row['reference_type'] === 'PURCHASE_ORDER') {
                                            echo htmlspecialchars($row['po_number']);
                                        } else {
                                            echo htmlspecialchars($row['reference_type']);
                                        }
                                        ?>
                                    </td>

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