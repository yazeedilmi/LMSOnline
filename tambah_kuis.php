<?php
require_once 'config/db.php';

if (!isLoggedIn() || !isGuru()) {
    redirect('index.php');
}

$kelas_id = $_GET['kelas_id'] ?? '';

if (empty($kelas_id)) {
    redirect('dashboard-guru.php');
}

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
    <title>Tambah Kuis - LMS</title>
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
        <div style="max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px;">
            <h2>Tambah Kuis Baru</h2>
            <p style="color: #7f8c8d; margin-bottom: 20px;">Kelas: <strong><?= htmlspecialchars($kelas['nama_kelas']) ?></strong></p>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <form action="process/tambah_kuis.php" method="POST" id="form-kuis">
                <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
                
                <div class="form-group">
                    <label>Judul Kuis *</label>
                    <input type="text" name="judul" placeholder="Contoh: Kuis Aljabar Dasar" required>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" placeholder="Deskripsi singkat tentang kuis ini" rows="3"></textarea>
                </div>
                
                <hr style="margin: 30px 0;">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Daftar Soal</h3>
                    <button type="button" onclick="tambahSoal()" class="btn btn-primary btn-sm">+ Tambah Soal</button>
                </div>
                
                <div id="soal-container"></div>
                
                <div style="margin-top: 30px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">Simpan Kuis</button>
                    <a href="detail_kelas.php?id=<?= $kelas_id ?>" class="btn">Batal</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let soalCount = 0;
        
        function tambahSoal() {
            soalCount++;
            const container = document.getElementById('soal-container');
            const soalHtml = `
                <div class="soal-item" id="soal-${soalCount}" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; position: relative;">
                    <button type="button" onclick="hapusSoal(${soalCount})" style="position: absolute; top: 10px; right: 10px; background: #e74c3c; color: white; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer;">Hapus</button>
                    
                    <h4 style="margin-bottom: 15px;">Soal ${soalCount}</h4>
                    
                    <div class="form-group">
                        <label>Pertanyaan *</label>
                        <textarea name="soal[${soalCount}][pertanyaan]" placeholder="Tulis pertanyaan di sini..." rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Pilihan A *</label>
                        <input type="text" name="soal[${soalCount}][pilihan_a]" placeholder="Pilihan A" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Pilihan B *</label>
                        <input type="text" name="soal[${soalCount}][pilihan_b]" placeholder="Pilihan B" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Pilihan C *</label>
                        <input type="text" name="soal[${soalCount}][pilihan_c]" placeholder="Pilihan C" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Pilihan D *</label>
                        <input type="text" name="soal[${soalCount}][pilihan_d]" placeholder="Pilihan D" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Jawaban Benar *</label>
                        <select name="soal[${soalCount}][jawaban_benar]" required>
                            <option value="">Pilih Jawaban Benar</option>
                            <option value="a">A</option>
                            <option value="b">B</option>
                            <option value="c">C</option>
                            <option value="d">D</option>
                        </select>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', soalHtml);
        }
        
        function hapusSoal(id) {
            const soal = document.getElementById('soal-' + id);
            if (soal) {
                soal.remove();
            }
        }
        
        // Tambah 1 soal default
        window.addEventListener('DOMContentLoaded', function() {
            tambahSoal();
        });
        
        // Validasi sebelum submit
        document.getElementById('form-kuis').addEventListener('submit', function(e) {
            const soalItems = document.querySelectorAll('.soal-item');
            if (soalItems.length === 0) {
                e.preventDefault();
                alert('Minimal harus ada 1 soal!');
            }
        });
    </script>
</body>
</html>