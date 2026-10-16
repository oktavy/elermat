<?php
/**
 * Manajemen Pengguna oleh Administrator
 * File: admin/users.php
 * FITUR G: Admin memiliki akses penuh melihat daftar pengguna dan menghapus akun (Siswa, Guru, Staff).
 */
require_once __DIR__ . '/../config/app.php';
require_role('admin');

$pageTitle = 'Manajemen Pengguna - ElearnMath Admin';
$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// 1. PROSES HAPUS PENGGUNA (FITUR G)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];

    // Cegah admin menghapus akunnya sendiri
    if ($deleteId === (int)$currentUser['id']) {
        flash_set('danger', 'Anda tidak dapat menghapus akun Anda sendiri saat sedang aktif masuk.');
        redirect('admin/users.php');
    }

    // Ambil data user sebelum dihapus
    $stmtFind = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmtFind->execute([$deleteId]);
    $targetUser = $stmtFind->fetch();

    if ($targetUser) {
        $db->beginTransaction();
        try {
            // Hapus pengguna (foreign keys ON DELETE CASCADE akan membersihkan submission, answers, class_students, logs)
            $stmtDel = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmtDel->execute([$deleteId]);

            $db->commit();
            flash_set('success', "Akun pengguna [{$targetUser['role']}] '{$targetUser['nama_lengkap']}' ({$targetUser['username']}) berhasil dihapus dari sistem.");
        } catch (Exception $e) {
            $db->rollBack();
            flash_set('danger', 'Gagal menghapus pengguna: ' . $e->getMessage());
        }
    } else {
        flash_set('warning', 'Pengguna tidak ditemukan.');
    }
    redirect('admin/users.php');
}

// 2. PROSES TAMBAH PENGGUNA BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username'] ?? '');
    $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'siswa';
    $password = trim($_POST['password'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $nisn = trim($_POST['nisn'] ?? '');
    $noHp = trim($_POST['no_hp'] ?? '');

    if ($role === 'admin') {
        flash_set('danger', 'Akun Administrator dibatasi strictly hanya untuk 3 akun resmi yang telah ditentukan.');
    } elseif (empty($username) || empty($namaLengkap) || empty($password)) {
        flash_set('danger', 'Username, Nama Lengkap, dan Kata Sandi wajib diisi.');
    } else {
        // Cek duplikasi username
        $chk = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $chk->execute([$username]);
        if ($chk->fetchColumn() > 0) {
            flash_set('danger', "Username '{$username}' sudah digunakan. Harap gunakan username lain.");
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmtAdd = $db->prepare("
                INSERT INTO users (username, password, role, status_approval, nama_lengkap, email, kelas, nisn, no_hp, created_at)
                VALUES (?, ?, ?, 'APPROVED', ?, ?, ?, ?, ?, NOW())
            ");
            $stmtAdd->execute([$username, $hashedPassword, $role, $namaLengkap, $email, $kelas, $nisn, $noHp]);
            flash_set('success', "Akun pengguna {$role} '{$namaLengkap}' berhasil ditambahkan dan disetujui!");
            redirect('admin/users.php');
        }
    }
}

// Filter role
$filterRole = $_GET['role'] ?? 'semua';
$searchQuery = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($filterRole !== 'semua') {
    $sql .= " AND role = ?";
    $params[] = $filterRole;
}

if (!empty($searchQuery)) {
    $sql .= " AND (nama_lengkap LIKE ? OR username LIKE ? OR email LIKE ? OR nisn LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql .= " ORDER BY FIELD(role, 'admin', 'guru', 'staff', 'siswa'), nama_lengkap ASC";
$stmtUsers = $db->prepare($sql);
$stmtUsers->execute($params);
$usersList = $stmtUsers->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
      <div>
        <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 4px;">
          FITUR G: Manajemen Pengguna Penuh
        </span>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
          Manajemen Data Akun Pengguna
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Administrator memiliki kendali penuh untuk meninjau data akun, menambahkan anggota baru, serta menghapus akun (Siswa, Guru, Staff).
        </p>
      </div>

      <div>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('addUserFormCard').scrollIntoView({ behavior: 'smooth' })">
          <i data-feather="user-plus"></i> Tambah Pengguna Baru
        </button>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Filter & Search Bar -->
    <div class="glass-card" style="padding: 16px; margin-bottom: 20px;">
      <form method="GET" action="" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <input type="text" name="q" class="form-input" style="width: 250px;" placeholder="Cari nama, username, NISN..." value="<?= htmlspecialchars($searchQuery) ?>">
        
        <select name="role" class="form-input" style="width: auto;" onchange="this.form.submit()">
          <option value="semua" <?= $filterRole === 'semua' ? 'selected' : '' ?>>Semua Role</option>
          <option value="guru" <?= $filterRole === 'guru' ? 'selected' : '' ?>>Guru</option>
          <option value="siswa" <?= $filterRole === 'siswa' ? 'selected' : '' ?>>Siswa</option>
          <option value="staff" <?= $filterRole === 'staff' ? 'selected' : '' ?>>Staff</option>
          <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>

        <button type="submit" class="btn btn-outline" style="padding: 9px 14px;">
          <i data-feather="search"></i> Cari
        </button>

        <?php if (!empty($searchQuery) || $filterRole !== 'semua'): ?>
          <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline" style="padding: 9px 14px;">Reset</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Tabel Daftar Pengguna -->
    <div class="glass-card">
      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th>Nama Lengkap</th>
              <th>Username</th>
              <th>Role</th>
              <th>Kelas / NISN</th>
              <th>Email & No. HP</th>
              <th>Status Presensi</th>
              <th>Aksi Admin</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usersList as $u): ?>
              <tr>
                <td>
                  <div style="font-weight: 700; color: var(--ink);"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                  <span style="font-size: 11px; color: var(--muted);">Terdaftar: <?= date('d/m/Y', strtotime($u['created_at'])) ?></span>
                </td>
                <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                <td>
                  <?php 
                    $roleColors = [
                      'admin' => 'background:#EDE9FE; color:#6D28D9;',
                      'guru' => 'background:#DBEAFE; color:#1E40AF;',
                      'siswa' => 'background:#FEF3C7; color:#92400E;',
                      'staff' => 'background:#F3F4F6; color:#374151;'
                    ];
                  ?>
                  <span style="<?= $roleColors[$u['role']] ?? '' ?> padding: 3px 8px; border-radius: 999px; font-size: 11px; font-weight: 700;">
                    <?= strtoupper($u['role']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($u['role'] === 'siswa'): ?>
                    <div>Kelas: <strong><?= htmlspecialchars($u['kelas'] ?? '-') ?></strong></div>
                    <span style="font-size: 11.5px; color: var(--muted);">NISN: <?= htmlspecialchars($u['nisn'] ?? '-') ?></span>
                  <?php elseif ($u['role'] === 'guru'): ?>
                    <div>Kelas: <?= htmlspecialchars($u['kelas'] ?? '-') ?></div>
                    <span style="font-size: 11.5px; color: var(--muted);"><?= htmlspecialchars($u['mapel'] ?? 'Matematika') ?></span>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <td>
                  <div style="font-size: 12.5px;"><?= htmlspecialchars($u['email'] ?? '-') ?></div>
                  <span style="font-size: 11px; color: var(--muted);"><?= htmlspecialchars($u['no_hp'] ?? '-') ?></span>
                </td>
                <td>
                  <?php if ($u['is_online']): ?>
                    <span style="color: var(--success); font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                      <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--success); display: inline-block;"></span> Online
                    </span>
                  <?php else: ?>
                    <span style="color: #94A3B8; font-size: 11.5px;">Offline</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($u['id'] !== $currentUser['id']): ?>
                    <!-- FITUR G: Tombol Hapus Pengguna -->
                    <button type="button" 
                            class="btn btn-danger" 
                            style="padding: 6px 12px; font-size: 11.5px;"
                            onclick="confirmDeleteUser(<?= $u['id'] ?>, '<?= addslashes(htmlspecialchars($u['nama_lengkap'])) ?>', '<?= $u['role'] ?>')">
                      <i data-feather="trash-2" style="width: 12px; height: 12px;"></i> Hapus
                    </button>
                  <?php else: ?>
                    <span style="font-size: 11px; color: var(--muted); font-style: italic;">Akun Anda</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Form Tambah Pengguna Baru -->
    <div class="glass-card" id="addUserFormCard" style="margin-top: 30px;">
      <h2 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0 0 16px 0;">
        + Tambah Akun Pengguna Baru
      </h2>

      <form method="POST" action="">
        <input type="hidden" name="action" value="add_user">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 14px;">
          <div>
            <label class="form-label">Role Pengguna</label>
            <select name="role" class="form-input" required>
              <option value="siswa">Siswa</option>
              <option value="guru">Guru</option>
              <option value="staff">Staff Administrasi</option>
              <option value="admin">Administrator</option>
            </select>
          </div>

          <div>
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-input" placeholder="Contoh: siswa3, guru2" required>
          </div>

          <div>
            <label class="form-label">Kata Sandi (Password)</label>
            <input type="text" name="password" class="form-input" placeholder="Masukkan kata sandi awal" required>
          </div>

          <div>
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-input" placeholder="Nama lengkap dan gelar" required>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 18px;">
          <div>
            <label class="form-label">Email Akun</label>
            <input type="email" name="email" class="form-input" placeholder="nama@sekolah.id">
          </div>

          <div>
            <label class="form-label">Kelas (Untuk Siswa / Guru)</label>
            <input type="text" name="kelas" class="form-input" placeholder="Contoh: VII-A">
          </div>

          <div>
            <label class="form-label">NISN (Khusus Siswa)</label>
            <input type="text" name="nisn" class="form-input" placeholder="10 digit nomor NISN">
          </div>

          <div>
            <label class="form-label">Nomor WhatsApp / HP</label>
            <input type="text" name="no_hp" class="form-input" placeholder="08xxxxxxxxxx">
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
          <i data-feather="check"></i> Daftarkan Pengguna Baru
        </button>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
