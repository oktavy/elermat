<?php
/**
 * Dashboard Guru & Pantauan Pembelajaran Interaktif
 * File: guru/index.php
 * 
 * FITUR LENGKAP:
 * 1. AKSES LANGSUNG GURU & KELOLA KELAS:
 *    Guru langsung aktif setelah mendaftar dan dapat mengampu kelas serta mengelola materi, LKPD, latihan, dan evaluasi.
 * 2. MONITORING AKTIVITAS REAL-TIME:
 *    - Pantauan Siswa Online (5 menit terakhir)
 *    - Pantauan Deteksi Keluar Tab Siswa Real-time (Integrasi tab_switch_logs)
 *    - Pengumpulan Tugas & Ujian Siswa Terbaru (Akses Koreksi AI & Manual)
 * 3. PINTASAN LENGKAP MANAJEMEN PENGAJARAN:
 *    - Kelola LKPD (Tambah, Edit, Hapus)
 *    - Kelola Latihan & Evaluasi
 *    - Kelola Materi Multimedia
 *    - Ekspor Nilai Spreadsheet & Jadwal Mengajar
 * 4. PENGHAPUSAN FITUR LAPOR ADMIN:
 *    Tombol dan fungsi "Lapor Admin" dihapus dan ditiadakan sepenuhnya karena tidak digunakan.
 */

require_once __DIR__ . '/../config/app.php';
require_role('guru');

$pageTitle = 'Dashboard Guru - ElearnMath';
$currentUser = auth_user();
$db = getDB();

// 1. Refresh Data Guru Langsung dari Database
$stmtFresh = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmtFresh->execute([$currentUser['id']]);
$freshU = $stmtFresh->fetch();
if ($freshU && is_array($freshU)) {
    $currentUser = array_merge($currentUser, $freshU);
    $_SESSION['user'] = $currentUser;
}

// 2. Tangani Pengajuan / Pendaftaran Kelas Baru oleh Guru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create_new_class') {
        $newClassName = trim($_POST['new_class_name'] ?? '');
        if (empty($newClassName)) {
            flash_set('danger', 'Nama kelas baru tidak boleh kosong.');
        } else {
            $stmtC = $db->prepare("SELECT id, name, status, teacher_id FROM classes WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $stmtC->execute([$newClassName]);
            $existing = $stmtC->fetch();

            if ($existing) {
                if ($existing['status'] === 'APPROVED') {
                    $insReq = $db->prepare("
                        INSERT INTO teacher_class_requests (teacher_id, class_id, status, created_at)
                        VALUES (?, ?, 'PENDING', NOW())
                        ON DUPLICATE KEY UPDATE status = 'PENDING'
                    ");
                    $insReq->execute([$currentUser['id'], $existing['id']]);

                    try {
                        $stmtAdmins = $db->query("SELECT id FROM users WHERE role = 'admin'");
                        foreach ($stmtAdmins->fetchAll() as $adm) {
                            send_notification(
                                $adm['id'],
                                'Pengajuan Mengampu Kelas Guru',
                                "Guru {$currentUser['nama_lengkap']} mengajukan untuk mengampu kelas '{$existing['name']}'. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).",
                                'info',
                                'admin/admit_user.php?tab=classes'
                            );
                        }
                    } catch (Exception $ex) {}
                    flash_set('success', "Kelas '{$existing['name']}' sudah ada di sistem. Permohonan Anda untuk mengampu kelas ini telah diajukan ke Admin!");
                } else {
                    flash_set('warning', "Kelas '{$existing['name']}' sudah diajukan sebelumnya dan sedang menunggu persetujuan (Admit) Admin.");
                }
            } else {
                $insClass = $db->prepare("
                    INSERT INTO classes (name, teacher_id, status, created_at)
                    VALUES (?, ?, 'PENDING_APPROVAL', NOW())
                ");
                $insClass->execute([$newClassName, $currentUser['id']]);
                $newClassId = $db->lastInsertId();

                $insReq = $db->prepare("
                    INSERT INTO teacher_class_requests (teacher_id, class_id, status, created_at)
                    VALUES (?, ?, 'PENDING', NOW())
                ");
                $insReq->execute([$currentUser['id'], $newClassId]);

                try {
                    $stmtAdmins = $db->query("SELECT id FROM users WHERE role = 'admin'");
                    foreach ($stmtAdmins->fetchAll() as $adm) {
                        send_notification(
                            $adm['id'],
                            'Pengajuan Kelas Guru Baru',
                            "Guru {$currentUser['nama_lengkap']} mengajukan pembuatan kelas baru '{$newClassName}'. Buka menu Persetujuan Pengguna untuk menyetujui (Admit Class).",
                            'info',
                            'admin/admit_user.php?tab=classes'
                        );
                    }
                } catch (Exception $ex) {}

                flash_set('success', "Pengajuan kelas baru '{$newClassName}' berhasil dikirim! Menunggu persetujuan (Admit) oleh Admin.");
            }
        }
        redirect('guru/index.php');

    } elseif ($action === 'join_existing_class') {
        $targetClassId = (int)($_POST['target_class_id'] ?? 0);
        if ($targetClassId <= 0) {
            flash_set('danger', 'Silakan pilih kelas yang ingin diampu.');
        } else {
            $stmtC = $db->prepare("SELECT id, name FROM classes WHERE id = ?");
            $stmtC->execute([$targetClassId]);
            $targetClass = $stmtC->fetch();

            if ($targetClass) {
                $insReq = $db->prepare("
                    INSERT INTO teacher_class_requests (teacher_id, class_id, status, created_at)
                    VALUES (?, ?, 'PENDING', NOW())
                    ON DUPLICATE KEY UPDATE status = 'PENDING'
                ");
                $insReq->execute([$currentUser['id'], $targetClassId]);

                try {
                    $stmtAdmins = $db->query("SELECT id FROM users WHERE role = 'admin'");
                    foreach ($stmtAdmins->fetchAll() as $adm) {
                        send_notification(
                            $adm['id'],
                            'Permohonan Mengampu Kelas Guru',
                            "Guru {$currentUser['nama_lengkap']} mengajukan untuk mengampu kelas '{$targetClass['name']}'. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).",
                            'info',
                            'admin/admit_user.php?tab=classes'
                        );
                    }
                } catch (Exception $ex) {}

                flash_set('success', "Permohonan mengampu Kelas '{$targetClass['name']}' berhasil dikirim! Menunggu persetujuan (Admit) oleh Admin.");
            }
        }
        redirect('guru/index.php');
    }
}

// 3. Ambil Kelas yang Diampu oleh Guru
$myClasses = [];
try {
    $stmtMyClasses = $db->prepare("
        SELECT DISTINCT c.id, c.name, c.status 
        FROM classes c
        LEFT JOIN teacher_class_requests tcr ON c.id = tcr.class_id AND tcr.teacher_id = ?
        WHERE (c.teacher_id = ? OR (tcr.teacher_id = ? AND tcr.status = 'APPROVED'))
          AND (c.status = 'APPROVED' OR c.status IS NULL)
        ORDER BY c.name ASC
    ");
    $stmtMyClasses->execute([$currentUser['id'], $currentUser['id'], $currentUser['id']]);
    $myClasses = $stmtMyClasses->fetchAll();
} catch (Exception $e) {
    $myClasses = [];
}

// Ambil kelas yang pending persetujuan
$pendingClasses = [];
try {
    $stmtPending = $db->prepare("
        SELECT c.id, c.name, 'Kelas Baru' AS tipe_req
        FROM classes c
        WHERE c.teacher_id = ? AND c.status = 'PENDING_APPROVAL'
        UNION
        SELECT c.id, c.name, 'Mengampu Kelas' AS tipe_req
        FROM teacher_class_requests tcr
        JOIN classes c ON tcr.class_id = c.id
        WHERE tcr.teacher_id = ? AND tcr.status = 'PENDING'
    ");
    $stmtPending->execute([$currentUser['id'], $currentUser['id']]);
    $pendingClasses = $stmtPending->fetchAll();
} catch (Exception $e) {
    $pendingClasses = [];
}

// Ambil seluruh daftar kelas sistem untuk opsi pendaftaran mengampu
$allClasses = [];
try {
    $stmtAll = $db->query("SELECT id, name FROM classes WHERE status = 'APPROVED' OR status IS NULL ORDER BY name ASC");
    $allClasses = $stmtAll->fetchAll();
} catch (Exception $e) {
    $allClasses = [];
}

$myClassNames = array_column($myClasses, 'name');
$myClassNamesStr = implode(', ', $myClassNames);

// 4. Statistik Ringkasan Guru
// A. Jumlah Siswa di Kelas yang Diampu
$totalStudents = 0;
if (!empty($myClassNames)) {
    $inClause = implode(',', array_fill(0, count($myClassNames), '?'));
    $stmtTotSiswa = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'siswa' AND status_approval = 'APPROVED' AND kelas IN ($inClause)");
    $stmtTotSiswa->execute($myClassNames);
    $totalStudents = (int)$stmtTotSiswa->fetchColumn();
} else {
    $totalStudents = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'siswa' AND status_approval = 'APPROVED'")->fetchColumn();
}

// B. Siswa Online Real-time (5 menit terakhir)
$onlineStudents = [];
try {
    $stmtOnline = $db->query("
        SELECT id, nama_lengkap, kelas, last_active, foto 
        FROM users 
        WHERE role = 'siswa' 
          AND status_approval = 'APPROVED'
          AND last_active >= (NOW() - INTERVAL 5 MINUTE)
        ORDER BY last_active DESC
    ");
    $onlineStudents = $stmtOnline->fetchAll();
} catch (Exception $e) {
    $onlineStudents = [];
}

// C. Tugas Perlu Dikoreksi
$pendingCorrections = 0;
try {
    $pendingCorrections = (int)$db->query("
        SELECT COUNT(*) FROM submissions 
        WHERE status = 'submitted' AND (is_corrected = 0 OR is_corrected IS NULL)
    ")->fetchColumn();
} catch (Exception $e) {
    $pendingCorrections = 0;
}

// D. Jumlah Modul Aktif (LKPD, Latihan, Evaluasi)
$countLkpd = (int)$db->query("SELECT COUNT(*) FROM assessments WHERE tipe = 'lkpd' AND is_active = 1")->fetchColumn();
$countLatihan = (int)$db->query("SELECT COUNT(*) FROM assessments WHERE tipe = 'latihan' AND is_active = 1")->fetchColumn();
$countEvaluasi = (int)$db->query("SELECT COUNT(*) FROM assessments WHERE tipe = 'evaluasi' AND is_active = 1")->fetchColumn();
$countMateri = (int)$db->query("SELECT COUNT(*) FROM materi")->fetchColumn();

// 5. Data Pantauan Deteksi Keluar Tab Ujian Real-Time Siswa
$recentTabLogs = [];
try {
    $stmtTabs = $db->query("
        SELECT t.* 
        FROM tab_switch_logs t
        ORDER BY t.last_switched_at DESC 
        LIMIT 10
    ");
    $recentTabLogs = $stmtTabs->fetchAll();
} catch (Exception $e) {
    $recentTabLogs = [];
}

// 6. Pengumpulan Tugas Siswa Terbaru
$recentSubmissions = [];
try {
    $stmtSubs = $db->query("
        SELECT s.id, s.submitted_at, s.is_corrected, s.total_score, s.attempt,
               u.nama_lengkap AS nama_siswa, u.kelas,
               a.judul AS judul_assessment, a.tipe AS tipe_assessment
        FROM submissions s
        JOIN users u ON s.student_id = u.id
        JOIN assessments a ON s.assessment_id = a.id
        ORDER BY s.submitted_at DESC 
        LIMIT 8
    ");
    $recentSubmissions = $stmtSubs->fetchAll();
} catch (Exception $e) {
    $recentSubmissions = [];
}

$flash = flash_get();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <!-- Hero Banner Guru -->
    <div class="hero-banner">
      <h1 class="hero-title">Selamat Datang, <?= htmlspecialchars($currentUser['nama_lengkap']) ?>! 👨‍🏫</h1>
      <p class="hero-subtitle">
        Kelola modul pengajaran, pantau keaktifan siswa kelas secara real-time, dan lakukan penilaian tugas secara komprehensif.
      </p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- Banner Peringatan Kelas Pending Persetujuan Admin -->
    <?php if (!empty($pendingClasses)): ?>
      <div style="background: #FFFBEB; border: 2px solid #F59E0B; border-radius: 14px; padding: 18px 22px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.1);">
        <strong style="color: #92400E; font-size: 14px; display: flex; align-items: center; gap: 8px;">
          <span>⏳</span> Pengajuan Kelas Sedang Menunggu Persetujuan (Admit) Admin
        </strong>
        <p style="color: #B45309; font-size: 13px; margin: 4px 0 10px 0;">
          Pengajuan kelas Anda di bawah ini sedang dalam proses verifikasi oleh Admin sekolah:
        </p>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
          <?php foreach ($pendingClasses as $pc): ?>
            <span style="background: #FEF3C7; color: #92400E; font-weight: 700; padding: 4px 12px; border-radius: 999px; font-size: 12px; border: 1px solid #FCD34D;">
              Kelas <?= htmlspecialchars($pc['name']) ?> (<?= htmlspecialchars($pc['tipe_req']) ?>)
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Quick Stats Grid Guru -->
    <div class="grid-cards" style="margin-bottom: 24px;">
      <!-- Card 1: Siswa di Kelas -->
      <div class="glass-card" style="display: flex; align-items: center; gap: 16px;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: var(--blue-100); color: var(--blue-600); display: flex; align-items: center; justify-content: center;">
          <i data-feather="users" style="width: 24px; height: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 12px; color: var(--muted); font-weight: 600;">TOTAL SISWA TERDAFTAR</div>
          <div style="font-size: 24px; font-weight: 800; color: var(--blue-800);"><?= $totalStudents ?> Siswa</div>
          <div style="font-size: 11px; color: var(--muted);"><?= !empty($myClassNamesStr) ? 'Kelas: ' . htmlspecialchars($myClassNamesStr) : 'Seluruh Siswa' ?></div>
        </div>
      </div>

      <!-- Card 2: Siswa Online Real-time -->
      <div class="glass-card" style="display: flex; align-items: center; gap: 16px;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: var(--success-bg); color: var(--success); display: flex; align-items: center; justify-content: center;">
          <i data-feather="activity" style="width: 24px; height: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 12px; color: var(--muted); font-weight: 600;">SISWA ONLINE SAAT INI</div>
          <div style="font-size: 24px; font-weight: 800; color: var(--success);"><?= count($onlineStudents) ?> Siswa</div>
          <div style="font-size: 11px; color: var(--muted);">Aktif dalam 5 menit terakhir</div>
        </div>
      </div>

      <!-- Card 3: Perlu Koreksi -->
      <div class="glass-card" style="display: flex; align-items: center; gap: 16px;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: var(--orange-100); color: var(--orange-dark); display: flex; align-items: center; justify-content: center;">
          <i data-feather="check-square" style="width: 24px; height: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 12px; color: var(--muted); font-weight: 600;">TUGAS PERLU KOREKSI</div>
          <div style="font-size: 24px; font-weight: 800; color: var(--orange-dark);"><?= $pendingCorrections ?> Tugas</div>
          <div style="font-size: 11px; color: var(--muted);"><a href="<?= BASE_URL ?>/guru/koreksi.php" style="color: var(--blue-600); text-decoration: none;">Buka Lembar Koreksi &rarr;</a></div>
        </div>
      </div>
    </div>

    <!-- Manajemen Kelas Guru -->
    <div class="glass-card" style="margin-bottom: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <div>
          <h2 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0;">
            Kelas yang Anda Ampu
          </h2>
          <p style="font-size: 12.5px; color: var(--muted); margin: 4px 0 0 0;">
            Kelola kelas mengajar aktif atau ajukan penambahan kelas baru ke Admin sekolah.
          </p>
        </div>
        <div style="display: flex; gap: 8px;">
          <a href="<?= BASE_URL ?>/guru/siswa.php" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;">
            <i data-feather="users" style="width: 13px; height: 13px;"></i> Daftar Siswa Kelas
          </a>
        </div>
      </div>

      <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px;">
        <?php if (empty($myClasses)): ?>
          <p style="color: var(--muted); font-size: 13px; margin: 0;">Anda belum memiliki kelas aktif. Silakan ajukan pembuatan atau pilih kelas yang ingin diampu di bawah.</p>
        <?php else: ?>
          <?php foreach ($myClasses as $cls): ?>
            <span style="background: #EFF6FF; border: 1px solid var(--blue-200); color: var(--blue-800); font-weight: 700; padding: 6px 16px; border-radius: 10px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
              <span>🏫</span> Kelas <?= htmlspecialchars($cls['name']) ?>
            </span>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Form Pengajuan Kelas Baru / Mengampu Kelas -->
      <div style="background: #F8FAFC; border: 1px solid var(--line); border-radius: 12px; padding: 16px; display: flex; gap: 20px; flex-wrap: wrap; align-items: center;">
        <!-- Opsi 1: Ajukan Kelas Baru -->
        <form method="POST" action="" style="display: flex; gap: 8px; align-items: center; flex: 1; min-width: 280px;">
          <input type="hidden" name="action" value="create_new_class">
          <input type="text" name="new_class_name" class="form-input" placeholder="Nama kelas baru (misal: VII-D)" required style="font-size: 12.5px;">
          <button type="submit" class="btn btn-primary" style="font-size: 12px; padding: 8px 14px; white-space: nowrap;">
            + Ajukan Kelas Baru
          </button>
        </form>

        <div style="color: var(--muted); font-size: 12px;">atau</div>

        <!-- Opsi 2: Mengampu Kelas yang Ada -->
        <form method="POST" action="" style="display: flex; gap: 8px; align-items: center; flex: 1; min-width: 280px;">
          <input type="hidden" name="action" value="join_existing_class">
          <select name="target_class_id" class="form-input" required style="font-size: 12.5px;">
            <option value="">-- Pilih Kelas Terdaftar --</option>
            <?php foreach ($allClasses as $ac): ?>
              <?php if (!in_array($ac['name'], $myClassNames)): ?>
                <option value="<?= $ac['id'] ?>">Kelas <?= htmlspecialchars($ac['name']) ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-outline" style="font-size: 12px; padding: 8px 14px; white-space: nowrap;">
            Ajukan Mengampu
          </button>
        </form>
      </div>
    </div>

    <!-- FITUR: DETEKSI KELUAR TAB SISWA REAL-TIME (PANEL GURU) -->
    <div class="glass-card" style="margin-bottom: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <div>
          <h2 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0; display: flex; align-items: center; gap: 8px;">
            <span style="width: 10px; height: 10px; border-radius: 50%; background: #EF4444;"></span>
            Pantauan Deteksi Keluar Tab Siswa Real-time
          </h2>
          <p style="font-size: 12.5px; color: var(--muted); margin: 4px 0 0 0;">
            Deteksi langsung jika siswa membuka tab lain atau meminimize layar saat pengerjaan LKPD, Latihan, atau Evaluasi.
          </p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
          <a href="<?= BASE_URL ?>/guru/deteksi.php" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;">
            Lihat Riwayat Lengkap &rarr;
          </a>
        </div>
      </div>

      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th>Siswa</th>
              <th>Kelas</th>
              <th>Modul Ujian</th>
              <th>Jumlah Pindah Tab</th>
              <th>Waktu Terakhir</th>
              <th>Keterangan Pelanggaran</th>
            </tr>
          </thead>
          <tbody id="guruTabSwitchBody">
            <?php if (empty($recentTabLogs)): ?>
              <tr>
                <td colspan="6" style="text-align: center; color: var(--muted); padding: 24px;">
                  Belum ada pelanggaran keluar tab ujian yang terdeteksi hari ini.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($recentTabLogs as $tlog): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($tlog['nama_siswa']) ?></strong></td>
                  <td><span class="badge-role" style="font-size: 11px;"><?= htmlspecialchars($tlog['kelas'] ?: '-') ?></span></td>
                  <td><span style="font-weight: 600; color: var(--blue-800); font-size: 13px;"><?= htmlspecialchars($tlog['assessment_title']) ?></span></td>
                  <td>
                    <span style="background: #FEE2E2; color: #DC2626; font-weight: 800; padding: 4px 10px; border-radius: 999px; font-size: 12px;">
                      <?= $tlog['switch_count'] ?> kali
                    </span>
                  </td>
                  <td style="font-size: 12px; color: var(--muted);">
                    <?= date('d M Y, H:i:s', strtotime($tlog['last_switched_at'])) ?> WIB
                  </td>
                  <td style="font-size: 12px; color: var(--ink);">
                    <?= htmlspecialchars($tlog['notes'] ?: 'Pindah tab saat pengerjaan') ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pengumpulan Tugas Siswa Terbaru -->
    <div class="glass-card" style="margin-bottom: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0;">
          Pengumpulan Tugas & Ujian Siswa Terbaru
        </h2>
        <a href="<?= BASE_URL ?>/guru/koreksi.php" class="btn btn-primary" style="font-size: 12px; padding: 6px 12px;">
          Buka Lembar Koreksi &rarr;
        </a>
      </div>

      <?php if (empty($recentSubmissions)): ?>
        <p style="color: var(--muted); font-size: 13px; margin: 0; padding: 16px 0;">Belum ada tugas yang dikumpulkan siswa hari ini.</p>
      <?php else: ?>
        <div class="data-table-container">
          <table class="table-custom">
            <thead>
              <tr>
                <th>Siswa</th>
                <th>Kelas</th>
                <th>Modul Tugas</th>
                <th>Waktu Pengumpulan</th>
                <th>Status Penilaian</th>
                <th>Tindakan</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentSubmissions as $row): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($row['nama_siswa']) ?></strong></td>
                  <td><span class="badge-role" style="font-size: 11px;"><?= htmlspecialchars($row['kelas'] ?? '-') ?></span></td>
                  <td><?= htmlspecialchars($row['judul_assessment']) ?></td>
                  <td><?= date('d M Y, H:i', strtotime($row['submitted_at'])) ?> WIB</td>
                  <td>
                    <?php if ($row['is_corrected']): ?>
                      <span style="background: var(--success-bg); color: var(--success); font-weight: 600; padding: 3px 8px; border-radius: 4px; font-size: 11.5px;">
                        Nilai: <?= $row['total_score'] ?> (Selesai)
                      </span>
                    <?php else: ?>
                      <span style="background: var(--orange-100); color: var(--orange-dark); font-weight: 600; padding: 3px 8px; border-radius: 4px; font-size: 11.5px;">
                        Perlu Koreksi
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="<?= BASE_URL ?>/guru/koreksi.php?submission_id=<?= $row['id'] ?>" class="btn btn-outline" style="font-size: 11.5px; padding: 5px 10px;">
                      Koreksi
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Pintasan Fitur Lengkap Pengajaran Guru -->
    <div class="glass-card">
      <h2 style="font-size: 17px; font-weight: 700; color: var(--blue-800); margin: 0 0 14px 0;">
        Pintasan Modul & Manajemen Pengajaran
      </h2>
      <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>/guru/lkpd.php" class="btn btn-primary">
          <i data-feather="file-text"></i> Kelola LKPD Mandiri
        </a>
        <a href="<?= BASE_URL ?>/guru/latihan.php" class="btn btn-outline">
          <i data-feather="edit-3"></i> Kelola Latihan Soal
        </a>
        <a href="<?= BASE_URL ?>/guru/evaluasi.php" class="btn btn-outline">
          <i data-feather="target"></i> Kelola Evaluasi Akhir
        </a>
        <a href="<?= BASE_URL ?>/guru/materi.php" class="btn btn-outline">
          <i data-feather="book-open"></i> Kelola Materi Pembelajaran
        </a>
        <a href="<?= BASE_URL ?>/guru/export_spreadsheet.php" class="btn btn-success">
          <i data-feather="download"></i> Ekspor Rekap Nilai Spreadsheet
        </a>
        <a href="<?= BASE_URL ?>/guru/koreksi.php" class="btn btn-outline">
          <i data-feather="check-circle"></i> Penilaian Koreksi AI & Guru
        </a>
        <a href="<?= BASE_URL ?>/guru/jadwal.php" class="btn btn-outline">
          <i data-feather="calendar"></i> Jadwal Mengajar (CRUD)
        </a>
        <a href="<?= BASE_URL ?>/guru/profile.php" class="btn btn-outline">
          <i data-feather="user"></i> Edit Profil Guru
        </a>
      </div>
    </div>

  </div>
</div>

<script>
  // Polling Real-time Log Deteksi Keluar Tab Guru setiap 10 detik
  setInterval(function() {
    fetch('<?= BASE_URL ?>/api/get_tab_switches.php')
      .then(r => r.json())
      .then(res => {
        if (res.success && res.data) {
          const tbody = document.getElementById('guruTabSwitchBody');
          if (!tbody) return;
          if (res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px;">Belum ada pelanggaran keluar tab ujian yang terdeteksi hari ini.</td></tr>';
            return;
          }

          let html = '';
          res.data.forEach(item => {
            html += `
              <tr>
                <td><strong>${escapeHtml(item.nama_siswa)}</strong></td>
                <td><span class="badge-role" style="font-size:11px;">${escapeHtml(item.kelas || '-')}</span></td>
                <td><span style="font-weight:600;color:var(--blue-800);">${escapeHtml(item.assessment_title)}</span></td>
                <td>
                  <span style="background:#FEE2E2;color:#DC2626;font-weight:800;padding:4px 10px;border-radius:999px;font-size:11.5px;">
                    ${item.switch_count} kali
                  </span>
                </td>
                <td style="font-size:12px;color:var(--muted);">${item.last_switched_at}</td>
                <td style="font-size:12px;color:var(--ink);">${escapeHtml(item.notes || 'Pindah tab saat pengerjaan')}</td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      })
      .catch(() => {});
  }, 10000);

  function escapeHtml(text) {
    if (!text) return '';
    return String(text)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
