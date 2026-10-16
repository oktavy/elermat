<?php
/**
 * Jadwal Mengajar Guru (CRUD Lengkap)
 * File: guru/jadwal.php
 * FITUR GURU: Edit Jadwal Pelajaran (CRUD: Create, Read, Update, Delete)
 */
require_once __DIR__ . '/../config/app.php';
require_role(['guru', 'admin']);

$pageTitle = 'Jadwal Mengajar Guru (CRUD) - ElearnMath';
$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

$editId = (int)($_GET['edit'] ?? 0);
$editSchedule = null;

if ($editId > 0) {
    $stmtE = $db->prepare("SELECT * FROM jadwal_pelajaran WHERE id = ? AND guru_id = ?");
    $stmtE->execute([$editId, $currentUser['id']]);
    $editSchedule = $stmtE->fetch();
}

// 1. CREATE JADWAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_schedule') {
    $kodeMapel = trim($_POST['kode_mapel'] ?? '');
    $namaMapel = trim($_POST['nama_mapel'] ?? 'Matematika');
    $kelas = trim($_POST['kelas'] ?? '');
    $hari = $_POST['hari'] ?? 'Senin';
    $jamMulai = $_POST['jam_mulai'] ?? '07:00';
    $jamSelesai = $_POST['jam_selesai'] ?? '08:20';
    $ruangan = trim($_POST['ruangan'] ?? 'Ruang Kelas');

    if (!empty($kodeMapel) && !empty($kelas)) {
        $ins = $db->prepare("
            INSERT INTO jadwal_pelajaran (guru_id, nama_guru, kode_mapel, nama_mapel, kelas, hari, jam_mulai, jam_selesai, ruangan, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $ins->execute([$currentUser['id'], $currentUser['nama_lengkap'], $kodeMapel, $namaMapel, $kelas, $hari, $jamMulai, $jamSelesai, $ruangan]);
        flash_set('success', 'Jadwal mengajar baru berhasil ditambahkan!');
        redirect('guru/jadwal.php');
    }
}

// 2. UPDATE JADWAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_schedule') {
    $schedId = (int)$_POST['schedule_id'];
    $kodeMapel = trim($_POST['kode_mapel'] ?? '');
    $namaMapel = trim($_POST['nama_mapel'] ?? 'Matematika');
    $kelas = trim($_POST['kelas'] ?? '');
    $hari = $_POST['hari'] ?? 'Senin';
    $jamMulai = $_POST['jam_mulai'] ?? '07:00';
    $jamSelesai = $_POST['jam_selesai'] ?? '08:20';
    $ruangan = trim($_POST['ruangan'] ?? 'Ruang Kelas');

    $upd = $db->prepare("
        UPDATE jadwal_pelajaran 
        SET kode_mapel = ?, nama_mapel = ?, kelas = ?, hari = ?, jam_mulai = ?, jam_selesai = ?, ruangan = ?
        WHERE id = ? AND guru_id = ?
    ");
    $upd->execute([$kodeMapel, $namaMapel, $kelas, $hari, $jamMulai, $jamSelesai, $ruangan, $schedId, $currentUser['id']]);
    flash_set('success', 'Jadwal mengajar berhasil diperbarui!');
    redirect('guru/jadwal.php');
}

// 3. DELETE JADWAL
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM jadwal_pelajaran WHERE id = ? AND guru_id = ?");
    $stmtDel->execute([$delId, $currentUser['id']]);
    flash_set('success', 'Jadwal mengajar berhasil dihapus.');
    redirect('guru/jadwal.php');
}

// 4. READ JADWAL
$stmt = $db->prepare("
    SELECT * FROM jadwal_pelajaran 
    WHERE guru_id = ? 
    ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), jam_mulai ASC
");
$stmt->execute([$currentUser['id']]);
$schedules = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
      <div>
        <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 4px;">
          FITUR GURU: CRUD Jadwal Pelajaran
        </span>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 4px 0;">
          Kelola Jadwal Mengajar Mandiri
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Tambah jadwal baru, ubah jam atau kelas ajar, serta kelola kalender mingguan Anda secara mandiri.
        </p>
      </div>

      <div>
        <?php if ($editSchedule): ?>
          <a href="<?= BASE_URL ?>/guru/jadwal.php" class="btn btn-outline">
            &larr; Batalkan Mode Edit
          </a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Form Tambah / Edit Jadwal Mengajar -->
    <div class="glass-card" style="margin-bottom: 24px;">
      <h2 style="font-size: 17px; font-weight: 700; color: var(--blue-800); margin: 0 0 16px 0;">
        <?= $editSchedule ? '✏️ Edit Jadwal Mengajar' : '+ Tambah Jadwal Mengajar Baru' ?>
      </h2>

      <form method="POST" action="">
        <input type="hidden" name="action" value="<?= $editSchedule ? 'update_schedule' : 'add_schedule' ?>">
        <?php if ($editSchedule): ?>
          <input type="hidden" name="schedule_id" value="<?= $editSchedule['id'] ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 14px;">
          <div>
            <label class="form-label">Hari</label>
            <select name="hari" class="form-input" required>
              <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $d): ?>
                <option value="<?= $d ?>" <?= ($editSchedule && $editSchedule['hari'] === $d) ? 'selected' : '' ?>><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="form-label">Jam Mulai</label>
            <input type="time" name="jam_mulai" class="form-input" value="<?= $editSchedule ? substr($editSchedule['jam_mulai'], 0, 5) : '07:00' ?>" required>
          </div>

          <div>
            <label class="form-label">Jam Selesai</label>
            <input type="time" name="jam_selesai" class="form-input" value="<?= $editSchedule ? substr($editSchedule['jam_selesai'], 0, 5) : '08:20' ?>" required>
          </div>

          <div>
            <label class="form-label">Kelas</label>
            <input type="text" name="kelas" class="form-input" placeholder="Contoh: VII-A" value="<?= htmlspecialchars($editSchedule['kelas'] ?? '') ?>" required>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 18px;">
          <div>
            <label class="form-label">Kode Mata Pelajaran</label>
            <input type="text" name="kode_mapel" class="form-input" placeholder="Contoh: MTK-01" value="<?= htmlspecialchars($editSchedule['kode_mapel'] ?? '') ?>" required>
          </div>

          <div>
            <label class="form-label">Nama Mata Pelajaran</label>
            <input type="text" name="nama_mapel" class="form-input" placeholder="Matematika" value="<?= htmlspecialchars($editSchedule['nama_mapel'] ?? 'Matematika') ?>" required>
          </div>

          <div>
            <label class="form-label">Ruangan</label>
            <input type="text" name="ruangan" class="form-input" placeholder="Contoh: Ruang Kelas VII-A" value="<?= htmlspecialchars($editSchedule['ruangan'] ?? 'Ruang Kelas') ?>">
          </div>
        </div>

        <button type="submit" class="btn btn-primary">
          <i data-feather="<?= $editSchedule ? 'save' : 'plus' ?>"></i>
          <?= $editSchedule ? 'Simpan Perubahan Jadwal' : 'Tambahkan ke Jadwal Saya' ?>
        </button>
      </form>
    </div>

    <!-- Tabel Daftar Jadwal Guru -->
    <div class="glass-card">
      <h2 style="font-size: 17px; font-weight: 700; color: var(--blue-800); margin: 0 0 16px 0;">
        Daftar Jadwal Mengajar Anda (<?= count($schedules) ?> Jadwal)
      </h2>

      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th>Hari</th>
              <th>Jam Mengajar</th>
              <th>Kelas</th>
              <th>Mata Pelajaran</th>
              <th>Ruangan</th>
              <th>Aksi CRUD</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($schedules)): ?>
              <tr>
                <td colspan="6" style="text-align: center; padding: 24px; color: var(--muted);">
                  Belum ada jadwal mengajar. Silakan gunakan formulir di atas untuk menambahkan jadwal baru.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($schedules as $item): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($item['hari']) ?></strong></td>
                  <td><?= substr($item['jam_mulai'], 0, 5) ?> - <?= substr($item['jam_selesai'], 0, 5) ?> WIB</td>
                  <td><span class="badge-role"><?= htmlspecialchars($item['kelas']) ?></span></td>
                  <td>
                    <strong><?= htmlspecialchars($item['nama_mapel']) ?></strong> 
                    <span style="font-size: 11px; color: var(--muted);">(<?= htmlspecialchars($item['kode_mapel']) ?>)</span>
                  </td>
                  <td><?= htmlspecialchars($item['ruangan']) ?></td>
                  <td>
                    <div style="display: flex; gap: 8px;">
                      <a href="?edit=<?= $item['id'] ?>" class="btn btn-outline" style="padding: 4px 10px; font-size: 11.5px;">
                        <i data-feather="edit" style="width: 12px; height: 12px;"></i> Edit
                      </a>
                      <a href="?delete=<?= $item['id'] ?>" class="btn btn-danger" style="padding: 4px 10px; font-size: 11.5px;" onclick="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
                        <i data-feather="trash-2" style="width: 12px; height: 12px;"></i> Hapus
                      </a>
                    </div>
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
