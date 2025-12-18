<?php
// process/tambah_kuis.php
require_once '../config/db.php';

if (!isLoggedIn() || !isGuru()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelas_id = $_POST['kelas_id'] ?? '';
    $judul = $_POST['judul'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $soal_list = $_POST['soal'] ?? [];
    
    if (empty($kelas_id) || empty($judul) || empty($soal_list)) {
        $_SESSION['error'] = 'Judul kuis dan minimal 1 soal harus diisi';
        redirect('../tambah_kuis.php?kelas_id=' . $kelas_id);
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
    
    // Mulai transaction
    $conn->begin_transaction();
    
    try {
        // Insert kuis
        $stmt = $conn->prepare("INSERT INTO kuis (kelas_id, judul, deskripsi) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $kelas_id, $judul, $deskripsi);
        $stmt->execute();
        $kuis_id = $conn->insert_id;
        
        // Insert soal-soal
        $stmt = $conn->prepare("INSERT INTO soal_kuis (kuis_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, urutan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $urutan = 1;
        foreach ($soal_list as $soal) {
            $pertanyaan = $soal['pertanyaan'] ?? '';
            $pilihan_a = $soal['pilihan_a'] ?? '';
            $pilihan_b = $soal['pilihan_b'] ?? '';
            $pilihan_c = $soal['pilihan_c'] ?? '';
            $pilihan_d = $soal['pilihan_d'] ?? '';
            $jawaban_benar = $soal['jawaban_benar'] ?? '';
            
            if (empty($pertanyaan) || empty($pilihan_a) || empty($pilihan_b) || empty($pilihan_c) || empty($pilihan_d) || empty($jawaban_benar)) {
                throw new Exception('Semua field soal harus diisi');
            }
            
            if (!in_array($jawaban_benar, ['a', 'b', 'c', 'd'])) {
                throw new Exception('Jawaban benar harus a, b, c, atau d');
            }
            
            $stmt->bind_param("issssssi", $kuis_id, $pertanyaan, $pilihan_a, $pilihan_b, $pilihan_c, $pilihan_d, $jawaban_benar, $urutan);
            $stmt->execute();
            $urutan++;
        }
        
        $conn->commit();
        $_SESSION['success'] = 'Kuis berhasil ditambahkan dengan ' . count($soal_list) . ' soal';
        redirect('../detail_kelas.php?id=' . $kelas_id);
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Gagal menambahkan kuis: ' . $e->getMessage();
        redirect('../tambah_kuis.php?kelas_id=' . $kelas_id);
    }
    
    $stmt->close();
}

$conn->close();
?>