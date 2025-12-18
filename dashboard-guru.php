<?php
require_once 'config/db.php';

if (!isLoggedIn() || !isGuru()) {
    redirect('index.php');
}

// Get daftar kelas guru
$guru_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT k.*, 
           COUNT(DISTINCT ak.siswa_id) as jumlah_siswa,
           COUNT(DISTINCT m.id) as jumlah_materi,
           COUNT(DISTINCT kz.id) as jumlah_kuis
    FROM kelas k
    LEFT JOIN anggota_kelas ak ON k.id = ak.kelas_id
    LEFT JOIN materi m ON k.id = m.kelas_id
    LEFT JOIN kuis kz ON k.id = kz.kelas_id
    WHERE k.guru_id = ?
    GROUP BY k.id
    ORDER BY k.created_at DESC
");
$stmt->bind_param("i", $guru_id);
$stmt->execute();
$kelas_list = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h2>LMS - Dashboard Guru</h2>
            <div class="nav-right">
                <span>Halo, <?= htmlspecialchars($_SESSION['nama']) ?></span>
                <a href="process/logout.php" class="btn btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1>Daftar Kelas</h1>
            <a href="tambah_kelas.php" class="btn btn-primary">+ Tambah Kelas</a>
        </div>
        
        <?php if ($kelas_list->num_rows > 0): ?>
            <div class="kelas-grid">
                <?php while ($kelas = $kelas_list->fetch_assoc()): ?>
                    <div class="kelas-card">
                        <h3><?= htmlspecialchars($kelas['nama_kelas']) ?></h3>
                        <p class="kelas-desc"><?= htmlspecialchars($kelas['deskripsi']) ?></p>
                        <div class="kelas-kode">
                            <strong>Kode Kelas:</strong> 
                            <span class="kode"><?= $kelas['kode_kelas'] ?></span>
                        </div>
                        <div class="kelas-stats">
                            <span>👥 <?= $kelas['jumlah_siswa'] ?> siswa</span>
                            <span>📚 <?= $kelas['jumlah_materi'] ?> materi</span>
                            <span>📝 <?= $kelas['jumlah_kuis'] ?> kuis</span>
                        </div>
                        <div class="kelas-actions">
                            <a href="detail_kelas.php?id=<?= $kelas['id'] ?>" class="btn btn-sm btn-primary">Lihat Detail</a>
                            <a href="tambah_materi.php?kelas_id=<?= $kelas['id'] ?>" class="btn btn-sm">+ Materi</a>
                            <a href="tambah_kuis.php?kelas_id=<?= $kelas['id'] ?>" class="btn btn-sm">+ Kuis</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Belum ada kelas. Silakan tambah kelas baru.</p>
                <a href="tambah_kelas.php" class="btn btn-primary">+ Tambah Kelas Pertama</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>