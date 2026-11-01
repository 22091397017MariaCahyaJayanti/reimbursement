
<?php
session_start();

// Koneksi database
$mysqli = new mysqli("localhost", "root", "", "reimbustmen");

if ($mysqli->connect_error) {
    die("Koneksi database gagal: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");

$success = "";
$error = "";

// Proses tambah user
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (empty($username) || empty($email) || empty($password)) {

        $error = "Semua kolom wajib diisi!";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid!";

    } elseif (strlen($password) < 8) {

        $error = "Password minimal 8 karakter!";

    } elseif (!in_array($role, ['user', 'admin'], true)) {

        $error = "Role tidak valid!";

    } else {

        // Cek email
        $check = $mysqli->prepare(
            "SELECT id FROM user WHERE email = ?"
        );

        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $error = "Email sudah terdaftar!";

        } else {

            // Hash password
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Simpan user
            $stmt = $mysqli->prepare(
                "INSERT INTO user
                (email, password, role, `created at`, username)
                VALUES (?, ?, ?, NOW(), ?)"
            );

            $stmt->bind_param(
                "ssss",
                $email,
                $hashed_password,
                $role,
                $username
            );

            if ($stmt->execute()) {

                $success = "User berhasil ditambahkan!";

            } else {

                $error = "Gagal menambahkan user: " . $stmt->error;

            }

            $stmt->close();
        }

        $check->close();
    }
}

// Mengambil daftar user
$users = $mysqli->query(
    "SELECT id, email, role, `created at`, username
     FROM user
     ORDER BY id DESC"
);

if (!$users) {
    die("Gagal mengambil data user: " . $mysqli->error);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola User - Admin</title>

    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <link rel="shortcut icon" href="assets/images/favicon.jpg">
</head>

<body>

<div class="container-scroller">

    <!-- Navbar -->
    <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-center">

        <div class="navbar-brand-wrapper d-flex align-items-center">
            <a class="navbar-brand brand-logo" href="dashboard_admin.php">
                <img src="assets/images/logo.svg" alt="logo">
            </a>
        </div>

        <div class="navbar-menu-wrapper d-flex align-items-center">
            <h4>Kelola User</h4>
        </div>

    </nav>

    <div class="container-fluid page-body-wrapper">

        <!-- Sidebar -->
        <nav class="sidebar sidebar-offcanvas" id="sidebar">

            <ul class="nav">

                <li class="nav-item">
                    <a class="nav-link" href="dashboard_admin.php">
                        <i class="mdi mdi-grid-large menu-icon"></i>
                        <span class="menu-title">Dashboard</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="users.php">
                        <i class="mdi mdi-account-multiple menu-icon"></i>
                        <span class="menu-title">Kelola User</span>
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

        <!-- Main Panel -->
        <div class="main-panel">

            <div class="content-wrapper">

                <div class="row">

                    <!-- Form Tambah User -->
                    <div class="col-lg-5 grid-margin stretch-card">

                        <div class="card">
                            <div class="card-body">

                                <h4 class="card-title">Tambah User</h4>

                                <p class="card-description">
                                    Masukkan informasi akun pengguna baru.
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

                                <form method="POST" action="users.php">

                                    <div class="form-group">
                                        <label>Nama Lengkap</label>
                                        <input
                                            type="text"
                                            name="username"
                                            class="form-control"
                                            placeholder="Masukkan nama lengkap"
                                            maxlength="50"
                                            required>
                                    </div>

                                    <div class="form-group">
                                        <label>Email</label>
                                        <input
                                            type="email"
                                            name="email"
                                            class="form-control"
                                            placeholder="Masukkan email"
                                            maxlength="100"
                                            required>
                                    </div>

                                    <div class="form-group">
                                        <label>Password</label>
                                        <input
                                            type="password"
                                            name="password"
                                            class="form-control"
                                            placeholder="Masukkan password"
                                            minlength="8"
                                            required>
                                        <small class="text-muted">
                                            Password minimal 8 karakter.
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label>Role</label>

                                        <select name="role" class="form-control" required>
                                            <option value="user">User</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-account-plus"></i>
                                        Tambah User
                                    </button>

                                    <a href="dashboard_admin.php" class="btn btn-light">
                                        Kembali
                                    </a>

                                </form>

                            </div>
                        </div>

                    </div>

                    <!-- Daftar User -->
                    <div class="col-lg-7 grid-margin stretch-card">

                        <div class="card">
                            <div class="card-body">

                                <h4 class="card-title">Daftar User</h4>

                                <p class="card-description">
                                    Informasi pengguna yang sudah terdaftar.
                                </p>

                                <div class="table-responsive">

                                    <table class="table">

                                        <thead>
                                            <tr>
                                                <th>No.</th>
                                                <th>Nama</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Tanggal Dibuat</th>
                                            </tr>
                                        </thead>

                                        <tbody>

                                            <?php
                                            $no = 1;

                                            while ($row = $users->fetch_assoc()):
                                            ?>

                                            <tr>

                                                <td>
                                                    <?php echo $no++; ?>
                                                </td>

                                                <td>
                                                    <?php echo htmlspecialchars($row['username']); ?>
                                                </td>

                                                <td>
                                                    <?php echo htmlspecialchars($row['email']); ?>
                                                </td>

                                                <td>
                                                    <?php if ($row['role'] == 'admin'): ?>
                                                        <label class="badge badge-danger">
                                                            Admin
                                                        </label>
                                                    <?php else: ?>
                                                        <label class="badge badge-success">
                                                            User
                                                        </label>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <?php echo htmlspecialchars($row['created at']); ?>
                                                </td>

                                            </tr>

                                            <?php endwhile; ?>

                                        </tbody>

                                    </table>

                                </div>

                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <!-- Footer -->
            <footer class="footer">
                <div class="d-sm-flex justify-content-center justify-content-sm-between">
                    <span class="text-center">
                        Copyright © 2023. All rights reserved.
                    </span>
                </div>
            </footer>

        </div>

    </div>

</div>

<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/template.js"></script>

</body>
</html>

<?php
$mysqli->close();
?>
