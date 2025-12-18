<?php
// process/submit_kuis.php
require_once '../config/db.php';

if (!isLoggedIn() || !isSiswa()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kuis_id = $_POST['kuis_id'] ?? '';
    $jawaban = $_POST['jawaban'] ?? [];
    $siswa_id = $_SESSION['user_id'];
    
    if (empty($kuis_id) || empty($jawaban)) {
        $_SESSION['error'] = 'Data tidak lengkap';
        redirect('../kuis.php?id=' . $kuis_id);
    }
    
    // Validasi siswa terdaftar di kelas ini
    $stmt = $conn->prepare("
        SELECT k.id 
        FROM kuis k
        JOIN anggota_kelas ak ON k.kelas_id = ak.kelas_id
        WHERE k.id = ? AND ak.siswa_id = ?
    ");
    $stmt->bind_param("ii", $kuis_id, $siswa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Anda tidak terdaftar di kelas ini';
        redirect('../dashboard-siswa.php');
    }
    
    // Cek apakah sudah mengerjakan
    $stmt = $conn->prepare("SELECT id FROM hasil_kuis WHERE kuis_id = ? AND siswa_id = ?");
    $stmt->bind_param("ii", $kuis_id, $siswa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Anda sudah mengerjakan kuis ini';
        redirect('../dashboard-siswa.php');
    }
    
    // Cek apakah semua materi sudah selesai
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_materi,
               SUM(CASE WHEN pm.selesai = 1 THEN 1 ELSE 0 END) as materi_selesai
        FROM materi m
        JOIN kuis k ON m.kelas_id = k.kelas_id
        LEFT JOIN progress_materi pm ON m.id = pm.materi_id AND pm.siswa_id = ?
        WHERE k.id = ?
    ");
    $stmt->bind_param("ii", $siswa_id, $kuis_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress = $result->fetch_assoc();
    
    if ($progress['total_materi'] > 0 && $progress['total_materi'] != $progress['materi_selesai']) {
        $_SESSION['error'] = 'Anda harus menyelesaikan semua materi terlebih dahulu';
        redirect('../kuis.php?id=' . $kuis_id);
    }
    
    // Mulai transaction
    $conn->begin_transaction();
    
    try {
        // Get semua soal
        $stmt = $conn->prepare("SELECT id, jawaban_benar FROM soal_kuis WHERE kuis_id = ? ORDER BY urutan");
        $stmt->bind_param("i", $kuis_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $soal_list = [];
        while ($row = $result->fetch_assoc()) {
            $soal_list[$row['id']] = $row['jawaban_benar'];
        }
        
        // Hitung nilai
        $benar = 0;
        $total = count($soal_list);
        
        // Insert hasil kuis
        $nilai = 0;
        $stmt = $conn->prepare("INSERT INTO hasil_kuis (kuis_id, siswa_id, nilai) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $kuis_id, $siswa_id, $nilai);
        $stmt->execute();
        $hasil_kuis_id = $conn->insert_id;
        
        // Insert jawaban dan hitung benar
        $stmt = $conn->prepare("INSERT INTO jawaban_siswa (hasil_kuis_id, soal_id, jawaban, benar) VALUES (?, ?, ?, ?)");
        
        foreach ($jawaban as $soal_id => $jawaban_siswa) {
            if (isset($soal_list[$soal_id])) {
                $is_benar = ($soal_list[$soal_id] === $jawaban_siswa) ? 1 : 0;
                if ($is_benar) $benar++;
                
                $stmt->bind_param("iisi", $hasil_kuis_id, $soal_id, $jawaban_siswa, $is_benar);
                $stmt->execute();
            }
        }
        
        // Update nilai
        $nilai = ($benar / $total) * 100;
        $stmt = $conn->prepare("UPDATE hasil_kuis SET nilai = ? WHERE id = ?");
        $stmt->bind_param("di", $nilai, $hasil_kuis_id);
        $stmt->execute();
        
        $conn->commit();
        $_SESSION['success'] = 'Kuis berhasil dikerjakan! Nilai Anda: ' . number_format($nilai, 2);
        redirect('../hasil_kuis.php?id=' . $hasil_kuis_id);
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Gagal menyimpan jawaban: ' . $e->getMessage();
        redirect('../kuis.php?id=' . $kuis_id);
    }
    
    $stmt->close();
}

$conn->close();
?>