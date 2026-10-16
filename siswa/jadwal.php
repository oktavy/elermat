<?php
/**
 * Halaman Jadwal Pelajaran Siswa
 * File: siswa/jadwal.php
 * FITUR A: Siswa memiliki menu/halaman terpisah untuk melihat jadwal pelajaran guru secara lengkap dan terstruktur.
 */
require_once __DIR__ . '/../config/app.php';
require_role('siswa');

$pageTitle = 'Jadwal Pelajaran Siswa - ElearnMath';
$currentUser = auth_user();
$db = getDB();

// Cek status Admit Admin
if (strtoupper($currentUser['status_approval'] ?? 'PENDING') !== 'APPROVED') {
    flash_set('warning', 'Akses Kelas Anda sedang menunggu persetujuan (Admit) dari Admin untuk masuk ke kelas tujuan.');
    redirect('siswa/index.php');
}

// Filter opsi (hari / kelas)
$filterHari = $_GET['hari'] ?? 'Semua';
$studentClass = $currentUser['kelas'] ?? '';

$sql = "SELECT * FROM jadwal_pelajaran WHERE 1=1";
$params = [];

if ($filterHari !== 'Semua') {
    $sql .= " AND hari = ?";
    $params[] = $filterHari;
}

$sql .= " ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), jam_mulai ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$allSchedules = $stmt->fetchAll();

// Kelompokkan jadwal per hari untuk tampilan tab / timeline terstruktur
$days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$schedulesByDay = [];
foreach ($days as $d) {
    $schedulesByDay[$d] = [];
}
foreach ($allSchedules as $item) {
    if (isset($schedulesByDay[$item['hari']])) {
        $schedulesByDay[$item['hari']][] = $item;
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <!-- Header Page -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
      <div>
        <div style="font-size: 12px; font-weight: 700; color: var(--blue-600); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
          Fitur Jadwal Pelajaran
        </div>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
          Jadwal Pelajaran & Sesi Belajar
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Informasi lengkap jadwal mengajar guru matematika dan sesi pembelajaran kelas.
        </p>
      </div>

      <!-- Filter Hari -->
      <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
        <label for="hari" style="font-size: 13px; font-weight: 600; color: var(--ink);">Pilih Hari:</label>
        <select name="hari" id="hari" class="form-input" style="width: auto; padding: 8px 14px;" onchange="this.form.submit()">
          <option value="Semua" <?= $filterHari === 'Semua' ? 'selected' : '' ?>>Semua Hari (Senin - Sabtu)</option>
          <?php foreach ($days as $d): ?>
            <option value="<?= $d ?>" <?= $filterHari === $d ? 'selected' : '' ?>><?= $d ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <!-- Tampilan Kartu Jadwal Terstruktur -->
    <?php foreach ($days as $day): ?>
      <?php if ($filterHari !== 'Semua' && $filterHari !== $day) continue; ?>
      
      <div class="glass-card" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 16px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--blue-600); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700;">
              <?= substr($day, 0, 1) ?>
            </div>
            <h2 style="font-size: 18px; font-weight: 700; color: var(--blue-800); margin: 0;">
              Hari <?= $day ?>
            </h2>
          </div>
          <span style="font-size: 12px; background: var(--blue-100); color: var(--blue-800); padding: 4px 10px; border-radius: 999px; font-weight: 600;">
            <?= count($schedulesByDay[$day]) ?> Jadwal
          </span>
        </div>

        <?php if (empty($schedulesByDay[$day])): ?>
          <p style="color: var(--muted); font-size: 13px; margin: 0; padding: 12px 0;">Tidak ada kegiatan belajar terjadwal pada hari ini.</p>
        <?php else: ?>
          <div class="data-table-container">
            <table class="table-custom">
              <thead>
                <tr>
                  <th style="width: 180px;">Waktu Belajar</th>
                  <th>Kelas</th>
                  <th>Kode & Mata Pelajaran</th>
                  <th>Guru Pengajar</th>
                  <th>Ruangan / Tempat</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($schedulesByDay[$day] as $item): ?>
                  <?php $isMyClass = !empty($studentClass) && stripos($item['kelas'], $studentClass) !== false; ?>
                  <tr style="<?= $isMyClass ? 'background: #EFF6FF;' : '' ?>">
                    <td>
                      <div style="font-weight: 700; color: var(--blue-800); font-size: 13.5px;">
                        <?= substr($item['jam_mulai'], 0, 5) ?> - <?= substr($item['jam_selesai'], 0, 5) ?> WIB
                      </div>
                      <div style="font-size: 11px; color: var(--muted);">Waktu Indonesia Barat</div>
                    </td>
                    <td>
                      <span class="badge-role" style="<?= $isMyClass ? 'background: var(--blue-600); color: #fff;' : '' ?>">
                        <?= htmlspecialchars($item['kelas']) ?>
                        <?= $isMyClass ? ' (Kelas Kamu)' : '' ?>
                      </span>
                    </td>
                    <td>
                      <div style="font-weight: 600; color: var(--ink);">
                        <?= htmlspecialchars($item['nama_mapel']) ?>
                      </div>
                      <span style="font-size: 11.5px; color: var(--muted);">Kode: <strong><?= htmlspecialchars($item['kode_mapel']) ?></strong></span>
                    </td>
                    <td>
                      <div style="display: flex; align-items: center; gap: 8px;">
                        <i data-feather="user" style="width: 14px; height: 14px; color: var(--muted);"></i>
                        <span style="font-weight: 600;"><?= htmlspecialchars($item['nama_guru']) ?></span>
                      </div>
                    </td>
                    <td>
                      <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--muted);">
                        <i data-feather="map-pin" style="width: 13px; height: 13px;"></i>
                        <?= htmlspecialchars($item['ruangan']) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

  </div> <!-- /.content-wrapper -->
</div> <!-- /.app-main -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
