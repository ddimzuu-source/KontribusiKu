<?php
$pageTitle       = 'Buat akun - KontribusiKu';
$showAuthButtons = false;
require __DIR__ . '/../includes/header.php';
?>

    <main class="auth-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-5">

                    <div class="auth-card shadow-sm">
                        <h1 class="h3 fw-bold mb-1">Buat akun</h1>
                        <p class="text-secondary mb-4">
                            Daftar untuk mulai mengelola proyek kelompokmu.
                        </p>

                        <!-- TODO backend: proses registrasi + token CSRF -->
                        <form action="register.php" method="post"
                              class="needs-validation" novalidate>

                            <div class="mb-3">
                                <label for="name" class="form-label">Nama lengkap</label>
                                <input type="text" class="form-control"
                                       id="name" name="name"
                                       autocomplete="name" required>
                                <div class="invalid-feedback">
                                    Nama lengkap wajib diisi.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control"
                                       id="email" name="email"
                                       autocomplete="email" required>
                                <div class="invalid-feedback">
                                    Masukkan alamat email yang valid.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group has-validation">
                                    <input type="password" class="form-control"
                                           id="password" name="password"
                                           minlength="8"
                                           autocomplete="new-password"
                                           aria-describedby="passwordHelp" required>
                                    <button type="button"
                                            class="btn btn-outline-secondary"
                                            data-toggle-password="#password"
                                            aria-pressed="false">
                                        Tampilkan
                                    </button>
                                    <div class="invalid-feedback">
                                        Password minimal 8 karakter.
                                    </div>
                                </div>
                                <div id="passwordHelp" class="form-text">
                                    Minimal 8 karakter.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password_confirm" class="form-label">
                                    Ulangi password
                                </label>
                                <input type="password" class="form-control"
                                       id="password_confirm" name="password_confirm"
                                       autocomplete="new-password" required>
                                <div class="invalid-feedback">
                                    Ulangi password dengan isi yang sama.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Buat akun
                            </button>
                        </form>

                        <p class="text-center text-secondary small mt-4 mb-0">
                            Sudah punya akun?
                            <a href="<?= BASE_URL ?>/auth/login.php">Masuk</a>
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </main>

<?php require __DIR__ . '/../includes/footer.php'; ?>