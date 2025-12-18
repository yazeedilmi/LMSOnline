<?php
// process/join_kelas.php
require_once '../config/db.php';

if (!isLoggedIn() || !isSiswa()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_kelas = strtoupper($_POST['kode_kelas'] ?? '');
    $siswa_id = $_SESSION['user_id'];
    
    if (empty($kode_kelas)) {
        $_SESSION['error'] = 'Kode kelas harus diisi';
        redirect('../dashboard-siswa.php');
    }
    
    // Cek apakah kelas ada
    $stmt = $conn->prepare("SELECT id, nama_kelas FROM kelas WHERE kode_kelas = ?");
    $stmt->bind_param("s", $kode_kelas);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Kode kelas tidak ditemukan';
        redirect('../dashboard-siswa.php');
    }
    
    $kelas = $result->fetch_assoc();
    $kelas_id = $kelas['id'];
    
    // Cek apakah sudah join
    $stmt = $conn->prepare("SELECT id FROM anggota_kelas WHERE kelas_id = ? AND siswa_id = ?");
    $stmt->bind_param("ii", $kelas_id, $siswa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Anda sudah bergabung di kelas ini';
        redirect('../dashboard-siswa.php');
    }
    
    // Join kelas
    $stmt = $conn->prepare("INSERT INTO anggota_kelas (kelas_id, siswa_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $kelas_id, $siswa_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Berhasil bergabung ke kelas: ' . $kelas['nama_kelas'];
        redirect('../dashboard-siswa.php');
    } else {
        $_SESSION['error'] = 'Gagal bergabung ke kelas';
        redirect('../dashboard-siswa.php');
    }
    
    $stmt->close();
}

$conn->close();
?>