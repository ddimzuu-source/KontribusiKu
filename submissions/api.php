<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$u      = require_login();
$uid    = (int)$u['id'];
$action = (string)($_GET['action'] ?? $_POST['action'] ?? '');

/* ---------- GET: unduh berkas (hanya anggota proyek) ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    if ($action !== 'download') {
        respond(false, 'Aksi tidak dikenal.', '', [], 404);
    }
    $st = db()->prepare(
        'SELECT s.file_path, s.original_name, s.mime_type, t.project_id
         FROM submissions s JOIN tasks t ON t.id = s.task_id
         WHERE s.id = ? AND s.type = \'file\''
    );
    $st->execute([get_int('id')]);
    $s = $st->fetch();
    if (!$s) {
        respond(false, 'Berkas tidak ditemukan.', '', [], 404);
    }
    require_member((int)$s['project_id']);
    $path = UPLOAD_DIR . '/' . basename((string)$s['file_path']);
    if (!is_file($path)) {
        respond(false, 'Berkas tidak ditemukan di server.', '', [], 404);
    }
    $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', (string)$s['original_name']);
    header('Content-Type: ' . $s['mime_type']);
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

/* ---------- POST ---------- */
require_post();

switch ($action) {
    case 'create':   // kirim hasil: type=file (+ multipart "file") atau type=link (+ link_url)
        [$task] = require_task_access(post_int('task_id'));
        require_active_project((int)$task['project_id']);
        if (!is_assignee((int)$task['id'], $uid)) {
            respond(false, 'Hanya penanggung jawab tugas yang dapat mengumpulkan hasil.', '', [], 403);
        }
        $sid = create_submission($task, $uid);
        respond(true, 'Hasil pekerjaan dikirim.', '', ['id' => $sid], 201);

    case 'review':   // decision=approved|revision (+ note)
        $sid = post_int('id');
        review_submission($sid, $uid);
        respond(true, post_str('decision') === 'approved' ? 'Hasil disetujui.' : 'Revisi diminta.');

    default:
        respond(false, 'Aksi tidak dikenal.', '', [], 404);
}
