<?php
require_once 'config/db.php';

if (!isLoggedIn() || !isSiswa()) {
    redirect('index.php');
}

$hasil_id = $_GET['id'] ?? '';

if (empty($hasil_id)) {
    redirect('dashboard-siswa.php');
}

// Get hasil kuis
$stmt = $conn->prepare("
    SELECT hk.*, k.judul, k.kelas_id, ks.nama_kelas
    FROM hasil_kuis hk
    JOIN kuis k ON hk.kuis_id = k.id
    JOIN kelas ks ON k.kelas_id = ks.id
    WHERE hk.id = ? AND hk.siswa_id = ?
");
$stmt->bind_param("ii", $hasil_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Hasil kuis tidak ditemukan';
    redirect('dashboard-siswa.php');
}

$hasil = $result->fetch_assoc();

// Get detail jawaban
$stmt = $conn->prepare("
    SELECT js.*, sk.pertanyaan, sk.pilihan_a, sk.pilihan_b, sk.pilihan_c, sk.pilihan_d, sk.jawaban_benar
    FROM jawaban_siswa js
    JOIN soal_kuis sk ON js.soal_id = sk.id
    WHERE js.hasil_kuis_id = ?
    ORDER BY sk.urutan, sk.id
");
$stmt->bind_param("i", $hasil_id);
$stmt->execute();
$jawaban_list = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Kuis - LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h2>LMS</h2>
            <div class="nav-right">
                <span><?= htmlspecialchars($_SESSION['nama']) ?></span>
                <a href="detail_kelas.php?id=<?= $hasil['kelas_id'] ?>" class="btn btn-sm">Kembali ke Kelas</a>
                <a href="process/logout.php" class="btn btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div style="max-width: 900px; margin: 0 auto;">
            <!-- Header Hasil -->
            <div style="background: white; padding: 40px; border-radius: 10px; margin-bottom: 30px; text-align: center;">
                <h1>Hasil Kuis</h1>
                <p style="color: #7f8c8d; margin: 10px 0;">Kelas: <?= htmlspecialchars($hasil['nama_kelas']) ?></p>
                <h2 style="color: #2c3e50;"><?= htmlspecialchars($hasil['judul']) ?></h2>
                
                <div style="margin: 30px 0;">
                    <div style="display: inline-block; background: <?= $hasil['nilai'] >= 70 ? '#27ae60' : '#e74c3c' ?>; color: white; padding: 30px 50px; border-radius: 15px;">
                        <div style="font-size: 48px; font-weight: bold;"><?= number_format($hasil['nilai'], 0) ?></div>
                        <div style="font-size: 18px; margin-top: 10px;">Nilai Anda</div>
                    </div>
                </div>
                
                <p style="color: #7f8c8d; margin-top: 20px;">
                    <strong>Status:</strong> 
                    <span style="color: <?= $hasil['nilai'] >= 70 ? '#27ae60' : '#e74c3c' ?>; font-weight: bold;">
                        <?= $hasil['nilai'] >= 70 ? '✓ LULUS' : '✗ BELUM LULUS' ?>
                    </span>
                </p>
                <p style="color: #7f8c8d; font-size: 14px;">Dikerjakan pada: <?= date('d/m/Y H:i', strtotime($hasil['completed_at'])) ?></p>
            </div>
            
            <!-- Detail Jawaban -->
            <div style="background: white; padding: 30px; border-radius: 10px;">
                <h2 style="margin-bottom: 25px;">📋 Review Jawaban</h2>
                
                <?php $no = 1; while ($jawab = $jawaban_list->fetch_assoc()): ?>
                    <div style="background: <?= $jawab['benar'] ? '#d4edda' : '#f8d7da' ?>; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid <?= $jawab['benar'] ? '#27ae60' : '#e74c3c' ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <strong>Soal <?= $no ?></strong>
                            <span style="background: <?= $jawab['benar'] ? '#27ae60' : '#e74c3c' ?>; color: white; padding: 5px 15px; border-radius: 5px; font-weight: bold;">
                                <?= $jawab['benar'] ? '✓ BENAR' : '✗ SALAH' ?>
                            </span>
                        </div>
                        
                        <p style="margin-bottom: 15px; font-weight: 500;"><?= nl2br(htmlspecialchars($jawab['pertanyaan'])) ?></p>
                        
                        <div style="background: white; padding: 15px; border-radius: 5px;">
                            <p><strong>Pilihan A:</strong> <?= htmlspecialchars($jawab['pilihan_a']) ?> <?= $jawab['jawaban_benar'] === 'a' ? '<span style="color: #27ae60;">✓</span>' : '' ?></p>
                            <p><strong>Pilihan B:</strong> <?= htmlspecialchars($jawab['pilihan_b']) ?> <?= $jawab['jawaban_benar'] === 'b' ? '<span style="color: #27ae60;">✓</span>' : '' ?></p>
                            <p><strong>Pilihan C:</strong> <?= htmlspecialchars($jawab['pilihan_c']) ?> <?= $jawab['jawaban_benar'] === 'c' ? '<span style="color: #27ae60;">✓</span>' : '' ?></p>
                            <p><strong>Pilihan D:</strong> <?= htmlspecialchars($jawab['pilihan_d']) ?> <?= $jawab['jawaban_benar'] === 'd' ? '<span style="color: #27ae60;">✓</span>' : '' ?></p>
                        </div>
                        
                        <div style="margin-top: 15px; padding: 10px; background: white; border-radius: 5px;">
                            <p><strong>Jawaban Anda:</strong> <span style="color: <?= $jawab['benar'] ? '#27ae60' : '#e74c3c' ?>; font-weight: bold;"><?= strtoupper($jawab['jawaban']) ?></span></p>
                            <?php if (!$jawab['benar']): ?>
                                <p><strong>Jawaban Benar:</strong> <span style="color: #27ae60; font-weight: bold;"><?= strtoupper($jawab['jawaban_benar']) ?></span></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php $no++; endwhile; ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="detail_kelas.php?id=<?= $hasil['kelas_id'] ?>" class="btn btn-primary">Kembali ke Kelas</a>
            </div>
        </div>
    </div>
</body>
</html>