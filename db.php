<?php
$host = 'localhost'; // Host database
$db = 'reimbustmen'; // Nama database
$user = 'root'; // Username phpMyAdmin (default adalah 'root')
$pass = ''; // Password phpMyAdmin (kosong jika belum diatur)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>
