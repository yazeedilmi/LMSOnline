<?php
// process/login.php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Email dan password harus diisi';
        redirect('../index.php');
    }
    
    $stmt = $conn->prepare("SELECT id, nama, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['success'] = 'Login berhasil!';
            
            // Redirect sesuai role
            if ($user['role'] === 'guru') {
                redirect('../dashboard-guru.php');
            } else {
                redirect('../dashboard-siswa.php');
            }
        } else {
            $_SESSION['error'] = 'Password salah';
            redirect('../index.php');
        }
    } else {
        $_SESSION['error'] = 'Email tidak ditemukan';
        redirect('../index.php');
    }
    
    $stmt->close();
}

$conn->close();
?>