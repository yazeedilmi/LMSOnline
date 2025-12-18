# 📚 LMS Online - Learning Management System

<div align="center">

![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Status](https://img.shields.io/badge/Status-Active-success?style=for-the-badge)

**Platform Pembelajaran Berbasis Web dengan Sistem Role-Based Access Control**

[Fitur](#-fitur-utama) • [Instalasi](#-instalasi--konfigurasi) • [Dokumentasi](#-arsitektur--teknologi) • [Kontribusi](#-kontribusi)

</div>

---

## 📖 Tentang Proyek

LMS Online adalah aplikasi manajemen pembelajaran berbasis web yang dibangun menggunakan **PHP Native murni** tanpa framework. Dirancang dengan arsitektur prosedural yang bersih dan terstruktur, sistem ini memisahkan logika pemrosesan (`process/`) dengan tampilan antarmuka untuk kemudahan maintenance dan skalabilitas.

### 🎯 Tujuan Proyek
- Menyediakan platform pembelajaran yang ringan dan mudah di-deploy
- Implementasi Role-Based Access Control untuk Guru dan Siswa
- Pembelajaran fundamental PHP tanpa ketergantungan framework

---

## ✨ Fitur Utama

### 👨‍🏫 **Panel Guru (Instructor)**

| Fitur | Deskripsi |
|-------|-----------|
| 📝 **Manajemen Kelas** | Membuat dan mengelola kelas dengan kode unik untuk enrollment siswa |
| 📚 **Distribusi Materi** | Upload dan organize bahan ajar (PDF/Dokumen) per kelas |
| 📊 **Sistem Kuis** | Membuat dan mengelola evaluasi pembelajaran |
| 👥 **Monitoring Siswa** | Tracking daftar siswa yang terdaftar di setiap kelas |

### 👨‍🎓 **Panel Siswa (Student)**

| Fitur | Deskripsi |
|-------|-----------|
| 🔑 **Easy Enrollment** | Bergabung ke kelas menggunakan *Class Code* dari guru |
| 📖 **Akses Materi** | Download dan pelajari materi yang tersedia |
| ✍️ **Pengerjaan Kuis** | Mengerjakan kuis dan melihat hasil evaluasi |
| 📈 **Tracking Progress** | Monitoring perkembangan pembelajaran |

---

## 🛠 Arsitektur & Teknologi

### Technology Stack

```
┌─────────────────────────────────────────┐
│           Frontend Layer                │
│  HTML5 • CSS3 • Vanilla JavaScript      │
└─────────────────────────────────────────┘
                   ↓
┌─────────────────────────────────────────┐
│         Application Layer               │
│      PHP 7.4+ (Procedural)              │
│  • Session-based Authentication         │
│  • Role-based Access Control            │
│  • File Upload Handling                 │
└─────────────────────────────────────────┘
                   ↓
┌─────────────────────────────────────────┐
│           Data Layer                    │
│        MySQL / MariaDB                  │
└─────────────────────────────────────────┘
```

### Keunggulan Arsitektur
- ⚡ **Performa Maksimal** - Tanpa overhead framework
- 🔧 **Mudah Maintenance** - Separation of Concerns
- 📦 **Lightweight** - Minimal dependencies
- 🎓 **Educational** - Memahami core PHP secara mendalam

---

## 🗄 Skema Database

### Diagram Relasi Entitas

```
┌─────────────┐         ┌──────────────┐
│    Users    │────1:N──│    Kelas     │
│             │         │              │
│ • id        │         │ • id_kelas   │
│ • username  │         │ • nama_kelas │
│ • password  │         │ • kode_kelas │
│ • role      │         │ • id_guru    │
└─────────────┘         └──────────────┘
                               │
                        ┌──────┴──────┐
                        │             │
                     1:N│          1:N│
                        │             │
                ┌───────▼────┐  ┌────▼──────┐
                │   Materi   │  │    Kuis   │
                │            │  │           │
                │ • id       │  │ • id_kuis │
                │ • judul    │  │ • judul   │
                │ • file     │  │ • soal    │
                │ • id_kelas │  │ • jawaban │
                └────────────┘  └───────────┘
                        
        ┌─────────────────┐
        │  Kelas_Siswa    │ ← Junction Table
        │                 │   (Many-to-Many)
        │ • id_kelas      │
        │ • id_siswa      │
        └─────────────────┘
```

### Tabel Utama
- **users**: Data autentikasi dan role pengguna
- **kelas**: Entitas pembelajaran utama
- **materi**: Bahan ajar yang di-upload guru
- **kuis**: Evaluasi pembelajaran
- **kelas_siswa**: Relasi enrollment siswa ke kelas

---

## ⚙️ Prasyarat Sistem

Pastikan sistem Anda memenuhi requirements berikut:

| Komponen | Versi Minimum | Rekomendasi |
|----------|---------------|-------------|
| **PHP** | 7.4+ | PHP 8.0+ |
| **MySQL** | 5.7+ | MySQL 8.0 / MariaDB 10.5+ |
| **Web Server** | Apache 2.4 / Nginx | XAMPP / Laragon (Windows) |
| **Browser** | Chrome 90+ / Firefox 88+ | Chrome/Edge terbaru |

---

## 🚀 Instalasi & Konfigurasi

### Step 1: Clone Repository

```bash
git clone https://github.com/yazeedilmi/lmsonline.git
cd lmsonline
```

### Step 2: Setup Database

1. Buka **phpMyAdmin** atau MySQL client
2. Buat database baru:
   ```sql
   CREATE DATABASE lms_matematika CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import skema database:
   ```bash
   mysql -u root -p lms_matematika < sql/lms_matematika.sql
   ```

### Step 3: Konfigurasi Koneksi

Edit file `config/db.php`:

```php
<?php
// config/db.php
$hostname = "localhost";
$username = "root";
$password = "";              // ⚠️ Sesuaikan dengan password MySQL Anda
$dbname   = "lms_matematika";

$conn = mysqli_connect($hostname, $username, $password, $dbname);

// Error handling
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
```

### Step 4: Set Permissions (Linux/Mac)

```bash
# Berikan izin tulis untuk folder uploads
chmod -R 755 uploads/

# Pastikan owner adalah web server user
sudo chown -R www-data:www-data uploads/
```

### Step 5: Jalankan Aplikasi

1. Start web server (XAMPP/Laragon)
2. Buka browser dan akses:
   ```
   http://localhost/lmsonline/
   ```
3. Login menggunakan akun default:
   - **Guru**: `guru@lms.com` / `password123`
   - **Siswa**: `siswa@lms.com` / `password123`

---

## 📂 Struktur Direktori

```
lmsonline/
│
├── 📁 assets/              # Static assets
│   ├── css/                # Stylesheet files
│   ├── js/                 # JavaScript files
│   └── img/                # Images & icons
│
├── 📁 config/              # Configuration files
│   └── db.php              # Database connection
│
├── 📁 process/             # Backend logic (Action Handlers)
│   ├── login.php           # Authentication logic
│   ├── register.php        # User registration
│   ├── join_kelas.php      # Student enrollment
│   ├── upload_materi.php   # Material upload handler
│   ├── create_kuis.php     # Quiz creation
│   └── ...
│
├── 📁 sql/                 # Database schema
│   └── lms_matematika.sql  # Initial DB structure
│
├── 📁 uploads/             # User uploaded files
│   ├── materi/             # Course materials
│   └── temp/               # Temporary files
│
├── 📄 index.php            # Landing page & entry point
├── 📄 login.php            # Login interface
├── 📄 register.php         # Registration interface
├── 📄 dashboard-guru.php   # Teacher dashboard
├── 📄 dashboard-siswa.php  # Student dashboard
├── 📄 materi.php           # Material viewer
├── 📄 kuis.php             # Quiz interface
└── 📄 README.md            # Documentation
```

### Penjelasan Struktur
- **`process/`**: Berisi semua logic backend (CRUD operations)
- **`config/`**: File konfigurasi sistem (database, constants)
- **`assets/`**: File statis yang di-serve ke client
- **`uploads/`**: Storage untuk file yang di-upload user

---

## 🔒 Keamanan

### Implementasi Keamanan Dasar

- ✅ **SQL Injection Prevention**: Menggunakan prepared statements
- ✅ **XSS Protection**: Output escaping dengan `htmlspecialchars()`
- ✅ **CSRF Protection**: Session token validation
- ✅ **File Upload Validation**: Extension & MIME type checking
- ✅ **Password Hashing**: Menggunakan `password_hash()` dan `password_verify()`

### Rekomendasi Tambahan (Production)
- Implementasi HTTPS/SSL
- Rate limiting untuk login attempts
- Content Security Policy (CSP) headers
- Regular security audits

---

## 📚 Resources & Tutorial

### Video Tutorial Relevan
Pelajari konsep upload file dan manajemen data menggunakan PHP Native:

[![Upload and Display Files in PHP](https://img.youtube.com/vi/B2O5as085Oc/0.jpg)](https://www.youtube.com/watch?v=B2O5as085Oc)

**[Upload and Display Files in PHP Tutorial](https://www.youtube.com/watch?v=B2O5as085Oc)**

---

## 🤝 Kontribusi

Kontribusi sangat terbuka untuk pengembangan proyek ini! 

### Cara Berkontribusi

1. **Fork** repository ini
2. Buat **branch** fitur baru (`git checkout -b feature/AmazingFeature`)
3. **Commit** perubahan (`git commit -m 'Add some AmazingFeature'`)
4. **Push** ke branch (`git push origin feature/AmazingFeature`)
5. Buat **Pull Request**

### Guidelines
- Untuk perubahan besar, buka **Issue** terlebih dahulu untuk diskusi
- Ikuti coding standards yang sudah ada
- Tambahkan dokumentasi untuk fitur baru
- Test perubahan Anda sebelum submit PR

---

## 📝 License

Project ini dilisensikan di bawah [MIT License](LICENSE) - bebas digunakan untuk keperluan personal maupun komersial.

---

## 👨‍💻 Author

<div align="center">

**Yazeed Ilmi**

[![GitHub](https://img.shields.io/badge/GitHub-yazeedilmi-181717?style=for-the-badge&logo=github)](https://github.com/yazeedilmi)

Dibuat dengan ❤️ dan ☕

</div>

---

## 📮 Support & Contact

Jika Anda menemukan bug atau memiliki saran, silakan:
- 🐛 Buat [Issue](https://github.com/yazeedilmi/lmsonline/issues) di GitHub
- 💬 Diskusi di [Discussions](https://github.com/yazeedilmi/lmsonline/discussions)
- ⭐ Beri **Star** jika proyek ini bermanfaat!

---

<div align="center">

**[⬆ Kembali ke atas](#-lms-online---learning-management-system)**

</div>
