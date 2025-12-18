<?php
// process/upload_materi.php
require_once '../config/db.php';

if (!isLoggedIn() || !isGuru()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelas_id = $_POST['kelas_id'] ?? '';
    $judul = $_POST['judul'] ?? '';
    
    if (empty($kelas_id) || empty($judul)) {
        $_SESSION['error'] = 'Kelas ID dan judul harus diisi';
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
    
    // Validasi file upload
    if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = 'File PDF harus diupload';
        redirect('../tambah_materi.php?kelas_id=' . $kelas_id);
    }
    
    $file = $_FILES['pdf'];
    $allowed_ext = ['pdf'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_ext)) {
        $_SESSION['error'] = 'Hanya file PDF yang diperbolehkan';
        redirect('../tambah_materi.php?kelas_id=' . $kelas_id);
    }
    
    // Max 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        $_SESSION['error'] = 'Ukuran file maksimal 10MB';
        redirect('../tambah_materi.php?kelas_id=' . $kelas_id);
    }
    
    // Create upload directory
    $upload_dir = '../uploads/materi/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.pdf';
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Get urutan terakhir
        $stmt = $conn->prepare("SELECT COALESCE(MAX(urutan), 0) + 1 as next_urutan FROM materi WHERE kelas_id = ?");
        $stmt->bind_param("i", $kelas_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $urutan = $result->fetch_assoc()['next_urutan'];
        
        // Insert materi
        $tipe = 'pdf';
        $konten = 'uploads/materi/' . $filename;
        $stmt = $conn->prepare("INSERT INTO materi (kelas_id, judul, tipe, konten, urutan) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $kelas_id, $judul, $tipe, $konten, $urutan);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Materi PDF berhasil diupload';
            redirect('../detail_kelas.php?id=' . $kelas_id);
        } else {
            unlink($filepath); // Hapus file jika gagal insert
            $_SESSION['error'] = 'Gagal menyimpan data materi';
            redirect('../tambah_materi.php?kelas_id=' . $kelas_id);
        }
    } else {
        $_SESSION['error'] = 'Gagal mengupload file';
        redirect('../tambah_materi.php?kelas_id=' . $kelas_id);
    }
    
    $stmt->close();
}

$conn->close();
?>