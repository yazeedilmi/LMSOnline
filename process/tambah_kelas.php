<?php
// process/tambah_kelas.php
require_once '../config/db.php';

if (!isLoggedIn() || !isGuru()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelas = $_POST['nama_kelas'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $guru_id = $_SESSION['user_id'];
    
    if (empty($nama_kelas)) {
        $_SESSION['error'] = 'Nama kelas harus diisi';
        redirect('../tambah_kelas.php');
    }
    
    // Generate kode kelas unik (6 karakter random)
    do {
        $kode_kelas = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $stmt = $conn->prepare("SELECT id FROM kelas WHERE kode_kelas = ?");
        $stmt->bind_param("s", $kode_kelas);
        $stmt->execute();
        $result = $stmt->get_result();
    } while ($result->num_rows > 0);
    
    // Insert kelas baru
    $stmt = $conn->prepare("INSERT INTO kelas (nama_kelas, deskripsi, kode_kelas, guru_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $nama_kelas, $deskripsi, $kode_kelas, $guru_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Kelas berhasil dibuat dengan kode: ' . $kode_kelas;
        redirect('../dashboard-guru.php');
    } else {
        $_SESSION['error'] = 'Gagal membuat kelas';
        redirect('../tambah_kelas.php');
    }
    
    $stmt->close();
}

$conn->close();
?>