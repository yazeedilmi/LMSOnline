<?php
// process/tambah_materi.php
require_once '../config/db.php';

if (!isLoggedIn() || !isGuru()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelas_id = $_POST['kelas_id'] ?? '';
    $judul = $_POST['judul'] ?? '';
    $tipe = $_POST['tipe'] ?? '';
    $konten = '';
    
    if (empty($kelas_id) || empty($judul) || empty($tipe)) {
        $_SESSION['error'] = 'Semua field harus diisi';
        redirect('../tambah_materi.php?kelas_id=' . $kelas_id);
    }
    
    // Validasi guru memiliki kelas ini
    $stmt = $conn->prepare("SELECT id FROM kelas WHERE id = ? AND guru_id = ?");
    $stmt->bind_param("ii", $kelas_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Kelas tidak ditemukan';
        redirect('../dashboard-guru.php');
    }
    
    if ($tipe === 'link') {
        $konten = $_POST['link'] ?? '';
        if (empty($konten)) {
            $_SESSION['error'] = 'Link harus diisi';
            redirect('../tambah_materi.php?kelas_id=' . $kelas_id);
        }
    } else if ($tipe === 'pdf') {
        // Handle upload PDF (akan dihandle di upload_materi.php)
        $_SESSION['error'] = 'Gunakan form upload PDF';
        redirect('../tambah_materi.php?kelas_id=' . $kelas_id);
    }
    
    // Get urutan terakhir
    $stmt = $conn->prepare("SELECT COALESCE(MAX(urutan), 0) + 1 as next_urutan FROM materi WHERE kelas_id = ?");
    $stmt->bind_param("i", $kelas_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $urutan = $result->fetch_assoc()['next_urutan'];
    
    // Insert materi
    $stmt = $conn->prepare("INSERT INTO materi (kelas_id, judul, tipe, konten, urutan) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $kelas_id, $judul, $tipe, $konten, $urutan);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Materi berhasil ditambahkan';
        redirect('../detail_kelas.php?id=' . $kelas_id);
    } else {
        $_SESSION['error'] = 'Gagal menambahkan materi';
        redirect('../tambah_materi.php?kelas_id=' . $kelas_id);
    }
    
    $stmt->close();
}

$conn->close();
?>