<?php
/**
 * Daftar Seluruh Siswa Kelas Terdaftar & Disetujui (Admitted)
 * File: guru/siswa.php
 * FITUR: Menampilkan siswa kelas yang terdaftar, status persetujuan, dan indikator online real-time.
 */
require_once __DIR__ . '/../config/app.php';
require_role(['guru', 'admin']);

$pageTitle = 'Daftar Siswa Kelas - ElearnMath Guru';
$currentUser = auth_user();
$db = getDB();

// Ambil kelas yang diampu guru
$teacherClassesRaw = $currentUser['kelas'] ?? '';
$teacherClasses = array_filter(array_map('trim', explode(',', $teacherClassesRaw)));

// Filter kelas terpilih
$filterClass = trim($_GET['kelas'] ?? '');
$search = trim($_GET['q'] ?? '');

$sql = "
    SELECT u.*, 
           TIMESTAMPDIFF(MINUTE, u.last_active, NOW()) AS minutes_ago,
           COUNT(s.id) AS total_submissions
    FROM users u
    LEFT JOIN submissions s ON u.id = s.student_id
    WHERE u.role = 'siswa' AND u.status_approval = 'APPROVED'
";
$params = [];

if (!empty($filterClass)) {
    $sql .= " AND u.kelas = ?";
    $params[] = $filterClass;
}
if (!empty($search)) {
    $sql .= " AND (u.nama_lengkap LIKE ? OR u.username LIKE ? OR u.nisn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY u.id ORDER BY u.kelas ASC, u.nama_lengkap ASC";

$stmtSiswa = $db->prepare($sql);
$stmtSiswa->execute($params);
$students = $stmtSiswa->fetchAll();

// Ambil semua daftar kelas unik
$allClasses = $db->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
      <div>
        <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 6px;">
          Peserta Didik Kelas
        </span>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
          Daftar Siswa Terdaftar & Disetujui (Admitted)
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Daftar siswa yang telah resmi disetujui masuk kelas. Pantau status kehadiran online, profil, dan rekap partisipasi tugas matematika.
        </p>
      </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="glass-card" style="margin-bottom: 24px; padding: 18px;">
      <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
        <div style="flex: 1; min-width: 200px;">
          <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Cari Siswa</label>
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Ketik nama, username, atau NISN..." class="form-input" style="padding: 8px 12px; font-size: 13px;">
        </div>

        <div style="width: 180px;">
          <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Filter Kelas</label>
          <select name="kelas" class="form-input" style="padding: 8px 12px; font-size: 13px;">
            <option value="">Semua Kelas</option>
            <?php foreach ($allClasses as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= $filterClass === $c ? 'selected' : '' ?>>
                Kelas <?= htmlspecialchars($c) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
            <i data-feather="search" style="width: 14px; height: 14px;"></i> Saring
          </button>
          <?php if (!empty($filterClass) || !empty($search)): ?>
            <a href="<?= BASE_URL ?>/guru/siswa.php" class="btn btn-outline" style="padding: 8px 12px; font-size: 13px;">Reset</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Tabel Daftar Siswa -->
    <div class="glass-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="font-size: 16px; font-weight: 700; color: var(--blue-800); margin: 0;">
          Total Siswa: <?= count($students) ?> Orang
        </h3>
      </div>

      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th>Siswa</th>
              <th>NISN</th>
              <th>Kelas</th>
              <th>Status Online</th>
              <th>Kontak (HP & Email)</th>
              <th>Tugas Terkumpul</th>
              <th style="text-align: center;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
              <tr>
                <td colspan="7" style="text-align: center; color: var(--muted); padding: 32px;">
                  Tidak ada data siswa yang ditemukan untuk kriteria filter ini.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($students as $s): ?>
                <?php 
                  $isOnline = (!empty($s['last_active']) && strtotime($s['last_active']) >= strtotime('-5 minutes'));
                ?>
                <tr>
                  <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                      <?php if (!empty($s['foto'])): ?>
                        <img src="<?= BASE_URL . '/' . htmlspecialchars($s['foto']) ?>" alt="Foto" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid <?= $isOnline ? 'var(--success)' : 'var(--line)' ?>;">
                      <?php else: ?>
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--blue-600); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; position: relative;">
                          <?= strtoupper(substr($s['nama_lengkap'], 0, 1)) ?>
                          <?php if ($isOnline): ?>
                            <span style="position: absolute; bottom: 0; right: 0; width: 10px; height: 10px; background: var(--success); border-radius: 50%; border: 2px solid #fff;"></span>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>
                      <div>
                        <div style="font-weight: 700; color: var(--ink); font-size: 13.5px;">
                          <?= htmlspecialchars($s['nama_lengkap']) ?>
                        </div>
                        <div style="font-size: 11px; color: var(--muted);">@<?= htmlspecialchars($s['username']) ?> &bull; <?= htmlspecialchars($s['jenis_kelamin'] ?: '-') ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($s['nisn'] ?: '-') ?></td>
                  <td>
                    <span class="badge-role" style="font-weight: 700; background: #EFF6FF; color: var(--blue-800);">
                      Kelas <?= htmlspecialchars($s['kelas'] ?: '-') ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($isOnline): ?>
                      <span style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; font-size: 12px; color: var(--success); background: var(--success-bg); padding: 4px 10px; border-radius: 999px;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--success);"></span> Aktif Sekarang
                      </span>
                    <?php else: ?>
                      <span style="font-size: 11.5px; color: var(--muted);">
                        <?= !empty($s['last_active']) ? date('d/m H:i', strtotime($s['last_active'])) : 'Belum aktif' ?>
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="font-size: 12.5px; color: var(--ink);"><?= htmlspecialchars($s['no_hp'] ?: '-') ?></div>
                    <div style="font-size: 11px; color: var(--muted);"><?= htmlspecialchars($s['email'] ?: '-') ?></div>
                  </td>
                  <td>
                    <span style="font-weight: 700; color: var(--blue-800); font-size: 13px;">
                      <?= $s['total_submissions'] ?> Tugas
                    </span>
                  </td>
                  <td style="text-align: center;">
                    <a href="<?= BASE_URL ?>/guru/nilai.php?siswa_id=<?= $s['id'] ?>" class="btn btn-outline" style="padding: 5px 12px; font-size: 12px;">
                      Lihat Nilai
                    </a>
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
