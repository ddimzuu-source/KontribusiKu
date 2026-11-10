<?php
declare(strict_types=1);

const CONTRIBUTION_DISCLAIMER =
    'Laporan ini adalah alat bantu evaluasi berdasarkan aktivitas yang tercatat, bukan ukuran mutlak '
    . 'kemampuan, usaha, atau kualitas kerja seseorang. Pertimbangkan juga kompleksitas tugas, kualitas hasil, '
    . 'kolaborasi, dan konteks pengerjaan.';

function contribution_report(int $projectId): array
{
    $sub = 'FROM task_assignees ta JOIN tasks t ON t.id = ta.task_id WHERE ta.user_id = u.id AND t.project_id = ?';
    $st = db()->prepare(
        "SELECT u.id, u.name, pm.role,
                (SELECT COUNT(*) $sub) AS assigned,
                (SELECT COUNT(*) $sub AND t.status = 'done') AS done,
                (SELECT COUNT(*) $sub AND t.status <> 'done' AND t.deadline IS NOT NULL AND t.deadline < CURDATE()) AS overdue,
                (SELECT COUNT(*) FROM submissions s JOIN tasks t ON t.id = s.task_id
                  WHERE s.user_id = u.id AND t.project_id = ?) AS submissions,
                (SELECT COUNT(*) FROM submissions s JOIN tasks t ON t.id = s.task_id
                  WHERE s.user_id = u.id AND t.project_id = ? AND s.status = 'revision') AS revisions_received,
                (SELECT COUNT(*) FROM activity_logs a WHERE a.user_id = u.id AND a.project_id = ?) AS activities
         FROM project_members pm JOIN users u ON u.id = pm.user_id
         WHERE pm.project_id = ?
         ORDER BY u.name"
    );
    $st->execute(array_fill(0, 7, $projectId));
    $members = $st->fetchAll();
    foreach ($members as &$m) {
        foreach (['assigned', 'done', 'overdue', 'submissions', 'revisions_received', 'activities'] as $k) {
            $m[$k] = (int)$m[$k];
        }
        $m['completion_rate'] = $m['assigned'] > 0 ? (int)round($m['done'] / $m['assigned'] * 100) : 0;
    }
    return [
        'progress'   => project_progress($projectId),
        'members'    => $members,
        'disclaimer' => CONTRIBUTION_DISCLAIMER,
    ];
}

function project_activity(int $projectId, int $limit, int $offset): array
{
    $st = db()->prepare(
        'SELECT a.id, a.action, a.entity_type, a.entity_id, a.details, a.created_at, u.name AS user_name
         FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id
         WHERE a.project_id = ? ORDER BY a.id DESC LIMIT ? OFFSET ?'
    );
    $st->bindValue(1, $projectId, PDO::PARAM_INT);
    $st->bindValue(2, $limit, PDO::PARAM_INT);
    $st->bindValue(3, $offset, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

function dashboard_stats(int $userId): array
{
    $pdo = db();

    $st = $pdo->prepare("SELECT COUNT(*) FROM project_members pm JOIN projects p ON p.id = pm.project_id
                         WHERE pm.user_id = ? AND p.status = 'active'");
    $st->execute([$userId]);
    $projects = (int)$st->fetchColumn();

    $st = $pdo->prepare(
        "SELECT t.status, COUNT(*) AS c,
                SUM(t.status <> 'done' AND t.deadline IS NOT NULL AND t.deadline < CURDATE()) AS overdue
         FROM task_assignees ta
         JOIN tasks t ON t.id = ta.task_id
         JOIN projects p ON p.id = t.project_id AND p.status = 'active'
         WHERE ta.user_id = ? GROUP BY t.status"
    );
    $st->execute([$userId]);
    $byStatus = ['todo' => 0, 'in_progress' => 0, 'submitted' => 0, 'revision' => 0, 'done' => 0];
    $overdue = 0;
    foreach ($st->fetchAll() as $r) {
        $byStatus[$r['status']] = (int)$r['c'];
        $overdue += (int)$r['overdue'];
    }

    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM submissions s
         JOIN tasks t ON t.id = s.task_id
         JOIN project_members pm ON pm.project_id = t.project_id AND pm.user_id = ? AND pm.role = 'leader'
         WHERE s.status = 'pending' AND s.user_id <> ?"
    );
    $st->execute([$userId, $userId]);
    $pendingReviews = (int)$st->fetchColumn();

    $st = $pdo->prepare(
        'SELECT a.action, a.entity_type, a.details, a.created_at, u.name AS user_name, p.title AS project_title
         FROM activity_logs a
         JOIN project_members pm ON pm.project_id = a.project_id AND pm.user_id = ?
         JOIN projects p ON p.id = a.project_id
         LEFT JOIN users u ON u.id = a.user_id
         ORDER BY a.id DESC LIMIT 10'
    );
    $st->execute([$userId]);

    return [
        'active_projects'     => $projects,
        'my_tasks_by_status'  => $byStatus,
        'my_tasks_overdue'    => $overdue,
        'pending_reviews'     => $pendingReviews,
        'recent_activity'     => $st->fetchAll(),
    ];
}
