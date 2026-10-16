<?php
/**
 * Sidebar Navigation Template
 * File: includes/sidebar.php
 * FITUR:
 * - Penamaan menu standar: Dashboard untuk Admin, Guru, dan Siswa.
 * - Integrasi menu Ekspor Nilai Spreadsheet.
 */
require_once __DIR__ . '/../config/app.php';
$role = $currentUser['role'] ?? 'siswa';
$currentScript = basename($_SERVER['PHP_SELF']);

$roleLabels = [
    'admin' => 'Administrator',
    'guru' => 'Guru Pengajar',
    'siswa' => 'Siswa',
    'staff' => 'Staff Administrasi'
];

$profileLinks = [
    'admin' => BASE_URL . '/admin/profile.php',
    'guru'  => BASE_URL . '/guru/profile.php',
    'siswa' => BASE_URL . '/siswa/profile.php'
];
$myProfileUrl = $profileLinks[$role] ?? (BASE_URL . '/siswa/profile.php');
?>

<aside class="app-sidebar">
  <div class="brand">
    <div class="logo-badge">∑</div>
    <div class="logo-text">Elearn<span>Math</span></div>
  </div>

  <div class="side-role">
    <span class="badge-role"><?= $roleLabels[$role] ?? ucfirst($role) ?></span>
  </div>

  <nav class="sidebar-nav">
    <?php if ($role === 'siswa'): ?>
      <!-- Menu Siswa: Navigasi Dashboard -->
      <a href="<?= BASE_URL ?>/siswa/index.php" class="nav-item <?= $currentScript == 'index.php' ? 'active' : '' ?>">
        <i data-feather="layout"></i>
        <span>Dashboard</span>
      </a>
      <a href="<?= BASE_URL ?>/siswa/jadwal.php" class="nav-item <?= $currentScript == 'jadwal.php' ? 'active' : '' ?>">
        <i data-feather="calendar"></i>
        <span>Jadwal Pelajaran</span>
      </a>
      <a href="<?= BASE_URL ?>/siswa/materi.php" class="nav-item <?= in_array($currentScript, ['materi.php', 'materi_detail.php']) ? 'active' : '' ?>">
        <i data-feather="book-open"></i>
        <span>Materi Pelajaran</span>
      </a>
      <a href="<?= BASE_URL ?>/siswa/lkpd.php" class="nav-item <?= in_array($currentScript, ['lkpd.php']) ? 'active' : '' ?>">
        <i data-feather="file-text"></i>
        <span>LKPD Mandiri</span>
      </a>
      <a href="<?= BASE_URL ?>/siswa/latihan.php" class="nav-item <?= in_array($currentScript, ['latihan.php']) ? 'active' : '' ?>">
        <i data-feather="edit-3"></i>
        <span>Latihan Soal</span>
      </a>
      <a href="<?= BASE_URL ?>/siswa/evaluasi.php" class="nav-item <?= in_array($currentScript, ['evaluasi.php']) ? 'active' : '' ?>">
        <i data-feather="target"></i>
        <span>Evaluasi Akhir</span>
      </a>
      <a href="<?= BASE_URL ?>/siswa/hasil.php" class="nav-item <?= $currentScript == 'hasil.php' ? 'active' : '' ?>">
        <i data-feather="award"></i>
        <span>Hasil & Nilai</span>
      </a>
      <a href="<?= BASE_URL ?>/siswa/profile.php" class="nav-item <?= $currentScript == 'profile.php' ? 'active' : '' ?>">
        <i data-feather="user"></i>
        <span>Profil & Foto Saya</span>
      </a>

    <?php elseif ($role === 'guru'): ?>
      <!-- Menu Guru: Navigasi Dashboard -->
      <a href="<?= BASE_URL ?>/guru/index.php" class="nav-item <?= $currentScript == 'index.php' ? 'active' : '' ?>">
        <i data-feather="layout"></i>
        <span>Dashboard</span>
      </a>
      <a href="<?= BASE_URL ?>/guru/siswa.php" class="nav-item <?= $currentScript == 'siswa.php' ? 'active' : '' ?>">
        <i data-feather="users"></i>
        <span>Daftar Siswa Kelas</span>
      </a>
      <a href="<?= BASE_URL ?>/guru/koreksi.php" class="nav-item <?= $currentScript == 'koreksi.php' ? 'active' : '' ?>">
        <i data-feather="check-square"></i>
        <span>Koreksi AI & Manual</span>
      </a>
      <a href="<?= BASE_URL ?>/guru/deteksi.php" class="nav-item <?= $currentScript == 'deteksi.php' ? 'active' : '' ?>">
        <i data-feather="alert-triangle"></i>
        <span>Deteksi Keluar Tab</span>
      </a>
      <a href="<?= BASE_URL ?>/guru/nilai.php" class="nav-item <?= $currentScript == 'nilai.php' ? 'active' : '' ?>">
        <i data-feather="bar-chart-2"></i>
        <span>Rekap Nilai Siswa</span>
      </a>
      <a href="<?= BASE_URL ?>/guru/export_spreadsheet.php" class="nav-item <?= in_array($currentScript, ['export_spreadsheet.php', 'export_grades.php']) ? 'active' : '' ?>">
        <i data-feather="download"></i>
        <span>Ekspor Spreadsheet</span>
      </a>
      <a href="<?= BASE_URL ?>/guru/materi.php" class="nav-item <?= $currentScript == 'materi.php' ? 'active' : '' ?>">
        <i data-feather="book"></i>
        <span>Kelola Materi</span>
      </a>
      <a href="<?= BASE_URL ?>/guru/lkpd.php" class="nav-item <?= $currentScript == 'lkpd.php' ? 'active' : '' ?>">
        <i data-feather="file-text"></i>
        <span>Kelola LKPD</span>
      </a>
      <a href="<?= BASE_URL ?>/guru/latihan.php" class="nav-item <?= in_array($currentScript, ['latihan.php', 'edit_question.php']) ? 'active' : '' ?>">
        <i data-feather="edit"></i>
        <span>Kelola Latihan</span>
      </a>
      <a href="<?= BASE_URL ?>/guru/evaluasi.php" class="nav-item <?= $currentScript == 'evaluasi.php' ? 'active' : '' ?>">
        <i data-feather="target"></i>
        <span>Kelola Evaluasi</span>
      </a>
      <a href="<?= BASE_URL ?>/guru/jadwal.php" class="nav-item <?= $currentScript == 'jadwal.php' ? 'active' : '' ?>">
        <i data-feather="calendar"></i>
        <span>Jadwal Mengajar (CRUD)</span>
      </a>
      <a href="<?= BASE_URL ?>/guru/profile.php" class="nav-item <?= $currentScript == 'profile.php' ? 'active' : '' ?>">
        <i data-feather="user"></i>
        <span>Edit Profil Guru</span>
      </a>

    <?php elseif ($role === 'admin'): ?>
      <!-- Menu Admin: Navigasi Dashboard -->
      <a href="<?= BASE_URL ?>/admin/index.php" class="nav-item <?= $currentScript == 'index.php' ? 'active' : '' ?>">
        <i data-feather="grid"></i>
        <span>Dashboard</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/admit_user.php" class="nav-item <?= $currentScript == 'admit_user.php' ? 'active' : '' ?>">
        <i data-feather="user-check"></i>
        <span>Persetujuan Pengguna</span>
        <?php 
          try {
            $dbSide = getDB();
            $sidePending = (int)$dbSide->query("SELECT COUNT(*) FROM users WHERE status_approval = 'PENDING'")->fetchColumn();
            if ($sidePending > 0): 
        ?>
          <span style="background: #E3543F; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 999px; margin-left: auto;">
            <?= $sidePending ?>
          </span>
        <?php 
            endif;
          } catch(Exception $e) {} 
        ?>
      </a>
      <a href="<?= BASE_URL ?>/admin/users.php" class="nav-item <?= $currentScript == 'users.php' ? 'active' : '' ?>">
        <i data-feather="users"></i>
        <span>Manajemen Pengguna</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/monitor_tab.php" class="nav-item <?= $currentScript == 'monitor_tab.php' ? 'active' : '' ?>">
        <i data-feather="alert-triangle"></i>
        <span>Monitor Keluar Tab</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/teacher_logs.php" class="nav-item <?= $currentScript == 'teacher_logs.php' ? 'active' : '' ?>">
        <i data-feather="activity"></i>
        <span>Aktivitas Login Guru dan Siswa</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/schedules.php" class="nav-item <?= $currentScript == 'schedules.php' ? 'active' : '' ?>">
        <i data-feather="calendar"></i>
        <span>Atur Jadwal Guru</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/classes.php" class="nav-item <?= $currentScript == 'classes.php' ? 'active' : '' ?>">
        <i data-feather="home"></i>
        <span>Kelola Kelas</span>
      </a>
      <a href="<?= BASE_URL ?>/guru/export_spreadsheet.php" class="nav-item <?= in_array($currentScript, ['export_spreadsheet.php', 'export.php']) ? 'active' : '' ?>">
        <i data-feather="file-text"></i>
        <span>Ekspor Rekap Nilai</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/profile.php" class="nav-item <?= $currentScript == 'profile.php' ? 'active' : '' ?>">
        <i data-feather="user"></i>
        <span>Edit Profil Admin</span>
      </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <?php if (!empty($currentUser['foto'])): ?>
      <img src="<?= BASE_URL . '/' . htmlspecialchars($currentUser['foto']) ?>" alt="Foto" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--orange);">
    <?php else: ?>
      <div class="avatar">
        <?= strtoupper(substr($currentUser['nama_lengkap'] ?? 'U', 0, 1)) ?>
      </div>
    <?php endif; ?>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($currentUser['nama_lengkap'] ?? '') ?></div>
      <a href="<?= BASE_URL ?>/auth/logout.php" class="logout-btn">Keluar Sistem</a>
    </div>
  </div>
</aside>
