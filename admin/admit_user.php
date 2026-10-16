<?php
/**
 * Persetujuan Pengguna & Pendaftaran Kelas (Admit Class)
 * File: admin/admit_user.php
 * FITUR:
 * - Admin melihat daftar Siswa yang baru mendaftar beserta pilihan kelasnya (Admit Siswa).
 * - Admin melihat daftar Pengajuan Kelas Baru oleh Guru berstatus 'PENDING_APPROVAL' (Admit Kelas Guru).
 * - Tombol Setujui (Admit) dan Tolak (Reject).
 * - Saat disetujui, status_approval menjadi 'APPROVED', siswa resmi masuk ke class_students dan tabel users diperbarui.
 * - Saat kelas guru disetujui, status kelas menjadi 'APPROVED' dan siswa dapat memilihnya.
 */
require_once __DIR__ . '/../config/app.php';
require_role('admin');

$pageTitle = 'Persetujuan Pengguna & Kelas (Admit Class) - ElearnMath Admin';
$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// Tangani Aksi Persetujuan / Penolakan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. ADMIT / REJECT KELAS GURU
    if ($action === 'admit_class') {
        $classId = (int)($_POST['class_id'] ?? 0);
        $teacherId = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
        $stmtCls = $db->prepare("SELECT * FROM classes WHERE id = ?");
        $stmtCls->execute([$classId]);
        $targetClass = $stmtCls->fetch();

        if ($targetClass) {
            $teacherId = $teacherId ?: ($targetClass['teacher_id'] ?? 0);
            
            // Set kelas APPROVED dan pastikan teacher_id terisi
            $updCls = $db->prepare("UPDATE classes SET status = 'APPROVED', teacher_id = IF(? > 0, ?, teacher_id) WHERE id = ?");
            $updCls->execute([$teacherId, $teacherId, $classId]);

            if ($teacherId > 0) {
                // Update teacher_class_requests
                try {
                    $updReq = $db->prepare("UPDATE teacher_class_requests SET status = 'APPROVED' WHERE class_id = ? AND teacher_id = ?");
                    $updReq->execute([$classId, $teacherId]);
                } catch (Exception $e) {}

                // Update users.kelas guru
                try {
                    $stmtTeacher = $db->prepare("SELECT kelas FROM users WHERE id = ?");
                    $stmtTeacher->execute([$teacherId]);
                    $currentKelas = $stmtTeacher->fetchColumn();
                    $kelasArr = array_filter(array_map('trim', explode(',', $currentKelas ?: '')));
                    if (!in_array($targetClass['name'], $kelasArr)) {
                        $kelasArr[] = $targetClass['name'];
                        $newKelas = implode(', ', $kelasArr);
                        $updUser = $db->prepare("UPDATE users SET kelas = ?, target_class_id = ? WHERE id = ?");
                        $updUser->execute([$newKelas, $classId, $teacherId]);
                    }
                } catch (Exception $e) {}

                send_notification(
                    $teacherId,
                    'Pengajuan Kelas Disetujui (Admit)',
                    "Permohonan keanggotaan/pembuatan kelas '{$targetClass['name']}' telah disetujui (Admit) oleh Admin. Kelas ini sekarang aktif dan Anda resmi mengampunya.",
                    'info',
                    'guru/index.php'
                );
            }
            flash_set('success', "Keanggotaan Guru pada Kelas '{$targetClass['name']}' berhasil disetujui (Admitted) dan kini aktif!");
        }
        redirect('admin/admit_user.php?tab=classes');
    } elseif ($action === 'reject_class') {
        $classId = (int)($_POST['class_id'] ?? 0);
        $teacherId = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
        $stmtCls = $db->prepare("SELECT * FROM classes WHERE id = ?");
        $stmtCls->execute([$classId]);
        $targetClass = $stmtCls->fetch();

        if ($targetClass) {
            $teacherId = $teacherId ?: ($targetClass['teacher_id'] ?? 0);
            if ($teacherId > 0) {
                try {
                    $updReq = $db->prepare("UPDATE teacher_class_requests SET status = 'REJECTED' WHERE class_id = ? AND teacher_id = ?");
                    $updReq->execute([$classId, $teacherId]);
                } catch (Exception $e) {}

                send_notification(
                    $teacherId,
                    'Pengajuan Kelas Ditolak',
                    "Mohon maaf, permohonan keanggotaan/pembuatan kelas '{$targetClass['name']}' ditolak oleh Admin.",
                    'warning',
                    'guru/index.php'
                );
            }
            if ($targetClass['status'] === 'PENDING_APPROVAL') {
                $delCls = $db->prepare("DELETE FROM classes WHERE id = ?");
                $delCls->execute([$classId]);
            }
            flash_set('warning', "Pengajuan kelas '{$targetClass['name']}' telah ditolak.");
        }
        redirect('admin/admit_user.php?tab=classes');
    }

    // 2. ADMIT / REJECT PENGGUNA (SISWA / GURU)
    $userId = (int)($_POST['user_id'] ?? 0);
    $stmtU = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmtU->execute([$userId]);
    $targetUser = $stmtU->fetch();

    if ($targetUser) {
        if ($action === 'admit') {
            $targetClassId = (int)($_POST['target_class_id'] ?? $targetUser['target_class_id']);
            
            // Ambil nama kelas
            $stmtC = $db->prepare("SELECT name FROM classes WHERE id = ?");
            $stmtC->execute([$targetClassId]);
            $className = $stmtC->fetchColumn() ?: ($targetUser['kelas'] ?: 'VII-A');

            $db->beginTransaction();
            try {
                // 1. Update status pengguna menjadi APPROVED dan set kelasnya
                $updUser = $db->prepare("
                    UPDATE users 
                    SET status_approval = 'APPROVED', kelas = ?, target_class_id = ? 
                    WHERE id = ?
                ");
                $updUser->execute([$className, $targetClassId, $userId]);

                // 2. Jika Siswa, daftarkan ke tabel class_students
                if ($targetUser['role'] === 'siswa' && $targetClassId > 0) {
                    $insCS = $db->prepare("
                        INSERT INTO class_students (class_id, student_id) 
                        VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE class_id = VALUES(class_id)
                    ");
                    $insCS->execute([$targetClassId, $userId]);
                }

                // 3. Kirim notifikasi sambutan ke pengguna
                send_notification(
                    $userId,
                    'Pendaftaran Disetujui (Admit Class)',
                    "Selamat {$targetUser['nama_lengkap']}! Pendaftaran Anda telah disetujui (Admit) oleh Admin. Anda kini resmi aktif di kelas {$className} dan seluruh fitur pembelajaran telah terbuka.",
                    'info',
                    ($targetUser['role'] === 'guru' ? 'guru/index.php' : 'siswa/index.php')
                );

                $db->commit();
                flash_set('success', "Akun {$targetUser['role']} '{$targetUser['nama_lengkap']}' berhasil disetujui (Admitted) ke kelas {$className}!");
            } catch (Exception $e) {
                $db->rollBack();
                flash_set('danger', 'Gagal memproses persetujuan: ' . $e->getMessage());
            }
        } elseif ($action === 'reject') {
            $reason = trim($_POST['reason'] ?? 'Pendaftaran tidak memenuhi kriteria verifikasi sekolah.');
            $updReject = $db->prepare("UPDATE users SET status_approval = 'REJECTED' WHERE id = ?");
            $updReject->execute([$userId]);

            send_notification(
                $userId,
                'Pendaftaran Ditolak',
                "Mohon maaf, pendaftaran akun Anda ditolak oleh Admin. Alasan: {$reason}",
                'warning',
                null
            );

            flash_set('warning', "Pendaftaran '{$targetUser['nama_lengkap']}' telah ditolak.");
        } elseif ($action === 'delete') {
            $del = $db->prepare("DELETE FROM users WHERE id = ?");
            $del->execute([$userId]);
            flash_set('success', "Data pendaftaran '{$targetUser['nama_lengkap']}' berhasil dihapus dari sistem.");
        }
    } else {
        flash_set('danger', 'Data pengguna tidak ditemukan.');
    }

    redirect('admin/admit_user.php');
}

// Filter Tab
$currentTab = $_GET['tab'] ?? 'pending';

// Hitung jumlah pendaftar pending untuk badge
$stmtCountPending = $db->query("SELECT COUNT(*) FROM users WHERE status_approval = 'PENDING'");
$pendingCount = (int)$stmtCountPending->fetchColumn();

// Hitung jumlah pengajuan kelas guru pending (kelas baru + permohonan gabung)
$stmtCountPendingClasses = $db->query("
    SELECT (
        (SELECT COUNT(*) FROM classes WHERE status = 'PENDING_APPROVAL') +
        (SELECT COUNT(*) FROM teacher_class_requests tcr JOIN classes c ON tcr.class_id = c.id WHERE tcr.status = 'PENDING' AND c.status = 'APPROVED')
    ) as total_pending
");
$pendingClassCount = (int)$stmtCountPendingClasses->fetchColumn();

// Ambil daftar kelas untuk modal ubah kelas jika diperlukan
$allClasses = $db->query("SELECT id, name FROM classes ORDER BY name ASC")->fetchAll();

// Ambil data sesuai tab
if ($currentTab === 'classes') {
    $stmtPendingClasses = $db->query("
        SELECT c.id AS class_id, c.name AS class_name, c.created_at, 'new_class' AS req_type,
               u.id AS teacher_id, u.nama_lengkap AS teacher_name, u.email AS teacher_email, u.username AS teacher_username
        FROM classes c 
        LEFT JOIN users u ON c.teacher_id = u.id 
        WHERE c.status = 'PENDING_APPROVAL'
        UNION ALL
        SELECT c.id AS class_id, c.name AS class_name, tcr.created_at, 'join_class' AS req_type,
               u.id AS teacher_id, u.nama_lengkap AS teacher_name, u.email AS teacher_email, u.username AS teacher_username
        FROM teacher_class_requests tcr 
        JOIN classes c ON tcr.class_id = c.id
        JOIN users u ON tcr.teacher_id = u.id 
        WHERE tcr.status = 'PENDING' AND c.status = 'APPROVED'
        ORDER BY created_at DESC
    ");
    $pendingClassList = $stmtPendingClasses->fetchAll();
} elseif ($currentTab === 'approved') {
    $stmtUsers = $db->query("
        SELECT u.*, c.name AS class_name 
        FROM users u 
        LEFT JOIN classes c ON u.target_class_id = c.id 
        WHERE u.status_approval = 'APPROVED' AND u.role IN ('siswa', 'guru')
        ORDER BY u.created_at DESC
    ");
    $userList = $stmtUsers->fetchAll();
} elseif ($currentTab === 'rejected') {
    $stmtUsers = $db->query("
        SELECT u.*, c.name AS class_name 
        FROM users u 
        LEFT JOIN classes c ON u.target_class_id = c.id 
        WHERE u.status_approval = 'REJECTED' AND u.role IN ('siswa', 'guru')
        ORDER BY u.created_at DESC
    ");
    $userList = $stmtUsers->fetchAll();
} else {
    $currentTab = 'pending';
    $stmtUsers = $db->query("
        SELECT u.*, c.name AS class_name 
        FROM users u 
        LEFT JOIN classes c ON u.target_class_id = c.id 
        WHERE u.status_approval = 'PENDING'
        ORDER BY u.created_at ASC
    ");
    $userList = $stmtUsers->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="margin-bottom: 24px;">
      <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 6px;">
        Persetujuan Pengguna & Kelas
      </span>
      <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
        Persetujuan Pendaftaran (Admit Class)
      </h1>
      <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
        Tinjau permohonan pendaftaran siswa ke kelas dan pengajuan kelas baru oleh guru, kemudian berikan persetujuan (Admit) untuk membuka akses pembelajaran.
      </p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- Tab Navigasi -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid var(--line); padding-bottom: 12px; flex-wrap: wrap;">
      <a href="<?= BASE_URL ?>/admin/admit_user.php?tab=pending" 
         class="btn <?= $currentTab === 'pending' ? 'btn-primary' : 'btn-outline' ?>" 
         style="font-size: 13px; padding: 8px 16px; border-radius: 999px;">
        ⏳ Siswa Menunggu Admit
        <?php if ($pendingCount > 0): ?>
          <span style="background: #E3543F; color: #fff; padding: 1px 7px; border-radius: 999px; font-size: 11px; margin-left: 6px;">
            <?= $pendingCount ?>
          </span>
        <?php endif; ?>
      </a>

      <a href="<?= BASE_URL ?>/admin/admit_user.php?tab=classes" 
         class="btn <?= $currentTab === 'classes' ? 'btn-primary' : 'btn-outline' ?>" 
         style="font-size: 13px; padding: 8px 16px; border-radius: 999px;">
        🏫 Pengajuan Kelas Guru
        <?php if ($pendingClassCount > 0): ?>
          <span style="background: #E3543F; color: #fff; padding: 1px 7px; border-radius: 999px; font-size: 11px; margin-left: 6px;">
            <?= $pendingClassCount ?>
          </span>
        <?php endif; ?>
      </a>

      <a href="<?= BASE_URL ?>/admin/admit_user.php?tab=approved" 
         class="btn <?= $currentTab === 'approved' ? 'btn-primary' : 'btn-outline' ?>" 
         style="font-size: 13px; padding: 8px 16px; border-radius: 999px;">
        ✅ Pengguna Disetujui (Approved)
      </a>

      <a href="<?= BASE_URL ?>/admin/admit_user.php?tab=rejected" 
         class="btn <?= $currentTab === 'rejected' ? 'btn-primary' : 'btn-outline' ?>" 
         style="font-size: 13px; padding: 8px 16px; border-radius: 999px;">
        ❌ Ditolak (Rejected)
      </a>
    </div>

    <?php if ($currentTab === 'classes'): ?>
      <!-- TAB PENGAJUAN KELAS GURU -->
      <div class="glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
          <h3 style="font-size: 16px; font-weight: 700; color: var(--blue-800); margin: 0;">
            Daftar Pengajuan Keanggotaan & Pembuatan Kelas Guru Menunggu Admit (<?= $pendingClassCount ?>)
          </h3>
        </div>

        <div class="data-table-container">
          <table class="table-custom">
            <thead>
              <tr>
                <th>Nama Kelas</th>
                <th>Jenis Pengajuan</th>
                <th>Guru Pengaju</th>
                <th>Waktu Pengajuan</th>
                <th>Status</th>
                <th style="text-align: center;">Tindakan Admin</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($pendingClassList)): ?>
                <tr>
                  <td colspan="6" style="text-align: center; color: var(--muted); padding: 32px;">
                    Tidak ada permohonan keanggotaan atau pembuatan kelas guru yang sedang menunggu persetujuan.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($pendingClassList as $cls): ?>
                  <tr>
                    <td>
                      <span style="font-weight: 800; font-size: 14px; color: var(--blue-800); background: #EFF6FF; padding: 6px 14px; border-radius: 6px; border: 1px solid #BFDBFE;">
                        Kelas <?= htmlspecialchars($cls['class_name']) ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($cls['req_type'] === 'new_class'): ?>
                        <span style="background: #E0E7FF; color: #3730A3; padding: 4px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700;">
                          ✨ Buat Rombel Baru
                        </span>
                      <?php else: ?>
                        <span style="background: #ECFDF5; color: #065F46; padding: 4px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700;">
                          🤝 Gabung Kelas Ada
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div style="font-weight: 700; color: var(--ink);"><?= htmlspecialchars($cls['teacher_name'] ?? 'Guru Pengajar') ?></div>
                      <span style="font-size: 11px; color: var(--muted);"><?= htmlspecialchars($cls['teacher_email'] ?? '') ?></span>
                    </td>
                    <td style="font-size: 12px; color: var(--muted);">
                      <?= date('d M Y, H:i', strtotime($cls['created_at'])) ?> WIB
                    </td>
                    <td>
                      <span style="background: #FEF3C7; color: #B45309; padding: 4px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 700;">
                        PENDING
                      </span>
                    </td>
                    <td style="text-align: center;">
                      <div style="display: inline-flex; gap: 8px; align-items: center;">
                        <form method="POST" action="" style="margin: 0;" onsubmit="return confirm('Setujui keanggotaan kelas <?= htmlspecialchars($cls['class_name']) ?> untuk guru ini?');">
                          <input type="hidden" name="action" value="admit_class">
                          <input type="hidden" name="class_id" value="<?= $cls['class_id'] ?>">
                          <input type="hidden" name="teacher_id" value="<?= $cls['teacher_id'] ?>">
                          <button type="submit" class="btn btn-success" style="padding: 6px 14px; font-size: 12px; font-weight: 700;">
                            <i data-feather="check" style="width: 14px; height: 14px;"></i> Setujui (Admit)
                          </button>
                        </form>

                        <form method="POST" action="" style="margin: 0;" onsubmit="return confirm('Tolak permohonan kelas <?= htmlspecialchars($cls['class_name']) ?>?');">
                          <input type="hidden" name="action" value="reject_class">
                          <input type="hidden" name="class_id" value="<?= $cls['class_id'] ?>">
                          <input type="hidden" name="teacher_id" value="<?= $cls['teacher_id'] ?>">
                          <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">
                            <i data-feather="x" style="width: 14px; height: 14px;"></i> Tolak
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php else: ?>
      <!-- TAB PENDAFTARAN PENGGUNA (SISWA / GURU) -->
      <div class="glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
          <h3 style="font-size: 16px; font-weight: 700; color: var(--blue-800); margin: 0;">
            <?php 
              if ($currentTab === 'pending') echo "Daftar Siswa Menunggu Admit ({$pendingCount})";
              elseif ($currentTab === 'approved') echo "Daftar Pengguna Aktif / Disetujui";
              else echo "Daftar Pendaftaran yang Ditolak";
            ?>
          </h3>
        </div>

        <div class="data-table-container">
          <table class="table-custom">
            <thead>
              <tr>
                <th>Pengguna / Pendaftar</th>
                <th>Role</th>
                <th>Username & Email</th>
                <th>Jenis Kelamin & Kontak</th>
                <th>Pilihan Kelas Tujuan</th>
                <th>Waktu Daftar</th>
                <th>Status</th>
                <th style="text-align: center;">Tindakan Admin</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($userList)): ?>
                <tr>
                  <td colspan="8" style="text-align: center; color: var(--muted); padding: 32px;">
                    Tidak ada data pendaftaran pada kategori ini.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($userList as $u): ?>
                  <tr>
                    <td>
                      <div style="display: flex; align-items: center; gap: 10px;">
                        <?php if (!empty($u['foto'])): ?>
                          <img src="<?= BASE_URL . '/' . htmlspecialchars($u['foto']) ?>" alt="Foto" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                          <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--blue-600); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                            <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?>
                          </div>
                        <?php endif; ?>
                        <div>
                          <div style="font-weight: 700; color: var(--ink); font-size: 13.5px;">
                            <?= htmlspecialchars($u['nama_lengkap']) ?>
                          </div>
                          <div style="font-size: 11px; color: var(--muted);">ID: #<?= $u['id'] ?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge-role" style="background: <?= $u['role'] === 'guru' ? 'var(--orange-100)' : 'var(--blue-100)' ?>; color: <?= $u['role'] === 'guru' ? 'var(--orange-dark)' : 'var(--blue-800)' ?>;">
                        <?= ucfirst($u['role']) ?>
                      </span>
                    </td>
                    <td>
                      <div style="font-weight: 600; color: var(--ink); font-size: 13px;">@<?= htmlspecialchars($u['username']) ?></div>
                      <div style="font-size: 11.5px; color: var(--muted);"><?= htmlspecialchars($u['email'] ?: '-') ?></div>
                    </td>
                    <td>
                      <div style="font-size: 12.5px;"><?= htmlspecialchars($u['jenis_kelamin'] ?: '-') ?></div>
                      <div style="font-size: 11.5px; color: var(--muted);"><?= htmlspecialchars($u['no_hp'] ?: '-') ?></div>
                    </td>
                    <td>
                      <span style="display: inline-block; padding: 4px 10px; background: #EFF6FF; border: 1px solid var(--blue-400); border-radius: 6px; font-weight: 700; color: var(--blue-800); font-size: 12.5px;">
                        Kelas <?= htmlspecialchars($u['kelas'] ?: ($u['class_name'] ?: 'Belum Dipilih')) ?>
                      </span>
                    </td>
                    <td style="font-size: 12px; color: var(--muted);">
                      <?= date('d M Y, H:i', strtotime($u['created_at'])) ?> WIB
                    </td>
                    <td>
                      <?php if ($u['status_approval'] === 'PENDING'): ?>
                        <span style="background: #FEF3C7; color: #B45309; padding: 4px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 700;">
                          PENDING
                        </span>
                      <?php elseif ($u['status_approval'] === 'APPROVED'): ?>
                        <span style="background: var(--success-bg); color: var(--success); padding: 4px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 700;">
                          APPROVED
                        </span>
                      <?php else: ?>
                        <span style="background: var(--danger-bg); color: var(--danger); padding: 4px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 700;">
                          REJECTED
                        </span>
                      <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                      <div style="display: inline-flex; gap: 6px; align-items: center;">
                        <?php if ($u['status_approval'] === 'PENDING' || $u['status_approval'] === 'REJECTED'): ?>
                          <!-- Form Setujui (Admit) -->
                          <form method="POST" action="" style="margin: 0;" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui (Admit) <?= htmlspecialchars($u['nama_lengkap']) ?> ke kelas <?= htmlspecialchars($u['kelas'] ?: 'pilihan') ?>?');">
                            <input type="hidden" name="action" value="admit">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="target_class_id" value="<?= $u['target_class_id'] ?: 1 ?>">
                            <button type="submit" class="btn btn-success" style="padding: 6px 12px; font-size: 12px; font-weight: 700;" title="Setujui dan masukkan ke kelas">
                              <i data-feather="check" style="width: 14px; height: 14px;"></i> Setujui (Admit)
                            </button>
                          </form>
                        <?php endif; ?>

                        <?php if ($u['status_approval'] === 'PENDING' || $u['status_approval'] === 'APPROVED'): ?>
                          <!-- Form Tolak (Reject) -->
                          <form method="POST" action="" style="margin: 0;" onsubmit="return confirm('Tolak pendaftaran akun <?= htmlspecialchars($u['nama_lengkap']) ?>?');">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 6px 10px; font-size: 12px;" title="Tolak pendaftaran">
                              <i data-feather="x" style="width: 14px; height: 14px;"></i> Tolak
                            </button>
                          </form>
                        <?php endif; ?>

                        <!-- Hapus Pengguna -->
                        <form method="POST" action="" style="margin: 0;" onsubmit="return confirm('Hapus permanen data pendaftaran ini?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                          <button type="submit" class="btn btn-outline" style="padding: 6px 8px; font-size: 12px; color: var(--danger); border-color: var(--danger);" title="Hapus permanen">
                            <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
