<?php
/**
 * Pantauan Integritas Ujian & Deteksi Keluar Tab Siswa (Panel Guru)
 * File: guru/deteksi.php
 * FITUR GURU: Deteksi Keluar Tab - Tampilkan log/notifikasi realtime jika ada siswa yang berpindah/keluar tab browser saat mengerjakan tugas.
 */
require_once __DIR__ . '/../config/app.php';
require_role(['guru', 'admin']);

$pageTitle = 'Deteksi Keluar Tab Siswa - ElearnMath Guru';
$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// Kelas yang diampu guru
$guruClasses = array_filter(array_map('trim', explode(',', $currentUser['kelas'] ?? '')));

$filterClass = trim($_GET['kelas'] ?? '');
$filterDate  = trim($_GET['tanggal'] ?? '');
$search      = trim($_GET['q'] ?? '');

$sql = "
    SELECT t.*, u.foto, u.nisn, u.email
    FROM tab_switch_logs t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE 1=1
";
$params = [];

// Jika guru memiliki kelas tertentu dan tidak memfilter secara spesifik
if (!empty($filterClass)) {
    $sql .= " AND t.kelas = ?";
    $params[] = $filterClass;
} elseif (!empty($guruClasses) && !in_array('admin', [$currentUser['role']])) {
    $placeholders = implode(',', array_fill(0, count($guruClasses), '?'));
    $sql .= " AND t.kelas IN ($placeholders)";
    foreach ($guruClasses as $gc) {
        $params[] = $gc;
    }
}

if (!empty($filterDate)) {
    $sql .= " AND DATE(t.last_switched_at) = ?";
    $params[] = $filterDate;
}
if (!empty($search)) {
    $sql .= " AND (t.nama_siswa LIKE ? OR t.assessment_title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY t.last_switched_at DESC";

$stmtLogs = $db->prepare($sql);
$stmtLogs->execute($params);
$logs = $stmtLogs->fetchAll();

// Daftar kelas untuk filter
$classes = $db->query("SELECT DISTINCT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="margin-bottom: 24px;">
      <span class="badge-role" style="background: var(--danger-bg); color: var(--danger); margin-bottom: 6px;">
        Integritas Akademik & Pengawasan Ujian
      </span>
      <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
        Deteksi Keluar Tab Browser Siswa
      </h1>
      <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
        Pantau secara langsung siswa di kelas Anda yang berpindah ke tab lain atau membuka aplikasi eksternal saat sedang mengerjakan LKPD, Latihan, atau Evaluasi.
      </p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="glass-card" style="margin-bottom: 24px; padding: 18px;">
      <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
        <div style="flex: 1; min-width: 180px;">
          <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Cari Siswa / Modul</label>
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Nama siswa atau judul ujian..." class="form-input" style="padding: 8px 12px; font-size: 13px;">
        </div>

        <div style="width: 160px;">
          <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Filter Kelas</label>
          <select name="kelas" class="form-input" style="padding: 8px 12px; font-size: 13px;">
            <option value="">Semua Kelas Guru</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= $filterClass === $c ? 'selected' : '' ?>>
                Kelas <?= htmlspecialchars($c) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="width: 160px;">
          <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Tanggal</label>
          <input type="date" name="tanggal" value="<?= htmlspecialchars($filterDate) ?>" class="form-input" style="padding: 8px 12px; font-size: 13px;">
        </div>

        <div>
          <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
            <i data-feather="filter" style="width: 14px; height: 14px;"></i> Terapkan Filter
          </button>
          <?php if (!empty($filterClass) || !empty($filterDate) || !empty($search)): ?>
            <a href="<?= BASE_URL ?>/guru/deteksi.php" class="btn btn-outline" style="padding: 8px 12px; font-size: 13px;">Reset</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Tabel Pelanggaran Tab -->
    <div class="glass-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="font-size: 16px; font-weight: 700; color: var(--blue-800); margin: 0;">
          Daftar Siswa Terdeteksi Pindah Tab (<?= count($logs) ?> Kejadian)
        </h3>
        <button type="button" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;" onclick="window.location.reload();">
          <i data-feather="refresh-cw" style="width: 14px; height: 14px;"></i> Segarkan Data
        </button>
      </div>

      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th>Waktu Terdeteksi</th>
              <th>Nama Siswa</th>
              <th>Kelas</th>
              <th>Tugas / Ujian</th>
              <th>Jumlah Keluar Tab</th>
              <th>Status Pelanggaran</th>
              <th>Aksi Tindakan</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr>
                <td colspan="7" style="text-align: center; color: var(--muted); padding: 36px;">
                  <i data-feather="shield" style="width: 36px; height: 36px; color: var(--success); opacity: 0.6; margin-bottom: 8px;"></i>
                  <p style="margin: 0; font-size: 14px; font-weight: 600; color: var(--success);">
                    Kondisi Aman: Tidak ada pelanggaran keluar tab yang tercatat.
                  </p>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($logs as $row): ?>
                <tr>
                  <td style="font-size: 12.5px; white-space: nowrap;">
                    <?= date('d M Y, H:i:s', strtotime($row['last_switched_at'])) ?> WIB
                  </td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                      <?php if (!empty($row['foto'])): ?>
                        <img src="<?= BASE_URL . '/' . htmlspecialchars($row['foto']) ?>" alt="Foto" style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover;">
                      <?php else: ?>
                        <div style="width: 34px; height: 34px; border-radius: 50%; background: var(--blue-600); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;">
                          <?= strtoupper(substr($row['nama_siswa'], 0, 1)) ?>
                        </div>
                      <?php endif; ?>
                      <div>
                        <div style="font-weight: 700; font-size: 13.5px; color: var(--ink);"><?= htmlspecialchars($row['nama_siswa']) ?></div>
                        <div style="font-size: 11px; color: var(--muted);">NISN: <?= htmlspecialchars($row['nisn'] ?: '-') ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge-role" style="font-size: 11.5px; background: #EFF6FF; color: var(--blue-800);">
                      Kelas <?= htmlspecialchars($row['kelas'] ?: '-') ?>
                    </span>
                  </td>
                  <td style="font-weight: 600; font-size: 13px; color: var(--blue-800);">
                    <?= htmlspecialchars($row['assessment_title']) ?>
                  </td>
                  <td>
                    <span style="font-weight: 800; font-size: 15px; color: <?= $row['switch_count'] >= 3 ? 'var(--danger)' : 'var(--orange-dark)' ?>;">
                      <?= $row['switch_count'] ?>x Pindah Tab
                    </span>
                  </td>
                  <td>
                    <?php if ($row['switch_count'] >= 5): ?>
                      <span style="background: var(--danger-bg); color: var(--danger); font-weight: 700; padding: 4px 10px; border-radius: 4px; font-size: 11px;">
                        🚨 PELANGGARAN TINGGI
                      </span>
                    <?php elseif ($row['switch_count'] >= 3): ?>
                      <span style="background: var(--orange-100); color: var(--orange-dark); font-weight: 700; padding: 4px 10px; border-radius: 4px; font-size: 11px;">
                        ⚠️ PERINGATAN SEDANG
                      </span>
                    <?php else: ?>
                      <span style="background: #FEF3C7; color: #B45309; font-weight: 600; padding: 4px 10px; border-radius: 4px; font-size: 11px;">
                        Peringatan Ringan
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="<?= BASE_URL ?>/guru/koreksi.php" class="btn btn-outline" style="font-size: 11px; padding: 4px 10px;">
                      Periksa Jawaban
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
