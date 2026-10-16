<?php
/**
 * Dashboard Administrator Utama
 * File: admin/index.php
 * FITUR:
 * 1. Navigasi Standar "Dashboard" (Pengganti nama lama Ringkasan).
 * 2. Metrik Pengguna, Guru, Siswa, dan Persetujuan Pendaftaran Kelas (Admit Class).
 * 3. Pantauan Real-Time Deteksi Keluar Tab Siswa (Integrasi tab_switch_logs).
 * 4. Audit Log Presensi Guru Masuk (Activity Logs).
 * 5. Pintasan Cepat Admit Class, Pengguna, Jadwal, dan Ekspor Spreadsheet.
 */
require_once __DIR__ . '/../config/app.php';
require_role('admin');

$pageTitle = 'Dashboard - ElearnMath Admin';
$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// 1. Hitung metrik pengguna
$stmtCounts = $db->query("
    SELECT role, COUNT(*) as total 
    FROM users 
    GROUP BY role
");
$roleCounts = $stmtCounts->fetchAll(PDO::FETCH_KEY_PAIR);

$countGuru = $roleCounts['guru'] ?? 0;
$countSiswa = $roleCounts['siswa'] ?? 0;
$countStaff = $roleCounts['staff'] ?? 0;

// 2. Hitung pendaftar siswa yang butuh admit & kelas guru pending
$stmtPendingSiswa = $db->query("SELECT COUNT(*) FROM users WHERE status_approval = 'PENDING'");
$pendingSiswaCount = (int)$stmtPendingSiswa->fetchColumn();

$stmtPendingKelas = $db->query("SELECT COUNT(*) FROM classes WHERE status = 'PENDING_APPROVAL'");
$pendingKelasCount = (int)$stmtPendingKelas->fetchColumn();

$totalPendingAdmit = $pendingSiswaCount + $pendingKelasCount;

// 3. Hitung login guru & murid hari ini
$stmtTodayLogins = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN role = 'guru' THEN 1 ELSE 0 END) as guru_total,
        SUM(CASE WHEN role = 'siswa' THEN 1 ELSE 0 END) as siswa_total
    FROM activity_logs 
    WHERE role IN ('guru', 'siswa') AND DATE(login_time) = CURDATE()
");
$todayLoginStats = $stmtTodayLogins->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'guru_total' => 0, 'siswa_total' => 0];

// 4. Hitung pelanggaran keluar tab hari ini
$stmtTodaySwitches = $db->query("
    SELECT COUNT(*) FROM tab_switch_logs 
    WHERE DATE(last_switched_at) = CURDATE()
");
$todaySwitchesCount = (int)$stmtTodaySwitches->fetchColumn();

// 5. Ambil 8 log deteksi keluar tab siswa terbaru
$stmtTabLogs = $db->query("
    SELECT t.*, u.foto
    FROM tab_switch_logs t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.last_switched_at DESC 
    LIMIT 8
");
$latestTabLogs = $stmtTabLogs->fetchAll();

// 6. Filter & Riwayat Aktivitas Login Guru dan Siswa pada Dashboard
$logSearch = trim($_GET['log_search'] ?? '');
$logRole = trim($_GET['log_role'] ?? '');
$logDate = trim($_GET['log_date'] ?? '');

$logSql = "
    SELECT l.*, u.username, u.foto as user_foto, COALESCE(NULLIF(l.kelas, ''), u.kelas, '-') as user_kelas
    FROM activity_logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE l.role IN ('guru', 'siswa')
";
$logParams = [];

if (!empty($logSearch)) {
    $logSql .= " AND (l.nama LIKE ? OR l.email LIKE ? OR u.username LIKE ? OR l.kelas LIKE ? OR l.ip_address LIKE ?)";
    $logParams[] = "%$logSearch%";
    $logParams[] = "%$logSearch%";
    $logParams[] = "%$logSearch%";
    $logParams[] = "%$logSearch%";
    $logParams[] = "%$logSearch%";
}

if (!empty($logRole) && in_array($logRole, ['guru', 'siswa'])) {
    $logSql .= " AND l.role = ?";
    $logParams[] = $logRole;
}

if (!empty($logDate)) {
    $logSql .= " AND DATE(l.login_time) = ?";
    $logParams[] = $logDate;
}

$logSql .= " ORDER BY l.login_time DESC LIMIT 15";
$stmtLogs = $db->prepare($logSql);
$stmtLogs->execute($logParams);
$latestActivityLogs = $stmtLogs->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <?php if (!empty($flash)): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="hero-banner">
      <h1 class="hero-title">Dashboard Administrator 🛡️</h1>
      <p class="hero-subtitle">
        Pusat kendali ekosistem ElearnMath: kelola akun resmi, pantau persetujuan kelas (Admit Class), awasi integritas pengerjaan ujian siswa secara real-time, dan pantau aktivitas guru.
      </p>
    </div>

    <!-- Statistik Metrik Dashboard -->
    <div class="grid-cards">
      <!-- Total Guru -->
      <div class="glass-card" style="display: flex; align-items: center; gap: 16px;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: var(--blue-100); color: var(--blue-600); display: flex; align-items: center; justify-content: center;">
          <i data-feather="user-check" style="width: 24px; height: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 12px; color: var(--muted); font-weight: 600;">TOTAL GURU PENGAJAR</div>
          <div style="font-size: 24px; font-weight: 800; color: var(--blue-800);"><?= $countGuru ?> Guru</div>
        </div>
      </div>

      <!-- Total Siswa -->
      <div class="glass-card" style="display: flex; align-items: center; gap: 16px;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: var(--orange-100); color: var(--orange-dark); display: flex; align-items: center; justify-content: center;">
          <i data-feather="users" style="width: 24px; height: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 12px; color: var(--muted); font-weight: 600;">TOTAL SISWA TERDAFTAR</div>
          <div style="font-size: 24px; font-weight: 800; color: var(--ink);"><?= $countSiswa ?> Siswa</div>
        </div>
      </div>

      <!-- Menunggu Admit Class -->
      <div class="glass-card" style="display: flex; align-items: center; gap: 16px; border: <?= $totalPendingAdmit > 0 ? '2px solid #F59E0B' : '1px solid var(--glass-border)' ?>;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: #FEF3C7; color: #B45309; display: flex; align-items: center; justify-content: center;">
          <i data-feather="user-plus" style="width: 24px; height: 24px;"></i>
        </div>
        <div style="flex: 1;">
          <div style="font-size: 12px; color: var(--muted); font-weight: 600;">MENUNGGU ADMIT CLASS</div>
          <div style="font-size: 24px; font-weight: 800; color: <?= $totalPendingAdmit > 0 ? '#B45309' : 'var(--ink)' ?>;">
            <?= $totalPendingAdmit ?> Pengajuan
          </div>
          <div style="font-size: 11px; color: var(--muted);">
            Siswa: <?= $pendingSiswaCount ?> | Kelas Guru: <?= $pendingKelasCount ?>
          </div>
        </div>
      </div>

      <!-- Pelanggaran Keluar Tab Hari Ini -->
      <div class="glass-card" style="display: flex; align-items: center; gap: 16px; border: <?= $todaySwitchesCount > 0 ? '2px solid #EF4444' : '1px solid var(--glass-border)' ?>;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: var(--danger-bg); color: var(--danger); display: flex; align-items: center; justify-content: center;">
          <i data-feather="alert-triangle" style="width: 24px; height: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 12px; color: var(--muted); font-weight: 600;">PELANGGARAN TAB HARI INI</div>
          <div style="font-size: 24px; font-weight: 800; color: var(--danger);"><?= $todaySwitchesCount ?> Kejadian</div>
          <div style="font-size: 11px; color: var(--muted);">Pantauan Realtime Ujian</div>
        </div>
      </div>
    </div>

    <!-- BANNER PERSETUJUAN KELAS / SISWA JIKA ADA PENDING -->
    <?php if ($totalPendingAdmit > 0): ?>
      <div style="background: #FFFBEB; border: 1px solid #FCD34D; border-radius: var(--radius-sm); padding: 16px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <span style="font-size: 24px;">🔔</span>
          <div>
            <div style="font-weight: 800; color: #92400E; font-size: 14px;">
              Terdapat <?= $totalPendingAdmit ?> Permohonan Masuk / Kelas Baru Menunggu Admit
            </div>
            <div style="font-size: 12.5px; color: #B45309;">
              <?= $pendingSiswaCount ?> pendaftaran siswa dan <?= $pendingKelasCount ?> pengajuan kelas baru guru menunggu persetujuan Admin agar fitur kelas dapat dibuka.
            </div>
          </div>
        </div>
        <a href="<?= BASE_URL ?>/admin/admit_user.php" class="btn btn-primary" style="font-size: 12.5px; padding: 8px 18px;">
          Buka Menu Admit Class &rarr;
        </a>
      </div>
    <?php endif; ?>

    <!-- ============================================================== -->
    <!-- FITUR: DETEKSI KELUAR TAB SISWA REAL-TIME                     -->
    <!-- ============================================================== -->
    <div class="glass-card" style="margin-bottom: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="width: 10px; height: 10px; border-radius: 50%; background: #EF4444; animation: pulse 1.5s infinite;"></span>
            <h2 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0;">
              Deteksi Keluar Tab Siswa Real-time
            </h2>
          </div>
          <p style="font-size: 12.5px; color: var(--muted); margin: 4px 0 0 0;">
            Memantau perpindahan tab browser atau jendela yang dilakukan siswa saat sedang mengerjakan tugas ujian.
          </p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
          <span id="tabSyncStatus" style="font-size: 11px; color: var(--muted); font-weight: 600;">
            <i data-feather="refresh-cw" style="width: 12px; height: 12px; vertical-align: middle;"></i> Live sync aktif
          </span>
          <a href="<?= BASE_URL ?>/admin/monitor_tab.php" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;">
            Lihat Semua Log Tab &rarr;
          </a>
        </div>
      </div>

      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th>Nama Siswa</th>
              <th>Kelas</th>
              <th>Ujian / Modul</th>
              <th>Frekuensi Pindah Tab</th>
              <th>Waktu Terakhir</th>
              <th>Keterangan Pelanggaran</th>
            </tr>
          </thead>
          <tbody id="tabSwitchTableBody">
            <?php if (empty($latestTabLogs)): ?>
              <tr>
                <td colspan="6" style="text-align: center; color: var(--muted); padding: 28px;">
                  Tidak ada catatan pelanggaran keluar tab ujian pada hari ini.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($latestTabLogs as $log): ?>
                <tr>
                  <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                      <?php if (!empty($log['foto'])): ?>
                        <img src="<?= BASE_URL . '/' . htmlspecialchars($log['foto']) ?>" alt="Foto" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                      <?php else: ?>
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--danger); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">
                          <?= strtoupper(substr($log['nama_siswa'], 0, 1)) ?>
                        </div>
                      <?php endif; ?>
                      <div>
                        <strong><?= htmlspecialchars($log['nama_siswa']) ?></strong>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge-role" style="font-size: 11px;"><?= htmlspecialchars($log['kelas'] ?: '-') ?></span>
                  </td>
                  <td>
                    <span style="font-weight: 600; color: var(--blue-800); font-size: 13px;">
                      <?= htmlspecialchars($log['assessment_title']) ?>
                    </span>
                  </td>
                  <td>
                    <span style="background: #FEE2E2; color: #DC2626; font-weight: 800; padding: 4px 10px; border-radius: 999px; font-size: 12px;">
                      <?= (int)$log['switch_count'] ?> kali keluar tab
                    </span>
                  </td>
                  <td style="font-size: 12px; color: var(--muted);">
                    <?= date('d M Y, H:i:s', strtotime($log['last_switched_at'])) ?> WIB
                  </td>
                  <td style="font-size: 12px; color: var(--ink);">
                    <?= htmlspecialchars($log['notes'] ?: 'Pindah tab peramban saat pengerjaan') ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- FITUR: AKTIVITAS LOGIN GURU DAN SISWA (AUDIT TRAIL LENGKAP)    -->
    <!-- ============================================================== -->
    <div class="glass-card" id="log-section" style="margin-bottom: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
        <div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <i data-feather="activity" style="color: var(--blue-600); width: 20px; height: 20px;"></i>
            <h2 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0;">
              Aktivitas Login Guru dan Siswa
            </h2>
          </div>
          <p style="font-size: 12.5px; color: var(--muted); margin: 4px 0 0 0;">
            Hari ini tercatat total <strong><?= (int)$todayLoginStats['total'] ?> sesi masuk</strong> (Guru: <?= (int)$todayLoginStats['guru_total'] ?>, Siswa: <?= (int)$todayLoginStats['siswa_total'] ?>). Log terekam otomatis setiap kali akun berhasil login.
          </p>
        </div>
        <a href="<?= BASE_URL ?>/admin/teacher_logs.php" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;">
          Buka Halaman Riwayat Audit Lengkap &rarr;
        </a>
      </div>

      <!-- Filter Pencarian, Role, dan Tanggal -->
      <form method="GET" action="#log-section" style="background: #F8FAFC; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px 16px; margin-bottom: 16px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px;">
          <input type="text" name="log_search" class="form-input" style="width: 100%;" placeholder="Cari nama, email, username, kelas, atau IP..." value="<?= htmlspecialchars($logSearch) ?>">
        </div>
        <div style="min-width: 160px;">
          <select name="log_role" class="form-input" style="width: 100%;">
            <option value="">Semua Role (Guru & Siswa)</option>
            <option value="guru" <?= $logRole === 'guru' ? 'selected' : '' ?>>Guru</option>
            <option value="siswa" <?= $logRole === 'siswa' ? 'selected' : '' ?>>Siswa / Murid</option>
          </select>
        </div>
        <div style="min-width: 150px;">
          <input type="date" name="log_date" class="form-input" style="width: 100%;" value="<?= htmlspecialchars($logDate) ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 9px 16px; font-size: 12.5px;">
          <i data-feather="search"></i> Filter Log
        </button>
        <?php if (!empty($logSearch) || !empty($logRole) || !empty($logDate)): ?>
          <a href="<?= BASE_URL ?>/admin/index.php#log-section" class="btn btn-outline" style="padding: 9px 14px; font-size: 12.5px;">Reset</a>
        <?php endif; ?>
      </form>

      <!-- Tabel Aktivitas Login -->
      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th style="width: 45px;">No</th>
              <th>Nama Pengguna</th>
              <th>Role</th>
              <th>Email / Username</th>
              <th>Kelas Terkait</th>
              <th>Waktu & Tanggal Login</th>
              <th>Status</th>
              <th>Alamat IP & Perangkat</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($latestActivityLogs)): ?>
              <tr>
                <td colspan="8" style="text-align: center; color: var(--muted); padding: 28px;">
                  Tidak ada catatan riwayat login yang sesuai dengan kriteria filter.
                </td>
              </tr>
            <?php else: ?>
              <?php $no = 1; foreach ($latestActivityLogs as $lg): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 9px;">
                      <?php if (!empty($lg['user_foto'])): ?>
                        <img src="<?= BASE_URL . '/' . htmlspecialchars($lg['user_foto']) ?>" alt="Foto" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                      <?php else: ?>
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $lg['role'] === 'guru' ? 'var(--blue-600)' : 'var(--orange-dark)' ?>; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">
                          <?= strtoupper(substr($lg['nama'] ?: 'U', 0, 1)) ?>
                        </div>
                      <?php endif; ?>
                      <div>
                        <strong><?= htmlspecialchars($lg['nama']) ?></strong>
                        <?php if (!empty($lg['username'])): ?>
                          <div style="font-size: 11px; color: var(--muted);">@<?= htmlspecialchars($lg['username']) ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td>
                    <?php if ($lg['role'] === 'guru'): ?>
                      <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); font-weight: 700; font-size: 11px;">Guru</span>
                    <?php else: ?>
                      <span class="badge-role" style="background: var(--orange-100); color: var(--orange-dark); font-weight: 700; font-size: 11px;">Siswa</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span style="font-size: 12.5px;"><?= htmlspecialchars($lg['email'] ?: ($lg['username'] ?? '-')) ?></span>
                  </td>
                  <td>
                    <span class="badge-role" style="font-size: 11px;">
                      <?= htmlspecialchars($lg['user_kelas'] ?: '-') ?>
                    </span>
                  </td>
                  <td>
                    <div style="font-weight: 700; color: var(--blue-800); font-size: 12.5px;">
                      <?= date('d M Y', strtotime($lg['login_time'])) ?>
                    </div>
                    <div style="font-size: 11.5px; color: var(--muted);">
                      <?= date('H:i:s', strtotime($lg['login_time'])) ?> WIB
                    </div>
                  </td>
                  <td>
                    <span style="background: var(--success-bg); color: var(--success); font-weight: 700; font-size: 11px; padding: 3px 8px; border-radius: 4px;">
                      Berhasil
                    </span>
                  </td>
                  <td>
                    <code style="font-size: 11px;"><?= htmlspecialchars($lg['ip_address']) ?></code>
                    <div style="font-size: 11px; color: var(--muted); max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($lg['user_agent'] ?? '') ?>">
                      <?= htmlspecialchars(substr($lg['user_agent'] ?? 'Browser', 0, 28)) ?>...
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pintasan Cepat Administrator -->
    <div class="glass-card">
      <h2 style="font-size: 17px; font-weight: 700; color: var(--blue-800); margin: 0 0 14px 0;">
        Pintasan Cepat Administrator
      </h2>
      <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>/admin/admit_user.php" class="btn btn-primary">
          <i data-feather="user-check"></i> Persetujuan Pengguna & Kelas (Admit Class)
        </a>
        <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline">
          <i data-feather="users"></i> Kelola Seluruh Pengguna
        </a>
        <a href="<?= BASE_URL ?>/admin/monitor_tab.php" class="btn btn-outline">
          <i data-feather="alert-triangle"></i> Monitor Integritas Ujian
        </a>
        <a href="<?= BASE_URL ?>/admin/classes.php" class="btn btn-outline">
          <i data-feather="home"></i> Kelola Data Kelas
        </a>
        <a href="<?= BASE_URL ?>/guru/export_spreadsheet.php" class="btn btn-success">
          <i data-feather="download"></i> Ekspor Spreadsheet Rekap Nilai
        </a>
        <a href="<?= BASE_URL ?>/admin/profile.php" class="btn btn-outline">
          <i data-feather="user"></i> Edit Profil Admin
        </a>
      </div>
    </div>
  </div>
</div>

<script>
  // Polling Real-time Log Deteksi Keluar Tab setiap 10 detik
  setInterval(function() {
    fetch('<?= BASE_URL ?>/api/get_tab_switches.php')
      .then(r => r.json())
      .then(res => {
        if (res.success && res.data) {
          const tbody = document.getElementById('tabSwitchTableBody');
          if (!tbody) return;
          if (res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:28px;">Tidak ada catatan pelanggaran keluar tab ujian pada hari ini.</td></tr>';
            return;
          }

          let html = '';
          res.data.forEach(item => {
            const firstLetter = (item.nama_siswa || 'S').charAt(0).toUpperCase();
            const avatarHtml = item.foto 
              ? `<img src="<?= BASE_URL ?>/${escapeHtml(item.foto)}" alt="Foto" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">`
              : `<div style="width:32px;height:32px;border-radius:50%;background:var(--danger);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;">${firstLetter}</div>`;
            
            html += `
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    ${avatarHtml}
                    <div><strong>${escapeHtml(item.nama_siswa)}</strong></div>
                  </div>
                </td>
                <td><span class="badge-role" style="font-size:11px;">${escapeHtml(item.kelas || '-')}</span></td>
                <td><span style="font-weight:600;color:var(--blue-800);font-size:13px;">${escapeHtml(item.assessment_title)}</span></td>
                <td>
                  <span style="background:#FEE2E2;color:#DC2626;font-weight:800;padding:4px 10px;border-radius:999px;font-size:12px;">
                    ${item.switch_count} kali keluar tab
                  </span>
                </td>
                <td style="font-size:12px;color:var(--muted);">${item.last_switched_at}</td>
                <td style="font-size:12px;color:var(--ink);">${escapeHtml(item.notes || 'Pindah tab peramban saat pengerjaan')}</td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      })
      .catch(() => {});
  }, 10000);

  function escapeHtml(text) {
    if (!text) return '';
    return String(text)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
