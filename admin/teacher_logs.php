<?php
/**
 * Log Aktivitas Login Pengguna (Guru & Murid) - Audit Trail
 * File: admin/teacher_logs.php
 * FITUR:
 * - Admin dapat memantau riwayat login Guru dan Murid.
 * - Menampilkan nama pengguna, role, email/username, waktu & tanggal login, informasi kelas terkait, IP, dan browser.
 * - Fitur pencarian, filter role (Guru/Murid), filter tanggal, dan urutan login terbaru.
 */
require_once __DIR__ . '/../config/app.php';
require_role('admin');

$pageTitle = 'Aktivitas Login Guru dan Siswa - ElearnMath Admin';
$db = getDB();

// Filter pencarian, role, dan tanggal
$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$filterDate = trim($_GET['date'] ?? '');

// Statistik login
$stmtStats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN role = 'guru' THEN 1 ELSE 0 END) as total_guru,
        SUM(CASE WHEN role = 'siswa' THEN 1 ELSE 0 END) as total_siswa,
        SUM(CASE WHEN DATE(login_time) = CURDATE() THEN 1 ELSE 0 END) as total_hari_ini
    FROM activity_logs
    WHERE role IN ('guru', 'siswa')
");
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC) ?: [
    'total' => 0,
    'total_guru' => 0,
    'total_siswa' => 0,
    'total_hari_ini' => 0
];

// Query log data
$sql = "
    SELECT l.*, u.username, u.foto as user_foto, COALESCE(NULLIF(l.kelas, ''), u.kelas, '-') as user_kelas
    FROM activity_logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE l.role IN ('guru', 'siswa')
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (l.nama LIKE ? OR l.email LIKE ? OR u.username LIKE ? OR l.kelas LIKE ? OR l.ip_address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($roleFilter) && in_array($roleFilter, ['guru', 'siswa'])) {
    $sql .= " AND l.role = ?";
    $params[] = $roleFilter;
}

if (!empty($filterDate)) {
    $sql .= " AND DATE(l.login_time) = ?";
    $params[] = $filterDate;
}

$sql .= " ORDER BY l.login_time DESC LIMIT 200";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="margin-bottom: 24px;">
      <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 4px;">
        Audit Trail Keamanan & Presensi
      </span>
      <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
        Aktivitas Login Guru dan Siswa
      </h1>
      <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
        Setiap kali akun guru atau murid berhasil masuk ke sistem, log terekam secara otomatis ke database untuk keperluan audit, transparansi kehadiran, dan rekam jejak digital.
      </p>
    </div>

    <!-- Kartu Metrik Log -->
    <div class="grid-cards" style="margin-bottom: 20px;">
      <div class="glass-card" style="display: flex; align-items: center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: var(--blue-100); color: var(--blue-600); display: flex; align-items: center; justify-content: center;">
          <i data-feather="activity" style="width: 22px; height: 22px;"></i>
        </div>
        <div>
          <div style="font-size: 11.5px; color: var(--muted); font-weight: 600;">TOTAL SESI LOGIN</div>
          <div style="font-size: 22px; font-weight: 800; color: var(--blue-800);"><?= (int)$stats['total'] ?></div>
        </div>
      </div>

      <div class="glass-card" style="display: flex; align-items: center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: #DBEAFE; color: #1E40AF; display: flex; align-items: center; justify-content: center;">
          <i data-feather="user-check" style="width: 22px; height: 22px;"></i>
        </div>
        <div>
          <div style="font-size: 11.5px; color: var(--muted); font-weight: 600;">LOGIN GURU</div>
          <div style="font-size: 22px; font-weight: 800; color: #1E40AF;"><?= (int)$stats['total_guru'] ?></div>
        </div>
      </div>

      <div class="glass-card" style="display: flex; align-items: center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: var(--orange-100); color: var(--orange-dark); display: flex; align-items: center; justify-content: center;">
          <i data-feather="users" style="width: 22px; height: 22px;"></i>
        </div>
        <div>
          <div style="font-size: 11.5px; color: var(--muted); font-weight: 600;">LOGIN MURID</div>
          <div style="font-size: 22px; font-weight: 800; color: var(--orange-dark);"><?= (int)$stats['total_siswa'] ?></div>
        </div>
      </div>

      <div class="glass-card" style="display: flex; align-items: center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: #D1FAE5; color: #065F46; display: flex; align-items: center; justify-content: center;">
          <i data-feather="clock" style="width: 22px; height: 22px;"></i>
        </div>
        <div>
          <div style="font-size: 11.5px; color: var(--muted); font-weight: 600;">LOGIN HARI INI</div>
          <div style="font-size: 22px; font-weight: 800; color: #065F46;"><?= (int)$stats['total_hari_ini'] ?></div>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="glass-card" style="padding: 16px; margin-bottom: 20px;">
      <form method="GET" action="" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 220px;">
          <input type="text" name="search" class="form-input" style="width: 100%;" placeholder="Cari nama, email, username, kelas, atau IP..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div style="min-width: 170px;">
          <select name="role" class="form-input" style="width: 100%;">
            <option value="">Semua Role (Guru & Murid)</option>
            <option value="guru" <?= $roleFilter === 'guru' ? 'selected' : '' ?>>Guru</option>
            <option value="siswa" <?= $roleFilter === 'siswa' ? 'selected' : '' ?>>Murid</option>
          </select>
        </div>
        <div style="min-width: 150px;">
          <input type="date" name="date" class="form-input" style="width: 100%;" value="<?= htmlspecialchars($filterDate) ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 9px 18px;">
          <i data-feather="search"></i> Saring Log
        </button>
        <?php if (!empty($search) || !empty($roleFilter) || !empty($filterDate)): ?>
          <a href="<?= BASE_URL ?>/admin/teacher_logs.php" class="btn btn-outline" style="padding: 9px 14px;">Reset</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Tabel Riwayat Log -->
    <div class="glass-card">
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
              <th>Alamat IP</th>
              <th>Browser / Perangkat</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr>
                <td colspan="8" style="text-align: center; padding: 32px; color: var(--muted);">
                  Tidak ada catatan aktivitas login pengguna yang sesuai dengan kriteria filter.
                </td>
              </tr>
            <?php else: ?>
              <?php $no = 1; foreach ($logs as $row): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                      <?php if (!empty($row['user_foto'])): ?>
                        <img src="<?= BASE_URL . '/' . htmlspecialchars($row['user_foto']) ?>" alt="Foto" style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover;">
                      <?php else: ?>
                        <div style="width: 34px; height: 34px; border-radius: 50%; background: <?= $row['role'] === 'guru' ? 'var(--blue-600)' : 'var(--orange-dark)' ?>; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">
                          <?= strtoupper(substr($row['nama'] ?: 'U', 0, 1)) ?>
                        </div>
                      <?php endif; ?>
                      <div>
                        <div style="font-weight: 700; color: var(--ink);"><?= htmlspecialchars($row['nama']) ?></div>
                        <?php if (!empty($row['username'])): ?>
                          <span style="font-size: 11px; color: var(--muted);">@<?= htmlspecialchars($row['username']) ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td>
                    <?php if ($row['role'] === 'guru'): ?>
                      <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); font-size: 11px; font-weight: 700;">
                        Guru
                      </span>
                    <?php else: ?>
                      <span class="badge-role" style="background: var(--orange-100); color: var(--orange-dark); font-size: 11px; font-weight: 700;">
                        Murid
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="font-size: 12.5px; color: var(--ink);"><?= htmlspecialchars($row['email'] ?: ($row['username'] ?? '-')) ?></div>
                  </td>
                  <td>
                    <span class="badge-role" style="font-size: 11px;">
                      <?= htmlspecialchars($row['user_kelas'] ?: '-') ?>
                    </span>
                  </td>
                  <td>
                    <div style="font-weight: 700; color: var(--blue-800); font-size: 13px;">
                      <?= date('d M Y', strtotime($row['login_time'])) ?>
                    </div>
                    <div style="font-size: 12px; color: var(--muted);">
                      <?= date('H:i:s', strtotime($row['login_time'])) ?> WIB
                    </div>
                  </td>
                  <td>
                    <code style="font-size: 11.5px;"><?= htmlspecialchars($row['ip_address']) ?></code>
                  </td>
                  <td style="max-width: 220px; font-size: 11.5px; color: var(--muted); line-height: 1.4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($row['user_agent'] ?? '') ?>">
                    <?= htmlspecialchars(substr($row['user_agent'] ?? 'Browser', 0, 35)) ?>...
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
