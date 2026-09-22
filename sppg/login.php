<?php

session_start();

require_once "config/database.php";

$error = "";

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = mysqli_query(
        $conn,
        "SELECT * FROM users WHERE username = '$username' LIMIT 1"
    );

    if (mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);

        if ($password == $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: index.php");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem SPPG</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2">
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo"></div>

                <h1>Login Sistem SPPG</h1>
                <p>Sistem Pengelolaan SPPG</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="login-alert">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="login-form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Masukkan username"
                        required
                    >
                </div>

                <div class="login-form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        required
                    >
                </div>

                <button
                    type="submit"
                    name="login"
                    class="login-button"
                >
                    Login
                </button>
            </form>

            <div class="login-footer">
                <p>Sistem Manajemen Keuangan & Stok SPPG</p>
            </div>
        </div>
    </div>
</body>

</html>