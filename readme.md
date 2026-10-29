# KontribusiKu

### Academic Teamwork & Contribution Management System

KontribusiKu adalah aplikasi berbasis web untuk membantu mahasiswa mengelola proyek akademik secara kolaboratif, mulai dari pembentukan kelompok, pembagian tugas, pemantauan progres, pengumpulan hasil pekerjaan, hingga pencatatan kontribusi setiap anggota.

Aplikasi ini dikembangkan sebagai proyek pemrograman web menggunakan PHP Native dan MySQL.

##  Latar Belakang

Dalam pengerjaan tugas kelompok, mahasiswa sering menghadapi kendala seperti pembagian tugas yang kurang jelas, progres pekerjaan yang sulit dipantau, keterlambatan penyelesaian tugas, serta kurangnya dokumentasi mengenai kontribusi setiap anggota.

Informasi pekerjaan juga sering tersebar di berbagai aplikasi komunikasi sehingga sulit ditelusuri kembali.

KontribusiKu dikembangkan untuk menyediakan satu platform terpusat yang membantu kelompok mahasiswa mengelola pekerjaan dan mendokumentasikan aktivitas proyek secara transparan.

##  Tujuan

1. Mempermudah pengelolaan proyek akademik mahasiswa.
2. Membantu pembagian tugas dan tanggung jawab secara terstruktur.
3. Memantau progres penyelesaian tugas dan proyek.
4. Mendokumentasikan aktivitas dan hasil pekerjaan setiap anggota.
5. Menyediakan laporan kontribusi sebagai bahan evaluasi kelompok.
6. Meningkatkan transparansi dan kolaborasi dalam pengerjaan tugas akademik.

##  Fitur Utama

### 1. Autentikasi dan Manajemen Pengguna
- Registrasi dan login pengguna.
- Logout dan pengelolaan sesi.
- Pengelolaan profil pengguna.
- Pengaturan hak akses berdasarkan role.
- Pengelolaan akun oleh administrator.

### 2. Manajemen Proyek
- Membuat, melihat, mengubah, dan mengarsipkan proyek.
- Mengatur deskripsi dan tenggat proyek.
- Menambahkan dan mengelola anggota kelompok.
- Menentukan ketua proyek.
- Melihat detail dan progres proyek.

### 3. Manajemen Tugas
- Membuat, melihat, mengubah, dan menghapus tugas sesuai hak akses.
- Menentukan penanggung jawab tugas.
- Mengatur prioritas dan tenggat.
- Memperbarui status pekerjaan.
- Memantau tugas yang belum selesai dan terlambat.

### 4. Pengumpulan dan Verifikasi Hasil
- Mengirim hasil pekerjaan berupa berkas atau tautan.
- Menyimpan versi pengumpulan.
- Memberikan persetujuan hasil pekerjaan.
- Meminta revisi disertai catatan.
- Melihat riwayat pemeriksaan.

### 5. Transparansi Kontribusi
- Mencatat riwayat aktivitas pengguna.
- Menampilkan tugas yang menjadi tanggung jawab setiap anggota.
- Menampilkan progres penyelesaian tugas.
- Menyediakan ringkasan kontribusi berdasarkan data aktivitas.
- Menampilkan riwayat pengumpulan dan revisi.

### 6. Dashboard dan Laporan
- Statistik jumlah proyek dan tugas.
- Ringkasan progres proyek.
- Rekap tugas selesai, berjalan, dan terlambat.
- Ringkasan aktivitas anggota.
- Laporan kontribusi untuk evaluasi kelompok.

##  Hak Akses Pengguna

| Role | Hak Akses |
|---|---|
| Administrator | Mengelola akun, memantau sistem, dan menangani moderasi. |
| Ketua Proyek | Mengelola proyek, anggota, tugas, penugasan, dan verifikasi hasil. |
| Anggota | Melihat proyek yang diikuti, mengerjakan tugas, mengirim hasil, dan memantau progres. |

Hak akses diperiksa di sisi server. Administrator tidak otomatis memiliki kewenangan akademik untuk menyetujui hasil pekerjaan setiap kelompok.

##  Teknologi

- **Backend:** PHP Native
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, Bootstrap
- **Interaktivitas:** JavaScript
- **Server lokal:** XAMPP
- **Database management:** phpMyAdmin
- **Version control:** Git dan GitHub

##  Struktur Proyek

```text
KontribusiKu/
├── README.md
├── index.php
├── .gitignore
├── config/
├── database/
├── includes/
├── auth/
├── projects/
├── tasks/
├── submissions/
├── reports/
├── admin/
├── assets/
└── storage/
    └── submissions/
```

##  Instalasi dan Menjalankan Aplikasi

### Prasyarat
- XAMPP dengan PHP dan MySQL/MariaDB.
- Web browser.
- Visual Studio Code.

### Langkah Instalasi

1. Clone repositori atau letakkan folder proyek di direktori `htdocs` XAMPP.

   ```bash
   git clone <URL_REPOSITORI> KontribusiKu
   ```

2. Jalankan Apache dan MySQL melalui XAMPP Control Panel.

3. Buka phpMyAdmin melalui `http://localhost/phpmyadmin`.

4. Buat database bernama `kontribusiku`, lalu impor file `database/schema.sql`.

5. Jika tersedia, impor `database/seed.sql` untuk memasukkan data awal pengujian.

6. Sesuaikan pengaturan koneksi database di `config/database.php`.

7. Buka aplikasi melalui browser:

   `http://localhost/KontribusiKu/`

8. Daftarkan akun pengguna dan uji fitur sesuai hak aksesnya.

> Catatan: Perintah Git di atas menggunakan placeholder URL repositori. Ganti dengan URL repositori kelompok yang sebenarnya. File konfigurasi dan SQL harus tersedia sebelum instalasi dapat diselesaikan.

##  Database

Database utama yang direncanakan meliputi:

- `users` — akun dan role pengguna.
- `projects` — informasi proyek akademik.
- `project_members` — keanggotaan proyek.
- `tasks` — data tugas.
- `task_assignees` — penanggung jawab tugas.
- `submissions` — pengumpulan hasil pekerjaan.
- `task_reviews` — pemeriksaan dan revisi hasil.
- `activity_logs` — riwayat aktivitas.

Relasi dan aturan integritas data didefinisikan dalam `database/schema.sql`.

##  Keamanan

- Password disimpan menggunakan `password_hash()` dan diperiksa menggunakan `password_verify()`.
- Query database menggunakan prepared statements.
- Hak akses dan keanggotaan proyek divalidasi di server.
- Form yang mengubah data dilindungi dari CSRF.
- Input dan output divalidasi atau di-escape sesuai konteks.
- Berkas yang diunggah dibatasi berdasarkan tipe dan ukuran.
- Aktivitas penting dicatat untuk mendukung audit.
- Kredensial database tidak dimasukkan ke repositori publik.

##  Pengujian

Pengujian meliputi:

- Registrasi, login, logout, dan validasi akun.
- Operasi CRUD proyek dan tugas.
- Penambahan serta pengelolaan anggota.
- Pengumpulan hasil dan alur revisi.
- Pembatasan akses antarproyek.
- Validasi status dan tenggat.
- Pencatatan riwayat aktivitas.
- Pengujian keamanan input dan berkas.

## Ruang Lingkup Pengembangan

Versi awal berfokus pada pengelolaan proyek, pembagian tugas, pengumpulan hasil, verifikasi, dan laporan kontribusi.

Fitur seperti chat real-time, notifikasi eksternal, integrasi GitHub, dan analisis kontribusi lanjutan dapat dipertimbangkan pada pengembangan berikutnya.

##  Prinsip Transparansi

Laporan kontribusi KontribusiKu merupakan alat bantu evaluasi berdasarkan aktivitas yang tercatat, bukan ukuran mutlak kemampuan, usaha, atau kualitas kerja seseorang.

Penilaian tetap perlu mempertimbangkan kompleksitas tugas, kualitas hasil, kolaborasi, dan konteks pengerjaan.

##  Contributor

**Nama Kelompok:** Kelompok 7

**Anggota:**
1. Dimas Darmawan
2. Muhammad Falih Fakhri
3. Ahsani Taqwim Sabiq
4. Alfianus Fierik Feto
5. Reva Pramnesti Inderasari
6. Azalea Zora Ammara

