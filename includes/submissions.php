<?php
declare(strict_types=1);

const ALLOWED_UPLOADS = [
    'pdf'  => ['application/pdf'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    'zip'  => ['application/zip'],
    'png'  => ['image/png'],
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'txt'  => ['text/plain'],
];

/** Validasi + simpan berkas unggahan. Mengembalikan metadata, atau menghentikan request jika tidak valid. */
function store_uploaded_file(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        respond(false, 'Unggahan berkas gagal (kode ' . (int)($file['error'] ?? -1) . ').');
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        respond(false, 'Ukuran berkas maksimal ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . ' MB.');
    }
    $original = basename((string)$file['name']);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!isset(ALLOWED_UPLOADS[$ext])) {
        respond(false, 'Tipe berkas tidak diizinkan. Gunakan: ' . implode(', ', array_keys(ALLOWED_UPLOADS)) . '.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    if (!in_array($mime, ALLOWED_UPLOADS[$ext], true)) {
        respond(false, 'Isi berkas tidak sesuai dengan ekstensinya.');
    }
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0750, true) && !is_dir(UPLOAD_DIR)) {
        throw new RuntimeException('Tidak dapat membuat folder upload.');
    }
    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $stored)) {
        throw new RuntimeException('Gagal menyimpan berkas.');
    }
    return ['stored' => $stored, 'original' => mb_substr($original, 0, 255), 'mime' => $mime];
}

function create_submission(array $task, int $userId): int
{
    $type = post_str('type');
    $note = post_str('note');
    if (mb_strlen($note) > 2000) {
        respond(false, 'Catatan maksimal 2000 karakter.');
    }
    $link = null;
    $meta = null;
    if ($type === 'link') {
        $link = post_str('link_url');
        $scheme = strtolower((string)parse_url($link, PHP_URL_SCHEME));
        if (!filter_var($link, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true) || strlen($link) > 500) {
            respond(false, 'Tautan harus berupa URL http/https yang valid.');
        }
    } elseif ($type === 'file') {
        if (empty($_FILES['file'])) {
            respond(false, 'Berkas belum dipilih.');
        }
        $meta = store_uploaded_file($_FILES['file']);
    } else {
        respond(false, 'Jenis pengumpulan harus "file" atau "link".');
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();
        // Kunci baris tugas agar versi tidak bentrok dan status dicek ulang.
        $st = $pdo->prepare('SELECT status FROM tasks WHERE id = ? FOR UPDATE');
        $st->execute([$task['id']]);
        $status = $st->fetchColumn();
        if (!in_array($status, ['todo', 'in_progress', 'revision'], true)) {
            throw new DomainException('Tugas ini tidak dapat dikumpulkan pada status saat ini.');
        }
        $st = $pdo->prepare('SELECT COALESCE(MAX(version), 0) + 1 FROM submissions WHERE task_id = ?');
        $st->execute([$task['id']]);
        $version = (int)$st->fetchColumn();

        $pdo->prepare(
            'INSERT INTO submissions (task_id, user_id, version, type, link_url, file_path, original_name, mime_type, note)
             VALUES (?,?,?,?,?,?,?,?,?)'
        )->execute([
            $task['id'], $userId, $version, $type, $link,
            $meta['stored'] ?? null, $meta['original'] ?? null, $meta['mime'] ?? null,
            $note !== '' ? $note : null,
        ]);
        $sid = (int)$pdo->lastInsertId();
        $pdo->prepare("UPDATE tasks SET status = 'submitted' WHERE id = ?")->execute([$task['id']]);
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($meta) {
            @unlink(UPLOAD_DIR . '/' . $meta['stored']);
        }
        if ($ex instanceof DomainException) {
            respond(false, $ex->getMessage(), '', [], 409);
        }
        throw $ex;
    }
    log_activity((int)$task['project_id'], 'submit', 'submission', $sid, "v$version untuk tugas #{$task['id']}");
    return $sid;
}

function review_submission(int $submissionId, int $reviewerId): array
{
    $decision = post_str('decision');
    $note     = post_str('note');
    if (!in_array($decision, ['approved', 'revision'], true)) {
        respond(false, 'Keputusan harus "approved" atau "revision".');
    }
    if ($decision === 'revision' && $note === '') {
        respond(false, 'Permintaan revisi wajib disertai catatan.');
    }
    if (mb_strlen($note) > 2000) {
        respond(false, 'Catatan maksimal 2000 karakter.');
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare(
            'SELECT s.id, s.user_id, s.status, s.version, s.task_id, t.project_id
             FROM submissions s JOIN tasks t ON t.id = s.task_id
             WHERE s.id = ? FOR UPDATE'
        );
        $st->execute([$submissionId]);
        $s = $st->fetch();
        if (!$s) {
            throw new DomainException('Pengumpulan tidak ditemukan.');
        }
        $role = project_role((int)$s['project_id'], $reviewerId);
        if ($role !== 'leader') {
            throw new DomainException('Hanya ketua proyek yang dapat memeriksa hasil.');
        }
        if ((int)$s['user_id'] === $reviewerId) {
            throw new DomainException('Ketua tidak dapat menyetujui hasil kerjanya sendiri.');
        }
        if ($s['status'] !== 'pending') {
            throw new DomainException('Pengumpulan ini sudah diperiksa.');
        }
        $pdo->prepare('UPDATE submissions SET status = ? WHERE id = ?')->execute([$decision, $submissionId]);
        $pdo->prepare('INSERT INTO task_reviews (submission_id, reviewer_id, decision, note) VALUES (?,?,?,?)')
            ->execute([$submissionId, $reviewerId, $decision, $note !== '' ? $note : null]);
        if ($decision === 'approved') {
            $pdo->prepare("UPDATE tasks SET status = 'done', completed_at = NOW() WHERE id = ?")->execute([$s['task_id']]);
        } else {
            $pdo->prepare("UPDATE tasks SET status = 'revision' WHERE id = ?")->execute([$s['task_id']]);
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($ex instanceof DomainException) {
            respond(false, $ex->getMessage(), '', [], 409);
        }
        throw $ex;
    }
    log_activity((int)$s['project_id'], $decision === 'approved' ? 'approve' : 'request_revision', 'submission', $submissionId);
    return $s;
}

/** Riwayat pengumpulan + pemeriksaan untuk satu tugas (terbaru dulu). */
function submission_history(int $taskId): array
{
    $st = db()->prepare(
        'SELECT s.id, s.version, s.type, s.link_url, s.original_name, s.note, s.status, s.created_at,
                u.id AS user_id, u.name AS user_name
         FROM submissions s JOIN users u ON u.id = s.user_id
         WHERE s.task_id = ? ORDER BY s.version DESC'
    );
    $st->execute([$taskId]);
    $subs = $st->fetchAll();
    if (!$subs) {
        return [];
    }
    $ids = array_map(fn($s) => (int)$s['id'], $subs);
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $st  = db()->prepare(
        "SELECT r.submission_id, r.decision, r.note, r.created_at, u.name AS reviewer_name
         FROM task_reviews r JOIN users u ON u.id = r.reviewer_id
         WHERE r.submission_id IN ($in) ORDER BY r.created_at"
    );
    $st->execute($ids);
    $byId = [];
    foreach ($st->fetchAll() as $r) {
        $byId[(int)$r['submission_id']][] = $r;
    }
    foreach ($subs as &$s) {
        $s['reviews'] = $byId[(int)$s['id']] ?? [];
    }
    return $subs;
}
