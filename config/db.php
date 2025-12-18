<?php
// config/db.php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'lms_sederhana';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Fungsi helper untuk cek login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fungsi helper untuk cek role
function isGuru() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'guru';
}

function isSiswa() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'siswa';
}

// Fungsi helper redirect
function redirect($url) {
    header("Location: $url");
    exit();
}
?>