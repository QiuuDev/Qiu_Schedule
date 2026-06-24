<div align="center">
  
  # 📅 Qiu's Schedule
  
  **Sistem Penjadwalan Cerdas, Cepat, dan Terstruktur.**
  
  [![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](#)
  [![MySQL](https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white)](#)
  [![Status](https://img.shields.io/badge/Status-Active-success?style=for-the-badge)](#)
  
  [Jelajahi Fitur](#-fitur-utama) • [Cara Instalasi](#%EF%B8%8F-cara-menjalankan-proyek) • [Struktur Direktori](#-struktur-direktori)
  
</div>

---

## 💡 Tentang Proyek
**Qiu's Schedule** adalah aplikasi manajemen jadwal berbasis web yang dikembangkan menggunakan PHP Native. Proyek ini dirancang untuk memberikan antarmuka yang bersih, fungsionalitas yang cepat tanpa framework yang berat, serta sistem autentikasi dan analitik jadwal yang komprehensif.

> **Visual Tour:**
> 
> | Halaman Login | Dasbor Utama | Halaman Analitik |
> | :---: | :---: | :---: |
> | `<img src="assets/images/login.jpeg" width="250">` | `<img src="assets/images/dashboard.jpeg" width="250">` | `<img src="assets/images/analitik.jpeg" width="250">` |

---

## ✨ Fitur Utama
* 🔐 **Sistem Autentikasi:** Login dan pendaftaran pengguna yang aman (dilengkapi dengan *helper* khusus).
* 👤 **Manajemen Profil:** Pengguna dapat menyesuaikan detail profil dan mengelola avatar/file unggahan.
* 📊 **Dasbor Analitik:** Pantau statistik penjadwalan dan aktivitas pengguna secara langsung.
* 🔌 **Notifikasi:** Notifikasi acara yang segera datang dan invitasi acara dari pengguna lain.

---

## ⚙️ Cara Menjalankan Proyek

Ikuti langkah-langkah di bawah ini untuk menjalankan kode ini di komputer lokal Anda.

### Prasyarat
Pastikan Anda sudah menginstal aplikasi web server lokal seperti **XAMPP**, **Laragon**, atau **MAMP** yang mendukung PHP dan MySQL.

### Langkah Instalasi

1. **Clone Repositori (atau Ekstrak File)**
   Letakkan seluruh folder proyek ini ke dalam direktori server lokal Anda:
   * Pengguna XAMPP: `C:\xampp\htdocs\qiu-schedule`
   * Pengguna Laragon: `C:\laragon\www\qiu-schedule`

2. **Setup Database**
   * Buka phpMyAdmin (biasanya di `http://localhost/phpmyadmin`).
   * Buat database baru dengan nama `db_qiu_schedule`.
   * Pilih opsi **Import**, kemudian unggah file `db_qiu_schedule.sql` yang terdapat di folder utama proyek.

3. **Konfigurasi Koneksi**
   * Buka file `config/database.php`.
   * Pastikan kredensial database sudah sesuai dengan server lokal Anda (secara default `root` dan tanpa password).

4. **Jalankan Aplikasi**
   Buka browser Anda dan akses:
   ```text
   http://localhost/qiu-schedule

