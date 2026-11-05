<?php
$pageTitle       = 'Masuk - KontribusiKu';
$showAuthButtons = false;
require __DIR__ . '/../includes/header.php';
?>

    <main class="auth-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-5">

                    <div class="auth-card shadow-sm">
                        <h1 class="h3 fw-bold mb-1">Masuk ke KontribusiKu</h1>
                        <p class="text-secondary mb-4">
                            Lanjutkan ke proyek kelompokmu.
                        </p>

                        <!-- TODO backend: proses login + token CSRF -->
                        <form action="login.php" method="post"
                              class="needs-validation" novalidate>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control"
                                       id="email" name="email"
                                       autocomplete="email" required>
                                <div class="invalid-feedback">
                                    Masukkan alamat email yang valid.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group has-validation">
                                    <input type="password" class="form-control"
                                           id="password" name="password"
                                           autocomplete="current-password" required>
                                    <button type="button"
                                            class="btn btn-outline-secondary"
                                            data-toggle-password="#password"
                                            aria-pressed="false">
                                        Tampilkan
                                    </button>
                                    <div class="invalid-feedback">
                                        Password wajib diisi.
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Masuk
                            </button>
                        </form>

                        <p class="text-center text-secondary small mt-4 mb-0">
                            Belum punya akun?
                            <a href="<?= BASE_URL ?>/auth/register.php">Buat akun</a>
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </main>

<?php require __DIR__ . '/../includes/footer.php'; ?>