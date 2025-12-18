<?php
require_once 'config/db.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$materi_id = $_GET['id'] ?? '';

if (empty($materi_id)) {
    redirect(isGuru() ? 'dashboard-guru.php' : 'dashboard-siswa.php');
}

// Get materi dan validasi akses
if (isGuru()) {
    $stmt = $conn->prepare("
        SELECT m.*, k.nama_kelas 
        FROM materi m
        JOIN kelas k ON m.kelas_id = k.id
        WHERE m.id = ? AND k.guru_id = ?
    ");
    $stmt->bind_param("ii", $materi_id, $_SESSION['user_id']);
} else {
    $stmt = $conn->prepare("
        SELECT m.*, k.nama_kelas 
        FROM materi m
        JOIN kelas k ON m.kelas_id = k.id
        JOIN anggota_kelas ak ON k.id = ak.kelas_id
        WHERE m.id = ? AND ak.siswa_id = ?
    ");
    $stmt->bind_param("ii", $materi_id, $_SESSION['user_id']);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Materi tidak ditemukan';
    redirect(isGuru() ? 'dashboard-guru.php' : 'dashboard-siswa.php');
}

$materi = $result->fetch_assoc();

// Jika siswa, cek progress
$is_completed = false;
if (isSiswa()) {
    $stmt = $conn->prepare("SELECT selesai FROM progress_materi WHERE materi_id = ? AND siswa_id = ?");
    $stmt->bind_param("ii", $materi_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $progress = $result->fetch_assoc();
        $is_completed = $progress['selesai'] == 1;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($materi['judul']) ?> - LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h2>LMS</h2>
            <div class="nav-right">
                <span><?= htmlspecialchars($_SESSION['nama']) ?></span>
                <a href="detail_kelas.php?id=<?= $materi['kelas_id'] ?>" class="btn btn-sm">Kembali ke Kelas</a>
                <a href="process/logout.php" class="btn btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div style="max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px;">
            <div style="margin-bottom: 20px;">
                <p style="color: #7f8c8d; margin-bottom: 10px;">Kelas: <?= htmlspecialchars($materi['nama_kelas']) ?></p>
                <h1><?= htmlspecialchars($materi['judul']) ?></h1>
                <span class="materi-badge badge-<?= $materi['tipe'] ?>"><?= strtoupper($materi['tipe']) ?></span>
                <?php if (isSiswa() && $is_completed): ?>
                    <span class="materi-badge badge-completed">✓ Selesai</span>
                <?php endif; ?>
            </div>
            
            <hr style="margin: 20px 0;">
            
            <?php if ($materi['tipe'] === 'pdf'): ?>
                <div style="text-align: center; margin: 30px 0;">
                    <embed src="<?= htmlspecialchars($materi['konten']) ?>" type="application/pdf" width="100%" height="600px" style="border: 1px solid #ddd; border-radius: 5px;">
                    <p style="margin-top: 15px;">
                        <a href="<?= htmlspecialchars($materi['konten']) ?>" target="_blank" class="btn btn-primary">Buka PDF di Tab Baru</a>
                    </p>
                </div>
            <?php else: ?>
                <div style="margin: 30px 0;">
                    <h3>Link Materi:</h3>
                    <div style="background: #ecf0f1; padding: 20px; border-radius: 5px; margin-top: 10px;">
                        <a href="<?= htmlspecialchars($materi['konten']) ?>" target="_blank" style="color: #3498db; font-size: 16px; word-break: break-all;">
                            <?= htmlspecialchars($materi['konten']) ?>
                        </a>
                    </div>
                    <p style="margin-top: 15px;">
                        <a href="<?= htmlspecialchars($materi['konten']) ?>" target="_blank" class="btn btn-primary">Buka Link</a>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (isSiswa()): ?>
                <hr style="margin: 30px 0;">
                <div style="text-align: center;">
                    <button onclick="toggleComplete()" id="btn-complete" class="btn <?= $is_completed ? 'btn-success' : 'btn-primary' ?>" style="font-size: 16px; padding: 15px 30px;">
                        <?= $is_completed ? '✓ Tandai Belum Selesai' : 'Tandai Selesai' ?>
                    </button>
                    <p style="color: #7f8c8d; margin-top: 10px; font-size: 14px;">Tandai materi sebagai selesai setelah Anda memahaminya</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isSiswa()): ?>
    <script>
        async function toggleComplete() {
            const btn = document.getElementById('btn-complete');
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';
            
            try {
                const formData = new FormData();
                formData.append('materi_id', '<?= $materi_id ?>');
                
                const response = await fetch('process/mark_complete.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (data.selesai == 1) {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-success');
                        btn.textContent = '✓ Tandai Belum Selesai';
                    } else {
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-primary');
                        btn.textContent = 'Tandai Selesai';
                    }
                } else {
                    alert('Gagal menyimpan progress: ' + data.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            }
            
            btn.disabled = false;
        }
    </script>
    <?php endif; ?>
</body>
</html>