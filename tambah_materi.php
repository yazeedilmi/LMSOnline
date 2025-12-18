<?php
require_once 'config/db.php';

if (!isLoggedIn() || !isGuru()) {
    redirect('index.php');
}

$kelas_id = $_GET['kelas_id'] ?? '';

if (empty($kelas_id)) {
    redirect('dashboard-guru.php');
}

// Validasi kelas milik guru ini
$stmt = $conn->prepare("SELECT nama_kelas FROM kelas WHERE id = ? AND guru_id = ?");
$stmt->bind_param("ii", $kelas_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Kelas tidak ditemukan';
    redirect('dashboard-guru.php');
}

$kelas = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Materi - LMS</title>
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
            <h2>Tambah Materi</h2>
            <p style="color: #7f8c8d; margin-bottom: 20px;">Kelas: <strong><?= htmlspecialchars($kelas['nama_kelas']) ?></strong></p>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('link')">Materi Link</button>
                <button class="tab-btn" onclick="showTab('pdf')">Upload PDF</button>
            </div>
            
            <!-- Form Materi Link -->
            <form id="form-link" action="process/tambah_materi.php" method="POST" style="margin-top: 20px;">
                <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
                <input type="hidden" name="tipe" value="link">
                
                <div class="form-group">
                    <label>Judul Materi *</label>
                    <input type="text" name="judul" placeholder="Contoh: Pengenalan Aljabar" required>
                </div>
                
                <div class="form-group">
                    <label>Link URL *</label>
                    <input type="url" name="link" placeholder="https://example.com/materi" required>
                    <small style="color: #7f8c8d;">Link ke video YouTube, artikel, atau sumber belajar lainnya</small>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Simpan Materi</button>
                    <a href="detail_kelas.php?id=<?= $kelas_id ?>" class="btn">Batal</a>
                </div>
            </form>
            
            <!-- Form Upload PDF -->
            <form id="form-pdf" action="process/upload_materi.php" method="POST" enctype="multipart/form-data" style="display: none; margin-top: 20px;">
                <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
                
                <div class="form-group">
                    <label>Judul Materi *</label>
                    <input type="text" name="judul" placeholder="Contoh: Modul Aljabar" required>
                </div>
                
                <div class="form-group">
                    <label>File PDF *</label>
                    <input type="file" name="pdf" accept=".pdf" required>
                    <small style="color: #7f8c8d;">Maksimal 10MB</small>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Upload Materi</button>
                    <a href="detail_kelas.php?id=<?= $kelas_id ?>" class="btn">Batal</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showTab(type) {
            const formLink = document.getElementById('form-link');
            const formPdf = document.getElementById('form-pdf');
            const tabs = document.querySelectorAll('.tab-btn');
            
            tabs.forEach(btn => btn.classList.remove('active'));
            
            if (type === 'link') {
                formLink.style.display = 'block';
                formPdf.style.display = 'none';
                tabs[0].classList.add('active');
            } else {
                formLink.style.display = 'none';
                formPdf.style.display = 'block';
                tabs[1].classList.add('active');
            }
        }
    </script>
</body>
</html>