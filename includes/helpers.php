<?php
declare(strict_types=1);

function db(): PDO
{
    global $pdo;
    return $pdo;
}

function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---------- Input ---------- */
function post_str(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_scalar($v) ? trim((string)$v) : '';
}

function post_int(string $key): int
{
    $v = filter_var($_POST[$key] ?? 0, FILTER_VALIDATE_INT);
    return $v === false ? 0 : (int)$v;
}

function get_int(string $key): int
{
    $v = filter_var($_GET[$key] ?? 0, FILTER_VALIDATE_INT);
    return $v === false ? 0 : (int)$v;
}

function post_int_list(string $key): array
{
    $raw = $_POST[$key] ?? [];
    if (!is_array($raw)) {
        $raw = [$raw];
    }
    $ids = [];
    foreach ($raw as $v) {
        $i = filter_var($v, FILTER_VALIDATE_INT);
        if ($i !== false && $i > 0) {
            $ids[$i] = $i;
        }
    }
    return array_values($ids);
}

function valid_date(string $d): bool
{
    $x = DateTime::createFromFormat('Y-m-d', $d);
    return $x !== false && $x->format('Y-m-d') === $d;
}

function page_params(): array
{
    $limit = min(100, max(1, get_int('limit') ?: 20));
    $page  = max(1, get_int('page') ?: 1);
    return [$limit, ($page - 1) * $limit];
}

/* ---------- Response ---------- */
function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
}

function back_url(): string
{
    $ref     = $_SERVER['HTTP_REFERER'] ?? '';
    $refHost = parse_url($ref, PHP_URL_HOST);
    $myHost  = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
    return ($refHost !== null && $refHost === $myHost) ? $ref : APP_URL . '/';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Untuk aksi (POST): JSON jika klien meminta JSON, selain itu flash + redirect. */
function respond(bool $ok, string $message, string $redirect = '', array $data = [], int $status = 0): never
{
    $status = $status ?: ($ok ? 200 : 400);
    if (wants_json()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }
    flash($ok ? 'success' : 'danger', $message);
    header('Location: ' . ($redirect !== '' ? $redirect : back_url()));
    exit;
}

/** Untuk pembacaan data (GET): selalu JSON. */
function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        respond(false, 'Metode tidak diizinkan.', '', [], 405);
    }
    csrf_verify();
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_verify(): void
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        respond(false, 'Token keamanan tidak valid. Muat ulang halaman.', '', [], 419);
    }
}

/* ---------- Audit ---------- */
function log_activity(?int $projectId, string $action, string $entityType, ?int $entityId = null, ?string $details = null): void
{
    $u = current_user();
    db()->prepare(
        'INSERT INTO activity_logs (user_id, project_id, action, entity_type, entity_id, details, ip_address)
         VALUES (?,?,?,?,?,?,?)'
    )->execute([
        $u['id'] ?? null,
        $projectId,
        $action,
        $entityType,
        $entityId,
        $details !== null ? mb_substr($details, 0, 500) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
