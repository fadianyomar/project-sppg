<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once "../../config/database.php";

/// PROSES SIMPAN PEMAKAIAN
if (isset($_POST['simpan_pemakaian'])) {

    $item_id = (int) $_POST['item_id'];
    $tanggal = $_POST['movement_date'];
    $quantity = (float) $_POST['quantity'];
    $description = trim($_POST['description']);
    $created_by = $_SESSION['user_id'];

    /// VALIDASI
    if ($item_id <= 0 || $quantity <= 0) {
        header("Location: pemakaian_barang.php?error=invalid");
        exit;
    }

    /// CEK STOK TERSEDIA
    $stmt_stock = mysqli_prepare(
        $conn,
        "SELECT
            COALESCE(
                SUM(
                    CASE
                        WHEN movement_type = 'IN' THEN quantity
                        WHEN movement_type = 'OUT' THEN -quantity
                        ELSE quantity
                    END
                ),
                0
            ) AS current_stock
        FROM stock_movements
        WHERE item_id = ?"
    );

    mysqli_stmt_bind_param($stmt_stock, "i", $item_id);
    mysqli_stmt_execute($stmt_stock);

    $result_stock = mysqli_stmt_get_result($stmt_stock);
    $stock_data = mysqli_fetch_assoc($result_stock);
    $current_stock = (float) $stock_data['current_stock'];

    /// STOK TIDAK MENCUKUPI
    if ($quantity > $current_stock) {
        header("Location: pemakaian_barang.php?error=stock");
        exit;
    }

    /// SIMPAN STOCK OUT
    $reference_type = "PEMAKAIAN";

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO stock_movements
        (
            item_id,
            movement_date,
            movement_type,
            quantity,
            reference_type,
            reference_id,
            description,
            created_by
        )
        VALUES
        (
            ?,
            ?,
            'OUT',
            ?,
            ?,
            NULL,
            ?,
            ?
        )"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "isdssi",
        $item_id,
        $tanggal,
        $quantity,
        $reference_type,
        $description,
        $created_by
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: pemakaian_barang.php?success=1");
        exit;
    } else {
        header("Location: pemakaian_barang.php?error=save");
        exit;
    }
}

/// AMBIL DATA BARANG
$items = mysqli_query(
    $conn,
    "SELECT
        i.id,
        i.item_code,
        i.item_name,
        i.unit,

        COALESCE(
            SUM(
                CASE
                    WHEN sm.movement_type = 'IN'
                        THEN sm.quantity

                    WHEN sm.movement_type = 'OUT'
                        THEN -sm.quantity

                    ELSE sm.quantity
                END
            ),
            0
        ) AS current_stock

    FROM items i

    LEFT JOIN stock_movements sm
        ON i.id = sm.item_id

    GROUP BY
        i.id,
        i.item_code,
        i.item_name,
        i.unit

    ORDER BY
        i.item_name ASC"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pemakaian Barang</title>
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
                    <img src="/sppg/assets/icons/pemakaian-barang1.png" alt="">
                </div>

                <div>
                    <h1>Pemakaian Barang</h1>
                    <p>Mencatat penggunaan barang yang keluar dari stok</p>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <main>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Pemakaian barang berhasil disimpan.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <?php if ($_GET['error'] === 'stock'): ?>
                    <div class="alert alert-error">
                        Jumlah pemakaian melebihi stok yang tersedia.
                    </div>
                <?php elseif ($_GET['error'] === 'invalid'): ?>
                    <div class="alert alert-error">
                        Data pemakaian tidak valid.
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        Pemakaian barang gagal disimpan.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST">

                    <div class="form-group">
                        <label>Barang</label>

                        <select name="item_id" required>
                            <option value="">-- Pilih Barang --</option>

                            <?php while ($item = mysqli_fetch_assoc($items)): ?>
                                <option value="<?= $item['id']; ?>">
                                    <?= htmlspecialchars($item['item_code']); ?> -
                                    <?= htmlspecialchars($item['item_name']); ?>
                                    (Stok:
                                    <?= number_format($item['current_stock'], 0, ',', '.'); ?>
                                    <?= htmlspecialchars($item['unit']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Pemakaian</label>

                        <input
                            type="date"
                            name="movement_date"
                            value="<?= date('Y-m-d'); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Jumlah Pemakaian</label>

                        <input
                            type="number"
                            name="quantity"
                            min="1"
                            step="1"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Keterangan</label>

                        <textarea
                            name="description"
                            rows="4"
                            placeholder="Contoh: Pemakaian beras untuk produksi MBG"
                        ></textarea>
                    </div>

                    <button type="submit" name="simpan_po" class="btn btn-primary">
                        <img src="/sppg/assets/icons/simpan.png" alt="" class="btn-icon">
                        Simpan
                    </button>

                </form>
            </div>

        </main>

    </div>

    <script src="../../assets/js/script.js"></script>

</body>

</html>