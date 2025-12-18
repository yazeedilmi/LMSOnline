<?php
require_once 'config/db.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$kelas_id = $_GET['id'] ?? '';

if (empty($kelas_id)) {
    redirect(isGuru() ? 'dashboard-guru.php' : 'dashboard-siswa.php');
}

// Get info kelas
if (isGuru()) {
    $stmt = $conn->prepare("SELECT * FROM kelas WHERE id = ? AND guru_id = ?");
    $stmt->bind_param("ii", $kelas_id, $_SESSION['user_id']);
} else {
    $stmt = $conn->prepare("
        SELECT k.* FROM kelas k
        JOIN anggota_kelas ak ON k.id = ak.kelas_id
        WHERE k.id = ? AND ak.siswa_id = ?
    ");
    $stmt->bind_param("ii", $kelas_id, $_SESSION['user_id']);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Kelas tidak ditemukan';
    redirect(isGuru() ? 'dashboard-guru.php' : 'dashboard-siswa.php');
}

$kelas = $result->fetch_assoc();

// Get materi
$stmt = $conn->prepare("SELECT * FROM materi WHERE kelas_id = ? ORDER BY urutan, created_at");
$stmt->bind_param("i", $kelas_id);
$stmt->execute();
$materi_list = $stmt->get_result();

// Get kuis
$stmt = $conn->prepare("SELECT * FROM kuis WHERE kelas_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $kelas_id);
$stmt->execute();
$kuis_list = $stmt->get_result();

// Logika Progres Siswa & Guru
$progress_map = [];
$hasil_map = [];
$siswa_list = [];

if (isSiswa()) {
    $stmt = $conn->prepare("SELECT materi_id, selesai FROM progress_materi WHERE siswa_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $progress_map[$row['materi_id']] = $row['selesai'];
    }
    
    $stmt = $conn->prepare("
        SELECT kuis_id, nilai 
        FROM hasil_kuis 
        WHERE siswa_id = ? AND kuis_id IN (SELECT id FROM kuis WHERE kelas_id = ?)
    ");
    $stmt->bind_param("ii", $_SESSION['user_id'], $kelas_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $hasil_map[$row['kuis_id']] = $row['nilai'];
    }
} else if (isGuru()) {
    $sql_siswa = "
        SELECT 
            u.id, u.nama, u.email,
            (SELECT COUNT(*) FROM progress_materi pm 
             JOIN materi m ON pm.materi_id = m.id 
             WHERE pm.siswa_id = u.id AND m.kelas_id = ? AND pm.selesai = 1) as materi_selesai,
            (SELECT COUNT(*) FROM materi WHERE kelas_id = ?) as total_materi
        FROM users u
        JOIN anggota_kelas ak ON u.id = ak.siswa_id
        WHERE ak.kelas_id = ?
        ORDER BY u.nama ASC
    ";
    $stmt = $conn->prepare($sql_siswa);
    $stmt->bind_param("iii", $kelas_id, $kelas_id, $kelas_id);
    $stmt->execute();
    $siswa_list = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($kelas['nama_kelas']) ?> - LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h2>LMS</h2>
            <div class="nav-right">
                <span><?= htmlspecialchars($_SESSION['nama']) ?></span>
                <a href="<?= isGuru() ? 'dashboard-guru.php' : 'dashboard-siswa.php' ?>" class="btn btn-sm">Dashboard</a>
                <a href="process/logout.php" class="btn btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <div class="kelas-header" style="background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px;">
            <h1><?= htmlspecialchars($kelas['nama_kelas']) ?></h1>
            <p style="color: #7f8c8d; margin: 10px 0;"><?= htmlspecialchars($kelas['deskripsi']) ?></p>
            <?php if (isGuru()): ?>
                <div class="kelas-kode">
                    <strong>Kode Kelas:</strong> <span class="kode"><?= $kelas['kode_kelas'] ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px;">
            <div class="page-header" style="margin-bottom: 20px;">
                <h2>📚 Materi Pembelajaran</h2>
            </div>
            
            <?php if ($materi_list->num_rows > 0): ?>
                <div class="materi-list">
                    <?php while ($materi = $materi_list->fetch_assoc()): 
                        $is_completed = isset($progress_map[$materi['id']]) && $progress_map[$materi['id']] == 1;
                    ?>
                        <div class="materi-item">
                            <div class="materi-info">
                                <h4>
                                    <?= htmlspecialchars($materi['judul']) ?>
                                    <span class="materi-badge badge-<?= $materi['tipe'] ?>"><?= strtoupper($materi['tipe']) ?></span>
                                    <?php if (isSiswa() && $is_completed): ?>
                                        <span class="materi-badge badge-completed">✓ Selesai</span>
                                    <?php endif; ?>
                                </h4>
                            </div>
                            <div class="materi-actions">
                                <a href="materi.php?id=<?= $materi['id'] ?>" class="btn btn-primary btn-sm">Buka Materi</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color: #7f8c8d; text-align: center; padding: 20px;">Belum ada materi</p>
            <?php endif; ?>
        </div>
        
        <div style="background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px;">
            <div class="page-header" style="margin-bottom: 20px;">
                <h2>📝 Kuis</h2>
            </div>
            
            <?php if ($kuis_list->num_rows > 0): ?>
                <div class="materi-list">
                    <?php while ($kuis = $kuis_list->fetch_assoc()): 
                        $sudah_dikerjakan = isset($hasil_map[$kuis['id']]);
                    ?>
                        <div class="materi-item">
                            <div class="materi-info">
                                <h4><?= htmlspecialchars($kuis['judul']) ?></h4>
                                <p style="font-size: 14px; color: #7f8c8d;"><?= htmlspecialchars($kuis['deskripsi']) ?></p>
                                <?php if (isSiswa() && $sudah_dikerjakan): ?>
                                    <p style="color: #27ae60; font-weight: bold; margin-top: 5px;">
                                        ✓ Sudah dikerjakan - Nilai: <?= number_format($hasil_map[$kuis['id']], 2) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="materi-actions">
                                <?php if (isSiswa()): ?>
                                    <?php if ($sudah_dikerjakan): ?>
                                        <span class="btn btn-sm" style="background: #95a5a6; cursor: not-allowed;">Sudah Selesai</span>
                                    <?php else: ?>
                                        <a href="kuis.php?id=<?= $kuis['id'] ?>" class="btn btn-success btn-sm">Kerjakan Kuis</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="kuis.php?id=<?= $kuis['id'] ?>" class="btn btn-primary btn-sm">Lihat Kuis</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color: #7f8c8d; text-align: center; padding: 20px;">Belum ada kuis</p>
            <?php endif; ?>
        </div>

        <?php if (isGuru()): ?>
        <div style="background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px;">
            <div class="page-header" style="margin-bottom: 20px;">
                <h2>👥 Daftar Siswa & Aktivitas</h2>
            </div>
            
            <?php if ($siswa_list->num_rows > 0): ?>
                <div class="materi-list">
                    <?php while ($siswa = $siswa_list->fetch_assoc()): ?>
                        <div class="materi-item" style="display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #3498db; gap: 15px;">
                            <div class="materi-info" style="flex: 1;">
                                <h4 style="margin: 0;"><?= htmlspecialchars($siswa['nama']) ?></h4>
                                <p style="font-size: 12px; color: #7f8c8d; margin: 2px 0;">Materi: <?= $siswa['materi_selesai'] ?> / <?= $siswa['total_materi'] ?> Selesai</p>
                            </div>
                            
                            <div class="materi-actions" style="display: flex; gap: 5px; flex-wrap: wrap; justify-content: flex-end; max-width: 50%;">
                                <?php
                                $stmt_n = $conn->prepare("
                                    SELECT h.nilai FROM hasil_kuis h
                                    JOIN kuis k ON h.kuis_id = k.id
                                    WHERE h.siswa_id = ? AND k.kelas_id = ?
                                ");
                                $stmt_n->bind_param("ii", $siswa['id'], $kelas_id);
                                $stmt_n->execute();
                                $nilais = $stmt_n->get_result();
                                
                                if ($nilais->num_rows > 0):
                                    while ($n = $nilais->fetch_assoc()): ?>
                                        <span style="background: #e8f5e9; color: #27ae60; padding: 2px 8px; border-radius: 4px; border: 1px solid #27ae60; font-size: 12px; font-weight: bold;">
                                            <?= (float)$n['nilai'] ?>
                                        </span>
                                    <?php endwhile;
                                else: ?>
                                    <span style="font-size: 11px; color: #bdc3c7; font-style: italic;">Belum ada kuis</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color: #7f8c8d; text-align: center; padding: 20px;">Belum ada siswa yang bergabung</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>