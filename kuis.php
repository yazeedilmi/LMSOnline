<?php
require_once 'config/db.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$kuis_id = $_GET['id'] ?? '';

if (empty($kuis_id)) {
    redirect(isGuru() ? 'dashboard-guru.php' : 'dashboard-siswa.php');
}

// Get kuis dan validasi akses
if (isGuru()) {
    $stmt = $conn->prepare("
        SELECT k.*, ks.nama_kelas 
        FROM kuis k
        JOIN kelas ks ON k.kelas_id = ks.id
        WHERE k.id = ? AND ks.guru_id = ?
    ");
    $stmt->bind_param("ii", $kuis_id, $_SESSION['user_id']);
} else {
    $stmt = $conn->prepare("
        SELECT k.*, ks.nama_kelas 
        FROM kuis k
        JOIN kelas ks ON k.kelas_id = ks.id
        JOIN anggota_kelas ak ON ks.id = ak.kelas_id
        WHERE k.id = ? AND ak.siswa_id = ?
    ");
    $stmt->bind_param("ii", $kuis_id, $_SESSION['user_id']);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Kuis tidak ditemukan';
    redirect(isGuru() ? 'dashboard-guru.php' : 'dashboard-siswa.php');
}

$kuis = $result->fetch_assoc();

// Get soal-soal
$stmt = $conn->prepare("SELECT * FROM soal_kuis WHERE kuis_id = ? ORDER BY urutan, id");
$stmt->bind_param("i", $kuis_id);
$stmt->execute();
$soal_list = $stmt->get_result();

// Jika siswa, cek sudah dikerjakan atau belum
$sudah_dikerjakan = false;
if (isSiswa()) {
    $stmt = $conn->prepare("SELECT id FROM hasil_kuis WHERE kuis_id = ? AND siswa_id = ?");
    $stmt->bind_param("ii", $kuis_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $sudah_dikerjakan = $result->num_rows > 0;
    
    // Cek apakah semua materi sudah selesai
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_materi,
               SUM(CASE WHEN pm.selesai = 1 THEN 1 ELSE 0 END) as materi_selesai
        FROM materi m
        JOIN kuis k ON m.kelas_id = k.kelas_id
        LEFT JOIN progress_materi pm ON m.id = pm.materi_id AND pm.siswa_id = ?
        WHERE k.id = ?
    ");
    $stmt->bind_param("ii", $_SESSION['user_id'], $kuis_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress = $result->fetch_assoc();
    $semua_materi_selesai = ($progress['total_materi'] == 0 || $progress['total_materi'] == $progress['materi_selesai']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($kuis['judul']) ?> - LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h2>LMS</h2>
            <div class="nav-right">
                <span><?= htmlspecialchars($_SESSION['nama']) ?></span>
                <a href="detail_kelas.php?id=<?= $kuis['kelas_id'] ?>" class="btn btn-sm">Kembali ke Kelas</a>
                <a href="process/logout.php" class="btn btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div style="max-width: 900px; margin: 0 auto;">
            <div style="background: white; padding: 30px; border-radius: 10px; margin-bottom: 20px;">
                <p style="color: #7f8c8d; margin-bottom: 10px;">Kelas: <?= htmlspecialchars($kuis['nama_kelas']) ?></p>
                <h1><?= htmlspecialchars($kuis['judul']) ?></h1>
                <?php if (!empty($kuis['deskripsi'])): ?>
                    <p style="color: #7f8c8d; margin-top: 10px;"><?= htmlspecialchars($kuis['deskripsi']) ?></p>
                <?php endif; ?>
                
                <div style="margin-top: 15px; padding: 15px; background: #ecf0f1; border-radius: 5px;">
                    <strong>Jumlah Soal:</strong> <?= $soal_list->num_rows ?> soal
                </div>
            </div>
            
            <?php if (isSiswa() && $sudah_dikerjakan): ?>
                <div class="alert alert-success">
                    ✓ Anda sudah mengerjakan kuis ini
                </div>
                <div style="text-align: center;">
                    <a href="detail_kelas.php?id=<?= $kuis['kelas_id'] ?>" class="btn btn-primary">Kembali ke Kelas</a>
                </div>
            <?php elseif (isSiswa() && !$semua_materi_selesai): ?>
                <div class="alert alert-error">
                    ⚠️ Anda harus menyelesaikan semua materi terlebih dahulu sebelum mengerjakan kuis
                </div>
                <div style="text-align: center;">
                    <a href="detail_kelas.php?id=<?= $kuis['kelas_id'] ?>" class="btn btn-primary">Kembali ke Kelas</a>
                </div>
            <?php elseif ($soal_list->num_rows > 0): ?>
                <form action="process/submit_kuis.php" method="POST" id="form-kuis">
                    <input type="hidden" name="kuis_id" value="<?= $kuis_id ?>">
                    
                    <?php $no = 1; while ($soal = $soal_list->fetch_assoc()): ?>
                        <div class="soal-container">
                            <div class="soal-header">
                                <div class="soal-number">Soal <?= $no ?></div>
                            </div>
                            
                            <div class="pertanyaan">
                                <?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?>
                            </div>
                            
                            <div class="pilihan-group">
                                <div class="pilihan-item">
                                    <input type="radio" name="jawaban[<?= $soal['id'] ?>]" value="a" id="soal<?= $soal['id'] ?>_a" required <?= isGuru() ? 'disabled' : '' ?>>
                                    <label for="soal<?= $soal['id'] ?>_a">
                                        <strong>A.</strong> <?= htmlspecialchars($soal['pilihan_a']) ?>
                                        <?php if (isGuru() && $soal['jawaban_benar'] === 'a'): ?>
                                            <span style="color: #27ae60; font-weight: bold;"> ✓ (Jawaban Benar)</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                                
                                <div class="pilihan-item">
                                    <input type="radio" name="jawaban[<?= $soal['id'] ?>]" value="b" id="soal<?= $soal['id'] ?>_b" required <?= isGuru() ? 'disabled' : '' ?>>
                                    <label for="soal<?= $soal['id'] ?>_b">
                                        <strong>B.</strong> <?= htmlspecialchars($soal['pilihan_b']) ?>
                                        <?php if (isGuru() && $soal['jawaban_benar'] === 'b'): ?>
                                            <span style="color: #27ae60; font-weight: bold;"> ✓ (Jawaban Benar)</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                                
                                <div class="pilihan-item">
                                    <input type="radio" name="jawaban[<?= $soal['id'] ?>]" value="c" id="soal<?= $soal['id'] ?>_c" required <?= isGuru() ? 'disabled' : '' ?>>
                                    <label for="soal<?= $soal['id'] ?>_c">
                                        <strong>C.</strong> <?= htmlspecialchars($soal['pilihan_c']) ?>
                                        <?php if (isGuru() && $soal['jawaban_benar'] === 'c'): ?>
                                            <span style="color: #27ae60; font-weight: bold;"> ✓ (Jawaban Benar)</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                                
                                <div class="pilihan-item">
                                    <input type="radio" name="jawaban[<?= $soal['id'] ?>]" value="d" id="soal<?= $soal['id'] ?>_d" required <?= isGuru() ? 'disabled' : '' ?>>
                                    <label for="soal<?= $soal['id'] ?>_d">
                                        <strong>D.</strong> <?= htmlspecialchars($soal['pilihan_d']) ?>
                                        <?php if (isGuru() && $soal['jawaban_benar'] === 'd'): ?>
                                            <span style="color: #27ae60; font-weight: bold;"> ✓ (Jawaban Benar)</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php $no++; endwhile; ?>
                    
                    <?php if (isSiswa()): ?>
                        <div style="text-align: center; margin-top: 30px;">
                            <button type="submit" class="btn btn-success" style="font-size: 18px; padding: 15px 40px;" onclick="return confirm('Yakin ingin submit jawaban? Anda tidak bisa mengubah jawaban setelah submit.')">
                                Submit Jawaban
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <p>Belum ada soal untuk kuis ini</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>