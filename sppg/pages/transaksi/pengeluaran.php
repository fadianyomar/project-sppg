<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once "../../config/database.php";

/// AMBIL KATEGORI PENGELUARAN
$query_categories = mysqli_query(
    $conn,
    "SELECT
        id,
        category_name
     FROM expense_categories
     ORDER BY category_name ASC"
);

/// AMBIL DATA PURCHASE ORDER
$query_po = mysqli_query(
    $conn,
    "SELECT
        po.id,
        po.po_number,
        po.po_date,
        s.supplier_name
     FROM purchase_orders po
     INNER JOIN suppliers s
        ON po.supplier_id = s.id
     WHERE po.status = 'diterima'
     ORDER BY po.po_date DESC, po.id DESC"
);

/// PROSES SIMPAN PENGELUARAN
if (isset($_POST['simpan_pengeluaran'])) {
    $transaction_date = $_POST['transaction_date'];
    $category_id = (int) $_POST['category_id'];

    $purchase_order_id = !empty($_POST['purchase_order_id'])
        ? (int) $_POST['purchase_order_id']
        : null;

    $amount = (float) $_POST['amount'];
    $description = trim($_POST['description']);
    $created_by = $_SESSION['user_id'];

    /// BUAT NOMOR TRANSAKSI
    $bulan = date('Ym', strtotime($transaction_date));

    $query_last = mysqli_query(
        $conn,
        "SELECT transaction_number
         FROM expense_transactions
         WHERE transaction_number LIKE 'EXP-$bulan-%'
         ORDER BY id DESC
         LIMIT 1"
    );

    if (mysqli_num_rows($query_last) > 0) {
        $last = mysqli_fetch_assoc($query_last);

        $last_number = (int) substr(
            $last['transaction_number'],
            -3
        );

        $next_number = $last_number + 1;
    } else {
        $next_number = 1;
    }

    $transaction_number =
        'EXP-' .
        $bulan .
        '-' .
        str_pad(
            $next_number,
            3,
            '0',
            STR_PAD_LEFT
        );

    /// SIMPAN KE DATABASE
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO expense_transactions
        (
            transaction_number,
            transaction_date,
            category_id,
            purchase_order_id,
            amount,
            description,
            created_by
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssiidsi",
        $transaction_number,
        $transaction_date,
        $category_id,
        $purchase_order_id,
        $amount,
        $description,
        $created_by
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: pengeluaran.php?success=1");
        exit;
    } else {
        $error = "Pengeluaran gagal disimpan: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

/// HAPUS PENGELUARAN
if (isset($_POST['hapus_pengeluaran'])) {
    $id = (int) $_POST['id'];

    $stmt_delete = mysqli_prepare(
        $conn,
        "DELETE FROM expense_transactions
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt_delete,
        "i",
        $id
    );

    if (mysqli_stmt_execute($stmt_delete)) {
        header("Location: pengeluaran.php?deleted=1");
        exit;
    } else {
        $error = "Pengeluaran gagal dihapus.";
    }

    mysqli_stmt_close($stmt_delete);
}

/// AMBIL DATA UNTUK EDIT
$edit_data = null;

if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];

    $stmt_edit = mysqli_prepare(
        $conn,
        "SELECT
            id,
            transaction_number,
            transaction_date,
            category_id,
            purchase_order_id,
            amount,
            description
         FROM expense_transactions
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt_edit,
        "i",
        $edit_id
    );

    mysqli_stmt_execute($stmt_edit);

    $result_edit = mysqli_stmt_get_result($stmt_edit);

    $edit_data = mysqli_fetch_assoc($result_edit);

    mysqli_stmt_close($stmt_edit);
}

/// AMBIL DATA PENGELUARAN
$query_expenses = mysqli_query(
    $conn,
    "SELECT
        et.id,
        et.transaction_number,
        et.transaction_date,
        et.amount,
        et.description,
        ec.category_name,
        po.po_number
     FROM expense_transactions et
     INNER JOIN expense_categories ec
        ON et.category_id = ec.id
     LEFT JOIN purchase_orders po
        ON et.purchase_order_id = po.id
     ORDER BY
        et.transaction_date DESC,
        et.id DESC"
);

/// UPDATE PENGELUARAN
if (isset($_POST['update_pengeluaran'])) {
    $id = (int) $_POST['id'];
    $transaction_date = $_POST['transaction_date'];
    $category_id = (int) $_POST['category_id'];

    $purchase_order_id = !empty($_POST['purchase_order_id'])
        ? (int) $_POST['purchase_order_id']
        : null;

    $amount = (float) $_POST['amount'];
    $description = trim($_POST['description']);

    $stmt_update = mysqli_prepare(
        $conn,
        "UPDATE expense_transactions
        SET
            transaction_date = ?,
            category_id = ?,
            purchase_order_id = ?,
            amount = ?,
            description = ?
        WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt_update,
        "siidsi",
        $transaction_date,
        $category_id,
        $purchase_order_id,
        $amount,
        $description,
        $id
    );

    if (mysqli_stmt_execute($stmt_update)) {
        header("Location: pengeluaran.php?edited=1");
        exit;
    } else {
        $error = "Pengeluaran gagal diperbarui.";
    }

    mysqli_stmt_close($stmt_update);
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pengeluaran</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

    <!-- TOP NAVIGATION -->
    <div class="top-navbar">

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
                <button type="button" class="top-menu-item dropdown-toggle active">
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

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- HEADER -->
        <header>
            <div class="page-title">
                <div class="page-title-icon">
                    <img src="/sppg/assets/icons/pengeluaran1.png" alt="">
                </div>

                <div>
                    <h1>Pengeluaran</h1>
                    <p>Pencatatan seluruh transaksi pengeluaran SPPG</p>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <main>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert-success">
                    Pengeluaran berhasil disimpan.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['edited'])): ?>
                <div class="alert-success">
                    Pengeluaran berhasil diperbarui.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert-success">
                    Pengeluaran berhasil dihapus.
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-section">

                <div class="page-header">
                    <div>
                        <h2>Data Pengeluaran</h2>
                        <p>Daftar transaksi pengeluaran SPPG.</p>
                    </div>

                    <button
                        type="button"
                        class="btn btn-primary"
                        onclick="toggleForm('form-pengeluaran', this, '+ Tambah Pengeluaran', '- Tutup Form')"
                    >
                        <?= $edit_data ? '- Tutup Form' : '+ Tambah Pengeluaran'; ?>
                    </button>
                </div>

                <!-- FORM -->
                <div
                    class="form-container"
                    id="form-pengeluaran"
                    style="display: <?= $edit_data ? 'block' : 'none'; ?>;"
                >

                    <h3><?= $edit_data ? 'Edit Pengeluaran' : 'Tambah Pengeluaran'; ?></h3>

                    <form method="POST">

                        <?php if ($edit_data): ?>
                            <input
                                type="hidden"
                                name="id"
                                value="<?= $edit_data['id']; ?>"
                            >
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Tanggal</label>
                            <input
                                type="date"
                                name="transaction_date"
                                value="<?= $edit_data
                                    ? htmlspecialchars($edit_data['transaction_date'])
                                    : date('Y-m-d'); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Kategori</label>

                            <select name="category_id" required>
                                <option value="">-- Pilih Kategori --</option>

                                <?php mysqli_data_seek($query_categories, 0); ?>

                                <?php while ($category = mysqli_fetch_assoc($query_categories)): ?>
                                    <option
                                        value="<?= $category['id']; ?>"
                                        <?= (
                                            $edit_data &&
                                            $edit_data['category_id'] == $category['id']
                                        ) ? 'selected' : ''; ?>
                                    >
                                        <?= htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Purchase Order</label>

                            <select name="purchase_order_id">
                                <option value="">-- Tidak terkait PO --</option>

                                <?php while ($po_option = mysqli_fetch_assoc($query_po)): ?>
                                    <option
                                        value="<?= $po_option['id']; ?>"
                                        <?= (
                                            $edit_data &&
                                            $edit_data['purchase_order_id'] == $po_option['id']
                                        ) ? 'selected' : ''; ?>
                                    >
                                        <?= htmlspecialchars($po_option['po_number']); ?> -
                                        <?= htmlspecialchars($po_option['supplier_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Jumlah</label>

                            <input
                                type="number"
                                name="amount"
                                value="<?= $edit_data ? $edit_data['amount'] : ''; ?>"
                                min="0"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Keterangan</label>

                            <textarea name="description" rows="3"><?= $edit_data
                                ? htmlspecialchars($edit_data['description'])
                                : ''; ?></textarea>
                        </div>

                        <?php if ($edit_data): ?>
                            <button type="submit" name="update_pengeluaran" class="btn btn-primary">
                                <img src="/sppg/assets/icons/simpan.png" alt="" class="btn-icon">
                                Simpan
                            </button>
                            <a href="pengeluaran.php" class="btn">Batal</a>

                        <?php else: ?>
                            <button type="submit" name="simpan_po" class="btn btn-primary">
                                <img src="/sppg/assets/icons/simpan.png" alt="" class="btn-icon">
                                Simpan
                            </button>

                        <?php endif; ?>

                    </form>
                </div>

                <!-- TABEL -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>No. Transaksi</th>
                                <th>Kategori</th>
                                <th>Purchase Order</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                            $no = 1;

                            while ($row = mysqli_fetch_assoc($query_expenses)):
                            ?>

                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['transaction_date'])); ?></td>
                                    <td><?= htmlspecialchars($row['transaction_number']); ?></td>
                                    <td><?= htmlspecialchars($row['category_name']); ?></td>

                                    <td>
                                        <?php if (!empty($row['po_number'])): ?>
                                            <?= htmlspecialchars($row['po_number']); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>

                                    <td>Rp<?= number_format($row['amount'], 0, ',', '.'); ?></td>
                                    <td><?= htmlspecialchars($row['description']); ?></td>

                                    <td>
                                        <a
                                            href="pengeluaran.php?edit=<?= $row['id']; ?>"
                                            class="btn btn-primary"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            class="action-form"
                                            onsubmit="return confirm('Yakin ingin menghapus pengeluaran ini?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= $row['id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="hapus_pengeluaran"
                                                class="btn btn-danger"
                                            >
                                                Hapus
                                            </button>
                                        </form>
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