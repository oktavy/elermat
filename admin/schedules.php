<?php
/**
 * Pengaturan Jadwal Pelajaran Guru oleh Admin
 * File: admin/schedules.php
 * FITUR A: Pengelolaan jadwal terstruktur
 */
require_once __DIR__ . '/../config/app.php';
require_role('admin');

$pageTitle = 'Atur Jadwal Guru - ElearnMath Admin';
$db = getDB();
$flash = flash_get();

// Tambah Jadwal Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_schedule') {
    $guruId = (int)$_POST['guru_id'];
    $kodeMapel = trim($_POST['kode_mapel'] ?? '');
    $namaMapel = trim($_POST['nama_mapel'] ?? 'Matematika');
    $kelas = trim($_POST['kelas'] ?? '');
    $hari = $_POST['hari'] ?? 'Senin';
    $jamMulai = $_POST['jam_mulai'] ?? '07:00';
    $jamSelesai = $_POST['jam_selesai'] ?? '08:20';
    $ruangan = trim($_POST['ruangan'] ?? 'Ruang Kelas');

    // Ambil nama guru
    $stmtG = $db->prepare("SELECT nama_lengkap FROM users WHERE id = ?");
    $stmtG->execute([$guruId]);
    $namaGuru = $stmtG->fetchColumn() ?: 'Guru Pengajar';

    if (!empty($kodeMapel) && !empty($kelas)) {
        $ins = $db->prepare("
            INSERT INTO jadwal_pelajaran (guru_id, nama_guru, kode_mapel, nama_mapel, kelas, hari, jam_mulai, jam_selesai, ruangan, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $ins->execute([$guruId, $namaGuru, $kodeMapel, $namaMapel, $kelas, $hari, $jamMulai, $jamSelesai, $ruangan]);

        // Notifikasi ke guru
        send_notification($guruId, 'Jadwal Mengajar Baru', "Administrator telah menambahkan jadwal mengajar baru untuk kelas {$kelas} pada hari {$hari}.", 'schedule', 'guru/jadwal.php');

        flash_set('success', 'Jadwal pelajaran berhasil ditambahkan!');
        redirect('admin/schedules.php');
    }
}

// Hapus Jadwal
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM jadwal_pelajaran WHERE id = ?");
    $stmtDel->execute([$delId]);
    flash_set('success', 'Jadwal pelajaran berhasil dihapus.');
    redirect('admin/schedules.php');
}

// Ambil daftar guru untuk dropdown
$teachers = $db->query("SELECT id, nama_lengkap FROM users WHERE role = 'guru' ORDER BY nama_lengkap ASC")->fetchAll();

// Ambil semua jadwal
$schedules = $db->query("
    SELECT * FROM jadwal_pelajaran 
    ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), jam_mulai ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="margin-bottom: 24px;">
      <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 4px;">
        FITUR A: Manajemen Jadwal
      </span>
      <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 4px 0;">
        Pengaturan Jadwal Pelajaran Guru
      </h1>
      <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
        Atur dan perbarui jadwal sesi belajar mengajar untuk seluruh kelas dan guru matematika.
      </p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Form Tambah Jadwal -->
    <div class="glass-card" style="margin-bottom: 24px;">
      <h2 style="font-size: 17px; font-weight: 700; color: var(--blue-800); margin: 0 0 16px 0;">
        + Tambah Jadwal Pelajaran Baru
      </h2>

      <form method="POST" action="">
        <input type="hidden" name="action" value="add_schedule">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 14px;">
          <div>
            <label class="form-label">Pilih Guru Pengajar</label>
            <select name="guru_id" class="form-input" required>
              <?php foreach ($teachers as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_lengkap']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="form-label">Kode Mata Pelajaran</label>
            <input type="text" name="kode_mapel" class="form-input" placeholder="Contoh: MTK-01" required>
          </div>

          <div>
            <label class="form-label">Nama Mata Pelajaran</label>
            <input type="text" name="nama_mapel" class="form-input" value="Matematika" required>
          </div>

          <div>
            <label class="form-label">Target Kelas</label>
            <input type="text" name="kelas" class="form-input" placeholder="Contoh: VII-A" required>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 18px;">
          <div>
            <label class="form-label">Hari</label>
            <select name="hari" class="form-input" required>
              <option value="Senin">Senin</option>
              <option value="Selasa">Selasa</option>
              <option value="Rabu">Rabu</option>
              <option value="Kamis">Kamis</option>
              <option value="Jumat">Jumat</option>
              <option value="Sabtu">Sabtu</option>
            </select>
          </div>

          <div>
            <label class="form-label">Jam Mulai</label>
            <input type="time" name="jam_mulai" class="form-input" value="07:00" required>
          </div>

          <div>
            <label class="form-label">Jam Selesai</label>
            <input type="time" name="jam_selesai" class="form-input" value="08:20" required>
          </div>

          <div>
            <label class="form-label">Ruangan</label>
            <input type="text" name="ruangan" class="form-input" placeholder="Contoh: Ruang Kelas VII-A">
          </div>
        </div>

        <button type="submit" class="btn btn-primary">
          <i data-feather="plus"></i> Simpan Jadwal Pelajaran
        </button>
      </form>
    </div>

    <!-- Tabel Daftar Jadwal Terdaftar -->
    <div class="glass-card">
      <h2 style="font-size: 17px; font-weight: 700; color: var(--blue-800); margin: 0 0 16px 0;">
        Daftar Jadwal Mengajar Aktif (<?= count($schedules) ?> Jadwal)
      </h2>

      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th>Hari</th>
              <th>Waktu</th>
              <th>Kelas</th>
              <th>Mata Pelajaran</th>
              <th>Guru Pengajar</th>
              <th>Ruangan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($schedules as $s): ?>
              <tr>
                <td><strong><?= htmlspecialchars($s['hari']) ?></strong></td>
                <td><?= substr($s['jam_mulai'], 0, 5) ?> - <?= substr($s['jam_selesai'], 0, 5) ?> WIB</td>
                <td><span class="badge-role"><?= htmlspecialchars($s['kelas']) ?></span></td>
                <td>
                  <strong><?= htmlspecialchars($s['nama_mapel']) ?></strong> 
                  <span style="font-size: 11px; color: var(--muted);">(<?= htmlspecialchars($s['kode_mapel']) ?>)</span>
                </td>
                <td><?= htmlspecialchars($s['nama_guru']) ?></td>
                <td><?= htmlspecialchars($s['ruangan']) ?></td>
                <td>
                  <a href="?delete=<?= $s['id'] ?>" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px;" onclick="return confirm('Hapus jadwal ini?')">
                    Hapus
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
