-- Database: lms_sederhana

CREATE DATABASE IF NOT EXISTS lms_sederhana;
USE lms_sederhana;

-- Tabel users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('guru', 'siswa') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel kelas
CREATE TABLE kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    kode_kelas VARCHAR(10) UNIQUE NOT NULL,
    guru_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel anggota_kelas (siswa yang join kelas)
CREATE TABLE anggota_kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id INT NOT NULL,
    siswa_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_member (kelas_id, siswa_id)
);

-- Tabel materi
CREATE TABLE materi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    tipe ENUM('pdf', 'link') NOT NULL,
    konten TEXT NOT NULL, -- path file PDF atau URL link
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE
);

-- Tabel progress_materi
CREATE TABLE progress_materi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materi_id INT NOT NULL,
    siswa_id INT NOT NULL,
    selesai BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (materi_id) REFERENCES materi(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (materi_id, siswa_id)
);

-- Tabel kuis
CREATE TABLE kuis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE
);

-- Tabel soal_kuis (pilihan ganda)
CREATE TABLE soal_kuis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kuis_id INT NOT NULL,
    pertanyaan TEXT NOT NULL,
    pilihan_a VARCHAR(255) NOT NULL,
    pilihan_b VARCHAR(255) NOT NULL,
    pilihan_c VARCHAR(255) NOT NULL,
    pilihan_d VARCHAR(255) NOT NULL,
    jawaban_benar ENUM('a', 'b', 'c', 'd') NOT NULL,
    urutan INT DEFAULT 0,
    FOREIGN KEY (kuis_id) REFERENCES kuis(id) ON DELETE CASCADE
);

-- Tabel hasil_kuis
CREATE TABLE hasil_kuis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kuis_id INT NOT NULL,
    siswa_id INT NOT NULL,
    nilai DECIMAL(5,2) NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kuis_id) REFERENCES kuis(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel jawaban_siswa
CREATE TABLE jawaban_siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hasil_kuis_id INT NOT NULL,
    soal_id INT NOT NULL,
    jawaban ENUM('a', 'b', 'c', 'd') NOT NULL,
    benar BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (hasil_kuis_id) REFERENCES hasil_kuis(id) ON DELETE CASCADE,
    FOREIGN KEY (soal_id) REFERENCES soal_kuis(id) ON DELETE CASCADE
);

