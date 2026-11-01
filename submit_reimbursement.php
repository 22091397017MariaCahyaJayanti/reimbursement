
<?php
session_start();

// Check whether the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Database connection
$mysqli = new mysqli("localhost", "root", "", "reimbustmen");

if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get reimbursement form data
    $tanggal = trim($_POST['tanggal'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $jumlah = $_POST['jumlah'] ?? '';

    // Validate required fields
    if ($tanggal === '' || $deskripsi === '' || $jumlah === '' || !is_numeric($jumlah) || (float)$jumlah <= 0) {
        die("Please complete all required fields correctly.");
    }

    // Get username from the logged-in user's account
    $stmt = $mysqli->prepare(
        "SELECT username FROM user WHERE id = ?"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $user_result = $stmt->get_result();
    $user_data = $user_result->fetch_assoc();

    $stmt->close();

    if (!$user_data) {
        die("User account not found.");
    }

    // Automatically use the account username as reimbursement name
    $nama = $user_data['username'];

    // Check uploaded file
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        die("Please upload a valid supporting document.");
    }

    $file = $_FILES['file'];

    // Create uploads directory if it does not exist
    $upload_directory = __DIR__ . '/uploads/';

    if (!is_dir($upload_directory)) {
        mkdir($upload_directory, 0755, true);
    }

    // Generate a unique filename to avoid overwriting existing files
    $original_name = basename($file['name']);
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);

    $new_file_name = bin2hex(random_bytes(8));

    if ($extension !== '') {
        $new_file_name .= '.' . strtolower($extension);
    }

    $file_path = $upload_directory . $new_file_name;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {

        // Save reimbursement with Pending status
        $status = 'Pending';

        $query = $mysqli->prepare("
            INSERT INTO reimbursement
            (user_id, tanggal, nama, deskripsi, jumlah, status, file)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $query->bind_param(
            "issssss",
            $user_id,
            $tanggal,
            $nama,
            $deskripsi,
            $jumlah,
            $status,
            $new_file_name
        );

        if ($query->execute()) {

            $query->close();
            $mysqli->close();

            header("Location: dashboard_user.php?status=submitted");
            exit();

        } else {

            // Remove uploaded file if database insertion fails
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            $query->close();

            die("Failed to submit reimbursement. Please try again.");
        }

    } else {
        die("Failed to upload supporting document.");
    }

} else {
    header("Location: dashboard_user.php");
    exit();
}

$mysqli->close();
?>
