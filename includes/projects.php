<?php
declare(strict_types=1);

function project_role(int $projectId, int $userId): ?string
{
    $st = db()->prepare('SELECT role FROM project_members WHERE project_id = ? AND user_id = ?');
    $st->execute([$projectId, $userId]);
    $r = $st->fetchColumn();
    return $r === false ? null : (string)$r;
}

/** Memastikan user login adalah anggota proyek. Non-anggota mendapat 404 (tidak membocorkan keberadaan proyek). */
function require_member(int $projectId): string
{
    $u = require_login();
    $role = $projectId > 0 ? project_role($projectId, (int)$u['id']) : null;
    if ($role === null) {
        respond(false, 'Proyek tidak ditemukan.', '', [], 404);
    }
    return $role;
}

function require_leader(int $projectId): void
{
    if (require_member($projectId) !== 'leader') {
        respond(false, 'Hanya ketua proyek yang dapat melakukan ini.', '', [], 403);
    }
}

function require_active_project(int $projectId): array
{
    $st = db()->prepare('SELECT * FROM projects WHERE id = ?');
    $st->execute([$projectId]);
    $p = $st->fetch();
    if (!$p) {
        respond(false, 'Proyek tidak ditemukan.', '', [], 404);
    }
    if ($p['status'] !== 'active') {
        respond(false, 'Proyek sudah diarsipkan dan tidak dapat diubah.', '', [], 409);
    }
    return $p;
}

function project_input(): array
{
    $title = post_str('title');
    $desc  = post_str('description');
    $dl    = post_str('deadline');
    if ($title === '' || mb_strlen($title) > 150) {
        respond(false, 'Judul proyek wajib diisi (maks. 150 karakter).');
    }
    if (mb_strlen($desc) > 2000) {
        respond(false, 'Deskripsi maksimal 2000 karakter.');
    }
    if ($dl !== '' && !valid_date($dl)) {
        respond(false, 'Format tenggat harus YYYY-MM-DD.');
    }
    return [$title, $desc !== '' ? $desc : null, $dl !== '' ? $dl : null];
}

function user_projects(int $userId, string $status = 'active'): array
{
    $st = db()->prepare(
        "SELECT p.id, p.title, p.description, p.deadline, p.status, pm.role AS my_role,
                (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) AS total_tasks,
                (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'done') AS done_tasks
         FROM projects p
         JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
         WHERE p.status = ?
         ORDER BY p.created_at DESC"
    );
    $st->execute([$userId, $status === 'archived' ? 'archived' : 'active']);
    return $st->fetchAll();
}

function project_members_list(int $projectId): array
{
    $st = db()->prepare(
        "SELECT u.id, u.name, u.email, pm.role, pm.joined_at
         FROM project_members pm JOIN users u ON u.id = pm.user_id
         WHERE pm.project_id = ?
         ORDER BY pm.role = 'leader' DESC, u.name"
    );
    $st->execute([$projectId]);
    return $st->fetchAll();
}

function project_progress(int $projectId): array
{
    $st = db()->prepare(
        "SELECT COUNT(*) AS total,
                SUM(status = 'done') AS done,
                SUM(status IN ('in_progress','submitted','revision')) AS running,
                SUM(status <> 'done' AND deadline IS NOT NULL AND deadline < CURDATE()) AS overdue
         FROM tasks WHERE project_id = ?"
    );
    $st->execute([$projectId]);
    $r = $st->fetch();
    $total = (int)($r['total'] ?? 0);
    $done  = (int)($r['done'] ?? 0);
    return [
        'total'   => $total,
        'done'    => $done,
        'running' => (int)($r['running'] ?? 0),
        'overdue' => (int)($r['overdue'] ?? 0),
        'percent' => $total > 0 ? (int)round($done / $total * 100) : 0,
    ];
}
