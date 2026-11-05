<?php
/**
 * dashboard.php
 * Semua data di bawah ini DUMMY. Ganti dengan data dari database
 * setelah backend siap (hapus array, isi dari query PDO).
 */
$pageTitle  = 'Dashboard - KontribusiKu';
$isLoggedIn = true;
$userName   = 'Falih';   // TODO backend: ambil dari $_SESSION
$activePage = 'dashboard';

$stats = [
    ['label' => 'Proyek aktif',    'value' => 3,  'class' => ''],
    ['label' => 'Tugas berjalan',  'value' => 8,  'class' => 'text-primary'],
    ['label' => 'Tugas selesai',   'value' => 14, 'class' => 'text-success'],
    ['label' => 'Tugas terlambat', 'value' => 2,  'class' => 'text-danger'],
];

$projects = [
    ['name' => 'Proyek Pemrograman Web',    'progress' => 65, 'deadline' => '20 Des 2026', 'color' => 'bg-primary'],
    ['name' => 'Makalah Kelompok',          'progress' => 80, 'deadline' => '28 Nov 2026', 'color' => 'bg-success'],
    ['name' => 'Presentasi Akhir Semester', 'progress' => 30, 'deadline' => '15 Jan 2027', 'color' => 'bg-warning'],
];

$tasks = [
    ['title' => 'Buat halaman login',     'project' => 'Proyek Pemrograman Web',    'due' => '08 Nov 2026', 'priority' => 'Tinggi', 'status' => 'Berjalan'],
    ['title' => 'Susun daftar pustaka',   'project' => 'Makalah Kelompok',          'due' => '10 Nov 2026', 'priority' => 'Sedang', 'status' => 'Berjalan'],
    ['title' => 'Rancang skema database', 'project' => 'Proyek Pemrograman Web',    'due' => '03 Nov 2026', 'priority' => 'Tinggi', 'status' => 'Terlambat'],
    ['title' => 'Kumpulkan bahan slide',  'project' => 'Presentasi Akhir Semester', 'due' => '20 Nov 2026', 'priority' => 'Rendah', 'status' => 'Belum mulai'],
];

$activities = [
    ['who' => 'Dimas',  'what' => 'menyelesaikan tugas "Koneksi database"',   'when' => '2 jam lalu'],
    ['who' => 'Kamu',   'what' => 'mengirim hasil tugas "Halaman register"',  'when' => 'Kemarin'],
    ['who' => 'Reva',   'what' => 'menambahkan tugas "Susun daftar pustaka"', 'when' => '2 hari lalu'],
    ['who' => 'Azalea', 'what' => 'meminta revisi pada "Bab pendahuluan"',    'when' => '3 hari lalu'],
];

$statusBadge = [
    'Berjalan'    => 'text-bg-primary',
    'Selesai'     => 'text-bg-success',
    'Terlambat'   => 'text-bg-danger',
    'Belum mulai' => 'text-bg-secondary',
];
$priorityBadge = [
    'Tinggi' => 'text-bg-danger',
    'Sedang' => 'text-bg-warning',
    'Rendah' => 'text-bg-secondary',
];

require __DIR__ . '/includes/header.php';
?>

    <main class="py-5">
        <div class="container">

            <!-- Sapaan -->
            <div class="mb-4">
                <h1 class="h3 fw-bold mb-1">Halo, <?= htmlspecialchars($userName) ?></h1>
                <p class="text-secondary mb-0">Ini ringkasan kerja kelompokmu hari ini.</p>
            </div>

            <!-- Statistik -->
            <div class="row g-3 mb-4">
                <?php foreach ($stats as $stat): ?>
                    <div class="col-6 col-lg-3">
                        <div class="stat-card">
                            <p class="stat-label"><?= htmlspecialchars($stat['label']) ?></p>
                            <p class="stat-value <?= $stat['class'] ?>"><?= (int) $stat['value'] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-4 mb-4">
                <!-- Progres proyek -->
                <div class="col-lg-7">
                    <section class="panel">
                        <h2 class="h5 fw-bold mb-4">Progres proyek</h2>

                        <?php foreach ($projects as $project): ?>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-semibold"><?= htmlspecialchars($project['name']) ?></span>
                                    <strong><?= (int) $project['progress'] ?>%</strong>
                                </div>
                                <div class="progress" role="progressbar"
                                     aria-label="Progres <?= htmlspecialchars($project['name']) ?>"
                                     aria-valuenow="<?= (int) $project['progress'] ?>"
                                     aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar <?= $project['color'] ?>"
                                         style="width: <?= (int) $project['progress'] ?>%"></div>
                                </div>
                                <small class="text-secondary">
                                    Tenggat <?= htmlspecialchars($project['deadline']) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </section>
                </div>

                <!-- Aktivitas terbaru -->
                <div class="col-lg-5">
                    <section class="panel">
                        <h2 class="h5 fw-bold mb-3">Aktivitas terbaru</h2>

                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-item">
                                <div>
                                    <strong><?= htmlspecialchars($activity['who']) ?></strong>
                                    <?= htmlspecialchars($activity['what']) ?>
                                </div>
                                <small class="text-secondary"><?= htmlspecialchars($activity['when']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </section>
                </div>
            </div>

            <!-- Tugas mendekati tenggat -->
            <section class="panel">
                <h2 class="h5 fw-bold mb-3">Tugas mendekati tenggat</h2>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Tugas</th>
                                <th scope="col">Proyek</th>
                                <th scope="col">Tenggat</th>
                                <th scope="col">Prioritas</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($task['title']) ?></td>
                                    <td><?= htmlspecialchars($task['project']) ?></td>
                                    <td><?= htmlspecialchars($task['due']) ?></td>
                                    <td>
                                        <span class="badge <?= $priorityBadge[$task['priority']] ?? 'text-bg-secondary' ?>">
                                            <?= htmlspecialchars($task['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $statusBadge[$task['status']] ?? 'text-bg-secondary' ?>">
                                            <?= htmlspecialchars($task['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>