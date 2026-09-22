<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once "../../config/database.php";

/// CEK ID PURCHASE ORDER
if (!isset($_GET['id'])) {
    header("Location: purchase_order.php");
    exit;
}

$po_id = (int) $_GET['id'];

/// AMBIL DATA PO
$stmt_po = mysqli_prepare(
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
     WHERE po.id = ?"
);

mysqli_stmt_bind_param($stmt_po, "i", $po_id);
mysqli_stmt_execute($stmt_po);

$result_po = mysqli_stmt_get_result($stmt_po);
$po = mysqli_fetch_assoc($result_po);

/// Jika PO tidak ditemukan
if (!$po) {
    die("Purchase Order tidak ditemukan.");
}

if (isset($_POST['hapus_barang'])) {
    $detail_id = (int) $_POST['detail_id'];

    /// Barang hanya boleh dihapus jika PO masih draft
    if ($po['status'] !== 'draft') {
        $error = "Barang tidak dapat dihapus karena PO sudah diproses.";
    } else {
        $stmt_delete = mysqli_prepare(
            $conn,
            "DELETE FROM purchase_order_details
             WHERE id = ?
             AND purchase_order_id = ?"
        );

        mysqli_stmt_bind_param($stmt_delete, "ii", $detail_id, $po_id);

        if (mysqli_stmt_execute($stmt_delete)) {
            header("Location: detail_po.php?id=$po_id&deleted=1");
            exit;
        } else {
            $error = "Barang gagal dihapus.";
        }

        mysqli_stmt_close($stmt_delete);
    }
}

/// Edit barang
if (isset($_POST['edit_barang'])) {
    $detail_id = (int) $_POST['detail_id'];
    $item_id = (int) $_POST['item_id'];
    $quantity = (float) $_POST['quantity'];
    $unit_price = (float) $_POST['unit_price'];

    $subtotal = $quantity * $unit_price;

    /// Edit hanya diperbolehkan jika PO masih draft
    if ($po['status'] !== 'draft') {
        $error = "Barang tidak dapat diedit karena PO sudah diproses.";
    } else {
        $stmt_edit = mysqli_prepare(
            $conn,
            "UPDATE purchase_order_details
             SET item_id = ?,
                 quantity = ?,
                 unit_price = ?,
                 subtotal = ?
             WHERE id = ?
             AND purchase_order_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt_edit,
            "idddii",
            $item_id,
            $quantity,
            $unit_price,
            $subtotal,
            $detail_id,
            $po_id
        );

        if (mysqli_stmt_execute($stmt_edit)) {
            header("Location: detail_po.php?id=$po_id&edited=1");
            exit;
        } else {
            $error = "Barang gagal diperbarui.";
        }

        mysqli_stmt_close($stmt_edit);
    }
}

/// Proses perubahan status
if (isset($_POST['ubah_status'])) {
    $status_baru = $_POST['status_baru'];
    $created_by = $_SESSION['user_id'];

    $status_diizinkan = [
        'diproses',
        'diterima',
        'dibatalkan'
    ];

    if (!in_array($status_baru, $status_diizinkan)) {
        $error = "Status tidak valid.";
    } else {
        $status_lama = $po['status'];
        $boleh_ubah = false;

        if (
            $status_lama === 'draft' &&
            in_array($status_baru, ['diproses', 'dibatalkan'])
        ) {
            $boleh_ubah = true;
        }

        if (
            $status_lama === 'diproses' &&
            in_array($status_baru, ['diterima', 'dibatalkan'])
        ) {
            $boleh_ubah = true;
        }

        if (!$boleh_ubah) {
            $error = "Perubahan status tidak diperbolehkan.";
        } else {

            if ($status_baru === 'diterima') {

                $stmt_check = mysqli_prepare(
                    $conn,
                    "SELECT id
                     FROM stock_movements
                     WHERE reference_type = 'PURCHASE_ORDER'
                     AND reference_id = ?
                     LIMIT 1"
                );

                mysqli_stmt_bind_param($stmt_check, "i", $po_id);
                mysqli_stmt_execute($stmt_check);

                $result_check = mysqli_stmt_get_result($stmt_check);

                if (mysqli_num_rows($result_check) > 0) {
                    $error = "PO ini sudah pernah diterima dan stok sudah dicatat.";
                    mysqli_stmt_close($stmt_check);
                } else {
                    mysqli_stmt_close($stmt_check);

                    $stmt_details = mysqli_prepare(
                        $conn,
                        "SELECT item_id, quantity
                         FROM purchase_order_details
                         WHERE purchase_order_id = ?"
                    );

                    mysqli_stmt_bind_param($stmt_details, "i", $po_id);
                    mysqli_stmt_execute($stmt_details);

                    $result_details = mysqli_stmt_get_result($stmt_details);

                    if (mysqli_num_rows($result_details) === 0) {
                        $error = "PO belum memiliki barang sehingga tidak dapat diterima.";
                        mysqli_stmt_close($stmt_details);
                    } else {

                        mysqli_begin_transaction($conn);

                        try {
                            $movement_date = date('Y-m-d');

                            $description =
                                "Penerimaan barang dari PO " .
                                $po['po_number'];

                            $stmt_stock = mysqli_prepare(
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
                                (?, ?, 'IN', ?, 'PURCHASE_ORDER', ?, ?, ?)"
                            );

                            while ($detail = mysqli_fetch_assoc($result_details)) {
                                $item_id = (int) $detail['item_id'];
                                $quantity = (float) $detail['quantity'];

                                mysqli_stmt_bind_param(
                                    $stmt_stock,
                                    "isdisi",
                                    $item_id,
                                    $movement_date,
                                    $quantity,
                                    $po_id,
                                    $description,
                                    $created_by
                                );

                                if (!mysqli_stmt_execute($stmt_stock)) {
                                    throw new Exception("Gagal mencatat stok.");
                                }
                            }

                            mysqli_stmt_close($stmt_stock);
                            mysqli_stmt_close($stmt_details);

                            $stmt_status = mysqli_prepare(
                                $conn,
                                "UPDATE purchase_orders
                                 SET status = ?
                                 WHERE id = ?"
                            );

                            mysqli_stmt_bind_param(
                                $stmt_status,
                                "si",
                                $status_baru,
                                $po_id
                            );

                            if (!mysqli_stmt_execute($stmt_status)) {
                                throw new Exception("Status PO gagal diperbarui.");
                            }

                            mysqli_stmt_close($stmt_status);

                            mysqli_commit($conn);

                            header(
                                "Location: detail_po.php?id=$po_id&status=1&stock=1"
                            );
                            exit;
                        } catch (Exception $e) {
                            mysqli_rollback($conn);
                            $error = $e->getMessage();
                        }
                    }
                }
            } else {

                $stmt_status = mysqli_prepare(
                    $conn,
                    "UPDATE purchase_orders
                     SET status = ?
                     WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt_status,
                    "si",
                    $status_baru,
                    $po_id
                );

                if (mysqli_stmt_execute($stmt_status)) {
                    header("Location: detail_po.php?id=$po_id&status=1");
                    exit;
                } else {
                    $error = "Status PO gagal diperbarui.";
                }

                mysqli_stmt_close($stmt_status);
            }
        }
    }
}

/// SIMPAN DETAIL BARANG
if (isset($_POST['tambah_barang'])) {
    $item_id = (int) $_POST['item_id'];
    $quantity = (float) $_POST['quantity'];
    $unit_price = (float) $_POST['unit_price'];

    $subtotal = $quantity * $unit_price;

    $stmt_detail = mysqli_prepare(
        $conn,
        "INSERT INTO purchase_order_details
        (
            purchase_order_id,
            item_id,
            quantity,
            unit_price,
            subtotal
        )
        VALUES (?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt_detail,
        "iiddd",
        $po_id,
        $item_id,
        $quantity,
        $unit_price,
        $subtotal
    );

    if (mysqli_stmt_execute($stmt_detail)) {
        header("Location: detail_po.php?id=$po_id&success=1");
        exit;
    } else {
        $error = "Barang gagal ditambahkan.";
    }
}

/// AMBIL MASTER BARANG
$query_items = mysqli_query(
    $conn,
    "SELECT
        id,
        item_code,
        item_name,
        unit
     FROM items
     ORDER BY item_name ASC"
);

/// AMBIL DETAIL BARANG PO
$stmt_items = mysqli_prepare(
    $conn,
    "SELECT
        pod.id,
        pod.quantity,
        pod.unit_price,
        pod.subtotal,
        i.item_code,
        i.item_name,
        i.unit
     FROM purchase_order_details pod
     INNER JOIN items i
        ON pod.item_id = i.id
     WHERE pod.purchase_order_id = ?
     ORDER BY pod.id ASC"
);

mysqli_stmt_bind_param($stmt_items, "i", $po_id);
mysqli_stmt_execute($stmt_items);

$result_items = mysqli_stmt_get_result($stmt_items);

// HITUNG TOTAL PO
$stmt_total = mysqli_prepare(
    $conn,
    "SELECT
        COALESCE(SUM(subtotal), 0) AS total
     FROM purchase_order_details
     WHERE purchase_order_id = ?"
);

mysqli_stmt_bind_param($stmt_total, "i", $po_id);
mysqli_stmt_execute($stmt_total);

$result_total = mysqli_stmt_get_result($stmt_total);
$data_total = mysqli_fetch_assoc($result_total);
$total_po = $data_total['total'];

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Purchase Order</title>
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

    </div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <header>
        <div>
            <h1>Detail Purchase Order</h1>
            <p><?= htmlspecialchars($po['po_number']); ?></p>
        </div>

        <div class="user-info">
            <div class="profile-icon">
                <img src="/sppg/assets/icons/profil.png" alt="">
            </div>

            <div class="user-detail">
                <strong><?= htmlspecialchars($_SESSION['name']); ?></strong>
                <small><?= htmlspecialchars($_SESSION['role']); ?></small>
            </div>

            <div class="header-action">
                <a href="../../logout.php" class="logout">
                    <img src="/sppg/assets/icons/logout.png" alt="" class="logout-icon">
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <main>
        <a href="purchase_order.php" class="btn">← Kembali</a>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert-success">Barang berhasil ditambahkan.</div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert-success">Barang berhasil dihapus.</div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['edited'])): ?>
            <div class="alert-success">Barang berhasil diperbarui.</div>
        <?php endif; ?>

        <?php if (isset($_GET['status'])): ?>
            <div class="alert-success">Status Purchase Order berhasil diperbarui.</div>
        <?php endif; ?>

        <?php if (isset($_GET['stock'])): ?>
            <div class="alert-success">PO berhasil diterima dan stok berhasil ditambahkan.</div>
        <?php endif; ?>

        <!-- INFORMASI PO -->
        <div class="form-container">
            <h2>Informasi Purchase Order</h2>

            <br>

            <p>
                <strong>Nomor PO:</strong>
                <?= htmlspecialchars($po['po_number']); ?>
            </p>

            <p>
                <strong>Tanggal:</strong>
                <?= date('d-m-Y', strtotime($po['po_date'])); ?>
            </p>

            <p>
                <strong>Supplier:</strong>
                <?= htmlspecialchars($po['supplier_name']); ?>
            </p>

            <p>
                <strong>Status:</strong>
                <?= ucfirst($po['status']); ?>
            </p>

            <p>
                <strong>Catatan:</strong>
                <?= htmlspecialchars($po['notes'] ?? '-'); ?>
            </p>
        </div>

        <div class="form-container">
            <h3>Status Purchase Order</h3>

            <p>
                Status saat ini:
                <span class="status status-<?= $po['status']; ?>">
                    <?= strtoupper($po['status']); ?>
                </span>
            </p>

            <?php if ($po['status'] === 'draft'): ?>

                <form method="POST" class="action-form">
                    <input type="hidden" name="status_baru" value="diproses">

                    <button
                        type="submit"
                        name="ubah_status"
                        class="btn btn-primary"
                        onclick="return confirm('Yakin ingin memproses PO ini?');"
                    >
                        Proses PO
                    </button>
                </form>

                <form method="POST" class="action-form">
                    <input type="hidden" name="status_baru" value="dibatalkan">

                    <button
                        type="submit"
                        name="ubah_status"
                        class="btn btn-danger"
                        onclick="return confirm('Yakin ingin membatalkan PO ini?');"
                    >
                        Batalkan PO
                    </button>
                </form>

            <?php elseif ($po['status'] === 'diproses'): ?>

                <form method="POST" class="action-form">
                    <input type="hidden" name="status_baru" value="diterima">

                    <button
                        type="submit"
                        name="ubah_status"
                        class="btn btn-primary"
                        onclick="return confirm('Yakin barang dari PO ini sudah diterima?');"
                    >
                        Terima Barang
                    </button>
                </form>

                <form method="POST" class="action-form">
                    <input type="hidden" name="status_baru" value="dibatalkan">

                    <button
                        type="submit"
                        name="ubah_status"
                        class="btn btn-danger"
                        onclick="return confirm('Yakin ingin membatalkan PO ini?');"
                    >
                        Batalkan PO
                    </button>
                </form>

            <?php elseif ($po['status'] === 'diterima'): ?>

                <p>PO ini sudah diterima.</p>

            <?php elseif ($po['status'] === 'dibatalkan'): ?>

                <p>PO ini telah dibatalkan.</p>

            <?php endif; ?>
        </div>

        <!-- FORM TAMBAH BARANG -->
        <div class="form-container">
            <h2>Tambah Barang</h2>

            <br>

            <form method="POST">
                <div class="form-group">
                    <label>Barang</label>

                    <select name="item_id" required>
                        <option value="">-- Pilih Barang --</option>

                        <?php while ($item = mysqli_fetch_assoc($query_items)): ?>
                            <option value="<?= $item['id']; ?>">
                                <?= htmlspecialchars($item['item_code']); ?> -
                                <?= htmlspecialchars($item['item_name']); ?>
                                (<?= htmlspecialchars($item['unit']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jumlah</label>
                    <input
                        type="number"
                        name="quantity"
                        min="0.01"
                        step="0.01"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Harga Satuan</label>
                    <input
                        type="number"
                        name="unit_price"
                        min="0"
                        step="1"
                        required
                    >
                </div>

                <button
                    type="submit"
                    name="tambah_barang"
                    class="btn btn-primary"
                >
                    + Tambah Barang
                </button>
            </form>
        </div>

        <!-- FORM EDIT BARANG -->
        <?php

        $edit_detail = null;

        if (isset($_GET['edit'])) {
            $edit_id = (int) $_GET['edit'];

            $stmt_edit_form = mysqli_prepare(
                $conn,
                "SELECT
                    pod.id,
                    pod.item_id,
                    pod.quantity,
                    pod.unit_price
                FROM purchase_order_details pod
                WHERE pod.id = ?
                AND pod.purchase_order_id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt_edit_form,
                "ii",
                $edit_id,
                $po_id
            );

            mysqli_stmt_execute($stmt_edit_form);

            $result_edit = mysqli_stmt_get_result($stmt_edit_form);
            $edit_detail = mysqli_fetch_assoc($result_edit);

            mysqli_stmt_close($stmt_edit_form);
        }

        ?>

        <?php if ($edit_detail): ?>

            <div class="form-container">
                <h3>Edit Barang</h3>

                <form method="POST">
                    <input
                        type="hidden"
                        name="detail_id"
                        value="<?= $edit_detail['id']; ?>"
                    >

                    <div class="form-group">
                        <label>Barang</label>

                        <select name="item_id" required>
                            <?php
                            $query_edit_items = mysqli_query(
                                $conn,
                                "SELECT id, item_code, item_name, unit
                                FROM items
                                ORDER BY item_name ASC"
                            );

                            while ($item = mysqli_fetch_assoc($query_edit_items)):
                            ?>
                                <option
                                    value="<?= $item['id']; ?>"
                                    <?= $item['id'] == $edit_detail['item_id'] ? 'selected' : ''; ?>
                                >
                                    <?= htmlspecialchars($item['item_code']); ?> -
                                    <?= htmlspecialchars($item['item_name']); ?>
                                    (<?= htmlspecialchars($item['unit']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Jumlah</label>

                        <input
                            type="number"
                            name="quantity"
                            value="<?= $edit_detail['quantity']; ?>"
                            min="0.01"
                            step="0.01"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Harga Satuan</label>

                        <input
                            type="number"
                            name="unit_price"
                            value="<?= $edit_detail['unit_price']; ?>"
                            min="0"
                            step="0.01"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        name="edit_barang"
                        class="btn btn-primary"
                    >
                        Simpan Perubahan
                    </button>

                    <a
                        href="detail_po.php?id=<?= $po_id; ?>"
                        class="btn"
                    >
                        Batal
                    </a>
                </form>
            </div>

        <?php endif; ?>

        <!-- DAFTAR BARANG -->
        <div class="table-container">
            <h2>Daftar Barang</h2>

            <br>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Barang</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                        <th>aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $no = 1;

                    while ($detail = mysqli_fetch_assoc($result_items)):
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($detail['item_code']); ?></td>
                            <td><?= htmlspecialchars($detail['item_name']); ?></td>
                            <td>
                                <?= number_format($detail['quantity'], 2, ',', '.'); ?>
                                <?= htmlspecialchars($detail['unit']); ?>
                            </td>
                            <td>
                                Rp<?= number_format($detail['unit_price'], 0, ',', '.'); ?>
                            </td>
                            <td>
                                Rp<?= number_format($detail['subtotal'], 0, ',', '.'); ?>
                            </td>

                            <td>
                                <?php if ($po['status'] === 'draft'): ?>

                                    <a
                                        href="detail_po.php?id=<?= $po_id; ?>&edit=<?= $detail['id']; ?>"
                                        class="btn btn-primary"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        class="action-form"
                                        onsubmit="return confirm('Yakin ingin menghapus barang ini?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="detail_id"
                                            value="<?= $detail['id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="hapus_barang"
                                            class="btn btn-danger"
                                        >
                                            Hapus
                                        </button>
                                    </form>

                                <?php else: ?>

                                    -

                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="5" style="text-align:right;">
                            Total PO
                        </th>

                        <th>
                            Rp<?= number_format($total_po, 0, ',', '.'); ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </main>
</div>

<script src="../../assets/js/script.js"></script>

</body>

</html>