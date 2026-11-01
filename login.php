<?php
session_start();  // Mulai session untuk menyimpan informasi login

require 'db.php'; // Koneksi ke database

// Ambil data dari request (JSON)
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';  // Ambil email dari request
$password = $data['password'] ?? '';  // Ambil password dari request

// Pastikan email dan password ada
if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Email dan password harus diisi"]);
    exit();
}

// Ambil data pengguna dari tabel "user"
$stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Validasi password
if ($user && password_verify($password, $user['password'])) {
    // Simpan informasi pengguna ke session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['username'] = $user['username']; // Pastikan 'username' ada di database
    $_SESSION['role'] = $user['role']; // Menyimpan role pengguna (admin atau user)

    // Cek role pengguna dan arahkan ke halaman yang sesuai
    if ($_SESSION['role'] == 'admin') {
        // Jika admin, arahkan ke dashboard admin
        echo json_encode(["status" => "success", "message" => "Login berhasil", "redirect" => "dashboard_admin.php"]);
    } else {
        // Jika user biasa, arahkan ke dashboard user
        echo json_encode(["status" => "success", "message" => "Login berhasil", "redirect" => "dashboard_user.php"]);
    }
} else {
    // Jika login gagal
    echo json_encode(["status" => "error", "message" => "Email atau password salah"]);
}
?>
