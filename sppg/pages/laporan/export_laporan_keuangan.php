<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    exit("Akses ditolak.");
}

require_once "../../config/database.php";

/// FILTER
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : date('Y-m-d');
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';

/// HEADER EXCEL
$filename = "laporan_keuangan_" . $tanggal_mulai . "_sampai_" . $tanggal_selesai . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

/// QUERY TRANSAKSI
if ($kategori === 'masuk') {
    $stmt = mysqli_prepare(
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
         ORDER BY transaction_date ASC, id ASC"
    );

    mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_selesai);

} elseif ($kategori === 'pengeluaran') {
    $stmt = mysqli_prepare(
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
         ORDER BY et.transaction_date ASC, et.id ASC"
    );

    mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_selesai);

} else {
    $stmt = mysqli_prepare(
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
         ORDER BY transaction_date ASC, id ASC"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $tanggal_mulai,
        $tanggal_selesai,
        $tanggal_mulai,
        $tanggal_selesai
    );
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

/// OUTPUT EXCEL

echo '<table border="1">';

echo '<tr>';
echo '<th colspan="7">LAPORAN KEUANGAN SPPG</th>';
echo '</tr>';

echo '<tr>';
echo '<td colspan="7">Periode: ' . htmlspecialchars($tanggal_mulai) . ' s/d ' . htmlspecialchars($tanggal_selesai) . '</td>';
echo '</tr>';

echo '<tr>';
echo '<th>No</th>';
echo '<th>Tanggal</th>';
echo '<th>No. Transaksi</th>';
echo '<th>Jenis</th>';
echo '<th>Kategori</th>';
echo '<th>Jumlah</th>';
echo '<th>Keterangan</th>';
echo '</tr>';

$no = 1;

while ($row = mysqli_fetch_assoc($result)) {
    echo '<tr>';

    echo '<td>' . $no++ . '</td>';
    echo '<td>' . date('d-m-Y', strtotime($row['transaction_date'])) . '</td>';
    echo '<td>' . htmlspecialchars($row['transaction_number']) . '</td>';
    echo '<td>' . htmlspecialchars($row['transaction_type']) . '</td>';
    echo '<td>' . htmlspecialchars($row['category']) . '</td>';
    echo '<td>' . $row['amount'] . '</td>';
    echo '<td>' . htmlspecialchars($row['description']) . '</td>';

    echo '</tr>';
}

echo '</table>';

mysqli_stmt_close($stmt);

exit;