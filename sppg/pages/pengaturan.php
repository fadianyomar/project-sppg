<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

$user_id = $_SESSION['user_id'];

$success = "";
$error = "";

/// UPDATE PROFIL
if (isset($_POST['simpan_profil'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);

    if ($name == "" || $username == "") {
        $error = "Nama dan username wajib diisi.";
    } else {

        $stmt_check = mysqli_prepare(
            $conn,
            "SELECT id
             FROM users
             WHERE username = ?
             AND id != ?"
        );

        mysqli_stmt_bind_param($stmt_check, "si", $username, $user_id);
        mysqli_stmt_execute($stmt_check);

        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            $error = "Username sudah digunakan.";
        } else {
            $stmt_update = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET name = ?, username = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param($stmt_update, "ssi", $name, $username, $user_id);

            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['name'] = $name;
                $_SESSION['username'] = $username;

                $success = "Profil berhasil diperbarui.";
            } else {
                $error = "Profil gagal diperbarui.";
            }

            mysqli_stmt_close($stmt_update);
        }

        mysqli_stmt_close($stmt_check);
    }
}

/// GANTI PASSWORD
if (isset($_POST['ganti_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    /// Ambil password lama dari database
    $stmt_password = mysqli_prepare(
        $conn,
        "SELECT password
         FROM users
         WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt_password, "i", $user_id);
    mysqli_stmt_execute($stmt_password);

    $result_password = mysqli_stmt_get_result($stmt_password);
    $data_password = mysqli_fetch_assoc($result_password);

    mysqli_stmt_close($stmt_password);

    /// Periksa password lama
    if ($password_lama !== $data_password['password']) {
        $error = "Password lama tidak sesuai.";
    } elseif (strlen($password_baru) < 6) {
        $error = "Password baru minimal 6 karakter.";
    } elseif ($password_baru !== $konfirmasi_password) {
        $error = "Konfirmasi password tidak sesuai.";
    } else {
        $stmt_update_password = mysqli_prepare(
            $conn,
            "UPDATE users
             SET password = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt_update_password, "si", $password_baru, $user_id);

        if (mysqli_stmt_execute($stmt_update_password)) {
            $success = "Password berhasil diubah.";
        } else {
            $error = "Password gagal diubah.";
        }

        mysqli_stmt_close($stmt_update_password);
    }
}

/// AMBIL DATA USER
$stmt_user = mysqli_prepare(
    $conn,
    "SELECT
        name,
        username,
        role
     FROM users
     WHERE id = ?"
);

mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);

$result_user = mysqli_stmt_get_result($stmt_user);
$user = mysqli_fetch_assoc($result_user);

mysqli_stmt_close($stmt_user);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pengaturan - SPPG</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <h2>SPPG</h2>
            <p>Sistem Pengelolaan SPPG</p>
        </div>

        <nav>
            <a href="../index.php">Dashboard</a>

            <div class="menu-title">Pengadaan</div>
            <a href="pengadaan/purchase_order.php">Purchase Order</a>

            <div class="menu-title">Transaksi</div>
            <a href="transaksi/dana_masuk.php">Dana Masuk</a>
            <a href="transaksi/pengeluaran.php">Pengeluaran</a>

            <div class="menu-title">Stok</div>
            <a href="stok/stok_saat_ini.php">Stok Saat Ini</a>
            <a href="stok/riwayat_stok.php">Riwayat Stok</a>
            <a href="/sppg/pages/stok/pemakaian_barang.php">Pemakaian Barang</a>

            <div class="menu-title">Laporan</div>
            <a href="laporan/laporan_keuangan.php">Laporan Keuangan</a>
            <a href="laporan/laporan_stok.php">Laporan Stok</a>

            <div class="menu-title">Sistem</div>
            <a href="pengaturan.php" class="active">Pengaturan</a>
        </nav>
    </div>

    <!-- MAIN CONTENT -->

    <div class="main-content">
        <header>
            <div>
                <h1>Pengaturan</h1>
                <p>Pengaturan akun pengguna</p>
            </div>

            <div class="user-info">
                <div>
                    <strong><?= htmlspecialchars($_SESSION['name']); ?></strong>
                    <small><?= htmlspecialchars($_SESSION['role']); ?></small>
                </div>

                <a href="../logout.php" class="logout">Logout</a>
            </div>
        </header>

        <main>
            <?php if ($success): ?>
                <div class="alert-success">
                    <?= htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- PROFIL -->
            <div class="dashboard-section">
                <h2>Profil Pengguna</h2>

                <form method="POST">
                    <div class="form-group">
                        <label>Nama</label>
                        <input
                            type="text"
                            name="name"
                            value="<?= htmlspecialchars($user['name']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input
                            type="text"
                            name="username"
                            value="<?= htmlspecialchars($user['username']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($user['role']); ?>"
                            readonly
                        >
                    </div>

                    <button
                        type="submit"
                        name="simpan_profil"
                        class="btn btn-primary"
                    >
                        Simpan Perubahan
                    </button>
                </form>
            </div>

            <!-- GANTI PASSWORD -->
            <div class="dashboard-section">
                <h2>Ganti Password</h2>

                <form method="POST">
                    <div class="form-group">
                        <label>Password Lama</label>
                        <input
                            type="password"
                            name="password_lama"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Password Baru</label>
                        <input
                            type="password"
                            name="password_baru"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Konfirmasi Password Baru</label>
                        <input
                            type="password"
                            name="konfirmasi_password"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        name="ganti_password"
                        class="btn btn-primary"
                    >
                        Ganti Password
                    </button>
                </form>
            </div>
        </main>
    </div>

</body>

</html>