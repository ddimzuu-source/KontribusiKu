<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . '/storage/submissions');
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
define('APP_URL', rtrim(getenv('APP_URL') ?: '/KontribusiKu', '/'));

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dukung body JSON selain form biasa
if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $body = json_decode((string)file_get_contents('php://input'), true);
    if (is_array($body)) {
        $_POST = $body;
    }
}

require BASE_PATH . '/config/database.php';   // menyediakan $pdo
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/projects.php';
require __DIR__ . '/tasks.php';
require __DIR__ . '/submissions.php';
require __DIR__ . '/reports.php';

set_exception_handler(function (Throwable $e): void {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    respond(false, 'Terjadi kesalahan pada server.', '', [], 500);
});
