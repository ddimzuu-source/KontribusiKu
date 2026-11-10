<?php
declare(strict_types=1);

function current_user(bool $refresh = false): ?array
{
    static $user = false;
    if ($refresh) {
        $user = false;
    }
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (!empty($_SESSION['user_id'])) {
        $st = db()->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
        $st->execute([(int)$_SESSION['user_id']]);
        $row = $st->fetch();
        if ($row && (int)$row['is_active'] === 1) {
            $user = $row;
        } else {
            unset($_SESSION['user_id']);
        }
    }
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    current_user(true);
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        respond(false, 'Silakan login terlebih dahulu.', APP_URL . '/auth/login.php', [], 401);
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        respond(false, 'Akses ditolak.', APP_URL . '/', [], 403);
    }
    return $u;
}

/** Pembatasan percobaan login sederhana (per sesi). */
function login_throttled(): bool
{
    $a = $_SESSION['login_fail'] ?? ['n' => 0, 't' => 0];
    if (time() - $a['t'] >= 300) {
        unset($_SESSION['login_fail']);
        return false;
    }
    return $a['n'] >= 5;
}

function login_failed(): void
{
    $a = $_SESSION['login_fail'] ?? ['n' => 0, 't' => 0];
    $_SESSION['login_fail'] = ['n' => $a['n'] + 1, 't' => time()];
}
