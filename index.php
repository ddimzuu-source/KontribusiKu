
<?php
$pageTitle = "KontribusiKu - Kelola Proyek Kelompok";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: Arial, sans-serif;
        }

        .navbar-brand {
            font-weight: bold;
            color: #2563eb;
        }

        .hero {
            padding: 100px 0;
        }

        .hero h1 {
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            font-weight: 700;
        }

        .hero p {
            color: #64748b;
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .feature-card {
            height: 100%;
            padding: 25px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
        }

        .feature-icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        footer {
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container py-2">
            <a class="navbar-brand" href="index.php">
                KontribusiKu
            </a>

            <div class="ms-auto d-flex gap-2">
                <a href="auth/login.php"
                   class="btn btn-outline-primary">
                    Masuk
                </a>

                <a href="auth/register.php"
                   class="btn btn-primary">
                    Daftar
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center g-5">

                <div class="col-lg-7">
                    <span class="badge text-bg-primary mb-3">
                        Academic Teamwork Platform
                    </span>

                    <h1 class="mb-4">
                        Kerja kelompok lebih teratur,
                        kontribusi lebih transparan.
                    </h1>

                    <p class="mb-4">
                        KontribusiKu membantu mahasiswa mengelola
                        proyek kelompok, membagi tugas, memantau
                        progres, dan melihat riwayat kontribusi
                        setiap anggota dalam satu tempat.
                    </p>

                    <a href="auth/register.php"
                       class="btn btn-primary btn-lg me-2">
                        Mulai Sekarang
                    </a>

                    <a href="#fitur"
                       class="btn btn-outline-secondary btn-lg">
                        Lihat Fitur
                    </a>
                </div>

                <div class="col-lg-5">
                    <div class="feature-card shadow-sm">
                        <h5 class="mb-4">Ringkasan Proyek</h5>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Desain antarmuka</span>
                            <strong>75%</strong>
                        </div>
                        <div class="progress mb-4" role="progressbar"
                             aria-label="Progres desain antarmuka"
                             aria-valuenow="75"
                             aria-valuemin="0"
                             aria-valuemax="100">
                            <div class="progress-bar"
                                 style="width: 75%"></div>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Dokumentasi</span>
                            <strong>40%</strong>
                        </div>
                        <div class="progress mb-4" role="progressbar"
                             aria-label="Progres dokumentasi"
                             aria-valuenow="40"
                             aria-valuemin="0"
                             aria-valuemax="100">
                            <div class="progress-bar bg-success"
                                 style="width: 40%"></div>
                        </div>

                        <div class="alert alert-light border mb-0">
                            <strong>Transparansi kontribusi</strong>
                            <p class="mb-0 mt-1 small">
                                Contoh tampilan progres proyek.
                                Data ini belum berasal dari database.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="fitur" class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Fitur Utama</h2>
                <p class="text-secondary">
                    Dirancang untuk membantu kerja kelompok
                    menjadi lebih jelas dan terorganisasi.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">📋</div>
                        <h5>Manajemen Tugas</h5>
                        <p class="text-secondary mb-0">
                            Buat tugas, tentukan penanggung jawab,
                            dan pantau status pengerjaannya.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">👥</div>
                        <h5>Kolaborasi Tim</h5>
                        <p class="text-secondary mb-0">
                            Kelola anggota, pembagian pekerjaan,
                            dan tanggung jawab dalam proyek.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h5>Transparansi Kontribusi</h5>
                        <p class="text-secondary mb-0">
                            Telusuri riwayat aktivitas dan hasil
                            pekerjaan untuk membantu evaluasi tim.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4">
        <div class="container text-center">
            <p class="text-secondary mb-1">
                &copy; <?= date("Y") ?> KontribusiKu
            </p>
            <small class="text-secondary">
                Kerja sama yang baik dimulai dari kontribusi yang jelas.
            </small>
        </div>
    </footer>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>

</body>
</html>
