
<?php
session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$mysqli = new mysqli("localhost", "root", "", "reimbustmen");

if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");

$user_id = $_SESSION['user_id'];

// =====================================
// CHANGE PASSWORD HANDLER
// =====================================
$password_success = "";
$password_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($current_password === '' || $new_password === '' || $confirm_password === '') {
        $password_error = "Please complete all password fields.";
    } elseif (strlen($new_password) < 8) {
        $password_error = "The new password must contain at least 8 characters.";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "The new password confirmation does not match.";
    } elseif ($current_password === $new_password) {
        $password_error = "Your new password must be different from your current password.";
    } else {
        $password_stmt = $mysqli->prepare("SELECT password FROM user WHERE id = ?");
        $password_stmt->bind_param("i", $user_id);
        $password_stmt->execute();

        $password_result = $password_stmt->get_result();
        $account = $password_result->fetch_assoc();

        $password_stmt->close();

        if (
            !$account ||
            !(
                password_verify($current_password, $account['password']) ||
                hash_equals((string)$account['password'], $current_password)
            )
        ) {
            $password_error = "Your current password is incorrect.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update_stmt = $mysqli->prepare("
                UPDATE user
                SET password = ?
                WHERE id = ?
            ");

            $update_stmt->bind_param("si", $hashed_password, $user_id);

            if ($update_stmt->execute()) {
                $password_success = "Your password has been changed successfully.";
            } else {
                $password_error = "Unable to update your password. Please try again.";
            }

            $update_stmt->close();
        }
    }
}

// =====================================
// GET USER REIMBURSEMENT HISTORY
// =====================================
$stmt = $mysqli->prepare("
    SELECT *
    FROM reimbursement
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>User Dashboard | Reimbursement System</title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css">
    <link rel="stylesheet" href="assets/vendors/select2/select2.min.css">
    <link rel="stylesheet" href="assets/vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <link rel="shortcut icon" href="assets/images/favicon.jpg">

    <style>

        /* =====================================
           NAVBAR CALENDAR
        ===================================== */

        .navbar-date-picker .form-control:disabled {
            background-color: #ffffff;
            color: #333333;
            opacity: 1;
            cursor: not-allowed;
        }

        .navbar-date-picker .input-group-text {
            background-color: #ffffff;
        }


        /* =====================================
           ACCOUNT ICON
        ===================================== */

        .account-icon {
            font-size: 34px;
            color: #555;
            transition: 0.2s;
        }

        .account-icon:hover {
            color: #4B49AC;
        }


        /* =====================================
           ACCOUNT DROPDOWN
        ===================================== */

        .account-dropdown {
            min-width: 200px;
            padding: 8px 0;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }

        .account-dropdown .dropdown-item {
            padding: 10px 18px;
            font-size: 14px;
        }

        .account-dropdown .dropdown-item:hover {
            background-color: #f4f4f8;
        }

        .account-name {
            font-weight: 600;
            color: #333;
        }


        /* =====================================
           REIMBURSEMENT CARD
        ===================================== */

        .reimbursement-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
        }

        .reimbursement-card .card-body {
            padding: 30px 34px;
        }

        .reimbursement-card .card-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .reimbursement-card .card-description {
            margin-bottom: 26px;
            color: #7987a1;
        }


        /* =====================================
           REIMBURSEMENT FORM LAYOUT
        ===================================== */

        .reimbursement-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            column-gap: 24px;
            row-gap: 4px;
        }

        .reimbursement-form .form-group {
            margin-bottom: 20px;
            min-width: 0;
        }

        /* Date uses full width */
        .reimbursement-form .date-field {
            grid-column: 1 / -1;
        }

        /* Description uses full width */
        .reimbursement-form .description-field {
            grid-column: 1 / -1;
        }

        /* Buttons use full width */
        .reimbursement-form .form-actions {
            grid-column: 1 / -1;
        }

        .reimbursement-form label {
            display: block;
            font-weight: 500;
            margin-bottom: 9px;
            color: #343a40;
        }

        .reimbursement-form .form-control {
            width: 100%;
            min-height: 48px;
            padding: 11px 14px;
            border: 1px solid #dce1e8;
            border-radius: 7px;
            font-size: 14px;
            background-color: #fff;
        }

        .reimbursement-form .form-control:focus {
            border-color: #4b49ac;
            box-shadow: 0 0 0 3px rgba(75, 73, 172, 0.10);
        }

        .reimbursement-form textarea.form-control {
            min-height: 110px;
            resize: vertical;
        }

        .reimbursement-form input[type="file"] {
            padding: 9px;
        }

        .reimbursement-form .form-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 4px;
        }

        .reimbursement-form .form-actions .btn {
            min-height: 43px;
            padding: 10px 22px;
            border-radius: 7px;
        }


        /* =====================================
           MOBILE RESPONSIVE
        ===================================== */

        @media (max-width: 767px) {

            .reimbursement-card .card-body {
                padding: 22px 18px;
            }

            .reimbursement-form {
                grid-template-columns: 1fr;
                column-gap: 0;
            }

            .reimbursement-form .date-field,
            .reimbursement-form .description-field,
            .reimbursement-form .form-actions {
                grid-column: 1;
            }

            .reimbursement-form .form-actions {
                flex-wrap: wrap;
            }

        }

    </style>

</head>


<body class="with-welcome-text">


<!-- =====================================
     NAVBAR
===================================== -->

<nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">

    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">

        <div class="me-3">

            <button class="navbar-toggler navbar-toggler align-self-center"
                    type="button"
                    data-bs-toggle="minimize">

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


    <div class="navbar-menu-wrapper d-flex align-items-top">

        <!-- Welcome -->
        <ul class="navbar-nav">

            <li class="nav-item fw-semibold d-none d-lg-block ms-0">

                <h1 class="welcome-text">

                    Welcome Back,

                    <span class="text-black fw-bold">

                        <?php
                        echo htmlspecialchars(
                            $_SESSION['username'] ?? 'User',
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>

                    </span>

                </h1>

                <h3 class="welcome-sub-text">
                    Your reimbursement dashboard
                </h3>

            </li>

        </ul>


        <!-- Calendar and Account -->
        <ul class="navbar-nav ms-auto align-items-center">

            <!-- Disabled Calendar -->
            <li class="nav-item d-none d-lg-block me-3">

                <div class="input-group date navbar-date-picker">

                    <span class="input-group-text">
                        <i class="icon-calendar calendar-icon"></i>
                    </span>

                    <input
                        type="text"
                        class="form-control"
                        value="<?php echo date('d/m/Y'); ?>"
                        disabled
                        readonly
                        autocomplete="off"
                    >

                </div>

            </li>


            <!-- Account Dropdown -->
            <li class="nav-item dropdown">

                <a
                    class="nav-link"
                    href="#"
                    id="accountDropdown"
                    role="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    title="Account"
                >

                    <i class="mdi mdi-account-circle account-icon"></i>

                </a>


                <div
                    class="dropdown-menu dropdown-menu-end account-dropdown"
                    aria-labelledby="accountDropdown"
                >

                    <h6 class="dropdown-header account-name">

                        <?php
                        echo htmlspecialchars(
                            $_SESSION['username'] ?? 'User',
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>

                    </h6>


                    <div class="dropdown-divider"></div>


                    <!-- Change Password -->
                    <a
                        class="dropdown-item"
                        href="#changePasswordModal"
                        data-bs-toggle="modal"
                    >

                        <i class="mdi mdi-lock-reset me-2"></i>
                        Change Password

                    </a>


                    <!-- Logout -->
                    <a
                        class="dropdown-item text-danger"
                        href="logout.php"
                    >

                        <i class="mdi mdi-logout me-2"></i>
                        Logout

                    </a>

                </div>

            </li>

        </ul>

    </div>

</nav>


<!-- END NAVBAR -->


<div class="container-fluid page-body-wrapper">


    <!-- =====================================
         SIDEBAR
    ===================================== -->

    <nav class="sidebar sidebar-offcanvas" id="sidebar">

        <ul class="nav">

            <li class="nav-item">

                <a class="nav-link active" href="dashboard_user.php">

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


    <!-- END SIDEBAR -->


    <!-- =====================================
         MAIN PANEL
    ===================================== -->

    <div class="main-panel">

        <div class="content-wrapper">


            <div class="row">

                <div class="col-sm-12">

                    <div class="home-tab">


                        <!-- TAB NAVIGATION -->
                        <div class="d-sm-flex align-items-center justify-content-between border-bottom">

                            <ul class="nav nav-tabs" role="tablist">

                                <li class="nav-item">

                                    <a
                                        class="nav-link active ps-0"
                                        id="form-tab"
                                        data-bs-toggle="tab"
                                        href="#formulir"
                                        role="tab"
                                        aria-controls="formulir"
                                        aria-selected="true"
                                    >

                                        Reimbursement Form

                                    </a>

                                </li>


                                <li class="nav-item">

                                    <a
                                        class="nav-link"
                                        id="riwayat-tab"
                                        data-bs-toggle="tab"
                                        href="#riwayat"
                                        role="tab"
                                        aria-controls="riwayat"
                                        aria-selected="false"
                                    >

                                        Submission History

                                    </a>

                                </li>

                            </ul>

                        </div>


                        <!-- =====================================
                             TAB CONTENT
                        ===================================== -->

                        <div class="tab-content">


                            <!-- =====================================
                                 REIMBURSEMENT FORM
                            ===================================== -->

                            <div
                                class="tab-pane fade show active"
                                id="formulir"
                                role="tabpanel"
                            >

                                <div class="col-lg-12 grid-margin stretch-card mt-4">

                                    <div class="card reimbursement-card">

                                        <div class="card-body">

                                            <h4 class="card-title">
                                                Reimbursement Form
                                            </h4>

                                            <p class="card-description">
                                                Please complete the form below to submit your reimbursement request.
                                            </p>


                                            <form
                                                class="reimbursement-form"
                                                action="submit_reimbursement.php"
                                                method="POST"
                                                enctype="multipart/form-data"
                                            >

                                                <!-- DATE -->
                                                <div class="form-group date-field">

                                                    <label for="tanggal">
                                                        Date
                                                    </label>

                                                    <input
                                                        type="date"
                                                        class="form-control"
                                                        id="tanggal"
                                                        name="tanggal"
                                                        required
                                                    >

                                                </div>


                                                <!-- DESCRIPTION -->
                                                <div class="form-group description-field">

                                                    <label for="deskripsi">
                                                        Description
                                                    </label>

                                                    <textarea
                                                        class="form-control"
                                                        id="deskripsi"
                                                        name="deskripsi"
                                                        rows="3"
                                                        placeholder="Describe the purpose and details of your reimbursement request"
                                                        required
                                                    ></textarea>

                                                </div>


                                                <!-- AMOUNT -->
                                                <div class="form-group">

                                                    <label for="jumlah">
                                                        Amount (IDR)
                                                    </label>

                                                    <input
                                                        type="number"
                                                        class="form-control"
                                                        id="jumlah"
                                                        name="jumlah"
                                                        min="1"
                                                        step="1"
                                                        placeholder="Enter reimbursement amount"
                                                        required
                                                    >

                                                </div>


                                                <!-- SUPPORTING DOCUMENT -->
                                                <div class="form-group">

                                                    <label for="file">
                                                        Supporting Document
                                                    </label>

                                                    <input
                                                        type="file"
                                                        class="form-control"
                                                        id="file"
                                                        name="file"
                                                        accept=".jpg,.jpeg,.png,.pdf"
                                                        required
                                                    >

                                                    <small class="form-text text-muted">
                                                        Accepted formats: JPG, JPEG, PNG, PDF.
                                                    </small>

                                                </div>


                                                <!-- BUTTONS -->
                                                <div class="form-actions">

                                                    <button type="submit" class="btn btn-primary">

                                                        <i class="mdi mdi-send"></i>
                                                        Submit Request

                                                    </button>


                                                    <button type="reset" class="btn btn-light">

                                                        Reset

                                                    </button>

                                                </div>

                                            </form>


                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- =====================================
                                 SUBMISSION HISTORY
                            ===================================== -->

                            <div
                                class="tab-pane fade"
                                id="riwayat"
                                role="tabpanel"
                            >

                                <div class="col-lg-12 grid-margin stretch-card mt-4">

                                    <div class="card">

                                        <div class="card-body">

                                            <h4 class="card-title">
                                                Submission History
                                            </h4>

                                            <p class="card-description">
                                                View the status of your reimbursement requests.
                                            </p>


                                            <div class="table-responsive">

                                                <table class="table table-bordered">

                                                    <thead>

                                                        <tr>

                                                            <th>No.</th>
                                                            <th>Date</th>
                                                            <th>Description</th>
                                                            <th>Amount</th>
                                                            <th>Status</th>
                                                            <th>File</th>

                                                        </tr>

                                                    </thead>


                                                    <tbody>

                                                        <?php

                                                        $nomor = 1;

                                                        if ($result->num_rows > 0):

                                                            while ($row = $result->fetch_assoc()):

                                                        ?>

                                                        <tr>

                                                            <!-- NUMBER -->
                                                            <td>
                                                                <?php echo $nomor++; ?>
                                                            </td>


                                                            <!-- DATE -->
                                                            <td>

                                                                <?php
                                                                echo htmlspecialchars(
                                                                    $row['tanggal'] ?? '',
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                );
                                                                ?>

                                                            </td>


                                                            <!-- DESCRIPTION -->
                                                            <td>

                                                                <?php
                                                                echo htmlspecialchars(
                                                                    $row['deskripsi'] ?? '',
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                );
                                                                ?>

                                                            </td>


                                                            <!-- AMOUNT -->
                                                            <td>

                                                                Rp

                                                                <?php
                                                                echo number_format(
                                                                    (float)$row['jumlah'],
                                                                    0,
                                                                    ',',
                                                                    '.'
                                                                );
                                                                ?>

                                                            </td>


                                                            <!-- STATUS -->
                                                            <td>

                                                                <?php if ($row['status'] == 'Pending'): ?>

                                                                    <span class="badge bg-warning text-dark">
                                                                        Pending
                                                                    </span>

                                                                <?php elseif ($row['status'] == 'Approved'): ?>

                                                                    <span class="badge bg-success">
                                                                        Approved
                                                                    </span>

                                                                <?php elseif ($row['status'] == 'Rejected'): ?>

                                                                    <span class="badge bg-danger">
                                                                        Rejected
                                                                    </span>

                                                                <?php else: ?>

                                                                    <?php
                                                                    echo htmlspecialchars(
                                                                        $row['status'],
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    );
                                                                    ?>

                                                                <?php endif; ?>

                                                            </td>


                                                            <!-- FILE -->
                                                            <td>

                                                                <?php if (!empty($row['file'])): ?>

                                                                    <a
                                                                        href="uploads/<?php echo rawurlencode(basename($row['file'])); ?>"
                                                                        target="_blank"
                                                                        rel="noopener noreferrer"
                                                                    >

                                                                        <i class="mdi mdi-file-document"></i>
                                                                        View File

                                                                    </a>

                                                                <?php else: ?>

                                                                    No file

                                                                <?php endif; ?>

                                                            </td>

                                                        </tr>


                                                        <?php

                                                            endwhile;

                                                        else:

                                                        ?>

                                                        <tr>

                                                            <td colspan="6" class="text-center">

                                                                No reimbursement submissions yet.

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


                            <!-- END SUBMISSION HISTORY -->


                        </div>

                    </div>

                </div>

            </div>


        </div>


        <!-- =====================================
             FOOTER
        ===================================== -->

        <footer class="footer">

            <div class="d-sm-flex justify-content-center justify-content-sm-between">

                <span class="float-none float-sm-end d-block mt-1 mt-sm-0 text-center">

                    Copyright © <?php echo date("Y"); ?>. All rights reserved.

                </span>

            </div>

        </footer>


    </div>

    <!-- END MAIN PANEL -->


</div>


<!-- =====================================
     CHANGE PASSWORD MODAL
===================================== -->

<div class="modal fade" id="changePasswordModal" tabindex="-1"
     aria-labelledby="changePasswordModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form method="POST" action="dashboard_user.php">

                <div class="modal-header">

                    <h5 class="modal-title" id="changePasswordModalLabel">
                        Change Password
                    </h5>

                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                    </button>

                </div>


                <div class="modal-body">

                    <?php if ($password_error !== ''): ?>

                        <div class="alert alert-danger">

                            <?php
                            echo htmlspecialchars(
                                $password_error,
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>

                        </div>

                    <?php endif; ?>


                    <?php if ($password_success !== ''): ?>

                        <div class="alert alert-success">

                            <?php
                            echo htmlspecialchars(
                                $password_success,
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>

                        </div>

                    <?php endif; ?>


                    <p class="text-muted">
                        Enter your current password and choose a new password.
                    </p>


                    <div class="form-group">

                        <label for="current_password">
                            Current Password
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="current_password"
                            name="current_password"
                            autocomplete="current-password"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="new_password">
                            New Password
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="new_password"
                            name="new_password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                        <small class="form-text text-muted">
                            Use at least 8 characters.
                        </small>

                    </div>


                    <div class="form-group">

                        <label for="confirm_password">
                            Confirm New Password
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="confirm_password"
                            name="confirm_password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                </div>


                <div class="modal-footer">

                    <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                            name="change_password"
                            value="1"
                            class="btn btn-primary">
                        Save New Password
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================
     JAVASCRIPT
===================================== -->

<script src="assets/vendors/js/vendor.bundle.base.js"></script>

<script src="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>

<script src="assets/vendors/select2/select2.min.js"></script>

<script src="assets/js/off-canvas.js"></script>

<script src="assets/js/template.js"></script>

<script src="assets/js/settings.js"></script>

<script src="assets/js/hoverable-collapse.js"></script>

<script src="assets/js/todolist.js"></script>

<script src="assets/js/dashboard.js"></script>


<!-- Reopen Change Password Modal When There Is A Message -->
<?php if ($password_error !== '' || $password_success !== ''): ?>

<script>

document.addEventListener("DOMContentLoaded", function () {

    var modalElement = document.getElementById("changePasswordModal");

    if (modalElement && window.bootstrap && bootstrap.Modal) {

        bootstrap.Modal.getOrCreateInstance(modalElement).show();

    } else if (modalElement && window.jQuery) {

        jQuery(modalElement).modal("show");

    }

});

</script>

<?php endif; ?>


</body>

</html>

<?php

$stmt->close();

$mysqli->close();

?>
