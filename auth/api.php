<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$action = (string)($_GET['action'] ?? $_POST['action'] ?? '');

// GET: csrf (ambil token untuk klien JSON), me (profil sesi)
if ($action === 'csrf') {
    json_out(['csrf_token' => csrf_token()]);
}
if ($action === 'me') {
    json_out(['user' => require_login()]);
}

require_post();

switch ($action) {
    case 'register':
        $name  = post_str('name');
        $email = strtolower(post_str('email'));
        $pass  = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        if ($name === '' || mb_strlen($name) > 100) {
            respond(false, 'Nama wajib diisi (maks. 100 karakter).');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            respond(false, 'Email tidak valid.');
        }
        if (strlen($pass) < 8 || strlen($pass) > 72) {
            respond(false, 'Password harus 8–72 karakter.');
        }
        if (isset($_POST['password_confirm']) && $_POST['password_confirm'] !== $pass) {
            respond(false, 'Konfirmasi password tidak cocok.');
        }
        try {
            db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?,?,?)')
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                respond(false, 'Email sudah terdaftar.', '', [], 409);
            }
            throw $ex;
        }
        $id = (int)db()->lastInsertId();
        login_user($id);
        log_activity(null, 'register', 'user', $id);
        respond(true, 'Registrasi berhasil.', APP_URL . '/', ['user_id' => $id], 201);

    case 'login':
        if (login_throttled()) {
            respond(false, 'Terlalu banyak percobaan. Coba lagi dalam 5 menit.', '', [], 429);
        }
        $email = strtolower(post_str('email'));
        $pass  = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $st = db()->prepare('SELECT id, password_hash, is_active FROM users WHERE email = ?');
        $st->execute([$email]);
        $row = $st->fetch();
        if (!$row || !password_verify($pass, $row['password_hash'])) {
            login_failed();
            respond(false, 'Email atau password salah.', '', [], 401);
        }
        if ((int)$row['is_active'] !== 1) {
            respond(false, 'Akun dinonaktifkan. Hubungi administrator.', '', [], 403);
        }
        if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($pass, PASSWORD_DEFAULT), $row['id']]);
        }
        unset($_SESSION['login_fail']);
        login_user((int)$row['id']);
        log_activity(null, 'login', 'user', (int)$row['id']);
        respond(true, 'Login berhasil.', APP_URL . '/', ['user' => current_user()]);

    case 'logout':
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        session_start();   // sesi baru untuk flash message
        respond(true, 'Anda telah logout.', APP_URL . '/auth/login.php');

    case 'update_profile':
        $u    = require_login();
        $name = post_str('name');
        if ($name === '' || mb_strlen($name) > 100) {
            respond(false, 'Nama wajib diisi (maks. 100 karakter).');
        }
        $newPass = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        if ($newPass !== '') {
            $old = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
            $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
            $st->execute([$u['id']]);
            if (!password_verify($old, (string)$st->fetchColumn())) {
                respond(false, 'Password saat ini salah.', '', [], 403);
            }
            if (strlen($newPass) < 8 || strlen($newPass) > 72) {
                respond(false, 'Password baru harus 8–72 karakter.');
            }
            db()->prepare('UPDATE users SET name = ?, password_hash = ? WHERE id = ?')
                ->execute([$name, password_hash($newPass, PASSWORD_DEFAULT), $u['id']]);
        } else {
            db()->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $u['id']]);
        }
        log_activity(null, 'update_profile', 'user', (int)$u['id']);
        respond(true, 'Profil diperbarui.');

    default:
        respond(false, 'Aksi tidak dikenal.', '', [], 404);
}
