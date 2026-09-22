<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once "../../config/database.php";

/// SIMPAN PURCHASE ORDER
if (isset($_POST['simpan_po'])) {
    $supplier_id = (int) $_POST['supplier_id'];
    $po_date = $_POST['po_date'];
    $notes = trim($_POST['notes']);
    $created_by = $_SESSION['user_id'];

    /// Cari nomor PO terakhir pada bulan berjalan
    $bulan = date('Ym');

    $query_last_po = mysqli_query(
        $conn,
        "SELECT po_number
         FROM purchase_orders
         WHERE po_number LIKE 'PO-$bulan-%'
         ORDER BY id DESC
         LIMIT 1"
    );

    if (mysqli_num_rows($query_last_po) > 0) {
        $last_po = mysqli_fetch_assoc($query_last_po);

        /// Ambil nomor urut dari PO terakhir
        $last_number = (int) substr($last_po['po_number'], -3);
        $next_number = $last_number + 1;
    } else {
        $next_number = 1;
    }

    /// Format nomor PO
    $po_number = 'PO-' . $bulan . '-' . str_pad($next_number, 3, '0', STR_PAD_LEFT);

    // Simpan ke database
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO purchase_orders
        (
            po_number,
            supplier_id,
            po_date,
            status,
            notes,
            created_by
        )
        VALUES (?, ?, ?, 'draft', ?, ?)"
    );

    mysqli_stmt_bind_param($stmt, "sissi", $po_number, $supplier_id, $po_date, $notes, $created_by);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: purchase_order.php?success=1");
        exit;
    } else {
        $error = "Gagal menyimpan Purchase Order: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

/// AMBIL DATA PURCHASE ORDER
$query_po = mysqli_query(
    $conn,
    "SELECT
        po.id,
        po.po_number,
        po.po_date,
        po.status,
        po.notes,
        s.supplier_name
     FROM purchase_orders po
     INNER JOIN suppliers s
        ON po.supplier_id = s.id
     ORDER BY po.id DESC"
);

/// AMBIL DATA SUPPLIER
$query_supplier = mysqli_query(
    $conn,
    "SELECT id, supplier_name
     FROM suppliers
     ORDER BY supplier_name ASC"
);

/// Ambil data supplier
$query_supplier = mysqli_query(
    $conn,
    "SELECT id, supplier_name
     FROM suppliers
     ORDER BY supplier_name ASC"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order</title>
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
                <button type="button" class="top-menu-item dropdown-toggle active">
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
                <img src="/sppg/assets/icons/purchase-order1.png" alt="">
            </div>

            <div>
                <h1>Purchase Order</h1>
                <p>Pengelolaan pengadaan barang SPPG</p>
            </div>
        </div>
    </header>

    <main>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert-success">Purchase Order berhasil disimpan.</div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <h2>Daftar Purchase Order</h2>
            </div>

            <button
                type="button"
                class="btn btn-primary"
                onclick="toggleForm('form-po', this, '+ Tambah PO', '- Tutup Form')"
            >
                + Tambah PO
            </button>
        </div>

        <!-- FORM PO -->
        <div class="form-container" id="form-po" style="display: none;">
            <h2 style="margin-bottom:20px;">Tambah Purchase Order</h2>

            <form method="POST">
                <div class="form-group">
                    <label>Nomor PO</label>
                    <input type="text" value="Nomor PO akan dibuat otomatis" disabled>
                </div>

                <div class="form-group">
                    <label>Supplier</label>

                    <select name="supplier_id" required>
                        <option value="">-- Pilih Supplier --</option>

                        <?php while ($supplier = mysqli_fetch_assoc($query_supplier)): ?>
                            <option value="<?= $supplier['id']; ?>">
                                <?= htmlspecialchars($supplier['supplier_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tanggal PO</label>
                    <input type="date" name="po_date" value="<?= date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="notes" rows="3" placeholder="Catatan pengadaan..."></textarea>
                </div>

                <button type="submit" name="simpan_po" class="btn btn-primary">
                    <img src="/sppg/assets/icons/simpan.png" alt="" class="btn-icon">
                    Simpan
                </button>
                
            </form>
        </div>

        <!-- TABEL PO -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nomor PO</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $no = 1;
                    while ($po = mysqli_fetch_assoc($query_po)):
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($po['po_number']); ?></td>
                            <td><?= date('d-m-Y', strtotime($po['po_date'])); ?></td>
                            <td><?= htmlspecialchars($po['supplier_name']); ?></td>
                            <td>
                                <span class="status status-<?= $po['status']; ?>">
                                    <?= ucfirst($po['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="detail_po.php?id=<?= $po['id']; ?>">Detail</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script src="../../assets/js/script.js"></script>

</body>
</html>