<?php
/**
 * Kelola Kelas Siswa
 * File: admin/classes.php
 */
require_once __DIR__ . '/../config/app.php';
require_role('admin');

$pageTitle = 'Kelola Kelas - ElearnMath Admin';
$db = getDB();
$flash = flash_get();

// Tambah Kelas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_class') {
    $name = trim($_POST['name'] ?? '');
    $teacherId = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;

    if (!empty($name)) {
        try {
            $ins = $db->prepare("INSERT INTO classes (name, teacher_id) VALUES (?, ?)");
            $ins->execute([$name, $teacherId]);
            flash_set('success', "Kelas '{$name}' berhasil ditambahkan!");
        } catch (Exception $e) {
            flash_set('danger', 'Nama kelas sudah ada atau terjadi kesalahan.');
        }
        redirect('admin/classes.php');
    }
}

// Hapus Kelas
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM classes WHERE id = ?");
    $stmtDel->execute([$delId]);
    flash_set('success', 'Kelas berhasil dihapus.');
    redirect('admin/classes.php');
}

$classes = $db->query("
    SELECT c.*, u.nama_lengkap AS wali_kelas, COUNT(cs.student_id) AS total_siswa
    FROM classes c
    LEFT JOIN users u ON c.teacher_id = u.id
    LEFT JOIN class_students cs ON c.id = cs.class_id
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetchAll();

$teachers = $db->query("SELECT id, nama_lengkap FROM users WHERE role = 'guru'")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="margin-bottom: 24px;">
      <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 4px 0;">
        Kelola Kelas & Wali Kelas
      </h1>
      <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
        Daftar rombel (rombongan belajar) dan penugasan wali kelas pengajar.
      </p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Form Tambah Kelas -->
    <div class="glass-card" style="margin-bottom: 24px;">
      <h2 style="font-size: 17px; font-weight: 700; color: var(--blue-800); margin: 0 0 16px 0;">
        + Tambah Rombel Kelas Baru
      </h2>

      <form method="POST" action="" style="display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="action" value="add_class">

        <div style="flex: 1; min-width: 200px;">
          <label class="form-label">Nama Kelas</label>
          <input type="text" name="name" class="form-input" placeholder="Contoh: VII-D" required>
        </div>

        <div style="flex: 1; min-width: 240px;">
          <label class="form-label">Wali Kelas / Guru Pembina</label>
          <select name="teacher_id" class="form-input">
            <option value="">-- Pilih Guru (Opsional) --</option>
            <?php foreach ($teachers as $t): ?>
              <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_lengkap']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 11px 20px;">
          <i data-feather="plus"></i> Tambah Kelas
        </button>
      </form>
    </div>

    <!-- Tabel Kelas -->
    <div class="glass-card">
      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th>Nama Rombel Kelas</th>
              <th>Wali Kelas</th>
              <th>Jumlah Siswa</th>
              <th>Tanggal Dibuat</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($classes as $c): ?>
              <tr>
                <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                <td><?= htmlspecialchars($c['wali_kelas'] ?? 'Belum Ditentukan') ?></td>
                <td><span class="badge-role"><?= $c['total_siswa'] ?> Siswa</span></td>
                <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                <td>
                  <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px;" onclick="return confirm('Hapus kelas ini?')">
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
