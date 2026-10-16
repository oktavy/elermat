<?php
/**
 * Monitor Pengawasan Keluar Tab Browser Seluruh Siswa (Pusat Admin)
 * File: admin/monitor_tab.php
 * FITUR: Monitoring pusat pelanggaran keluar tab saat siswa mengerjakan LKPD, Latihan, atau Evaluasi.
 */
require_once __DIR__ . '/../config/app.php';
require_role('admin');

$pageTitle = 'Monitor Keluar Tab (Pengawasan Pusat) - ElearnMath Admin';
$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// Aksi Hapus / Reset Log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_single' && !empty($_POST['log_id'])) {
        $delId = (int)$_POST['log_id'];
        $stmtDel = $db->prepare("DELETE FROM tab_switch_logs WHERE id = ?");
        $stmtDel->execute([$delId]);
        flash_set('success', 'Log pelanggaran berhasil dihapus.');
    } elseif ($_POST['action'] === 'clear_all') {
        $db->query("DELETE FROM tab_switch_logs");
        flash_set('success', 'Seluruh data log pengawasan keluar tab berhasil direset!');
    }
    redirect('admin/monitor_tab.php');
}

// Filter
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

if (!empty($filterClass)) {
    $sql .= " AND t.kelas = ?";
    $params[] = $filterClass;
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

// Statistik
$totalIncidents = count($logs);
$totalSwitches  = array_sum(array_column($logs, 'switch_count'));

// Daftar Kelas untuk dropdown filter
$classes = $db->query("SELECT DISTINCT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
      <div>
        <span class="badge-role" style="background: var(--danger-bg); color: var(--danger); margin-bottom: 6px;">
          Pusat Integritas & Pengawasan Ujian
        </span>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
          Monitor Deteksi Keluar Tab Browser (Seluruh Siswa)
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Pantau secara sentral aktivitas siswa dari seluruh kelas yang berpindah atau meminimalkan jendela saat mengerjakan LKPD, Latihan, dan Evaluasi.
        </p>
      </div>

      <?php if (!empty($logs)): ?>
        <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin mereset/menghapus seluruh log keluar tab? Tindakan ini tidak dapat dibatalkan.');">
          <input type="hidden" name="action" value="clear_all">
          <button type="submit" class="btn btn-danger" style="font-size: 12.5px; padding: 8px 16px;">
            <i data-feather="trash-2" style="width: 14px; height: 14px;"></i> Reset Seluruh Log
          </button>
        </form>
      <?php endif; ?>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- Kartu Metrik Ringkasan -->
    <div class="grid-cards" style="margin-bottom: 24px;">
      <div class="glass-card" style="display: flex; align-items: center; gap: 16px;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: var(--danger-bg); color: var(--danger); display: flex; align-items: center; justify-content: center;">
          <i data-feather="alert-triangle" style="width: 24px; height: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 12px; color: var(--muted); font-weight: 600;">TOTAL INSIDEN TERCATAT</div>
          <div style="font-size: 24px; font-weight: 800; color: var(--danger);"><?= $totalIncidents ?> Kejadian</div>
        </div>
      </div>

      <div class="glass-card" style="display: flex; align-items: center; gap: 16px;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: var(--orange-100); color: var(--orange-dark); display: flex; align-items: center; justify-content: center;">
          <i data-feather="repeat" style="width: 24px; height: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 12px; color: var(--muted); font-weight: 600;">AKUMULASI PINDAH TAB</div>
          <div style="font-size: 24px; font-weight: 800; color: var(--orange-dark);"><?= $totalSwitches ?> Kali</div>
        </div>
      </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="glass-card" style="margin-bottom: 24px; padding: 18px;">
      <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
        <div style="flex: 1; min-width: 180px;">
          <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Cari Siswa / Modul</label>
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Ketik nama atau ujian..." class="form-input" style="padding: 8px 12px; font-size: 13px;">
        </div>

        <div style="width: 160px;">
          <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Filter Kelas</label>
          <select name="kelas" class="form-input" style="padding: 8px 12px; font-size: 13px;">
            <option value="">Semua Kelas</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= $filterClass === $c ? 'selected' : '' ?>>
                Kelas <?= htmlspecialchars($c) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="width: 160px;">
          <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Tanggal Kejadian</label>
          <input type="date" name="tanggal" value="<?= htmlspecialchars($filterDate) ?>" class="form-input" style="padding: 8px 12px; font-size: 13px;">
        </div>

        <div>
          <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
            <i data-feather="filter" style="width: 14px; height: 14px;"></i> Terapkan
          </button>
          <?php if (!empty($filterClass) || !empty($filterDate) || !empty($search)): ?>
            <a href="<?= BASE_URL ?>/admin/monitor_tab.php" class="btn btn-outline" style="padding: 8px 12px; font-size: 13px;">Reset</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Tabel Log Pelanggaran -->
    <div class="glass-card">
      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th>Waktu Kejadian</th>
              <th>Nama Siswa & Identitas</th>
              <th>Kelas</th>
              <th>Modul yang Dikerjakan</th>
              <th>Frekuensi Pindah Tab</th>
              <th>Tingkat Peringatan</th>
              <th>Catatan Pengawas</th>
              <th style="text-align: center;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr>
                <td colspan="8" style="text-align: center; color: var(--muted); padding: 32px;">
                  Tidak ada catatan pelanggaran keluar tab yang ditemukan.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td style="font-size: 12.5px; color: var(--ink); white-space: nowrap;">
                    <?= date('d M Y, H:i:s', strtotime($log['last_switched_at'])) ?> WIB
                  </td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                      <?php if (!empty($log['foto'])): ?>
                        <img src="<?= BASE_URL . '/' . htmlspecialchars($log['foto']) ?>" alt="Foto" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                      <?php else: ?>
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--blue-600); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;">
                          <?= strtoupper(substr($log['nama_siswa'], 0, 1)) ?>
                        </div>
                      <?php endif; ?>
                      <div>
                        <div style="font-weight: 700; font-size: 13px; color: var(--ink);"><?= htmlspecialchars($log['nama_siswa']) ?></div>
                        <div style="font-size: 11px; color: var(--muted);">NISN: <?= htmlspecialchars($log['nisn'] ?: '-') ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge-role" style="background: #EFF6FF; color: var(--blue-800);">
                      <?= htmlspecialchars($log['kelas'] ?: '-') ?>
                    </span>
                  </td>
                  <td style="font-weight: 600; font-size: 13px; color: var(--blue-800);">
                    <?= htmlspecialchars($log['assessment_title']) ?>
                  </td>
                  <td>
                    <span style="font-weight: 800; font-size: 14px; color: <?= $log['switch_count'] >= 3 ? 'var(--danger)' : 'var(--orange-dark)' ?>;">
                      <?= $log['switch_count'] ?> kali
                    </span>
                  </td>
                  <td>
                    <?php if ($log['switch_count'] >= 5): ?>
                      <span style="background: var(--danger-bg); color: var(--danger); font-weight: 700; padding: 4px 8px; border-radius: 4px; font-size: 11px;">
                        🚨 PELANGGARAN BERAT
                      </span>
                    <?php elseif ($log['switch_count'] >= 3): ?>
                      <span style="background: var(--orange-100); color: var(--orange-dark); font-weight: 700; padding: 4px 8px; border-radius: 4px; font-size: 11px;">
                        ⚠️ PERINGATAN SEDANG
                      </span>
                    <?php else: ?>
                      <span style="background: #FEF3C7; color: #B45309; font-weight: 600; padding: 4px 8px; border-radius: 4px; font-size: 11px;">
                        Peringatan Ringan
                      </span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size: 12px; color: var(--muted);">
                    <?= htmlspecialchars($log['notes'] ?: 'Pindah tab peramban saat pengerjaan') ?>
                  </td>
                  <td style="text-align: center;">
                    <form method="POST" action="" style="margin: 0;" onsubmit="return confirm('Hapus entri log ini?');">
                      <input type="hidden" name="action" value="delete_single">
                      <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                      <button type="submit" class="btn btn-outline" style="padding: 4px 8px; font-size: 11px; color: var(--danger); border-color: var(--danger);" title="Hapus Log">
                        <i data-feather="trash-2" style="width: 12px; height: 12px;"></i>
                      </button>
                    </form>
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
