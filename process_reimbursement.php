<?php
session_start();

// Menghubungkan ke database
$mysqli = new mysqli("localhost", "root", "", "reimbustmen");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Memeriksa jika tombol 'Approve' ditekan
if (isset($_POST['accept'])) {
    $id = $_POST['id']; // ID pengajuan

    // Update status pengajuan menjadi 'Approved'
    $updateQuery = "UPDATE reimbursement SET status = 'Approved' WHERE id = ?";
    $stmt = $mysqli->prepare($updateQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// Memeriksa jika tombol 'Reject' ditekan
if (isset($_POST['reject'])) {
    $id = $_POST['id']; // ID pengajuan

    // Update status pengajuan menjadi 'Rejected'
    $updateQuery = "UPDATE reimbursement SET status = 'Rejected' WHERE id = ?";
    $stmt = $mysqli->prepare($updateQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// Setelah update status, arahkan kembali ke halaman admin dashboard
header("Location: dashboard_admin.php");
exit();
?>
