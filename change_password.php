
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "reimbustmen");

if ($mysqli->connect_error) {
    die("Koneksi database gagal.");
}

$mysqli->set_charset("utf8mb4");

$success = "";
$error = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {

        $error = "Permintaan tidak valid. Refresh halaman.";

    } elseif ($current === '' || $new === '' || $confirm === '') {

        $error = "Semua kolom wajib diisi!";

    } elseif (strlen($new) < 8) {

        $error = "Password baru minimal 8 karakter!";

    } elseif ($new !== $confirm) {

        $error = "Konfirmasi password tidak cocok!";

    } elseif ($current === $new) {

        $error = "Password baru harus berbeda dari password lama!";

    } else {

        $stmt = $mysqli->prepare(
            "SELECT password FROM user WHERE id = ?"
        );

        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($current, $user['password'])) {

            $error = "Password lama salah!";

        } else {

            $hashed = password_hash($new, PASSWORD_DEFAULT);

            $update = $mysqli->prepare(
                "UPDATE user SET password = ? WHERE id = ?"
            );

            $update->bind_param(
                "si",
                $hashed,
                $_SESSION['user_id']
            );

            if ($update->execute()) {
                $success = "Password berhasil diperbarui!";
            } else {
                $error = "Gagal memperbarui password!";
            }

            $update->close();
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ganti Password</title>

    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/images/favicon.jpg">
</head>

<body class="with-welcome-text">

    <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">

        <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">

            <div class="me-3">
                <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
                    <span class="icon-menu"></span>
                </button>
            </div>

            <div>
                <a class="navbar-brand brand-logo" href="dashboard_user.php">
                    <img src="assets/images/logo.svg" alt="logo">
                </a>

                <a class="navbar-brand brand-logo-mini" href="dashboard_user.php">
                    <img src="assets/images/logo-mini.svg" alt="logo">
                </a>
            </div>

        </div>

        <div class="navbar-menu-wrapper d-flex align-items-center">

            <h4>Ganti Password</h4>

        </div>

    </nav>


    <div class="container-fluid page-body-wrapper">

        <nav class="sidebar sidebar-offcanvas" id="sidebar">

            <ul class="nav">

                <li class="nav-item">
                    <a class="nav-link" href="dashboard_user.php">
                        <i class="mdi mdi-grid-large menu-icon"></i>
                        <span class="menu-title">Dashboard</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="change_password.php">
                        <i class="mdi mdi-lock-reset menu-icon"></i>
                        <span class="menu-title">Ganti Password</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="mdi mdi-logout menu-icon"></i>
                        <span class="menu-title">Logout</span>
                    </a>
                </li>

            </ul>

        </nav>


        <div class="main-panel">

            <div class="content-wrapper">

                <div class="row">

                    <div class="col-lg-6 grid-margin stretch-card">

                        <div class="card">

                            <div class="card-body">

                                <h4 class="card-title">Ganti Password</h4>

                                <p class="card-description">
                                    Ubah password akun kamu.
                                </p>

                                <?php if ($success): ?>
                                    <div class="alert alert-success">
                                        <?php echo htmlspecialchars($success); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger">
                                        <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="change_password.php">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                                    >

                                    <div class="form-group">

                                        <label>Password Lama</label>

                                        <input
                                            type="password"
                                            name="current_password"
                                            class="form-control"
                                            placeholder="Masukkan password lama"
                                            required
                                        >

                                    </div>

                                    <div class="form-group">

                                        <label>Password Baru</label>

                                        <input
                                            type="password"
                                            name="new_password"
                                            class="form-control"
                                            placeholder="Masukkan password baru"
                                            minlength="8"
                                            required
                                        >

                                    </div>

                                    <div class="form-group">

                                        <label>Konfirmasi Password Baru</label>

                                        <input
                                            type="password"
                                            name="confirm_password"
                                            class="form-control"
                                            placeholder="Ulangi password baru"
                                            minlength="8"
                                            required
                                        >

                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-lock-reset"></i>
                                        Simpan Password Baru
                                    </button>

                                    <a href="dashboard_user.php" class="btn btn-light">
                                        Kembali
                                    </a>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <footer class="footer">
                <div class="d-sm-flex justify-content-center justify-content-sm-between">
                    <span>Copyright © 2024. All rights reserved.</span>
                </div>
            </footer>

        </div>

    </div>

    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <script src="assets/js/template.js"></script>

</body>

</html>

<?php
$mysqli->close();
?>
