<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once "../../config/database.php";

/// PROSES HAPUS DANA MASUK
if (isset($_POST['hapus_dana'])) {
    $id = (int) $_POST['id'];

    $stmt_delete = mysqli_prepare(
        $conn,
        "DELETE FROM income_transactions
         WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt_delete, "i", $id);

    if (mysqli_stmt_execute($stmt_delete)) {
        header("Location: dana_masuk.php?deleted=1");
        exit;
    } else {
        $error = "Dana masuk gagal dihapus.";
    }

    mysqli_stmt_close($stmt_delete);
}

/// AMBIL DATA UNTUK EDIT
$edit_data = null;

if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];

    $stmt_edit_data = mysqli_prepare(
        $conn,
        "SELECT
            id,
            transaction_number,
            transaction_date,
            source,
            category,
            amount,
            description
         FROM income_transactions
         WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt_edit_data, "i", $edit_id);
    mysqli_stmt_execute($stmt_edit_data);

    $result_edit_data = mysqli_stmt_get_result($stmt_edit_data);
    $edit_data = mysqli_fetch_assoc($result_edit_data);

    mysqli_stmt_close($stmt_edit_data);
}

/// PROSES UPDATE DANA MASUK
if (isset($_POST['update_dana'])) {
    $id               = (int) $_POST['id'];
    $transaction_date = $_POST['transaction_date'];
    $source           = trim($_POST['source']);
    $category         = trim($_POST['category']);
    $amount           = (float) $_POST['amount'];
    $description      = trim($_POST['description']);

    $stmt_update = mysqli_prepare(
        $conn,
        "UPDATE income_transactions
         SET
            transaction_date = ?,
            source = ?,
            category = ?,
            amount = ?,
            description = ?
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt_update,
        "sssdsi",
        $transaction_date,
        $source,
        $category,
        $amount,
        $description,
        $id
    );

    if (mysqli_stmt_execute($stmt_update)) {
        header("Location: dana_masuk.php?edited=1");
        exit;
    } else {
        $error = "Dana masuk gagal diperbarui: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt_update);
}

/// PROSES SIMPAN DANA MASUK
if (isset($_POST['simpan_dana'])) {
    $transaction_date = $_POST['transaction_date'];
    $source           = trim($_POST['source']);
    $category         = trim($_POST['category']);
    $amount           = (float) $_POST['amount'];
    $description      = trim($_POST['description']);
    $created_by       = $_SESSION['user_id'];

    /// Membuat nomor transaksi otomatis
    $bulan = date('Ym', strtotime($transaction_date));

    $query_last = mysqli_query(
        $conn,
        "SELECT transaction_number
         FROM income_transactions
         WHERE transaction_number LIKE 'DM-$bulan-%'
         ORDER BY id DESC
         LIMIT 1"
    );

    if (mysqli_num_rows($query_last) > 0) {
        $last = mysqli_fetch_assoc($query_last);

        $last_number = (int) substr($last['transaction_number'], -3);
        $next_number = $last_number + 1;
    } else {
        $next_number = 1;
    }

    $transaction_number =
        'DM-' .
        $bulan .
        '-' .
        str_pad(
            $next_number,
            3,
            '0',
            STR_PAD_LEFT
        );

    /// Simpan ke database
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO income_transactions
        (
            transaction_number,
            transaction_date,
            source,
            category,
            amount,
            description,
            created_by
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssssdsi",
        $transaction_number,
        $transaction_date,
        $source,
        $category,
        $amount,
        $description,
        $created_by
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: dana_masuk.php?success=1");
        exit;
    } else {
        $error = "Dana masuk gagal disimpan: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

/// AMBIL DATA DANA MASUK

$query = mysqli_query(
    $conn,
    "SELECT
        id,
        transaction_number,
        transaction_date,
        source,
        category,
        amount,
        description
     FROM income_transactions
     ORDER BY
        transaction_date DESC,
        id DESC"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dana Masuk</title>
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
                    <img src="/sppg/assets/icons/dana-masuk1.png" alt="">
                </div>

                <div>
                    <h1>Dana Masuk</h1>
                    <p>Kelola transaksi dana masuk</p>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <main>
            <?php if (isset($_GET['success'])): ?>
                <div class="alert-success">Dana masuk berhasil disimpan.</div>
            <?php endif; ?>

            <?php if (isset($_GET['edited'])): ?>
                <div class="alert-success">Dana masuk berhasil diperbarui.</div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert-success">Dana masuk berhasil dihapus.</div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert-error"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="dashboard-section">

                <!-- HEADER SECTION -->
                <div class="page-header">
                    <div>
                        <h2>Riwayat Dana Masuk</h2>
                        <p>Daftar transaksi Dana Masuk SPPG.</p>
                    </div>

                    <button
                        type="button"
                        class="btn btn-primary"
                        onclick="toggleForm('form-dana', this, '+ Tambah Dana Masuk', '- Tutup Form')"
                    >
                        <?= $edit_data
                            ? '- Tutup Form'
                            : '+ Tambah Dana Masuk'; ?>
                    </button>
                </div>

                <!-- FORM -->
                <div
                    class="form-container"
                    id="form-dana"
                    style="display: <?= $edit_data ? 'block' : 'none'; ?>;"
                >
                    <h2><?= $edit_data ? 'Edit Dana Masuk' : 'Tambah Dana Masuk'; ?></h2>

                    <br>

                    <form method="POST" action="">

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
                            <label>Sumber Dana</label>
                            <input
                                type="text"
                                name="source"
                                placeholder="Contoh: BGN"
                                value="<?= $edit_data
                                    ? htmlspecialchars($edit_data['source'])
                                    : ''; ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Kategori</label>
                            <input
                                type="text"
                                name="category"
                                placeholder="Contoh: Dana Operasional"
                                value="<?= $edit_data
                                    ? htmlspecialchars($edit_data['category'])
                                    : ''; ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Jumlah</label>
                            <input
                                type="number"
                                name="amount"
                                min="1"
                                step="0.01"
                                placeholder="Contoh: 50000000"
                                value="<?= $edit_data
                                    ? htmlspecialchars($edit_data['amount'])
                                    : ''; ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Keterangan</label>
                            <textarea
                                name="description"
                                rows="4"
                                placeholder="Keterangan dana masuk"
                            ><?= $edit_data
                                ? htmlspecialchars($edit_data['description'])
                                : ''; ?></textarea>
                        </div>

                        <?php if ($edit_data): ?>
                            <button type="submit" name="update_dana" class="btn btn-primary">
                                <img src="/sppg/assets/icons/simpan.png" alt="" class="btn-icon">
                                Simpan
                            </button>
                            <a href="dana_masuk.php" class="btn">Batal</a>

                        <?php else: ?>
                            <button type="submit" name="simpan_po" class="btn btn-primary">
                                <img src="/sppg/assets/icons/simpan.png" alt="" class="btn-icon">
                                Simpan
                            </button>

                        <?php endif; ?>

                    </form>
                </div>

                <!-- TABLE -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>No. Transaksi</th>
                                <th>Sumber Dana</th>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $no = 1;

                            while ($row = mysqli_fetch_assoc($query)):
                            ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['transaction_date'])); ?></td>
                                    <td><?= htmlspecialchars($row['transaction_number']); ?></td>
                                    <td><?= htmlspecialchars($row['source']); ?></td>
                                    <td><?= htmlspecialchars($row['category']); ?></td>
                                    <td>Rp<?= number_format($row['amount'], 0, ',', '.'); ?></td>
                                    <td><?= htmlspecialchars($row['description']); ?></td>

                                    <td>
                                        <a
                                            href="dana_masuk.php?edit=<?= $row['id']; ?>#form-dana"
                                            class="btn btn-primary"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            class="action-form"
                                            onsubmit="return confirm('Yakin ingin menghapus transaksi dana masuk ini?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= $row['id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="hapus_dana"
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