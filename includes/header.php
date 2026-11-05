<?php
/**
 * includes/header.php
 *
 * Variabel opsional yang bisa di-set dari halaman sebelum require file ini:
 *   $pageTitle       => judul tab browser
 *   $showAuthButtons => false untuk menyembunyikan tombol Masuk/Daftar (halaman auth)
 *   $isLoggedIn      => true untuk menampilkan navbar setelah login
 *   $userName        => nama pengguna di navbar (sementara dummy)
 *   $activePage      => 'dashboard' dst. untuk menandai menu aktif
 */
if (!defined('BASE_URL')) {
    // Sesuaikan jika nama folder di htdocs/www berbeda
    define('BASE_URL', '/KontribusiKu');
}

$pageTitle       = $pageTitle ?? 'KontribusiKu - Kelola Proyek Kelompok';
$showAuthButtons = $showAuthButtons ?? true;
$isLoggedIn      = $isLoggedIn ?? false;
$userName        = $userName ?? 'Pengguna';
$activePage      = $activePage ?? '';
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

    <!-- CSS proyek -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container py-2">
            <a class="navbar-brand"
               href="<?= BASE_URL . ($isLoggedIn ? '/dashboard.php' : '/index.php') ?>">
                KontribusiKu
            </a>

            <?php if ($isLoggedIn): ?>
                <!-- Menu setelah login. Tambah menu Proyek/Tugas di sini saat halamannya sudah ada. -->
                <ul class="navbar-nav flex-row me-auto ms-3">
                    <li class="nav-item">
                        <a class="nav-link <?= $activePage === 'dashboard' ? 'active fw-semibold' : '' ?>"
                           href="<?= BASE_URL ?>/dashboard.php">
                            Dashboard
                        </a>
                    </li>
                </ul>

                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle"
                            type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <?= htmlspecialchars($userName) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <!-- TODO backend: buat auth/logout.php (hapus sesi) -->
                        <li>
                            <a class="dropdown-item"
                               href="<?= BASE_URL ?>/auth/logout.php">
                                Keluar
                            </a>
                        </li>
                    </ul>
                </div>

            <?php elseif ($showAuthButtons): ?>
                <div class="ms-auto d-flex gap-2">
                    <a href="<?= BASE_URL ?>/auth/login.php"
                       class="btn btn-outline-primary">
                        Masuk
                    </a>

                    <a href="<?= BASE_URL ?>/auth/register.php"
                       class="btn btn-primary">
                        Daftar
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </nav>