<?php
require_once 'config/db.php';

if (!isLoggedIn() || !isGuru()) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kelas - LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h2>LMS</h2>
            <div class="nav-right">
                <span><?= htmlspecialchars($_SESSION['nama']) ?></span>
                <a href="dashboard-guru.php" class="btn btn-sm">Dashboard</a>
                <a href="process/logout.php" class="btn btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="auth-box" style="max-width: 600px;">
            <h2>Tambah Kelas Baru</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <form action="process/tambah_kelas.php" method="POST">
                <div class="form-group">
                    <label>Nama Kelas *</label>
                    <input type="text" name="nama_kelas" placeholder="Contoh: Matematika Kelas 10" required>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" placeholder="Deskripsi singkat tentang kelas ini"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Buat Kelas</button>
                    <a href="dashboard-guru.php" class="btn">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>