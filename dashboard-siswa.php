<?php
require_once 'config/db.php';

if (!isLoggedIn() || !isSiswa()) {
    redirect('index.php');
}

$siswa_id = $_SESSION['user_id'];

// Get kelas yang diikuti siswa
$stmt = $conn->prepare("
    SELECT k.*, u.nama as nama_guru,
           COUNT(DISTINCT m.id) as total_materi,
           COUNT(DISTINCT pm.id) as materi_selesai,
           COUNT(DISTINCT kz.id) as total_kuis,
           COUNT(DISTINCT hk.id) as kuis_selesai
    FROM kelas k
    JOIN anggota_kelas ak ON k.id = ak.kelas_id
    JOIN users u ON k.guru_id = u.id
    LEFT JOIN materi m ON k.id = m.kelas_id
    LEFT JOIN progress_materi pm ON m.id = pm.materi_id AND pm.siswa_id = ? AND pm.selesai = 1
    LEFT JOIN kuis kz ON k.id = kz.kelas_id
    LEFT JOIN hasil_kuis hk ON kz.id = hk.kuis_id AND hk.siswa_id = ?
    WHERE ak.siswa_id = ?
    GROUP BY k.id
    ORDER BY ak.joined_at DESC
");
$stmt->bind_param("iii", $siswa_id, $siswa_id, $siswa_id);
$stmt->execute();
$kelas_list = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h2>LMS - Dashboard Siswa</h2>
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
            <h1>Kelas Saya</h1>
            <button onclick="showJoinForm()" class="btn btn-primary">+ Join Kelas</button>
        </div>
        
        <!-- Form Join Kelas -->
        <div id="join-form" style="display: none; margin-bottom: 30px;">
            <div class="auth-box" style="max-width: 500px; margin: 0 0 20px 0;">
                <h3>Join Kelas Baru</h3>
                <form action="process/join_kelas.php" method="POST">
                    <div class="form-group">
                        <label>Kode Kelas</label>
                        <input type="text" name="kode_kelas" placeholder="Masukkan kode kelas" required style="text-transform: uppercase;">
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">Join</button>
                        <button type="button" onclick="hideJoinForm()" class="btn">Batal</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($kelas_list->num_rows > 0): ?>
            <div class="kelas-grid">
                <?php while ($kelas = $kelas_list->fetch_assoc()): 
                    $progress_materi = $kelas['total_materi'] > 0 ? ($kelas['materi_selesai'] / $kelas['total_materi']) * 100 : 0;
                ?>
                    <div class="kelas-card">
                        <h3><?= htmlspecialchars($kelas['nama_kelas']) ?></h3>
                        <p class="kelas-desc"><?= htmlspecialchars($kelas['deskripsi']) ?></p>
                        <div class="kelas-info">
                            <p><strong>Guru:</strong> <?= htmlspecialchars($kelas['nama_guru']) ?></p>
                        </div>
                        <div class="progress-bar" style="background: #ecf0f1; height: 10px; border-radius: 5px; margin: 15px 0; overflow: hidden;">
                            <div style="background: #27ae60; height: 100%; width: <?= $progress_materi ?>%; transition: 0.3s;"></div>
                        </div>
                        <div class="kelas-stats">
                            <span>📚 <?= $kelas['materi_selesai'] ?>/<?= $kelas['total_materi'] ?> materi</span>
                            <span>📝 <?= $kelas['kuis_selesai'] ?>/<?= $kelas['total_kuis'] ?> kuis</span>
                        </div>
                        <div class="kelas-actions">
                            <a href="detail_kelas.php?id=<?= $kelas['id'] ?>" class="btn btn-primary btn-sm">Buka Kelas</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Belum ada kelas yang diikuti. Silakan join kelas dengan kode dari guru.</p>
                <button onclick="showJoinForm()" class="btn btn-primary">Join Kelas Sekarang</button>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function showJoinForm() {
            document.getElementById('join-form').style.display = 'block';
        }
        
        function hideJoinForm() {
            document.getElementById('join-form').style.display = 'none';
        }
    </script>
</body>
</html>