<?php
/**
 * includes/header.php
 *
 * Variabel opsional yang bisa di-set dari halaman sebelum require file ini:
 *   $pageTitle       => judul tab browser
 *   $showAuthButtons => false untuk menyembunyikan tombol Masuk/Daftar (halaman auth)
 */
if (!defined('BASE_URL')) {
    // Sesuaikan jika nama folder di htdocs berbeda
    define('BASE_URL', '/KontribusiKu');
}

$pageTitle       = $pageTitle ?? 'KontribusiKu - Kelola Proyek Kelompok';
$showAuthButtons = $showAuthButtons ?? true;
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
            <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
                KontribusiKu
            </a>

            <?php if ($showAuthButtons): ?>
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
