<?php
declare(strict_types=1);

const TASK_PRIORITIES = ['low', 'medium', 'high'];

function get_task(int $taskId): ?array
{
    $st = db()->prepare(
        'SELECT t.*, p.status AS project_status
         FROM tasks t JOIN projects p ON p.id = t.project_id
         WHERE t.id = ?'
    );
    $st->execute([$taskId]);
    return $st->fetch() ?: null;
}

/** Ambil tugas lalu pastikan user login anggota proyeknya. Mengembalikan [task, peran]. */
function require_task_access(int $taskId): array
{
    $task = get_task($taskId);
    if (!$task) {
        respond(false, 'Tugas tidak ditemukan.', '', [], 404);
    }
    $role = require_member((int)$task['project_id']);
    return [$task, $role];
}

function is_assignee(int $taskId, int $userId): bool
{
    $st = db()->prepare('SELECT 1 FROM task_assignees WHERE task_id = ? AND user_id = ?');
    $st->execute([$taskId, $userId]);
    return (bool)$st->fetchColumn();
}

function task_input(): array
{
    $title = post_str('title');
    $desc  = post_str('description');
    $prio  = post_str('priority') ?: 'medium';
    $dl    = post_str('deadline');
    if ($title === '' || mb_strlen($title) > 150) {
        respond(false, 'Judul tugas wajib diisi (maks. 150 karakter).');
    }
    if (mb_strlen($desc) > 2000) {
        respond(false, 'Deskripsi maksimal 2000 karakter.');
    }
    if (!in_array($prio, TASK_PRIORITIES, true)) {
        respond(false, 'Prioritas tidak valid.');
    }
    if ($dl !== '' && !valid_date($dl)) {
        respond(false, 'Format tenggat harus YYYY-MM-DD.');
    }
    return [$title, $desc !== '' ? $desc : null, $prio, $dl !== '' ? $dl : null];
}

/** Ganti penanggung jawab; semua user harus anggota proyek. */
function sync_assignees(int $taskId, int $projectId, array $userIds): void
{
    if ($userIds) {
        $in = implode(',', array_fill(0, count($userIds), '?'));
        $st = db()->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ? AND user_id IN ($in)");
        $st->execute(array_merge([$projectId], $userIds));
        if ((int)$st->fetchColumn() !== count($userIds)) {
            respond(false, 'Penanggung jawab harus anggota proyek.');
        }
    }
    db()->prepare('DELETE FROM task_assignees WHERE task_id = ?')->execute([$taskId]);
    $ins = db()->prepare('INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)');
    foreach ($userIds as $uid) {
        $ins->execute([$taskId, $uid]);
    }
}

function task_assignees(array $taskIds): array
{
    if (!$taskIds) {
        return [];
    }
    $in = implode(',', array_fill(0, count($taskIds), '?'));
    $st = db()->prepare(
        "SELECT ta.task_id, u.id, u.name
         FROM task_assignees ta JOIN users u ON u.id = ta.user_id
         WHERE ta.task_id IN ($in) ORDER BY u.name"
    );
    $st->execute($taskIds);
    $map = [];
    foreach ($st->fetchAll() as $r) {
        $map[(int)$r['task_id']][] = ['id' => (int)$r['id'], 'name' => $r['name']];
    }
    return $map;
}

/** Filter: status, mine (tugas saya), overdue (terlambat). Semua anggota boleh melihat semua tugas (transparansi). */
function list_tasks(int $projectId, int $userId, array $f): array
{
    $sql = 'SELECT t.id, t.title, t.priority, t.deadline, t.status, t.completed_at,
                   (t.status <> \'done\' AND t.deadline IS NOT NULL AND t.deadline < CURDATE()) AS is_overdue
            FROM tasks t WHERE t.project_id = ?';
    $args = [$projectId];
    if (!empty($f['status']) && in_array($f['status'], ['todo', 'in_progress', 'submitted', 'revision', 'done'], true)) {
        $sql .= ' AND t.status = ?';
        $args[] = $f['status'];
    }
    if (!empty($f['mine'])) {
        $sql .= ' AND EXISTS (SELECT 1 FROM task_assignees ta WHERE ta.task_id = t.id AND ta.user_id = ?)';
        $args[] = $userId;
    }
    if (!empty($f['overdue'])) {
        $sql .= ' AND t.status <> \'done\' AND t.deadline IS NOT NULL AND t.deadline < CURDATE()';
    }
    $sql .= ' ORDER BY t.status = \'done\', FIELD(t.priority, \'high\', \'medium\', \'low\'), t.deadline IS NULL, t.deadline';
    $st = db()->prepare($sql);
    $st->execute($args);
    $tasks = $st->fetchAll();
    $assignees = task_assignees(array_map(fn($t) => (int)$t['id'], $tasks));
    foreach ($tasks as &$t) {
        $t['is_overdue'] = (bool)$t['is_overdue'];
        $t['assignees']  = $assignees[(int)$t['id']] ?? [];
    }
    return $tasks;
}

function delete_task(int $taskId): void
{
    $st = db()->prepare('SELECT file_path FROM submissions WHERE task_id = ? AND file_path IS NOT NULL');
    $st->execute([$taskId]);
    $files = $st->fetchAll(PDO::FETCH_COLUMN);
    db()->prepare('DELETE FROM tasks WHERE id = ?')->execute([$taskId]);
    foreach ($files as $f) {
        $path = UPLOAD_DIR . '/' . basename((string)$f);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
