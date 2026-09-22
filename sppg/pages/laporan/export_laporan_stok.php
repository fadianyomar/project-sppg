<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    exit("Akses ditolak.");
}

require_once "../../config/database.php";

/// FILTER
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'semua';

/// DATA STOK
$stmt = mysqli_prepare(
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

mysqli_stmt_bind_param($stmt, "s", $tanggal_selesai);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

/// DATA PERIODE
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

$period_data = [];

while ($period = mysqli_fetch_assoc($result_period)) {
    $period_data[$period['item_id']] = [
        'period_in' => $period['period_in'],
        'period_out' => $period['period_out']
    ];
}

mysqli_stmt_close($stmt_period);

/// EXPORT EXCEL
header("Content-Type: application/vnd.ms-excel");
header(
    "Content-Disposition: attachment; filename=Laporan_Stok_"
    . $tanggal_mulai
    . "_"
    . $tanggal_selesai
    . ".xls"
);
header("Pragma: no-cache");
header("Expires: 0");

?>

<table border="1">
    <tr>
        <th colspan="8">LAPORAN STOK SPPG</th>
    </tr>
    <tr>
        <td colspan="8">
            Periode:
            <?= htmlspecialchars($tanggal_mulai); ?>
            s/d
            <?= htmlspecialchars($tanggal_selesai); ?>
        </td>
    </tr>
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

    <?php
    $no = 1;

    while ($row = mysqli_fetch_assoc($result)):
        $period_in = 0;
        $period_out = 0;

        if (isset($period_data[$row['id']])) {
            $period_in = $period_data[$row['id']]['period_in'];
            $period_out = $period_data[$row['id']]['period_out'];
        }

        $stock_akhir = $row['stock_in'] - $row['stock_out'];

        if ($stock_akhir <= 0) {
            $status = "Habis";
            $status_value = "habis";
        } elseif ($stock_akhir <= $row['minimum_stock']) {
            $status = "Menipis";
            $status_value = "menipis";
        } else {
            $status = "Aman";
            $status_value = "aman";
        }

        /// FILTER STATUS
        if ($status_filter !== 'semua' && $status_filter !== $status_value) {
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
            <td><?= $status; ?></td>
        </tr>

    <?php endwhile; ?>
</table>