/*
 * Data contoh (mock) untuk demo frontend.
 * Bentuk datanya mengikuti kolom di database/schema.sql, sehingga nanti
 * cukup mengganti sumber data dengan panggilan fetch() ke backend PHP.
 *
 * Pengguna yang sedang "login" di demo: user id 1 (Dimas Darmawan).
 * Dimas adalah ketua di Website KontribusiKu dan anggota di Laporan Basis Data.
 */
var MOCK = {
  users: [
    { id: 1, name: 'Dimas Darmawan' },
    { id: 2, name: 'Muhammad Falih Fakhri' },
    { id: 3, name: 'Ahsani Taqwim Sabiq' },
    { id: 4, name: 'Alfianus Fierik Feto' },
    { id: 5, name: 'Reva Pramnesti Inderasari' },
    { id: 6, name: 'Azalea Zora Ammara' }
  ],

  projects: [
    {
      id: 1,
      title: 'Website KontribusiKu',
      description: 'Pengembangan fitur laporan kontribusi kelompok.',
      deadline: '2026-12-20',
      status: 'active',
      members: [
        { user_id: 1, role: 'leader' },
        { user_id: 2, role: 'member' },
        { user_id: 3, role: 'member' },
        { user_id: 4, role: 'member' },
        { user_id: 5, role: 'member' },
        { user_id: 6, role: 'member' }
      ]
    },
    {
      id: 2,
      title: 'Laporan Basis Data',
      description: 'Perancangan basis data dan ERD.',
      deadline: '2026-11-30',
      status: 'active',
      members: [
        { user_id: 4, role: 'leader' },
        { user_id: 1, role: 'member' },
        { user_id: 5, role: 'member' }
      ]
    }
  ],

  tasks: [
    { id: 1, project_id: 1, title: 'Rancang skema database', description: 'Buat ERD dan schema.sql.', priority: 'high', deadline: '2026-11-10', status: 'done', created_by: 1, completed_at: '2026-11-08T14:30:00', assignees: [3] },
    { id: 2, project_id: 1, title: 'Modul autentikasi', description: 'Register, login, logout, dan manajemen sesi.', priority: 'high', deadline: '2026-11-15', status: 'submitted', created_by: 1, completed_at: null, assignees: [2] },
    { id: 3, project_id: 1, title: 'Halaman manajemen tugas', description: 'CRUD tugas dan penugasan anggota.', priority: 'medium', deadline: '2026-11-20', status: 'revision', created_by: 1, completed_at: null, assignees: [5] },
    { id: 4, project_id: 1, title: 'Dashboard dan laporan', description: 'Statistik progres dan ringkasan kontribusi.', priority: 'medium', deadline: '2026-12-05', status: 'in_progress', created_by: 1, completed_at: null, assignees: [1, 6] },
    { id: 5, project_id: 1, title: 'Dokumentasi README', description: 'Lengkapi README dan panduan instalasi.', priority: 'low', deadline: '2026-11-01', status: 'todo', created_by: 1, completed_at: null, assignees: [4] },
    { id: 6, project_id: 1, title: 'Laporan mingguan proyek', description: 'Ringkasan progres minggu ini oleh seluruh anggota.', priority: 'high', deadline: '2026-11-12', status: 'todo', created_by: 1, completed_at: null, assignees: [1, 2, 3, 4, 5, 6] },
    { id: 7, project_id: 2, title: 'Susun ERD', description: 'Gambar ERD lengkap.', priority: 'high', deadline: '2026-11-20', status: 'todo', created_by: 4, completed_at: null, assignees: [1] },
    { id: 8, project_id: 2, title: 'Normalisasi tabel', description: 'Tuliskan proses normalisasi.', priority: 'medium', deadline: '2026-11-25', status: 'todo', created_by: 4, completed_at: null, assignees: [5] }
  ],

  submissions: [
    { id: 1, task_id: 1, user_id: 3, version: 1, type: 'link', link_url: 'https://github.com/ddimzuu-source/KontribusiKu', original_name: null, note: 'Schema versi pertama.', status: 'approved', created_at: '2026-11-07T10:00:00',
      reviews: [{ reviewer_id: 1, decision: 'approved', note: 'Relasi sudah lengkap.', created_at: '2026-11-08T14:30:00' }] },
    { id: 2, task_id: 2, user_id: 2, version: 1, type: 'link', link_url: 'https://github.com/ddimzuu-source/KontribusiKu/pulls', original_name: null, note: 'Login dan register selesai.', status: 'pending', created_at: '2026-11-09T16:20:00', reviews: [] },
    { id: 3, task_id: 3, user_id: 5, version: 1, type: 'file', link_url: null, original_name: 'halaman-tugas-draft.pdf', note: 'Draft halaman tugas.', status: 'revision', created_at: '2026-11-09T09:15:00',
      reviews: [{ reviewer_id: 1, decision: 'revision', note: 'Tambahkan validasi tenggat dan batasi akses antarproyek.', created_at: '2026-11-09T11:00:00' }] }
  ],

  activity: [
    { id: 5, project_id: 1, user_id: 2, text: 'Mengirim hasil v1 untuk "Modul autentikasi"', created_at: '2026-11-09T16:20:00' },
    { id: 4, project_id: 1, user_id: 1, text: 'Meminta revisi untuk "Halaman manajemen tugas"', created_at: '2026-11-09T11:00:00' },
    { id: 3, project_id: 1, user_id: 5, text: 'Mengirim hasil v1 untuk "Halaman manajemen tugas"', created_at: '2026-11-09T09:15:00' },
    { id: 2, project_id: 1, user_id: 1, text: 'Menyetujui hasil untuk "Rancang skema database"', created_at: '2026-11-08T14:30:00' },
    { id: 1, project_id: 1, user_id: 3, text: 'Mengirim hasil v1 untuk "Rancang skema database"', created_at: '2026-11-07T10:00:00' }
  ]
};
