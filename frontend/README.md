# KontribusiKu – Frontend (demo)

Frontend statis untuk UTS Pemrograman Web (HTML5, CSS3, JavaScript).
Dibuat tanpa framework agar mudah di-deploy ke Netlify, Vercel, atau GitHub Pages.

## Struktur

```
frontend/
├── index.html        Dashboard
├── proyek.html       Daftar proyek + detail proyek + buat proyek
├── tugas.html        Daftar tugas, filter, kirim hasil, periksa (ketua)
├── laporan.html      Laporan kontribusi per anggota (+ unduh CSV)
├── login.html        Masuk (demo)
├── register.html     Daftar (demo)
└── assets/
    ├── css/style.css
    └── js/
        ├── data.js   Data contoh (bentuknya mengikuti database/schema.sql)
        └── app.js    Logika halaman, modal, dan aturan hak akses
```

## Menjalankan lokal

Buka `login.html` di browser, atau jalankan server statis, misalnya:

```bash
cd frontend
python -m http.server 8000
```

Lalu buka `http://localhost:8000`. Untuk masuk, isi email dan password apa saja.

## Deploy (live demo)

**Netlify Drop (paling cepat):** buka https://app.netlify.com/drop lalu seret folder `frontend/` ke halaman itu. Tautan langsung keluar.

**Vercel:** impor repo, lalu atur *Root Directory* ke `frontend`. Tidak ada build command.

**GitHub Pages:** hanya bisa melayani root repo atau folder `/docs`. Salin isi `frontend/` ke `docs/` lalu pilih *Settings → Pages → Deploy from branch → main /docs*.

Semua tautan di halaman bersifat relatif, jadi tidak perlu diubah saat dipindah.

## Fitur yang didemokan

- Login dan register (validasi di sisi klien).
- Dashboard: statistik, proyek saya, aktivitas terbaru, tugas per status, dan ringkasan kontribusi.
- Proyek: daftar, detail, anggota dan peran, serta pembuatan proyek baru (pembuat jadi ketua).
- Tugas: filter (Semua, Individu, Kelompok, Deadline), pencarian, filter status, tugas baru (khusus ketua), kirim hasil berupa tautan atau berkas, serta riwayat pengumpulan.
- Pemeriksaan hasil oleh ketua: setujui, atau minta revisi dengan catatan wajib.
- Laporan kontribusi per anggota dengan disclaimer, serta unduh CSV.

Aturan hak akses meniru backend PHP: hanya ketua yang bisa memeriksa, ketua tidak bisa menyetujui hasil kerjanya sendiri, dan hanya penanggung jawab yang bisa mengirim hasil.

## Catatan

- Data disimpan di `localStorage` browser dan tidak dikirim ke server. Tombol **Reset data contoh** di footer mengembalikan data awal.
- Penilaian dengan angka tidak termasuk versi ini.
- Untuk menghubungkan ke backend PHP nanti, ganti fungsi-fungsi di `app.js` yang membaca `state` dengan panggilan `fetch()` ke endpoint `auth/api.php`, `submissions/api.php`, dan endpoint proyek/tugas yang akan dibuat.

Kelompok 7 · Universitas Paramadina
