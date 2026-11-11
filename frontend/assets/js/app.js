/*
 * KontribusiKu - logika frontend (demo).
 * Semua data disimpan di localStorage. Aturan hak akses meniru backend PHP
 * (submissions/api.php, includes/submissions.php, includes/tasks.php).
 */
(function () {
  'use strict';

  var STORE_KEY = 'kontribusiku_v1';
  var SESSION_KEY = 'kontribusiku_session';
  var ME = 1; // pengguna demo yang sedang login

  var STATUS = {
    todo: { label: 'To Do', cls: 'chip-gray', pct: 0 },
    in_progress: { label: 'In Progress', cls: 'chip-blue', pct: 50 },
    submitted: { label: 'Menunggu Review', cls: 'chip-orange', pct: 80 },
    revision: { label: 'Revisi', cls: 'chip-red', pct: 30 },
    done: { label: 'Selesai', cls: 'chip-green', pct: 100 }
  };
  var PRIORITY = { high: ['Tinggi', 'chip-red'], medium: ['Sedang', 'chip-yellow'], low: ['Rendah', 'chip-gray'] };
  var SUB_STATUS = { pending: ['Menunggu', 'chip-orange'], approved: ['Disetujui', 'chip-green'], revision: ['Revisi', 'chip-red'] };
  var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  var NAV = [
    { key: 'dashboard', label: 'Dashboard', href: 'index.html' },
    { key: 'proyek', label: 'Proyek', href: 'proyek.html' },
    { key: 'pengumpulan', label: 'Pengumpulan', href: 'tugas.html?status=submitted' },
    { key: 'laporan', label: 'Laporan', href: 'laporan.html' },
    { key: 'tugas', label: 'Tugas', href: 'tugas.html' }
  ];
  var DISCLAIMER = 'Laporan ini adalah alat bantu evaluasi berdasarkan aktivitas yang tercatat, bukan ukuran mutlak ' +
    'kemampuan, usaha, atau kualitas kerja seseorang. Pertimbangkan juga kompleksitas tugas, kualitas hasil, ' +
    'kolaborasi, dan konteks pengerjaan.';

  // ---------- Utilitas ----------
  function esc(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function $(sel, root) { return (root || document).querySelector(sel); }
  function p2(n) { return (n < 10 ? '0' : '') + n; }
  function todayStr() {
    var d = new Date();
    return d.getFullYear() + '-' + p2(d.getMonth() + 1) + '-' + p2(d.getDate());
  }
  function fmtDate(s) {
    if (!s) return '-';
    var x = String(s).slice(0, 10).split('-');
    return (+x[2]) + ' ' + BULAN[+x[1] - 1] + ' ' + x[0];
  }
  function fmtTime(iso) {
    try {
      return new Date(iso).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
    } catch (e) { return iso; }
  }
  function initials(name) {
    return String(name).split(' ').filter(Boolean).slice(0, 2).map(function (w) { return w[0]; }).join('').toUpperCase();
  }
  function avatar(id, name) {
    return '<span class="avatar" style="background:hsl(' + ((id * 67) % 360) + ' 55% 52%)" title="' + esc(name) + '">' +
      esc(initials(name)) + '</span>';
  }
  function avatars(ids) {
    return '<div class="avatars">' + ids.map(function (id) { return avatar(id, nameOf(id)); }).join('') + '</div>';
  }
  function toNode(html) {
    var t = document.createElement('template');
    t.innerHTML = html.trim();
    return t.content.firstElementChild;
  }
  function statusChip(s) { return '<span class="chip ' + STATUS[s].cls + '">' + STATUS[s].label + '</span>'; }
  function prioChip(p) { return '<span class="chip ' + PRIORITY[p][1] + '">' + PRIORITY[p][0] + '</span>'; }
  function subChip(s) { return '<span class="chip ' + SUB_STATUS[s][1] + '">' + SUB_STATUS[s][0] + '</span>'; }
  function lateChip() { return ' <span class="chip chip-red">Terlambat</span>'; }

  // ---------- State ----------
  var state = null;
  function clone(o) { return JSON.parse(JSON.stringify(o)); }
  function load() {
    if (state) return state;
    var saved = null;
    try { saved = JSON.parse(localStorage.getItem(STORE_KEY) || 'null'); } catch (e) { saved = null; }
    state = saved && saved.tasks ? saved : clone(MOCK);
    return state;
  }
  function save() { try { localStorage.setItem(STORE_KEY, JSON.stringify(state)); } catch (e) { /* abaikan */ } }
  function resetData() { state = clone(MOCK); save(); }
  function sessionOk() {
    try { return !!localStorage.getItem(SESSION_KEY); } catch (e) { return false; }
  }

  // ---------- Query ----------
  function nameOf(id) {
    var u = state.users.find(function (x) { return x.id === id; });
    return u ? u.name : '-';
  }
  function projectOf(id) { return state.projects.find(function (p) { return p.id === +id; }) || null; }
  function taskOf(id) { return state.tasks.find(function (t) { return t.id === +id; }) || null; }
  function membership(pid, uid) {
    var p = projectOf(pid);
    if (!p) return null;
    var m = p.members.find(function (x) { return x.user_id === uid; });
    return m ? m.role : null;
  }
  function myProjects() {
    return state.projects.filter(function (p) { return membership(p.id, ME) !== null; });
  }
  function projectTasks(pid) { return state.tasks.filter(function (t) { return t.project_id === pid; }); }
  function isAssignee(t, uid) { return t.assignees.indexOf(uid) !== -1; }
  function subsOf(taskId) {
    return state.submissions.filter(function (s) { return s.task_id === taskId; })
      .sort(function (a, b) { return b.version - a.version; });
  }
  function isLate(t) { return t.status !== 'done' && !!t.deadline && t.deadline < todayStr(); }
  function canSubmit(t) {
    return isAssignee(t, ME) && ['todo', 'in_progress', 'revision'].indexOf(t.status) !== -1;
  }
  function canEditStatus(t) {
    var role = membership(t.project_id, ME);
    return !!role && ['todo', 'in_progress', 'revision'].indexOf(t.status) !== -1 &&
      (role === 'leader' || isAssignee(t, ME));
  }
  function pendingSub(taskId) {
    return subsOf(taskId).find(function (s) { return s.status === 'pending'; }) || null;
  }
  function nextId(list) {
    return list.reduce(function (m, x) { return Math.max(m, x.id); }, 0) + 1;
  }
  function log(pid, text) {
    state.activity.unshift({ id: nextId(state.activity), project_id: pid, user_id: ME, text: text, created_at: new Date().toISOString() });
  }

  // ---------- Aksi (meniru aturan backend) ----------
  function addTask(f) {
    var role = membership(f.project_id, ME);
    if (!role) return 'Anda bukan anggota proyek ini.';
    if (role !== 'leader') return 'Hanya ketua proyek yang dapat menambah tugas.';
    if (!f.title) return 'Judul tugas wajib diisi.';
    if (f.title.length > 150) return 'Judul maksimal 150 karakter.';
    if (!f.assignees.length) return 'Pilih minimal satu penanggung jawab.';
    if (f.assignees.some(function (uid) { return membership(f.project_id, uid) === null; })) {
      return 'Penanggung jawab harus anggota proyek.';
    }
    state.tasks.push({
      id: nextId(state.tasks), project_id: f.project_id, title: f.title, description: f.description || '',
      priority: f.priority, deadline: f.deadline || null, status: 'todo', created_by: ME,
      completed_at: null, assignees: f.assignees
    });
    log(f.project_id, 'Menambahkan tugas "' + f.title + '"');
    save();
    return null;
  }

  function setStatus(t, status) {
    if (!membership(t.project_id, ME)) return 'Akses ditolak.';
    if (!canEditStatus(t)) return 'Status tugas ini tidak dapat diubah.';
    if (status !== 'todo' && status !== 'in_progress') return 'Status tidak valid.';
    t.status = status;
    log(t.project_id, 'Mengubah status "' + t.title + '" menjadi ' + STATUS[status].label);
    save();
    return null;
  }

  function submitWork(t, f) {
    if (!isAssignee(t, ME)) return 'Hanya penanggung jawab tugas yang dapat mengumpulkan hasil.';
    if (!canSubmit(t)) return 'Tugas ini tidak dapat dikumpulkan pada status saat ini.';
    if (f.note.length > 2000) return 'Catatan maksimal 2000 karakter.';
    var sub = { type: f.type, link_url: null, original_name: null };
    if (f.type === 'link') {
      if (!/^https?:\/\/\S+$/i.test(f.link) || f.link.length > 500) return 'Tautan harus berupa URL http/https yang valid.';
      sub.link_url = f.link;
    } else {
      if (!f.fileName) return 'Berkas belum dipilih.';
      var ext = (f.fileName.split('.').pop() || '').toLowerCase();
      if (['pdf', 'docx', 'xlsx', 'pptx', 'zip', 'png', 'jpg', 'jpeg', 'txt'].indexOf(ext) === -1) {
        return 'Tipe berkas tidak diizinkan.';
      }
      sub.original_name = f.fileName;
    }
    var versions = subsOf(t.id);
    var version = versions.length ? versions[0].version + 1 : 1;
    state.submissions.push({
      id: nextId(state.submissions), task_id: t.id, user_id: ME, version: version,
      type: sub.type, link_url: sub.link_url, original_name: sub.original_name,
      note: f.note || null, status: 'pending', created_at: new Date().toISOString(), reviews: []
    });
    t.status = 'submitted';
    log(t.project_id, 'Mengirim hasil v' + version + ' untuk "' + t.title + '"');
    save();
    return null;
  }

  function reviewSubmission(sub, decision, note) {
    var t = taskOf(sub.task_id);
    if (!t) return 'Tugas tidak ditemukan.';
    if (membership(t.project_id, ME) !== 'leader') return 'Hanya ketua proyek yang dapat memeriksa hasil.';
    if (sub.user_id === ME) return 'Ketua tidak dapat menyetujui hasil kerjanya sendiri.';
    if (sub.status !== 'pending') return 'Pengumpulan ini sudah diperiksa.';
    if (decision !== 'approved' && decision !== 'revision') return 'Keputusan tidak valid.';
    if (decision === 'revision' && !note) return 'Permintaan revisi wajib disertai catatan.';
    sub.status = decision;
    sub.reviews.push({ reviewer_id: ME, decision: decision, note: note || null, created_at: new Date().toISOString() });
    if (decision === 'approved') {
      t.status = 'done';
      t.completed_at = new Date().toISOString().slice(0, 19);
      log(t.project_id, 'Menyetujui hasil untuk "' + t.title + '"');
    } else {
      t.status = 'revision';
      log(t.project_id, 'Meminta revisi untuk "' + t.title + '"');
    }
    save();
    return null;
  }

  function addProject(f) {
    if (!f.title) return 'Judul proyek wajib diisi.';
    if (f.title.length > 150) return 'Judul maksimal 150 karakter.';
    var id = nextId(state.projects);
    state.projects.push({
      id: id, title: f.title, description: f.description || '', deadline: f.deadline || null,
      status: 'active', members: [{ user_id: ME, role: 'leader' }]
    });
    log(id, 'Membuat proyek "' + f.title + '"');
    save();
    return null;
  }

  function doLogin(fd) {
    var email = String(fd.get('email') || '').trim();
    var pass = String(fd.get('password') || '');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return 'Format email tidak valid.';
    if (!pass) return 'Password wajib diisi.';
    try { localStorage.setItem(SESSION_KEY, JSON.stringify({ email: email, at: Date.now() })); } catch (e) { /* abaikan */ }
    return null;
  }

  function doRegister(fd) {
    var name = String(fd.get('name') || '').trim();
    var email = String(fd.get('email') || '').trim();
    var pass = String(fd.get('password') || '');
    if (name.length < 3 || name.length > 100) return 'Nama wajib diisi (3–100 karakter).';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return 'Format email tidak valid.';
    if (pass.length < 8 || pass.length > 72) return 'Password harus 8–72 karakter.';
    if (pass !== String(fd.get('password_confirm') || '')) return 'Konfirmasi password tidak cocok.';
    return doLogin(fd);
  }

  // ---------- Shell (sidebar + topbar) ----------
  function renderShell() {
    var sb = $('#sidebar');
    var tp = $('#topbar');
    if (!sb || !tp) return;
    var page = document.body.dataset.page;
    var params = new URLSearchParams(location.search);
    var active = page === 'tugas' && params.get('status') === 'submitted' ? 'pengumpulan' : page;
    sb.innerHTML =
      '<div class="brand"><div class="brand-logo">KK</div><div><strong>KontribusiKu</strong>' +
      '<small>Academic Teamwork &amp; Contribution Management</small></div></div>' +
      '<nav class="nav" aria-label="Menu utama">' +
      NAV.map(function (n) {
        return '<a href="' + n.href + '"' + (n.key === active ? ' class="active" aria-current="page"' : '') + '>' + n.label + '</a>';
      }).join('') +
      '</nav>' +
      '<div class="sidebar-foot"><button type="button" class="btn btn-outline btn-sm btn-block" data-action="logout">Keluar</button></div>';

    var me = nameOf(ME);
    tp.innerHTML =
      '<button type="button" class="icon-btn menu-btn" data-action="toggle-nav" aria-label="Buka menu">☰</button>' +
      '<form class="search-form" data-form="search" role="search">' +
      '<input class="search" name="q" type="search" placeholder="Cari tugas" aria-label="Cari tugas"></form>' +
      '<div class="user-chip">' + avatar(ME, me) + '<div><strong>' + esc(me) + '</strong><small>Mahasiswa</small></div></div>';
  }

  // ---------- Modal dan toast ----------
  function closeModal() { var m = $('#modal'); if (m) m.remove(); }
  function openModal(title, bodyHtml) {
    closeModal();
    var node = toNode(
      '<div class="modal-backdrop" id="modal"><div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">' +
      '<div class="modal-head"><h2 id="modal-title">' + esc(title) + '</h2>' +
      '<button type="button" class="icon-btn" data-action="close-modal" aria-label="Tutup">×</button></div>' +
      '<div class="modal-body">' + bodyHtml + '</div></div></div>'
    );
    document.body.appendChild(node);
    var first = node.querySelector('input:not([type=hidden]),select,textarea');
    if (first) first.focus();
  }
  function toast(msg, type) {
    var t = toNode('<div class="toast ' + (type === 'error' ? 'error' : '') + '" role="status">' + esc(msg) + '</div>');
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 3200);
  }

  // ---------- Modal: tugas ----------
  function openTaskModal(id) {
    var t = taskOf(id);
    if (!t) return;
    var role = membership(t.project_id, ME);
    var subs = subsOf(t.id);
    var pend = pendingSub(t.id);
    var late = isLate(t);

    var statusForm = '';
    if (canEditStatus(t)) {
      statusForm = '<form class="inline" data-form="set-status">' +
        '<input type="hidden" name="task_id" value="' + t.id + '">' +
        '<label class="form-label" for="st-sel">Ubah status</label>' +
        '<select id="st-sel" name="status" class="select-sm">' +
        ['todo', 'in_progress'].map(function (k) {
          return '<option value="' + k + '"' + (t.status === k ? ' selected' : '') + '>' + STATUS[k].label + '</option>';
        }).join('') +
        '</select><button class="btn btn-outline btn-sm" type="submit">Simpan</button></form>';
    }

    var subList = subs.length
      ? '<ul class="sub-list">' + subs.map(function (s) {
          var what = s.type === 'link'
            ? '<a href="' + esc(s.link_url) + '" target="_blank" rel="noopener">' + esc(s.link_url) + '</a>'
            : 'Berkas: ' + esc(s.original_name);
          var reviews = s.reviews.map(function (r) {
            return '<div class="review"><strong>' + esc(nameOf(r.reviewer_id)) + '</strong> ' +
              (r.decision === 'approved' ? 'menyetujui' : 'meminta revisi') + ': ' + esc(r.note || '-') + '</div>';
          }).join('');
          return '<li class="sub"><div><strong>v' + s.version + '</strong> · ' + esc(nameOf(s.user_id)) + ' · ' +
            '<span class="small muted">' + fmtTime(s.created_at) + '</span></div>' +
            '<div>' + what + '</div>' +
            (s.note ? '<div class="small muted">' + esc(s.note) + '</div>' : '') +
            '<div class="row" style="margin-top:6px">' + subChip(s.status) + '</div>' + reviews + '</li>';
        }).join('') + '</ul>'
      : '<p class="muted small">Belum ada pengumpulan.</p>';

    var submitForm = '';
    if (canSubmit(t)) {
      submitForm = '<form class="form card-inset" data-form="submit-work" style="margin-top:12px">' +
        '<input type="hidden" name="task_id" value="' + t.id + '">' +
        '<h3>Kirim hasil</h3>' +
        '<div class="seg"><label><input type="radio" name="type" value="link" checked> Tautan</label>' +
        '<label><input type="radio" name="type" value="file"> Berkas</label></div>' +
        '<div class="field"><label for="sw-link">Tautan (http/https)</label>' +
        '<input id="sw-link" name="link" type="url" placeholder="https://github.com/..."></div>' +
        '<div class="field"><label for="sw-file">Berkas (pdf, docx, zip, png, jpg, dll.)</label>' +
        '<input id="sw-file" name="file" type="file" accept=".pdf,.docx,.xlsx,.pptx,.zip,.png,.jpg,.jpeg,.txt"></div>' +
        '<div class="field"><label for="sw-note">Catatan</label><textarea id="sw-note" name="note" rows="2" maxlength="2000"></textarea></div>' +
        '<button class="btn btn-primary" type="submit">Kirim Hasil</button></form>';
    }

    var reviewForm = '';
    if (role === 'leader' && pend && pend.user_id !== ME) {
      reviewForm = '<form class="form card-inset" data-form="review" style="margin-top:12px">' +
        '<input type="hidden" name="submission_id" value="' + pend.id + '">' +
        '<h3>Periksa hasil v' + pend.version + ' dari ' + esc(nameOf(pend.user_id)) + '</h3>' +
        '<div class="field"><label for="rv-note">Catatan (wajib untuk revisi)</label>' +
        '<textarea id="rv-note" name="note" rows="2" maxlength="2000"></textarea></div>' +
        '<div class="row-gap"><button class="btn btn-success" name="decision" value="approved" type="submit">Setujui</button>' +
        '<button class="btn btn-warn" name="decision" value="revision" type="submit">Minta Revisi</button></div></form>';
    }

    openModal(t.title,
      '<div class="detail">' +
      '<p class="small muted">' + esc(projectOf(t.project_id).title) + '</p>' +
      '<p>' + esc(t.description || 'Tidak ada deskripsi.') + '</p>' +
      '<dl class="meta">' +
      '<dt>Prioritas</dt><dd>' + prioChip(t.priority) + '</dd>' +
      '<dt>Tenggat</dt><dd>' + fmtDate(t.deadline) + (late ? lateChip() : '') + '</dd>' +
      '<dt>Penanggung jawab</dt><dd>' + t.assignees.map(function (id) { return esc(nameOf(id)); }).join(', ') + '</dd>' +
      '<dt>Status</dt><dd>' + statusChip(t.status) + '</dd></dl>' +
      statusForm +
      '<h3>Riwayat pengumpulan</h3>' + subList +
      submitForm + reviewForm +
      '</div>');
  }

  function openNewTaskModal(pid) {
    var leaderOf = myProjects().filter(function (p) { return membership(p.id, ME) === 'leader' && p.status === 'active'; });
    if (!leaderOf.length) { toast('Anda belum menjadi ketua proyek mana pun.', 'error'); return; }
    var sel = leaderOf.some(function (p) { return p.id === pid; }) ? pid : leaderOf[0].id;
    var opts = leaderOf.map(function (p) {
      return '<option value="' + p.id + '"' + (p.id === sel ? ' selected' : '') + '>' + esc(p.title) + '</option>';
    }).join('');
    openModal('Tugas Baru',
      '<form class="form" data-form="new-task">' +
      '<div class="field"><label for="nt-proj">Proyek</label>' +
      '<select id="nt-proj" name="project_id" data-filter="modal-project">' + opts + '</select></div>' +
      '<div class="field"><label for="nt-title">Judul</label>' +
      '<input id="nt-title" name="title" type="text" maxlength="150" required></div>' +
      '<div class="field"><label for="nt-desc">Deskripsi</label>' +
      '<textarea id="nt-desc" name="description" rows="3" maxlength="2000"></textarea></div>' +
      '<div class="form-row">' +
      '<div class="field"><label for="nt-prio">Prioritas</label>' +
      '<select id="nt-prio" name="priority"><option value="medium">Sedang</option><option value="high">Tinggi</option><option value="low">Rendah</option></select></div>' +
      '<div class="field"><label for="nt-dl">Tenggat</label><input id="nt-dl" name="deadline" type="date"></div></div>' +
      '<div class="field"><span class="form-label">Penanggung jawab</span>' +
      '<div id="assignee-box">' + assigneeChecks(sel) + '</div></div>' +
      '<button class="btn btn-primary" type="submit">Simpan Tugas</button></form>');
  }

  function assigneeChecks(pid) {
    var p = projectOf(pid);
    if (!p) return '';
    return p.members.map(function (m) {
      var n = nameOf(m.user_id);
      return '<label class="check"><input type="checkbox" name="assignees" value="' + m.user_id + '"> ' +
        avatar(m.user_id, n) + ' ' + esc(n) + '</label>';
    }).join('');
  }

  // ---------- Modal: proyek ----------
  function openNewProjectModal() {
    openModal('Proyek Baru',
      '<form class="form" data-form="new-project">' +
      '<div class="field"><label for="np-title">Judul proyek</label>' +
      '<input id="np-title" name="title" type="text" maxlength="150" required></div>' +
      '<div class="field"><label for="np-desc">Deskripsi</label>' +
      '<textarea id="np-desc" name="description" rows="3" maxlength="2000"></textarea></div>' +
      '<div class="field"><label for="np-dl">Tenggat</label><input id="np-dl" name="deadline" type="date"></div>' +
      '<p class="small muted">Kamu otomatis menjadi ketua proyek ini.</p>' +
      '<button class="btn btn-primary" type="submit">Buat Proyek</button></form>');
  }

  function openProjectModal(id) {
    var p = projectOf(id);
    if (!p) return;
    var role = membership(p.id, ME);
    var ts = projectTasks(p.id);
    var done = ts.filter(function (t) { return t.status === 'done'; }).length;
    var pct = ts.length ? Math.round(done / ts.length * 100) : 0;
    var members = p.members.slice().sort(function (a, b) { return (b.role === 'leader') - (a.role === 'leader'); })
      .map(function (m) {
        return '<li>' + avatar(m.user_id, nameOf(m.user_id)) + '<span>' + esc(nameOf(m.user_id)) + '</span> ' +
          (m.role === 'leader' ? '<span class="chip chip-blue">Ketua</span>' : '<span class="chip chip-gray">Anggota</span>') + '</li>';
      }).join('');
    var acts = state.activity.filter(function (a) { return a.project_id === p.id; }).slice(0, 5)
      .map(function (a) { return '<li><span>' + esc(a.text) + '</span> <span class="time">' + fmtTime(a.created_at) + '</span></li>'; })
      .join('');
    openModal(p.title,
      '<p>' + esc(p.description) + '</p>' +
      '<p class="small muted">Tenggat: ' + fmtDate(p.deadline) + ' · Peran kamu: ' + (role === 'leader' ? 'Ketua' : 'Anggota') + '</p>' +
      '<div class="progress" style="margin:10px 0"><span style="width:' + pct + '%"></span></div>' +
      '<p class="small muted">' + done + ' dari ' + ts.length + ' tugas selesai</p>' +
      '<h3 style="margin-top:16px">Anggota</h3><ul class="member-list">' + members + '</ul>' +
      '<h3>Aktivitas terbaru</h3>' + (acts ? '<ul class="activity-list">' + acts + '</ul>' : '<p class="muted small">Belum ada aktivitas.</p>') +
      '<div class="row-gap" style="margin-top:16px"><a class="btn btn-outline" href="tugas.html?pid=' + p.id + '">Lihat Tugas</a>' +
      '<a class="btn btn-outline" href="laporan.html?pid=' + p.id + '">Lihat Laporan</a></div>');
  }

  // ---------- Halaman: dashboard ----------
  var dashTab = 'todo';
  function taskRow(t) {
    return '<tr><td><button class="link-btn" data-action="open-task" data-id="' + t.id + '">' + esc(t.title) + '</button>' +
      '<div class="small muted">' + esc(projectOf(t.project_id).title) + '</div></td>' +
      '<td>' + prioChip(t.priority) + '</td>' +
      '<td>' + fmtDate(t.deadline) + (isLate(t) ? lateChip() : '') + '</td>' +
      '<td>' + statusChip(t.status) + '</td>' +
      '<td>' + avatars(t.assignees) + '</td></tr>';
  }

  function renderDashboard(root) {
    var myTasks = state.tasks.filter(function (t) {
      var p = projectOf(t.project_id);
      return isAssignee(t, ME) && membership(t.project_id, ME) !== null && p && p.status === 'active';
    });
    var total = myTasks.length;
    var done = myTasks.filter(function (t) { return t.status === 'done'; }).length;
    var late = myTasks.filter(isLate).length;
    var running = total - done - late;
    var donePct = total ? Math.round(done / total * 100) : 0;
    var latePct = total ? Math.round(late / total * 100) : 0;
    var runPct = total ? 100 - donePct - latePct : 0;
    var a = donePct, b = donePct + runPct;
    var grad = total
      ? 'conic-gradient(var(--ok) 0% ' + a + '%, var(--info) ' + a + '% ' + b + '%, var(--warn) ' + b + '% 100%)'
      : 'conic-gradient(#e2e8f0 0% 100%)';

    var myProj = myProjects().filter(function (p) { return p.status === 'active'; });
    var first = myProj[0];
    var firstTasks = first ? projectTasks(first.id) : [];
    var firstDone = firstTasks.filter(function (t) { return t.status === 'done'; }).length;
    var firstPct = firstTasks.length ? Math.round(firstDone / firstTasks.length * 100) : 0;

    var listed = myTasks.filter(function (t) {
      if (dashTab === 'todo') return t.status === 'todo';
      if (dashTab === 'progress') return ['in_progress', 'submitted', 'revision'].indexOf(t.status) !== -1;
      return t.status === 'done';
    });
    var activity = state.activity.filter(function (x) { return membership(x.project_id, ME) !== null; }).slice(0, 5);
    var canCreate = myProjects().some(function (p) { return membership(p.id, ME) === 'leader'; });
    var firstName = nameOf(ME).split(' ')[0];

    root.innerHTML =
      '<section class="hero"><h1>Halo, ' + esc(firstName) + '!</h1>' +
      '<p>Kelola proyek akademikmu dengan mudah bersama KontribusiKu.</p>' +
      '<a class="btn btn-light" href="proyek.html">Lihat Proyek Saya →</a></section>' +

      '<section class="grid-stats">' +
      statCard('P', 'Total Proyek', myProj.length, 'var(--info)') +
      statCard('✓', 'Tugas Selesai', done, 'var(--ok)') +
      statCard('!', 'Tugas Tertunda', late, 'var(--danger)') +
      statCard('%', 'Kontribusi', donePct + '%', 'var(--warn)') +
      '</section>' +

      '<section class="grid-2">' +
      '<div class="card"><div class="section-head"><h2>Proyek Saya</h2><a href="proyek.html">Lihat Semua →</a></div>' +
      (first
        ? '<h3>' + esc(first.title) + '</h3><p class="small muted">' + esc(first.description) + '</p>' +
          '<p class="small muted">Tenggat: ' + fmtDate(first.deadline) + '</p>' +
          '<div class="progress" style="margin:10px 0"><span style="width:' + firstPct + '%"></span></div>' +
          '<div class="row" style="justify-content:space-between">' + avatars(first.members.map(function (m) { return m.user_id; })) +
          '<span class="small"><strong>' + firstPct + '%</strong> selesai</span></div>' +
          '<div style="margin-top:12px"><button class="btn btn-sm btn-outline" data-action="open-project" data-id="' + first.id + '">Buka Proyek</button></div>'
        : '<p class="muted">Belum ada proyek.</p>') +
      '</div>' +
      '<div class="card"><div class="section-head"><h2>Aktivitas Terbaru</h2><a href="laporan.html">Lihat Semua →</a></div>' +
      (activity.length
        ? '<ul class="activity-list">' + activity.map(function (x) {
            return '<li>' + avatar(x.user_id, nameOf(x.user_id)) + '<div><div>' + esc(x.text) +
              '</div><div class="time">' + esc(nameOf(x.user_id)) + ' · ' + fmtTime(x.created_at) + '</div></div></li>';
          }).join('') + '</ul>'
        : '<p class="muted">Belum ada aktivitas.</p>') +
      '</div></section>' +

      '<section class="card"><div class="section-head"><h2>Manajemen Tugas</h2>' +
      (canCreate ? '<button class="btn btn-primary btn-sm" data-action="new-task">+ Tambah Tugas</button>' : '') + '</div>' +
      '<div class="tabs" style="margin-bottom:12px">' +
      tabBtn('dash-tab', 'todo', 'To Do', myTasks.filter(function (t) { return t.status === 'todo'; }).length, dashTab) +
      tabBtn('dash-tab', 'progress', 'In Progress', myTasks.filter(function (t) { return ['in_progress', 'submitted', 'revision'].indexOf(t.status) !== -1; }).length, dashTab) +
      tabBtn('dash-tab', 'done', 'Selesai', done, dashTab) +
      '</div>' +
      (listed.length
        ? '<div class="table-wrap"><table><thead><tr><th>Tugas</th><th>Prioritas</th><th>Tenggat</th><th>Status</th><th>Penanggung jawab</th></tr></thead><tbody>' +
          listed.map(taskRow).join('') + '</tbody></table></div>'
        : '<p class="muted">Tidak ada tugas di kategori ini.</p>') +
      '</section>' +

      '<section class="card"><div class="section-head"><h2>Ringkasan Kontribusi</h2></div>' +
      '<div class="grid-2" style="grid-template-columns:1fr 1fr">' +
      '<div><div class="donut" style="background:' + grad + '"><div class="donut-hole">' + donePct + '%</div></div></div>' +
      '<ul class="legend">' +
      '<li><span><span class="dot" style="background:var(--ok)"></span>Selesai</span><strong>' + donePct + '%</strong></li>' +
      '<li><span><span class="dot" style="background:var(--info)"></span>Berjalan (' + running + ')</span><strong>' + runPct + '%</strong></li>' +
      '<li><span><span class="dot" style="background:var(--warn)"></span>Tertunda (' + late + ')</span><strong>' + latePct + '%</strong></li>' +
      '</ul></div></section>';
  }

  function statCard(icon, label, value, color) {
    return '<div class="card stat"><span class="stat-ico" style="background:' + color + '" aria-hidden="true">' + icon + '</span>' +
      '<div><div class="small muted">' + label + '</div><div class="stat-value">' + value + '</div></div></div>';
  }
  function tabBtn(action, key, label, count, current) {
    return '<button type="button" class="tab' + (current === key ? ' active' : '') + '" data-action="' + action +
      '" data-tab="' + key + '">' + label + ' (' + count + ')</button>';
  }

  // ---------- Halaman: proyek ----------
  function renderProyek(root) {
    var list = myProjects();
    var cards = list.map(function (p) {
      var ts = projectTasks(p.id);
      var done = ts.filter(function (t) { return t.status === 'done'; }).length;
      var pct = ts.length ? Math.round(done / ts.length * 100) : 0;
      var role = membership(p.id, ME);
      return '<article class="card project-card" data-action="open-project" data-id="' + p.id + '" tabindex="0">' +
        '<div class="row" style="justify-content:space-between"><h3>' + esc(p.title) + '</h3>' +
        (role === 'leader' ? '<span class="chip chip-blue">Ketua</span>' : '<span class="chip chip-gray">Anggota</span>') + '</div>' +
        '<p class="small muted">' + esc(p.description) + '</p>' +
        '<p class="small muted">Tenggat: ' + fmtDate(p.deadline) + '</p>' +
        '<div class="progress" style="margin:10px 0"><span style="width:' + pct + '%"></span></div>' +
        '<div class="row" style="justify-content:space-between">' + avatars(p.members.map(function (m) { return m.user_id; })) +
        '<span class="small">' + done + '/' + ts.length + ' tugas</span></div></article>';
    }).join('');
    root.innerHTML =
      '<div class="page-head"><div><h1>Proyek</h1><p class="muted">Proyek kelompok yang kamu ikuti.</p></div>' +
      '<button class="btn btn-primary" data-action="new-project">+ Proyek Baru</button></div>' +
      '<div class="grid-cards">' + (cards || '<p class="muted">Belum ada proyek.</p>') + '</div>';
  }

  // ---------- Halaman: tugas ----------
  var tugasState = { tab: 'all', status: '', q: '', pid: 0, init: false };
  var TAB_LABEL = { all: 'Semua', individu: 'Tugas Individu', kelompok: 'Tugas Kelompok', deadline: 'Deadline' };

  function taskCard(t) {
    var role = membership(t.project_id, ME);
    var pend = pendingSub(t.id);
    var kind = t.assignees.length > 1 ? 'Tugas Kelompok' : 'Tugas Individu';
    return '<article class="task-card s-' + t.status + '">' +
      '<div class="task-head"><div><h3>' + esc(t.title) + '</h3>' +
      '<p class="small muted">' + kind + ' · ' + t.assignees.length + ' penanggung jawab</p></div>' +
      '<div class="row">' + prioChip(t.priority) + statusChip(t.status) + '</div></div>' +
      '<p class="small">' + esc(t.description) + '</p>' +
      '<div class="task-foot"><div class="row"><span class="chip ' + (isLate(t) ? 'chip-red' : 'chip-gray') + '">Deadline: ' +
      fmtDate(t.deadline) + '</span>' + avatars(t.assignees) + '</div>' +
      '<div class="row">' +
      (canSubmit(t) ? '<button class="btn btn-sm btn-primary" data-action="open-task" data-id="' + t.id + '">Kirim Hasil</button>' : '') +
      (role === 'leader' && pend && pend.user_id !== ME ? '<button class="btn btn-sm btn-success" data-action="open-task" data-id="' + t.id + '">Periksa</button>' : '') +
      '<button class="btn btn-sm btn-outline" data-action="open-task" data-id="' + t.id + '">Detail</button></div></div>' +
      '<div class="progress" aria-label="Progres ' + STATUS[t.status].pct + ' persen"><span style="width:' + STATUS[t.status].pct + '%"></span></div>' +
      '</article>';
  }

  function renderTugas(root) {
    var list = myProjects().filter(function (p) { return p.status === 'active'; });
    if (!tugasState.init) {
      var qs = new URLSearchParams(location.search);
      tugasState.q = qs.get('q') || '';
      tugasState.status = qs.get('status') || '';
      if (qs.get('pid')) tugasState.pid = +qs.get('pid');
      tugasState.init = true;
    }
    if (!tugasState.pid || !projectOf(tugasState.pid)) tugasState.pid = list[0] ? list[0].id : 0;
    var pid = tugasState.pid;
    if (!pid) { root.innerHTML = '<p class="muted">Belum ada proyek.</p>'; return; }

    var isLeader = membership(pid, ME) === 'leader';
    var all = projectTasks(pid);
    var f = tugasState;
    var items = all.filter(function (t) {
      if (f.q && t.title.toLowerCase().indexOf(f.q.toLowerCase()) === -1) return false;
      if (f.status && t.status !== f.status) return false;
      if (f.tab === 'individu' && t.assignees.length !== 1) return false;
      if (f.tab === 'kelompok' && t.assignees.length < 2) return false;
      if (f.tab === 'deadline' && (t.status === 'done' || !t.deadline)) return false;
      return true;
    });
    if (f.tab === 'deadline') {
      items.sort(function (a, b) { return a.deadline.localeCompare(b.deadline); });
    } else {
      items.sort(function (a, b) {
        if ((a.status === 'done') !== (b.status === 'done')) return a.status === 'done' ? 1 : -1;
        return (a.deadline || '9999').localeCompare(b.deadline || '9999');
      });
    }

    var projOpts = list.map(function (p) {
      return '<option value="' + p.id + '"' + (p.id === pid ? ' selected' : '') + '>' + esc(p.title) + '</option>';
    }).join('');
    var statusOpts = '<option value="">Semua status</option>' + Object.keys(STATUS).map(function (k) {
      return '<option value="' + k + '"' + (f.status === k ? ' selected' : '') + '>' + STATUS[k].label + '</option>';
    }).join('');
    var tabs = Object.keys(TAB_LABEL).map(function (k) {
      return '<button type="button" class="tab' + (f.tab === k ? ' active' : '') + '" data-action="tab" data-tab="' + k + '">' + TAB_LABEL[k] + '</button>';
    }).join('');

    var done = all.filter(function (t) { return t.status === 'done'; }).length;
    var overall = all.length ? Math.round(done / all.length * 100) : 0;
    var counts = Object.keys(STATUS).map(function (k) {
      return '<li><span>' + statusChip(k) + '</span><strong>' + all.filter(function (t) { return t.status === k; }).length + '</strong></li>';
    }).join('');
    var nextTask = all.filter(function (t) { return t.status !== 'done' && t.deadline; })
      .sort(function (a, b) { return a.deadline.localeCompare(b.deadline); })[0];

    root.innerHTML =
      '<div class="page-head"><div><h1>Tugas</h1><p class="muted">Kelola tugas, deadline, dan pengumpulan hasil.</p></div>' +
      '<div class="row"><select class="select-sm" data-filter="tugas-project" aria-label="Pilih proyek">' + projOpts + '</select>' +
      (isLeader ? '<button class="btn btn-primary" data-action="new-task" data-pid="' + pid + '">+ Tugas Baru</button>' : '') +
      '</div></div>' +
      '<div class="grid-2">' +
      '<section class="card"><div class="filter-bar"><div class="tabs">' + tabs + '</div>' +
      '<div class="row"><input class="search search-sm" data-filter="tugas-q" type="search" placeholder="Cari judul tugas" value="' + esc(f.q) + '" aria-label="Cari judul tugas">' +
      '<select data-filter="tugas-status" aria-label="Filter status">' + statusOpts + '</select></div></div>' +
      '<div class="task-list">' + (items.length ? items.map(taskCard).join('') : '<p class="muted">Tidak ada tugas yang cocok dengan filter.</p>') + '</div>' +
      '</section>' +
      '<aside class="card"><h2>Ringkasan</h2><p class="small muted">' + esc(projectOf(pid).title) + '</p>' +
      '<div class="progress" style="margin:10px 0"><span style="width:' + overall + '%"></span></div>' +
      '<p class="small muted">' + done + ' dari ' + all.length + ' tugas selesai (' + overall + '%)</p>' +
      '<ul class="legend" style="margin-top:12px">' + counts + '</ul>' +
      '<h3 style="margin-top:16px">Deadline terdekat</h3>' +
      (nextTask ? '<p><button class="link-btn" data-action="open-task" data-id="' + nextTask.id + '">' + esc(nextTask.title) + '</button><br>' +
        '<span class="small muted">' + fmtDate(nextTask.deadline) + (isLate(nextTask) ? lateChip() : '') + '</span></p>' : '<p class="muted small">Tidak ada.</p>') +
      '</aside></div>';
  }

  // ---------- Halaman: laporan ----------
  var laporanPid = 0;
  var lastReportRows = [];
  function renderLaporan(root) {
    var list = myProjects();
    var qs = new URLSearchParams(location.search);
    if (!laporanPid && qs.get('pid')) laporanPid = +qs.get('pid');
    if (!laporanPid || !projectOf(laporanPid)) laporanPid = list[0] ? list[0].id : 0;
    var p = projectOf(laporanPid);
    if (!p) { root.innerHTML = '<p class="muted">Belum ada proyek.</p>'; return; }

    var tasks = projectTasks(p.id);
    var taskIds = tasks.map(function (t) { return t.id; });
    lastReportRows = p.members.map(function (m) {
      var assigned = tasks.filter(function (t) { return isAssignee(t, m.user_id); });
      var done = assigned.filter(function (t) { return t.status === 'done'; }).length;
      var subs = state.submissions.filter(function (s) { return s.user_id === m.user_id && taskIds.indexOf(s.task_id) !== -1; });
      return {
        name: nameOf(m.user_id), id: m.user_id, role: m.role === 'leader' ? 'Ketua' : 'Anggota',
        assigned: assigned.length, done: done, overdue: assigned.filter(isLate).length,
        submissions: subs.length, revisions: subs.filter(function (s) { return s.status === 'revision'; }).length,
        activities: state.activity.filter(function (a) { return a.user_id === m.user_id && a.project_id === p.id; }).length,
        rate: assigned.length ? Math.round(done / assigned.length * 100) : 0
      };
    });
    var doneAll = tasks.filter(function (t) { return t.status === 'done'; }).length;
    var overall = tasks.length ? Math.round(doneAll / tasks.length * 100) : 0;
    var projOpts = list.map(function (x) {
      return '<option value="' + x.id + '"' + (x.id === p.id ? ' selected' : '') + '>' + esc(x.title) + '</option>';
    }).join('');
    var rows = lastReportRows.map(function (r) {
      return '<tr><td>' + avatar(r.id, r.name) + ' ' + esc(r.name) + '</td><td>' + r.role + '</td><td>' + r.assigned +
        '</td><td>' + r.done + '</td><td>' + r.overdue + '</td><td>' + r.submissions + '</td><td>' + r.revisions +
        '</td><td>' + r.activities + '</td><td style="min-width:140px"><div class="row"><div class="progress" style="flex:1">' +
        '<span style="width:' + r.rate + '%"></span></div><strong>' + r.rate + '%</strong></div></td></tr>';
    }).join('');
    var acts = state.activity.filter(function (a) { return a.project_id === p.id; }).slice(0, 10)
      .map(function (a) {
        return '<li>' + avatar(a.user_id, nameOf(a.user_id)) + '<div><div>' + esc(a.text) + '</div><div class="time">' +
          esc(nameOf(a.user_id)) + ' · ' + fmtTime(a.created_at) + '</div></div></li>';
      }).join('');

    root.innerHTML =
      '<div class="page-head"><div><h1>Laporan Kontribusi</h1><p class="muted">Rekap berdasarkan aktivitas yang tercatat.</p></div>' +
      '<div class="row"><select class="select-sm" data-filter="laporan-project" aria-label="Pilih proyek">' + projOpts + '</select>' +
      '<button class="btn btn-outline" data-action="export-csv">Unduh CSV</button></div></div>' +
      '<section class="card"><div class="row" style="justify-content:space-between;margin-bottom:12px">' +
      '<div><h2>' + esc(p.title) + '</h2><p class="small muted">' + doneAll + ' dari ' + tasks.length + ' tugas selesai</p></div>' +
      '<div style="min-width:200px"><div class="progress"><span style="width:' + overall + '%"></span></div>' +
      '<p class="small muted" style="text-align:right">' + overall + '% selesai</p></div></div>' +
      '<p class="disclaimer">' + esc(DISCLAIMER) + '</p></section>' +
      '<section class="card"><h2 style="margin-bottom:12px">Kontribusi per anggota</h2><div class="table-wrap"><table>' +
      '<thead><tr><th>Anggota</th><th>Peran</th><th>Ditugaskan</th><th>Selesai</th><th>Terlambat</th><th>Pengumpulan</th><th>Revisi</th><th>Aktivitas</th><th>Tingkat selesai</th></tr></thead>' +
      '<tbody>' + rows + '</tbody></table></div></section>' +
      '<section class="card"><h2 style="margin-bottom:12px">Aktivitas terbaru</h2>' +
      (acts ? '<ul class="activity-list">' + acts + '</ul>' : '<p class="muted">Belum ada aktivitas.</p>') + '</section>';
  }

  function exportCsv() {
    if (!lastReportRows.length) return;
    var head = ['Nama', 'Peran', 'Ditugaskan', 'Selesai', 'Terlambat', 'Pengumpulan', 'Revisi', 'Aktivitas', 'Tingkat selesai (%)'];
    var lines = [head.join(',')].concat(lastReportRows.map(function (r) {
      return [r.name, r.role, r.assigned, r.done, r.overdue, r.submissions, r.revisions, r.activities, r.rate]
        .map(function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; }).join(',');
    }));
    var blob = new Blob(['﻿' + lines.join('\n')], { type: 'text/csv;charset=utf-8' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'laporan-kontribusi.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
  }

  // ---------- Render dan event ----------
  var PAGES = { dashboard: renderDashboard, proyek: renderProyek, tugas: renderTugas, laporan: renderLaporan };
  function render() {
    var root = $('#content');
    var fn = PAGES[document.body.dataset.page];
    if (root && fn) fn(root);
  }

  function onClick(e) {
    var modal = $('#modal');
    if (modal && e.target === modal) { closeModal(); return; }
    var el = e.target.closest ? e.target.closest('[data-action]') : null;
    if (!el) return;
    switch (el.dataset.action) {
      case 'close-modal': closeModal(); break;
      case 'open-task': openTaskModal(+el.dataset.id); break;
      case 'new-task': openNewTaskModal(+el.dataset.pid || 0); break;
      case 'new-project': openNewProjectModal(); break;
      case 'open-project': openProjectModal(+el.dataset.id); break;
      case 'tab': tugasState.tab = el.dataset.tab; render(); break;
      case 'dash-tab': dashTab = el.dataset.tab; render(); break;
      case 'export-csv': exportCsv(); break;
      case 'toggle-nav':
        var sb = $('#sidebar');
        if (sb) sb.classList.toggle('open');
        break;
      case 'reset':
        resetData();
        render();
        toast('Data contoh dikembalikan.');
        break;
      case 'logout':
        try { localStorage.removeItem(SESSION_KEY); } catch (x) { /* abaikan */ }
        location.href = 'login.html';
        break;
    }
  }

  function onChange(e) {
    var f = e.target.dataset ? e.target.dataset.filter : '';
    if (!f) return;
    if (f === 'tugas-project') { tugasState.pid = +e.target.value; render(); }
    else if (f === 'tugas-status') { tugasState.status = e.target.value; render(); }
    else if (f === 'tugas-q') { tugasState.q = e.target.value.trim(); render(); }
    else if (f === 'laporan-project') { laporanPid = +e.target.value; render(); }
    else if (f === 'modal-project') {
      var box = $('#assignee-box');
      if (box) box.innerHTML = assigneeChecks(+e.target.value);
    }
  }

  function onSubmit(e) {
    var form = e.target;
    var kind = form.dataset ? form.dataset.form : '';
    if (!kind) return;
    e.preventDefault();
    var fd = new FormData(form);
    var err = null;

    if (kind === 'search') {
      var q = String(fd.get('q') || '').trim();
      location.href = 'tugas.html' + (q ? '?q=' + encodeURIComponent(q) : '');
      return;
    }
    if (kind === 'login') {
      err = doLogin(fd);
      if (!err) { location.href = 'index.html'; return; }
    } else if (kind === 'register') {
      err = doRegister(fd);
      if (!err) { location.href = 'index.html'; return; }
    } else if (kind === 'new-task') {
      err = addTask({
        project_id: +fd.get('project_id'),
        title: String(fd.get('title') || '').trim(),
        description: String(fd.get('description') || '').trim(),
        priority: String(fd.get('priority') || 'medium'),
        deadline: String(fd.get('deadline') || '') || null,
        assignees: fd.getAll('assignees').map(Number)
      });
      if (!err) { tugasState.pid = +fd.get('project_id'); toast('Tugas dibuat.'); }
    } else if (kind === 'set-status') {
      var t1 = taskOf(+fd.get('task_id'));
      err = t1 ? setStatus(t1, String(fd.get('status'))) : 'Tugas tidak ditemukan.';
      if (!err) toast('Status diperbarui.');
    } else if (kind === 'submit-work') {
      var t2 = taskOf(+fd.get('task_id'));
      var file = fd.get('file');
      var type = String(fd.get('type') || 'link');
      err = t2 ? submitWork(t2, {
        type: type,
        link: String(fd.get('link') || '').trim(),
        fileName: file && file.name ? file.name : '',
        note: String(fd.get('note') || '').trim()
      }) : 'Tugas tidak ditemukan.';
      if (!err) toast('Hasil pekerjaan dikirim.');
    } else if (kind === 'review') {
      var sub = state.submissions.find(function (s) { return s.id === +fd.get('submission_id'); });
      var decision = e.submitter && e.submitter.value ? e.submitter.value : '';
      err = sub ? reviewSubmission(sub, decision, String(fd.get('note') || '').trim()) : 'Pengumpulan tidak ditemukan.';
      if (!err) toast(decision === 'approved' ? 'Hasil disetujui.' : 'Revisi diminta.');
    } else if (kind === 'new-project') {
      err = addProject({
        title: String(fd.get('title') || '').trim(),
        description: String(fd.get('description') || '').trim(),
        deadline: String(fd.get('deadline') || '') || null
      });
      if (!err) toast('Proyek dibuat.');
    }

    if (err) { toast(err, 'error'); return; }
    closeModal();
    render();
  }

  function init() {
    var page = document.body.dataset.page;
    document.addEventListener('click', onClick);
    document.addEventListener('change', onChange);
    document.addEventListener('submit', onSubmit);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeModal();
      if (e.key === 'Enter' && e.target && e.target.matches && e.target.matches('[data-action][tabindex]')) e.target.click();
    });
    if (page === 'auth') return; // login dan register: hanya form, tanpa shell
    if (!sessionOk()) { location.replace('login.html'); return; }
    load();
    renderShell();
    render();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
