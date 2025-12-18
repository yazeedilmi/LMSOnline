<?php
// process/mark_complete.php
require_once '../config/db.php';

if (!isLoggedIn() || !isSiswa()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materi_id = $_POST['materi_id'] ?? '';
    $siswa_id = $_SESSION['user_id'];
    
    if (empty($materi_id)) {
        echo json_encode(['success' => false, 'message' => 'Materi ID tidak valid']);
        exit();
    }
    
    // Cek apakah siswa terdaftar di kelas ini
    $stmt = $conn->prepare("
        SELECT m.id 
        FROM materi m
        JOIN anggota_kelas ak ON m.kelas_id = ak.kelas_id
        WHERE m.id = ? AND ak.siswa_id = ?
    ");
    $stmt->bind_param("ii", $materi_id, $siswa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Anda tidak terdaftar di kelas ini']);
        exit();
    }
    
    // Cek apakah sudah ada progress
    $stmt = $conn->prepare("SELECT id, selesai FROM progress_materi WHERE materi_id = ? AND siswa_id = ?");
    $stmt->bind_param("ii", $materi_id, $siswa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update
        $progress = $result->fetch_assoc();
        $new_selesai = $progress['selesai'] ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE progress_materi SET selesai = ?, completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $new_selesai, $progress['id']);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'selesai' => $new_selesai]);
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO progress_materi (materi_id, siswa_id, selesai, completed_at) VALUES (?, ?, 1, NOW())");
        $stmt->bind_param("ii", $materi_id, $siswa_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'selesai' => 1]);
    }
    
    $stmt->close();
}

$conn->close();
?>