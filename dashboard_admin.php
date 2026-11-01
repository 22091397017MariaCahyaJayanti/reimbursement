
<?php
session_start();

$mysqli = new mysqli("localhost", "root", "", "reimbustmen");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");

// =====================================
// FLASH MESSAGE
// =====================================
$success = $_SESSION["flash_success"] ?? "";
$error = $_SESSION["flash_error"] ?? "";
$reopenModal = $_SESSION["reopen_modal"] ?? "";
$editForm = $_SESSION["edit_form"] ?? [];

unset(
    $_SESSION["flash_success"],
    $_SESSION["flash_error"],
    $_SESSION["reopen_modal"],
    $_SESSION["edit_form"]
);

function redirectDashboard() {
    header("Location: dashboard_admin.php");
    exit;
}

// =====================================
// ADD USER - USER ROLE ONLY
// =====================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_user"])) {

    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $email === "" || $password === "") {
        $_SESSION["flash_error"] = "All fields are required.";
        $_SESSION["reopen_modal"] = "add";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["flash_error"] = "Invalid email format.";
        $_SESSION["reopen_modal"] = "add";

    } elseif (strlen($password) < 6) {
        $_SESSION["flash_error"] = "Password must be at least 6 characters.";
        $_SESSION["reopen_modal"] = "add";

    } else {

        $check = $mysqli->prepare("SELECT id FROM user WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $_SESSION["flash_error"] = "Email is already registered.";
            $_SESSION["reopen_modal"] = "add";

        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = "user";

            $stmt = $mysqli->prepare("
                INSERT INTO user
                (email, password, role, `created at`, username)
                VALUES (?, ?, ?, NOW(), ?)
            ");

            $stmt->bind_param("ssss", $email, $hashed_password, $role, $username);

            if ($stmt->execute()) {
                $_SESSION["flash_success"] = "User successfully added.";
            } else {
                $_SESSION["flash_error"] = "Failed to add user.";
                $_SESSION["reopen_modal"] = "add";
            }

            $stmt->close();
        }

        $check->close();
    }

    redirectDashboard();
}

// =====================================
// EDIT USER / ADMIN
// =====================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_user"])) {

    $user_id = (int)($_POST["user_id"] ?? 0);
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $new_password = $_POST["new_password"] ?? "";

    $_SESSION["reopen_modal"] = "edit";

    if ($user_id <= 0 || $email === "") {
        $_SESSION["flash_error"] = "Invalid information.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["flash_error"] = "Invalid email format.";

    } else {

        $roleCheck = $mysqli->prepare("SELECT role FROM user WHERE id = ?");
        $roleCheck->bind_param("i", $user_id);
        $roleCheck->execute();

        $roleResult = $roleCheck->get_result();
        $account = $roleResult->fetch_assoc();

        $roleCheck->close();

        if (!$account) {
            $_SESSION["flash_error"] = "User not found.";
            $_SESSION["reopen_modal"] = "";

        } elseif ($account["role"] === "user" && $username === "") {
            $_SESSION["flash_error"] = "Name is required.";

        } elseif ($new_password !== "" && strlen($new_password) < 6) {
            $_SESSION["flash_error"] = "Password must be at least 6 characters.";

        } else {

            $_SESSION["edit_form"] = [
                "id" => $user_id,
                "username" => $username,
                "email" => $email,
                "role" => $account["role"]
            ];

            $check = $mysqli->prepare("
                SELECT id FROM user
                WHERE email = ? AND id != ?
            ");

            $check->bind_param("si", $email, $user_id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $_SESSION["flash_error"] = "Email is already used by another account.";

            } else {

                if ($account["role"] === "admin") {

                    if ($new_password !== "") {

                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                        $stmt = $mysqli->prepare("
                            UPDATE user
                            SET email = ?, password = ?
                            WHERE id = ? AND role = 'admin'
                        ");

                        $stmt->bind_param("ssi", $email, $hashed_password, $user_id);

                    } else {

                        $stmt = $mysqli->prepare("
                            UPDATE user
                            SET email = ?
                            WHERE id = ? AND role = 'admin'
                        ");

                        $stmt->bind_param("si", $email, $user_id);
                    }

                } else {

                    $stmt = $mysqli->prepare("
                        UPDATE user
                        SET username = ?, email = ?
                        WHERE id = ? AND role = 'user'
                    ");

                    $stmt->bind_param("ssi", $username, $email, $user_id);
                }

                if ($stmt->execute()) {

                    $_SESSION["flash_success"] = "User successfully updated.";
                    unset($_SESSION["edit_form"], $_SESSION["reopen_modal"]);

                    if (
                        isset($_SESSION["user_id"]) &&
                        (int)$_SESSION["user_id"] === $user_id &&
                        $account["role"] === "user"
                    ) {
                        $_SESSION["username"] = $username;
                    }

                } else {
                    $_SESSION["flash_error"] = "Failed to update user.";
                }

                $stmt->close();
            }

            $check->close();
        }
    }

    redirectDashboard();
}

// =====================================
// DELETE USER - ADMIN CANNOT BE DELETED
// =====================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_user"])) {

    $user_id = (int)($_POST["user_id"] ?? 0);

    if ($user_id <= 0) {
        $_SESSION["flash_error"] = "Invalid user.";

    } elseif (
        isset($_SESSION["user_id"]) &&
        (int)$_SESSION["user_id"] === $user_id
    ) {
        $_SESSION["flash_error"] = "You cannot delete your own account.";

    } else {

        $stmt = $mysqli->prepare("
            DELETE FROM user
            WHERE id = ? AND role = 'user'
        ");

        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {

            if ($stmt->affected_rows > 0) {
                $_SESSION["flash_success"] = "User successfully deleted.";
            } else {
                $_SESSION["flash_error"] = "User not found or cannot be deleted.";
            }

        } else {
            $_SESSION["flash_error"] =
                "Failed to delete user. This user may have existing reimbursement records.";
        }

        $stmt->close();
    }

    redirectDashboard();
}

// =====================================
// GET PENDING REIMBURSEMENTS
// =====================================
$query = "
    SELECT reimbursement.*, user.username
    FROM reimbursement
    JOIN user ON reimbursement.user_id = user.id
    WHERE reimbursement.status = 'Pending'
    ORDER BY reimbursement.created_at DESC
";

$result = $mysqli->query($query);
$pengajuan = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// =====================================
// GET REIMBURSEMENT HISTORY
// Only Approved and Rejected requests
// =====================================
$history_query = "
    SELECT reimbursement.*, user.username
    FROM reimbursement
    JOIN user ON reimbursement.user_id = user.id
    WHERE reimbursement.status IN ('Approved', 'Rejected')
    ORDER BY reimbursement.created_at DESC
";

$history_result = $mysqli->query($history_query);
$riwayat = $history_result ? $history_result->fetch_all(MYSQLI_ASSOC) : [];

// =====================================
// GET REGISTERED USERS
// =====================================
$user_query = "
    SELECT id, username, email, role, `created at`
    FROM user
    ORDER BY
        CASE WHEN role = 'admin' THEN 0 ELSE 1 END,
        id DESC
";

$users = $mysqli->query($user_query);

if (!$users) {
    die("Failed to retrieve users: " . $mysqli->error);
}

$daftar_user = $users->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Dashboard Admin</title>

    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/simple-line-icons/simple-line-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css">
    <link rel="stylesheet" href="assets/vendors/select2/select2.min.css">
    <link rel="stylesheet" href="assets/vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/images/favicon.jpg">

    <style>
        .custom-toast {
            position: fixed;
            top: 25px;
            right: 25px;
            z-index: 99999;
            min-width: 300px;
            max-width: 420px;
            padding: 16px 20px;
            border-radius: 10px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.18);
            animation: toastSlideIn .35s ease;
        }

        .custom-toast.success {
            background: #28a745;
        }

        .custom-toast.error {
            background: #dc3545;
        }

        .custom-toast button {
            border: none;
            background: transparent;
            color: white;
            font-size: 22px;
            cursor: pointer;
            line-height: 1;
        }

        .custom-toast.hide {
            animation: toastFadeOut .35s forwards;
        }

        @keyframes toastSlideIn {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes toastFadeOut {
            to {
                opacity: 0;
                transform: translateX(30px);
            }
        }

        .history-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        .history-status.approved {
            background: #d4edda;
            color: #155724;
        }

        .history-status.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 576px) {
            .custom-toast {
                left: 15px;
                right: 15px;
                min-width: unset;
            }
        }

        /* Make the footer stay at the bottom of the page */
.main-panel {
    min-height: calc(100vh - 70px);
    display: flex;
    flex-direction: column;
}

.content-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.content-wrapper > .row {
    flex: 1;
}

.footer {
    margin-top: auto;
    width: 100%;
    padding-top: 25px;
    padding-bottom: 20px;
}
    </style>
</head>

<body class="with-welcome-text">

<!-- FLOATING FLASH MESSAGE -->
<?php if ($success !== "" || $error !== ""): ?>
    <div id="flashToast"
         class="custom-toast <?php echo $success !== "" ? "success" : "error"; ?>">

        <span>
            <i class="mdi <?php echo $success !== "" ? "mdi-check-circle" : "mdi-alert-circle"; ?>"></i>
            <?php
            echo htmlspecialchars(
                $success !== "" ? $success : $error,
                ENT_QUOTES,
                "UTF-8"
            );
            ?>
        </span>

        <button type="button" onclick="closeToast()" aria-label="Close">
            &times;
        </button>
    </div>
<?php endif; ?>


<!-- NAVBAR -->
<nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">

    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">

        <div class="me-3">
            <button class="navbar-toggler navbar-toggler align-self-center"
                    type="button" data-bs-toggle="minimize">
                <span class="icon-menu"></span>
            </button>
        </div>

        <div>
            <a class="navbar-brand brand-logo" href="dashboard_admin.php">
                <img src="assets/images/logo.svg" alt="logo">
            </a>

            <a class="navbar-brand brand-logo-mini" href="dashboard_admin.php">
                <img src="assets/images/logo-mini.svg" alt="logo">
            </a>
        </div>

    </div>

    <div class="navbar-menu-wrapper d-flex align-items-top">

        <ul class="navbar-nav">
            <li class="nav-item fw-semibold d-none d-lg-block ms-0">

                <h1 class="welcome-text">
                    Welcome Back,
                    <span class="text-black fw-bold">
                        <?php
                        echo htmlspecialchars(
                            $_SESSION["username"] ?? "Admin",
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        ?>
                    </span>
                </h1>

                <h3 class="welcome-sub-text">
                    Your performance summary this week
                </h3>

            </li>
        </ul>

        <ul class="navbar-nav ms-auto">

            <li class="nav-item me-3 d-flex align-items-center">
                <button type="button"
                        class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#manageUsersModal">

                    <i class="mdi mdi-account-multiple"></i>
                    Manage Users

                </button>
            </li>

            <li class="nav-item d-none d-lg-block">
                <div id="datepicker-popup"
                     class="input-group date datepicker navbar-date-picker">

                    <span class="input-group-addon input-group-prepend border-right">
                        <span class="icon-calendar input-group-text calendar-icon"></span>
                    </span>

                    <input type="text" class="form-control">
                </div>
            </li>

        </ul>
    </div>
</nav>


<div class="container-fluid page-body-wrapper">

    <!-- SIDEBAR -->
    <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">

            <li class="nav-item">
                <a class="nav-link" href="dashboard_admin.php">
                    <i class="mdi mdi-grid-large menu-icon"></i>
                    <span class="menu-title">Dashboard</span>
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


    <!-- MAIN PANEL -->
    <div class="main-panel">
        <div class="content-wrapper">

            <div class="row">
                <div class="col-sm-12">
                    <div class="home-tab">

                        <!-- REIMBURSEMENT TABS -->
                        <div class="d-sm-flex align-items-center justify-content-between border-bottom">

                            <ul class="nav nav-tabs" role="tablist">

                                <li class="nav-item">
                                    <a class="nav-link active ps-0"
                                       id="requests-tab"
                                       data-bs-toggle="tab"
                                       href="#overview"
                                       role="tab"
                                       aria-controls="overview"
                                       aria-selected="true">
                                        Reimbursement Requests
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link"
                                       id="history-tab"
                                       data-bs-toggle="tab"
                                       href="#history"
                                       role="tab"
                                       aria-controls="history"
                                       aria-selected="false">
                                        Reimbursement History
                                    </a>
                                </li>

                            </ul>
                        </div>


                        <div class="tab-content">

                            <!-- ===================================== -->
                            <!-- TAB 1: PENDING REIMBURSEMENT REQUESTS -->
                            <!-- ===================================== -->

                            <div class="tab-pane fade show active"
                                 id="overview"
                                 role="tabpanel"
                                 aria-labelledby="requests-tab">

                                <div class="col-lg-12 grid-margin stretch-card mt-4">
                                    <div class="card">
                                        <div class="card-body">

                                            <h4 class="card-title">Pending Reimbursement</h4>

                                            <p class="card-description">
                                                Please verify the information of reimbursement requests.
                                            </p>

                                            <div class="table-responsive">
                                                <table class="table" id="dataTable">

                                                    <thead>
                                                        <tr>
                                                            <th>No.</th>
                                                            <th>Name</th>
                                                            <th>Date</th>
                                                            <th>Description</th>
                                                            <th>Amount</th>
                                                            <th>Status</th>
                                                            <th>File</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <?php $nomor = 1; ?>

                                                        <?php foreach ($pengajuan as $row): ?>
                                                            <tr>
                                                                <td><?php echo $nomor++; ?></td>

                                                                <td>
                                                                    <?php echo htmlspecialchars($row["username"], ENT_QUOTES, "UTF-8"); ?>
                                                                </td>

                                                                <td>
                                                                    <?php echo htmlspecialchars($row["tanggal"], ENT_QUOTES, "UTF-8"); ?>
                                                                </td>

                                                                <td>
                                                                    <?php echo htmlspecialchars($row["deskripsi"], ENT_QUOTES, "UTF-8"); ?>
                                                                </td>

                                                                <td>
                                                                    Rp <?php echo number_format($row["jumlah"], 0, ",", "."); ?>
                                                                </td>

                                                                <td>
                                                                    <label class="badge badge-warning">
                                                                        <?php echo htmlspecialchars($row["status"], ENT_QUOTES, "UTF-8"); ?>
                                                                    </label>
                                                                </td>

                                                                <td>
                                                                    <a href="uploads/<?php echo rawurlencode($row["file"]); ?>"
                                                                       target="_blank"
                                                                       rel="noopener noreferrer">
                                                                        View File
                                                                    </a>
                                                                </td>

                                                                <td>
                                                                    <form action="process_reimbursement.php" method="POST">

                                                                        <input type="hidden"
                                                                               name="id"
                                                                               value="<?php echo (int)$row["id"]; ?>">

                                                                        <button type="submit"
                                                                                name="accept"
                                                                                class="btn btn-success btn-sm">
                                                                            Approve
                                                                        </button>

                                                                        <button type="submit"
                                                                                name="reject"
                                                                                class="btn btn-danger btn-sm">
                                                                            Reject
                                                                        </button>

                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>

                                                        <?php if (count($pengajuan) === 0): ?>
                                                            <tr>
                                                                <td colspan="8" class="text-center">
                                                                    No pending reimbursement requests.
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>

                                                    </tbody>
                                                </table>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                            </div>


                            <!-- ===================================== -->
                            <!-- TAB 2: REIMBURSEMENT HISTORY -->
                            <!-- ===================================== -->

                            <div class="tab-pane fade"
                                 id="history"
                                 role="tabpanel"
                                 aria-labelledby="history-tab">

                                <div class="col-lg-12 grid-margin stretch-card mt-4">
                                    <div class="card">
                                        <div class="card-body">

                                            <h4 class="card-title">Reimbursement History</h4>

                                            <p class="card-description">
                                                History of reimbursement requests that have been approved or rejected.
                                            </p>

                                            <div class="table-responsive">
                                                <table class="table table-hover" id="historyTable">

                                                    <thead>
                                                        <tr>
                                                            <th>No.</th>
                                                            <th>Name</th>
                                                            <th>Date</th>
                                                            <th>Description</th>
                                                            <th>Amount</th>
                                                            <th>Status</th>
                                                            <th>File</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <?php $historyNo = 1; ?>

                                                        <?php foreach ($riwayat as $row): ?>
                                                            <tr>

                                                                <td><?php echo $historyNo++; ?></td>

                                                                <td>
                                                                    <?php echo htmlspecialchars($row["username"], ENT_QUOTES, "UTF-8"); ?>
                                                                </td>

                                                                <td>
                                                                    <?php echo htmlspecialchars($row["tanggal"], ENT_QUOTES, "UTF-8"); ?>
                                                                </td>

                                                                <td>
                                                                    <?php echo htmlspecialchars($row["deskripsi"], ENT_QUOTES, "UTF-8"); ?>
                                                                </td>

                                                                <td>
                                                                    Rp <?php echo number_format($row["jumlah"], 0, ",", "."); ?>
                                                                </td>

                                                                <td>
                                                                    <?php if ($row["status"] === "Approved"): ?>
                                                                        <span class="history-status approved">
                                                                            Approved
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span class="history-status rejected">
                                                                            Rejected
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </td>

                                                                <td>
                                                                    <?php if (!empty($row["file"])): ?>
                                                                        <a href="uploads/<?php echo rawurlencode($row["file"]); ?>"
                                                                           target="_blank"
                                                                           rel="noopener noreferrer">
                                                                            View File
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">No file</span>
                                                                    <?php endif; ?>
                                                                </td>

                                                            </tr>
                                                        <?php endforeach; ?>

                                                        <?php if (count($riwayat) === 0): ?>
                                                            <tr>
                                                                <td colspan="7" class="text-center">
                                                                    No reimbursement history available.
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>

                                                    </tbody>
                                                </table>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>


            <footer class="footer">
                <div class="d-sm-flex justify-content-center justify-content-sm-between">
                    <span class="float-none float-sm-end d-block mt-1 mt-sm-0 text-center">
                        Copyright © 2023. All rights reserved.
                    </span>
                </div>
            </footer>

        </div>
    </div>
</div>


<!-- ===================================== -->
<!-- MANAGE USERS MODAL -->
<!-- ===================================== -->

<div class="modal fade" id="manageUsersModal" tabindex="-1"
     aria-labelledby="manageUsersModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="manageUsersModalLabel">
                    Manage Users
                </h5>

                <button type="button" class="btn-close"
                        data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>

            <div class="modal-body">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Registered Users</h5>

                    <button type="button"
                            class="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#addUserModal">

                        <i class="mdi mdi-account-plus"></i>
                        Add User

                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">

                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Date Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (count($daftar_user) > 0): ?>
                                <?php $no = 1; ?>

                                <?php foreach ($daftar_user as $user): ?>
                                    <tr>

                                        <td><?php echo $no++; ?></td>

                                        <td>
                                            <?php echo htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8"); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8"); ?>
                                        </td>

                                        <td>
                                            <?php if ($user["role"] === "admin"): ?>
                                                <label class="badge badge-danger">Admin</label>
                                            <?php else: ?>
                                                <label class="badge badge-success">User</label>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($user["created at"], ENT_QUOTES, "UTF-8"); ?>
                                        </td>

                                        <td class="text-nowrap">

                                            <button type="button"
                                                    class="btn btn-info btn-sm"
                                                    title="Edit"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editUserModal"
                                                    data-id="<?php echo (int)$user["id"]; ?>"
                                                    data-username="<?php echo htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8"); ?>"
                                                    data-email="<?php echo htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8"); ?>"
                                                    data-role="<?php echo htmlspecialchars($user["role"], ENT_QUOTES, "UTF-8"); ?>">

                                                <i class="mdi mdi-pencil"></i>
                                            </button>

                                            <?php if ($user["role"] === "user"): ?>

                                                <button type="button"
                                                        class="btn btn-danger btn-sm"
                                                        title="Delete"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteUserModal"
                                                        data-id="<?php echo (int)$user["id"]; ?>"
                                                        data-username="<?php echo htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8"); ?>">

                                                    <i class="mdi mdi-delete"></i>
                                                </button>

                                            <?php endif; ?>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        No users found.
                                    </td>
                                </tr>
                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>


<!-- ===================================== -->
<!-- ADD USER MODAL -->
<!-- ===================================== -->

<div class="modal fade" id="addUserModal" tabindex="-1"
     aria-labelledby="addUserModalLabel" aria-hidden="true">

    <div class="modal-dialog">
        <div class="modal-content">

            <form method="POST" action="dashboard_admin.php">

                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">
                        Add New User
                    </h5>

                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>

                <div class="modal-body">

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="username" class="form-control"
                               placeholder="Enter full name" maxlength="50" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control"
                               placeholder="Enter email" maxlength="100" required>
                    </div>

                    <div class="form-group">
                        <label>Initial Password</label>
                        <input type="password" name="password" class="form-control"
                               placeholder="Enter initial password"
                               minlength="6" required>

                        <small class="text-muted">
                            Password must be at least 6 characters.
                        </small>
                    </div>

                    <input type="hidden" name="add_user" value="1">

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-account-plus"></i>
                        Add User
                    </button>

                </div>

            </form>
        </div>
    </div>
</div>


<!-- ===================================== -->
<!-- EDIT USER / ADMIN MODAL -->
<!-- ===================================== -->

<div class="modal fade" id="editUserModal" tabindex="-1"
     aria-labelledby="editUserModalLabel" aria-hidden="true">

    <div class="modal-dialog">
        <div class="modal-content">

            <form method="POST" action="dashboard_admin.php">

                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">
                        Edit User
                    </h5>

                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="user_id" id="edit_user_id">

                    <div class="form-group" id="editNameGroup">
                        <label>Full Name</label>
                        <input type="text" name="username" id="edit_username"
                               class="form-control" maxlength="50">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email"
                               class="form-control" maxlength="100" required>
                    </div>

                    <div class="form-group" id="editPasswordGroup" style="display:none;">
                        <label>New Admin Password</label>
                        <input type="password" name="new_password"
                               id="edit_new_password"
                               class="form-control" minlength="6"
                               placeholder="Leave blank to keep current password">

                        <small class="text-muted">
                            Leave blank if you do not want to change the password.
                            Minimum 6 characters.
                        </small>
                    </div>

                    <input type="hidden" name="edit_user" value="1">

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save"></i>
                        Save Changes
                    </button>

                </div>

            </form>
        </div>
    </div>
</div>


<!-- ===================================== -->
<!-- DELETE CONFIRMATION MODAL -->
<!-- ===================================== -->

<div class="modal fade" id="deleteUserModal" tabindex="-1"
     aria-labelledby="deleteUserModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" action="dashboard_admin.php">

                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">
                        Confirm Delete
                    </h5>

                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>

                <div class="modal-body text-center">

                    <i class="mdi mdi-alert-circle-outline text-danger"
                       style="font-size:55px;"></i>

                    <h5 class="mt-3">Are you sure?</h5>

                    <p>
                        Do you really want to delete
                        <strong id="delete_username"></strong>?
                    </p>

                    <p class="text-danger mb-0">
                        This action cannot be undone.
                    </p>

                    <input type="hidden" name="user_id" id="delete_user_id">
                    <input type="hidden" name="delete_user" value="1">

                </div>

                <div class="modal-footer justify-content-center">

                    <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-delete"></i>
                        Yes, Delete
                    </button>

                </div>

            </form>
        </div>
    </div>
</div>


<!-- JS PLUGINS -->
<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
<script src="assets/vendors/datatables.net-bs4/dataTables.bootstrap4.js"></script>
<script src="assets/js/template.js"></script>
<script src="assets/js/dashboard.js"></script>

<script>

// =====================================
// AUTO HIDE FLASH MESSAGE
// =====================================
function closeToast() {
    var toast = document.getElementById("flashToast");

    if (toast) {
        toast.classList.add("hide");

        setTimeout(function () {
            toast.remove();
        }, 350);
    }
}

document.addEventListener("DOMContentLoaded", function () {

    var toast = document.getElementById("flashToast");

    if (toast) {
        setTimeout(closeToast, 4000);
    }

});


// =====================================
// EDIT MODAL
// =====================================
document.getElementById("editUserModal").addEventListener(
    "show.bs.modal",
    function (event) {

        var button = event.relatedTarget;

        var id = button.getAttribute("data-id");
        var username = button.getAttribute("data-username");
        var email = button.getAttribute("data-email");
        var role = button.getAttribute("data-role");

        document.getElementById("edit_user_id").value = id;
        document.getElementById("edit_username").value = username;
        document.getElementById("edit_email").value = email;

        var nameGroup = document.getElementById("editNameGroup");
        var passwordGroup = document.getElementById("editPasswordGroup");
        var usernameInput = document.getElementById("edit_username");
        var passwordInput = document.getElementById("edit_new_password");
        var title = document.getElementById("editUserModalLabel");

        passwordInput.value = "";

        if (role === "admin") {

            title.textContent = "Edit Admin Account";

            nameGroup.style.display = "none";
            passwordGroup.style.display = "block";

            usernameInput.required = false;
            usernameInput.disabled = true;

        } else {

            title.textContent = "Edit User";

            nameGroup.style.display = "block";
            passwordGroup.style.display = "none";

            usernameInput.required = true;
            usernameInput.disabled = false;
        }
    }
);


// =====================================
// DELETE MODAL
// =====================================
document.getElementById("deleteUserModal").addEventListener(
    "show.bs.modal",
    function (event) {

        var button = event.relatedTarget;

        document.getElementById("delete_user_id").value =
            button.getAttribute("data-id");

        document.getElementById("delete_username").textContent =
            button.getAttribute("data-username");
    }
);


// =====================================
// REOPEN MODALS AFTER ACTION
// =====================================
document.addEventListener("DOMContentLoaded", function () {

    var reopenModal = <?php echo json_encode($reopenModal); ?>;
    var hasMessage = <?php echo json_encode($success !== "" || $error !== ""); ?>;
    var editForm = <?php echo json_encode($editForm); ?>;

    if (!hasMessage) {
        return;
    }

    var manageElement = document.getElementById("manageUsersModal");
    var manageModal = new bootstrap.Modal(manageElement);

    manageModal.show();

    if (reopenModal === "add") {

        manageElement.addEventListener("shown.bs.modal", function () {
            new bootstrap.Modal(
                document.getElementById("addUserModal")
            ).show();
        }, { once: true });

    }

    if (reopenModal === "edit" && editForm.id) {

        manageElement.addEventListener("shown.bs.modal", function () {

            var editModalElement = document.getElementById("editUserModal");
            var editModal = new bootstrap.Modal(editModalElement);

            document.getElementById("edit_user_id").value = editForm.id;
            document.getElementById("edit_username").value = editForm.username || "";
            document.getElementById("edit_email").value = editForm.email || "";

            var nameGroup = document.getElementById("editNameGroup");
            var passwordGroup = document.getElementById("editPasswordGroup");
            var usernameInput = document.getElementById("edit_username");
            var title = document.getElementById("editUserModalLabel");

            if (editForm.role === "admin") {
                title.textContent = "Edit Admin Account";
                nameGroup.style.display = "none";
                passwordGroup.style.display = "block";
                usernameInput.required = false;
                usernameInput.disabled = true;
            } else {
                title.textContent = "Edit User";
                nameGroup.style.display = "block";
                passwordGroup.style.display = "none";
                usernameInput.required = true;
                usernameInput.disabled = false;
            }

            editModal.show();

        }, { once: true });
    }

});

</script>

</body>
</html>

<?php
$mysqli->close();
?>
