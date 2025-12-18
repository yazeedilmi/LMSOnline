<?php
// process/register.php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if (empty($nama) || empty($email) || empty($password) || empty($role)) {
        $_SESSION['error'] = 'Semua field harus diisi';
        redirect('../index.php');
    }
    
    if (!in_array($role, ['guru', 'siswa'])) {
        $_SESSION['error'] = 'Role tidak valid';
        redirect('../index.php');
    }
    
    // Cek email sudah ada atau belum
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Email sudah terdaftar';
        redirect('../index.php');
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user baru
    $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Registrasi berhasil! Silakan login';
        redirect('../index.php');
    } else {
        $_SESSION['error'] = 'Registrasi gagal';
        redirect('../index.php');
    }
    
    $stmt->close();
}

$conn->close();
?>