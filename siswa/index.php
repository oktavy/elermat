<?php
/**
 * Dashboard Siswa ElearnMath
 * File: siswa/index.php
 * 
 * FITUR LENGKAP & INTEGRASI:
 * 1. BATASAN AKSES KELAS SISWA (ADMIT BY ADMIN):
 *    - Siswa yang baru mendaftar / belum di-ACC (Admit) oleh Admin:
 *      * BISA login dan masuk ke Dashboard Siswa.
 *      * SELURUH KONTEN KELAS (Materi, LKPD, Latihan, Evaluasi, Jadwal) SAMA SEKALI BELUM MUNCUL / BELUM MASUK.
 *      * Tampilkan banner informasi: "Akses Kelas Anda sedang menunggu persetujuan (Admit) dari Admin untuk masuk ke kelas tujuan."
 *    - Setelah di-ACC Admin:
 *      * Seluruh materi, LKPD, latihan, evaluasi, dan jadwal pelajaran kelas otomatis terhubung dan muncul.
 * 2. HITUNGAN DINAMIS & PROGRES BELAJAR:
 *    - Menghitung secara dinamis modul yang telah dibaca, LKPD tuntas, latihan tuntas, dan progres belajar aktual.
 * 3. TAUTAN MATERI MULTIMEDIA DINAMIS:
 *    - Menampilkan label jenis konten dan ikon visual spesifik (PowerPoint, PDF, Word, Excel, Modul Web, YouTube).
 */

require_once __DIR__ . '/../config/app.php';
require_role('siswa');

$pageTitle = 'Dashboard Siswa - ElearnMath';
$currentUser = auth_user();
$db = getDB();

// 1. Refresh Status Pengguna Langsung dari DB untuk Memastikan Status Admit Terbaru
$stmtUser = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$currentUser['id']]);
$freshUser = $stmtUser->fetch();
if ($freshUser && is_array($freshUser)) {
    $currentUser = array_merge($currentUser, $freshUser);
    $_SESSION['user'] = $currentUser;
}

// 2. Tangani Pengajuan / Pemilihan Kelas oleh Siswa dari Dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'choose_class') {
    $classId = (int)($_POST['target_class_id'] ?? 0);
    if ($classId > 0) {
        $stmtC = $db->prepare("SELECT name FROM classes WHERE id = ?");
        $stmtC->execute([$classId]);
        $cName = $stmtC->fetchColumn();
        if ($cName) {
            $upd = $db->prepare("UPDATE users SET target_class_id = ?, kelas = ?, status_approval = 'PENDING' WHERE id = ?");
            $upd->execute([$classId, $cName, $currentUser['id']]);

            // Kirim notifikasi ke Admin
            try {
                $stmtAdmins = $db->query("SELECT id FROM users WHERE role = 'admin'");
                foreach ($stmtAdmins->fetchAll() as $adm) {
                    send_notification(
                        $adm['id'],
                        'Pengajuan Kelas Murid',
                        "Murid {$currentUser['nama_lengkap']} mengajukan masuk ke kelas {$cName}. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).",
                        'info',
                        'admin/admit_user.php'
                    );
                }
            } catch (Exception $ex) {}

            flash_set('success', "Permohonan masuk ke Kelas {$cName} berhasil diajukan! Menunggu persetujuan (Admit) oleh Admin.");
            redirect('siswa/index.php');
        }
    } else {
        flash_set('danger', 'Silakan pilih kelas tujuan yang valid.');
        redirect('siswa/index.php');
    }
}

$isAdmitted = (strtoupper($currentUser['status_approval'] ?? 'PENDING') === 'APPROVED');
$studentClass = $currentUser['kelas'] ?? '';

// Ambil daftar kelas yang disetujui untuk opsi formulir pengajuan kelas siswa
$availableClasses = [];
try {
    $stmtAvail = $db->query("SELECT id, name FROM classes WHERE status = 'APPROVED' OR status IS NULL ORDER BY name ASC");
    $availableClasses = $stmtAvail->fetchAll();
} catch (Exception $e) {
    $availableClasses = [];
}

// Inisialisasi variabel modul dan aktivitas
$allMateri = [];
$totalModules = 0;
$completedModules = 0;
$progressPercent = 0;
$readMateriIds = [];
$allAssessments = [];
$subsByAssessment = [];
$todaySchedules = [];

// =========================================================================
// 1. DATA KONTEN KELAS: HANYA DIAMBIL JIKA SISWA SUDAH DI-ADMIT ADMIN
// Siswa yang belum di-ACC Admin SAMA SEKALI BELUM MUNCUL konten kelasnya
// =========================================================================
if ($isAdmitted) {
    // 1. Modul Materi Pembelajaran
    try {
        $stmtMateri = $db->prepare("
            SELECT id, judul, urutan, kelas, media_link, youtube_url 
            FROM materi 
            WHERE (kelas = 'Semua Kelas' OR kelas = ? OR kelas IS NULL OR ? = '')
            ORDER BY urutan ASC, id ASC
        ");
        $stmtMateri->execute([$studentClass, $studentClass]);
        $allMateri = $stmtMateri->fetchAll();
        $totalModules = count($allMateri);

        // Riwayat baca materi siswa dari materi_reads
        $stmtReads = $db->prepare("SELECT DISTINCT materi_id FROM materi_reads WHERE user_id = ?");
        $stmtReads->execute([$currentUser['id']]);
        $readMateriIds = $stmtReads->fetchAll(PDO::FETCH_COLUMN);

        foreach ($allMateri as $mat) {
            if (in_array($mat['id'], $readMateriIds)) {
                $completedModules++;
            }
        }
        $progressPercent = $totalModules > 0 ? min(100, round(($completedModules / $totalModules) * 100)) : 0;
    } catch (Exception $e) {
        $allMateri = [];
        $totalModules = 0;
        $completedModules = 0;
        $progressPercent = 0;
    }

    // 2. Seluruh Asesmen Kelas (LKPD, Latihan, Evaluasi)
    try {
        $stmtTotal = $db->prepare("
            SELECT id, tipe, judul, passing_grade, urutan, kelas, duration_minutes 
            FROM assessments 
            WHERE is_active = 1 
              AND (kelas = 'Semua Kelas' OR kelas = ? OR kelas IS NULL OR ? = '')
            ORDER BY FIELD(tipe, 'lkpd', 'latihan', 'evaluasi'), urutan ASC, id ASC
        ");
        $stmtTotal->execute([$studentClass, $studentClass]);
        $allAssessments = $stmtTotal->fetchAll();
    } catch (Exception $e) {
        $allAssessments = [];
    }

    // 3. Pengerjaan Sah Siswa
    try {
        $stmtStudentSubs = $db->prepare("
            SELECT s.id, s.assessment_id, s.total_score, s.status, s.is_corrected, s.attempt, s.allow_retake, 
                   a.tipe, a.passing_grade, a.judul
            FROM submissions s
            JOIN assessments a ON s.assessment_id = a.id
            WHERE s.student_id = ? AND a.is_active = 1 AND s.status IN ('submitted', 'reviewed')
            ORDER BY s.attempt DESC, s.id DESC
        ");
        $stmtStudentSubs->execute([$currentUser['id']]);
        $studentSubmissions = $stmtStudentSubs->fetchAll();

        foreach ($studentSubmissions as $sub) {
            if (!isset($subsByAssessment[$sub['assessment_id']])) {
                $subsByAssessment[$sub['assessment_id']] = $sub;
            }
        }
    } catch (Exception $e) {
        $subsByAssessment = [];
    }

    // 4. Jadwal Pelajaran Hari Ini
    $hariIndonesia = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    $hariIni = $hariIndonesia[date('l')] ?? 'Senin';

    if (!empty($studentClass)) {
        try {
            $stmtJadwal = $db->prepare("
                SELECT * FROM jadwal_pelajaran 
                WHERE (kelas = ? OR kelas LIKE ?) AND hari = ?
                ORDER BY jam_mulai ASC
            ");
            $stmtJadwal->execute([$studentClass, '%' . $studentClass . '%', $hariIni]);
            $todaySchedules = $stmtJadwal->fetchAll();
        } catch (Exception $e) {
            $todaySchedules = [];
        }
    }
} else {
    $hariIndonesia = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    $hariIni = $hariIndonesia[date('l')] ?? 'Senin';
}

// =========================================================================
// 2. STATUS AKTUAL 4 KOMPONEN PEMBELAJARAN
// =========================================================================

// A. Komponen Materi
$totalMateri = $totalModules;
$readMateriCount = $completedModules;

if (!$isAdmitted) {
    $materiStatus = 'Akses Terkunci';
    $materiBadgeBg = '#F1F5F9';
    $materiBadgeColor = '#64748B';
} elseif ($totalMateri === 0) {
    $materiStatus = 'Belum ada materi';
    $materiBadgeBg = '#F1F5F9';
    $materiBadgeColor = '#64748B';
} elseif ($readMateriCount === 0) {
    $materiStatus = 'Belum dibaca';
    $materiBadgeBg = '#FEE2E2';
    $materiBadgeColor = '#DC2626';
} elseif ($readMateriCount < $totalMateri) {
    $materiStatus = "Sedang dipelajari ({$readMateriCount}/{$totalMateri} dibaca)";
    $materiBadgeBg = '#FEF3C7';
    $materiBadgeColor = '#B45309';
} else {
    $materiStatus = "Tuntas ({$readMateriCount}/{$totalMateri} selesai)";
    $materiBadgeBg = 'var(--success-bg)';
    $materiBadgeColor = 'var(--success)';
}

// B. Komponen LKPD
$lkpdList = array_values(array_filter($allAssessments, fn($a) => $a['tipe'] === 'lkpd'));
$totalLkpd = count($lkpdList);
$submittedLkpdCount = 0;
$passedLkpdCount = 0;
$gradedLkpdCount = 0;

foreach ($lkpdList as $item) {
    if (isset($subsByAssessment[$item['id']])) {
        $submittedLkpdCount++;
        $sub = $subsByAssessment[$item['id']];
        $score = $sub['total_score'];
        $kkm = (int)($item['passing_grade'] ?? 0);
        if ($sub['is_corrected'] == 1 || $score !== null) {
            $gradedLkpdCount++;
            if ($kkm <= 0 || ($score !== null && $score >= $kkm)) {
                $passedLkpdCount++;
            }
        }
    }
}

if (!$isAdmitted) {
    $lkpdStatus = 'Akses Terkunci';
    $lkpdBadgeBg = '#F1F5F9';
    $lkpdBadgeColor = '#64748B';
} elseif ($totalLkpd === 0) {
    $lkpdStatus = 'Belum ada LKPD';
    $lkpdBadgeBg = '#F1F5F9';
    $lkpdBadgeColor = '#64748B';
} elseif ($totalLkpd === 1) {
    $singleLkpd = $lkpdList[0];
    $lkpdSub = $subsByAssessment[$singleLkpd['id']] ?? null;
    
    if (!$lkpdSub) {
        $lkpdStatus = 'Belum dikerjakan';
        $lkpdBadgeBg = '#FEE2E2';
        $lkpdBadgeColor = '#DC2626';
    } else {
        $score = $lkpdSub['total_score'];
        $isGraded = ($lkpdSub['is_corrected'] == 1 || $score !== null);
        $kkm = (int)($singleLkpd['passing_grade'] ?? 0);
        
        if (!empty($lkpdSub['allow_retake'])) {
            $lkpdStatus = 'Izin Retake Aktif (Nilai: ' . ($score !== null ? $score : '-') . ')';
            $lkpdBadgeBg = '#FEF3C7';
            $lkpdBadgeColor = '#B45309';
        } elseif (!$isGraded || $score === null) {
            $lkpdStatus = 'Sudah dikumpulkan (Sedang dikoreksi)';
            $lkpdBadgeBg = '#E0F2FE';
            $lkpdBadgeColor = '#0369A1';
        } elseif ($kkm > 0) {
            if ($score >= $kkm) {
                $lkpdStatus = "Tuntas (Nilai: {$score})";
                $lkpdBadgeBg = 'var(--success-bg)';
                $lkpdBadgeColor = 'var(--success)';
            } else {
                $lkpdStatus = "Belum tuntas (Nilai: {$score} / KKM: {$kkm})";
                $lkpdBadgeBg = '#FEF3C7';
                $lkpdBadgeColor = '#B45309';
            }
        } else {
            $lkpdStatus = "Tuntas (Nilai: {$score})";
            $lkpdBadgeBg = 'var(--success-bg)';
            $lkpdBadgeColor = 'var(--success)';
        }
    }
} else {
    // Multiple LKPD
    if ($submittedLkpdCount === 0) {
        $lkpdStatus = 'Belum dikerjakan';
        $lkpdBadgeBg = '#FEE2E2';
        $lkpdBadgeColor = '#DC2626';
    } elseif ($submittedLkpdCount < $totalLkpd) {
        $lkpdStatus = "Sedang dikerjakan ({$submittedLkpdCount}/{$totalLkpd} LKPD)";
        $lkpdBadgeBg = '#FEF3C7';
        $lkpdBadgeColor = '#B45309';
    } else {
        if ($gradedLkpdCount < $totalLkpd) {
            $lkpdStatus = 'Sudah dikumpulkan (Sedang dikoreksi)';
            $lkpdBadgeBg = '#E0F2FE';
            $lkpdBadgeColor = '#0369A1';
        } elseif ($passedLkpdCount >= $totalLkpd) {
            $lkpdStatus = "Tuntas ({$passedLkpdCount}/{$totalLkpd} tuntas)";
            $lkpdBadgeBg = 'var(--success-bg)';
            $lkpdBadgeColor = 'var(--success)';
        } else {
            $lkpdStatus = "Belum tuntas ({$passedLkpdCount}/{$totalLkpd} tuntas)";
            $lkpdBadgeBg = '#FEF3C7';
            $lkpdBadgeColor = '#B45309';
        }
    }
}

// C. Komponen Latihan
$latihanList = array_values(array_filter($allAssessments, fn($a) => $a['tipe'] === 'latihan'));
$totalLatihan = count($latihanList);
$submittedLatCount = 0;
$passedLatCount = 0;
$gradedLatCount = 0;

foreach ($latihanList as $item) {
    if (isset($subsByAssessment[$item['id']])) {
        $submittedLatCount++;
        $sub = $subsByAssessment[$item['id']];
        $score = $sub['total_score'];
        $kkm = (int)($item['passing_grade'] ?? 0);
        if ($sub['is_corrected'] == 1 || $score !== null) {
            $gradedLatCount++;
            if ($kkm <= 0 || ($score !== null && $score >= $kkm)) {
                $passedLatCount++;
            }
        }
    }
}

if (!$isAdmitted) {
    $latihanStatus = 'Akses Terkunci';
    $latihanBadgeBg = '#F1F5F9';
    $latihanBadgeColor = '#64748B';
} elseif ($totalLatihan === 0) {
    $latihanStatus = 'Belum ada latihan';
    $latihanBadgeBg = '#F1F5F9';
    $latihanBadgeColor = '#64748B';
} elseif ($totalLatihan === 1) {
    $singleLat = $latihanList[0];
    $latSub = $subsByAssessment[$singleLat['id']] ?? null;
    
    if (!$latSub) {
        $latihanStatus = 'Belum dikerjakan';
        $latihanBadgeBg = '#FEE2E2';
        $latihanBadgeColor = '#DC2626';
    } else {
        $score = $latSub['total_score'];
        $isGraded = ($latSub['is_corrected'] == 1 || $score !== null);
        $kkm = (int)($singleLat['passing_grade'] ?? 0);
        
        if (!$isGraded || $score === null) {
            $latihanStatus = 'Sudah dikumpulkan (Sedang dikoreksi)';
            $latihanBadgeBg = '#E0F2FE';
            $latihanBadgeColor = '#0369A1';
        } elseif ($kkm > 0) {
            if ($score >= $kkm) {
                $latihanStatus = "Tuntas (Nilai: {$score})";
                $latihanBadgeBg = 'var(--success-bg)';
                $latihanBadgeColor = 'var(--success)';
            } else {
                $latihanStatus = "Belum tuntas (Nilai: {$score} / KKM: {$kkm})";
                $latihanBadgeBg = '#FEF3C7';
                $latihanBadgeColor = '#B45309';
            }
        } else {
            $latihanStatus = "Selesai (Nilai: {$score})";
            $latihanBadgeBg = 'var(--success-bg)';
            $latihanBadgeColor = 'var(--success)';
        }
    }
} else {
    // Multiple Latihan
    if ($submittedLatCount === 0) {
        $latihanStatus = 'Belum dikerjakan';
        $latihanBadgeBg = '#FEE2E2';
        $latihanBadgeColor = '#DC2626';
    } elseif ($submittedLatCount < $totalLatihan) {
        $latihanStatus = "Sedang dikerjakan ({$submittedLatCount}/{$totalLatihan} latihan)";
        $latihanBadgeBg = '#FEF3C7';
        $latihanBadgeColor = '#B45309';
    } else {
        if ($gradedLatCount < $totalLatihan) {
            $latihanStatus = 'Sudah dikumpulkan (Sedang dikoreksi)';
            $latihanBadgeBg = '#E0F2FE';
            $latihanBadgeColor = '#0369A1';
        } elseif ($passedLatCount >= $totalLatihan) {
            $latihanStatus = "Tuntas ({$passedLatCount}/{$totalLatihan} latihan)";
            $latihanBadgeBg = 'var(--success-bg)';
            $latihanBadgeColor = 'var(--success)';
        } else {
            $latihanStatus = "Belum tuntas ({$passedLatCount}/{$totalLatihan} tuntas)";
            $latihanBadgeBg = '#FEF3C7';
            $latihanBadgeColor = '#B45309';
        }
    }
}

// D. Komponen Evaluasi
$evaluasiList = array_values(array_filter($allAssessments, fn($a) => $a['tipe'] === 'evaluasi'));
$totalEvaluasi = count($evaluasiList);

// Evaluasi terkunci jika Latihan 1 belum dikerjakan
$isEvaluasiLocked = false;
if ($isAdmitted) {
    try {
        $stmtLat1 = $db->query("SELECT id FROM assessments WHERE tipe = 'latihan' AND urutan = 1 LIMIT 1");
        $lat1Id = $stmtLat1 ? $stmtLat1->fetchColumn() : null;
        if ($lat1Id && !isset($subsByAssessment[$lat1Id])) {
            $isEvaluasiLocked = true;
        }
    } catch (Exception $e) {}
}

if (!$isAdmitted) {
    $evaluasiStatus = 'Akses Terkunci';
    $evalBadgeBg = '#F1F5F9';
    $evalBadgeColor = '#64748B';
} elseif ($totalEvaluasi === 0) {
    $evaluasiStatus = 'Belum ada evaluasi';
    $evalBadgeBg = '#F1F5F9';
    $evalBadgeColor = '#64748B';
} elseif ($isEvaluasiLocked) {
    $evaluasiStatus = 'Terkunci 🔒 (Selesaikan Latihan 1)';
    $evalBadgeBg = '#F1F5F9';
    $evalBadgeColor = '#64748B';
} else {
    $singleEval = $evaluasiList[0];
    $evalSub = $subsByAssessment[$singleEval['id']] ?? null;
    
    if (!$evalSub) {
        $evaluasiStatus = 'Belum dikerjakan';
        $evalBadgeBg = '#FEE2E2';
        $evalBadgeColor = '#DC2626';
    } else {
        $score = $evalSub['total_score'];
        $isGraded = ($evalSub['is_corrected'] == 1 || $score !== null);
        $kkm = (int)($singleEval['passing_grade'] ?? 0);
        
        if (!$isGraded || $score === null) {
            $evaluasiStatus = 'Sudah dikumpulkan (Sedang dikoreksi)';
            $evalBadgeBg = '#E0F2FE';
            $evalBadgeColor = '#0369A1';
        } elseif ($kkm > 0) {
            if ($score >= $kkm) {
                $evaluasiStatus = "Tuntas (Nilai: {$score})";
                $evalBadgeBg = 'var(--success-bg)';
                $evalBadgeColor = 'var(--success)';
            } else {
                $evaluasiStatus = "Belum tuntas (Nilai: {$score} / KKM: {$kkm})";
                $evalBadgeBg = '#FEF3C7';
                $evalBadgeColor = '#B45309';
            }
        } else {
            $evaluasiStatus = "Selesai (Nilai: {$score})";
            $evalBadgeBg = 'var(--success-bg)';
            $evalBadgeColor = 'var(--success)';
        }
    }
}

$flash = flash_get();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <!-- Hero Banner Siswa -->
    <div class="hero-banner">
      <h1 class="hero-title">Halo, <?= htmlspecialchars($currentUser['nama_lengkap']) ?>! 👋</h1>
      <p class="hero-subtitle">
        Selamat datang di Dashboard ElearnMath. Pantau status pembelajaran Anda, selesaikan lembar LKPD mandiri, modul materi, latihan soal bertingkat, serta evaluasi akhir.
      </p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- ============================================================== -->
    <!-- ALUR SEBELUM DI-ADMIT ADMIN (BANNER INFORMASI & FORM KELAS)    -->
    <!-- ============================================================== -->
    <?php if (!$isAdmitted): ?>
      <div style="background: #FFFBEB; border: 2px solid #F59E0B; border-radius: 16px; padding: 22px 24px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.1); display: flex; align-items: flex-start; gap: 16px;">
        <div style="font-size: 32px; line-height: 1;">⏳</div>
        <div style="flex: 1;">
          <h3 style="font-size: 16px; font-weight: 800; color: #92400E; margin: 0 0 6px 0;">
            Akses Kelas Anda sedang menunggu persetujuan (Admit) dari Admin untuk masuk ke kelas tujuan.
          </h3>
          <p style="font-size: 13.5px; color: #B45309; margin: 0 0 14px 0; line-height: 1.6;">
            <?php if (!empty($studentClass)): ?>
              Status keanggotaan Anda di <strong>Kelas <?= htmlspecialchars($studentClass) ?></strong> sedang menunggu persetujuan (Admit) dari Admin. Seluruh modul materi, LKPD, latihan soal, dan evaluasi kelas belum muncul pada akun Anda hingga disetujui demi ketertiban verifikasi sekolah.
            <?php else: ?>
              Anda belum memilih kelas belajar. Silakan pilih kelas tujuan Anda pada formulir di bawah ini untuk diajukan ke Admin.
            <?php endif; ?>
          </p>

          <!-- Form Pilih / Ganti Pengajuan Kelas -->
          <form method="POST" action="" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; background: rgba(255,255,255,0.85); padding: 12px 16px; border-radius: var(--radius-sm); border: 1px solid #FCD34D;">
            <input type="hidden" name="action" value="choose_class">
            <span style="font-size: 13px; font-weight: 700; color: #92400E;">Pilih / Ganti Kelas:</span>
            <select name="target_class_id" class="form-input" required style="width: auto; min-width: 220px; padding: 8px 12px; font-size: 13px;">
              <option value="">-- Pilih Kelas Belajar --</option>
              <?php foreach ($availableClasses as $ac): ?>
                <option value="<?= $ac['id'] ?>" <?= ($studentClass === $ac['name']) ? 'selected' : '' ?>>
                  Kelas <?= htmlspecialchars($ac['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: 12.5px; font-weight: 700;">
              Ajukan Masuk Kelas
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <!-- Quick Stats Grid (Widget Dinamis) -->
    <div class="grid-cards">
      <!-- Widget 1: Jadwal Hari Ini -->
      <div class="glass-card" style="display: flex; align-items: center; gap: 16px;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: var(--blue-100); color: var(--blue-600); display: flex; align-items: center; justify-content: center;">
          <i data-feather="calendar" style="width: 24px; height: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 12px; color: var(--muted); font-weight: 600;">JADWAL HARI INI</div>
          <div style="font-size: 22px; font-weight: 800; color: var(--blue-800);">
            <?= $isAdmitted ? count($todaySchedules) . ' Sesi' : '<span style="color:var(--muted); font-size:16px;">Terkunci 🔒</span>' ?>
          </div>
          <div style="font-size: 11px; color: var(--muted);"><?= $hariIni ?></div>
        </div>
      </div>

      <!-- Widget 2: Progres Belajar (Dinamis Persentase Aktual) -->
      <div class="glass-card" style="display: flex; flex-direction: column; justify-content: center; gap: 10px;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 40px; height: 40px; border-radius: 12px; background: #EFF6FF; color: var(--blue-600); display: flex; align-items: center; justify-content: center;">
              <i data-feather="trending-up" style="width: 20px; height: 20px;"></i>
            </div>
            <div>
              <div style="font-size: 11.5px; color: var(--muted); font-weight: 700; letter-spacing: 0.5px;">PROGRES BELAJAR</div>
              <div style="font-size: 22px; font-weight: 800; color: var(--blue-800);"><?= $isAdmitted ? $progressPercent . '%' : '0%' ?></div>
            </div>
          </div>
          <span style="font-size: 12px; font-weight: 700; color: <?= $progressPercent == 100 ? 'var(--success)' : 'var(--blue-600)' ?>;">
            <?php if (!$isAdmitted): ?>
              Menunggu Admit
            <?php elseif ($progressPercent == 100): ?>
              Tuntas 🎉
            <?php else: ?>
              Sedang Berjalan
            <?php endif; ?>
          </span>
        </div>
        <!-- Progress Bar Visual -->
        <div style="width: 100%; height: 8px; background: #E2E8F0; border-radius: 999px; overflow: hidden;">
          <div style="width: <?= $isAdmitted ? $progressPercent : 0 ?>%; height: 100%; background: linear-gradient(90deg, var(--blue-600), #10B981); transition: width 0.5s ease;"></div>
        </div>
      </div>

      <!-- Widget 3: Jumlah Modul Tuntas (Dinamis Sesuai Modul Aktual Guru) -->
      <div class="glass-card" style="display: flex; align-items: center; gap: 16px;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: var(--success-bg); color: var(--success); display: flex; align-items: center; justify-content: center;">
          <i data-feather="award" style="width: 24px; height: 24px;"></i>
        </div>
        <div>
          <div style="font-size: 12px; color: var(--muted); font-weight: 600;">JUMLAH MODUL TUNTAS</div>
          <div style="font-size: 22px; font-weight: 800; color: var(--success);">
            <?= $isAdmitted ? "{$completedModules} / {$totalModules}" : '<span style="font-size:16px; color:var(--muted);">Terkunci 🔒</span>' ?>
          </div>
          <div style="font-size: 11px; color: var(--muted);">Modul Materi Pembelajaran</div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- FITUR: AKTIVITAS LENGKAP (MATERI, LKPD, LATIHAN, EVALUASI)     -->
    <!-- ============================================================== -->
    <div class="glass-card" style="margin-bottom: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <div>
          <h2 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0; display: flex; align-items: center; gap: 8px;">
            <i data-feather="activity" style="width: 20px; height: 20px; color: var(--blue-600);"></i>
            Aktivitas Pembelajaran
          </h2>
          <p style="font-size: 13px; color: var(--muted); margin: 4px 0 0 0;">
            Pantau status aktivitas pembelajaran Anda: Materi, LKPD, Latihan, dan Evaluasi secara aktual.
          </p>
        </div>
        <span style="font-size: 12px; color: var(--muted); font-weight: 600;">
          Kelas: <strong><?= htmlspecialchars($studentClass ?: 'Belum Terdaftar') ?></strong>
        </span>
      </div>

      <?php if (!$isAdmitted): ?>
        <div style="text-align: center; padding: 36px 20px; color: var(--muted); background: #F8FAFC; border-radius: var(--radius-sm); border: 1px dashed var(--line);">
          <div style="font-size: 28px; margin-bottom: 8px;">🔒</div>
          <strong style="color: var(--ink); font-size: 14px; display: block; margin-bottom: 4px;">Konten Kelas Belum Tersedia</strong>
          <span>Seluruh aktivitas pembelajaran (Materi, LKPD, Latihan, dan Evaluasi) belum muncul pada akun Anda sampai keanggotaan kelas disetujui (Admit) oleh Admin sekolah.</span>
        </div>
      <?php else: ?>
        <!-- 4 Kotak Ringkasan Aktivitas Pembelajaran -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 20px;">
          <!-- 1. Status Materi -->
          <div style="background: #fff; border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 18px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-weight: 700; color: var(--ink); font-size: 14px; display: flex; align-items: center; gap: 6px;">
                  <i data-feather="book-open" style="width: 16px; height: 16px; color: var(--blue-600);"></i>
                  Materi
                </span>
                <span style="font-size: 11px; color: var(--muted);"><?= $readMateriCount ?>/<?= $totalMateri ?> Selesai</span>
              </div>
              <div style="margin-bottom: 14px;">
                <span style="background: <?= $materiBadgeBg ?>; color: <?= $materiBadgeColor ?>; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 999px; display: inline-block;">
                  <?= $materiStatus ?>
                </span>
              </div>
              <p style="font-size: 12px; color: var(--muted); margin: 0 0 16px 0; line-height: 1.5;">
                Baca dan pelajari konsep materi matematika dengan dukungan berkas multimedia.
              </p>
            </div>
            <div>
              <a href="<?= BASE_URL ?>/siswa/materi.php" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 12px; padding: 7px 12px;">
                Buka Materi &rarr;
              </a>
            </div>
          </div>

          <!-- 2. Status LKPD -->
          <div style="background: #fff; border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 18px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-weight: 700; color: var(--ink); font-size: 14px; display: flex; align-items: center; gap: 6px;">
                  <i data-feather="file-text" style="width: 16px; height: 16px; color: var(--orange-dark);"></i>
                  LKPD
                </span>
                <span style="font-size: 11px; color: var(--muted);"><?= $submittedLkpdCount ?>/<?= $totalLkpd ?> Selesai</span>
              </div>
              <div style="margin-bottom: 14px;">
                <span style="background: <?= $lkpdBadgeBg ?>; color: <?= $lkpdBadgeColor ?>; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 999px; display: inline-block;">
                  <?= $lkpdStatus ?>
                </span>
              </div>
              <p style="font-size: 12px; color: var(--muted); margin: 0 0 16px 0; line-height: 1.5;">
                Lembar Kerja Peserta Didik mandiri dengan jawaban uraian teks atau foto lembar kerja.
              </p>
            </div>
            <div>
              <a href="<?= BASE_URL ?>/siswa/lkpd.php" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 12px; padding: 7px 12px;">
                Buka LKPD &rarr;
              </a>
            </div>
          </div>

          <!-- 3. Status Latihan -->
          <div style="background: #fff; border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 18px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-weight: 700; color: var(--ink); font-size: 14px; display: flex; align-items: center; gap: 6px;">
                  <i data-feather="edit-3" style="width: 16px; height: 16px; color: var(--blue-800);"></i>
                  Latihan
                </span>
                <span style="font-size: 11px; color: var(--muted);"><?= $submittedLatCount ?>/<?= $totalLatihan ?> Selesai</span>
              </div>
              <div style="margin-bottom: 14px;">
                <span style="background: <?= $latihanBadgeBg ?>; color: <?= $latihanBadgeColor ?>; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 999px; display: inline-block;">
                  <?= $latihanStatus ?>
                </span>
              </div>
              <p style="font-size: 12px; color: var(--muted); margin: 0 0 16px 0; line-height: 1.5;">
                Latihan soal mandiri bertingkat untuk menguji pemahaman konsep matematika.
              </p>
            </div>
            <div>
              <a href="<?= BASE_URL ?>/siswa/latihan.php" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 12px; padding: 7px 12px;">
                Mulai Latihan &rarr;
              </a>
            </div>
          </div>

          <!-- 4. Status Evaluasi -->
          <div style="background: #fff; border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 18px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-weight: 700; color: var(--ink); font-size: 14px; display: flex; align-items: center; gap: 6px;">
                  <i data-feather="target" style="width: 16px; height: 16px; color: #DC2626;"></i>
                  Evaluasi
                </span>
                <span style="font-size: 11px; color: var(--muted);"><?= (!empty($evaluasiList) && isset($subsByAssessment[$evaluasiList[0]['id']])) ? '1' : '0' ?>/<?= $totalEvaluasi ?> Selesai</span>
              </div>
              <div style="margin-bottom: 14px;">
                <span style="background: <?= $evalBadgeBg ?>; color: <?= $evalBadgeColor ?>; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 999px; display: inline-block;">
                  <?= $evaluasiStatus ?>
                </span>
              </div>
              <p style="font-size: 12px; color: var(--muted); margin: 0 0 16px 0; line-height: 1.5;">
                Ujian komprehensif akhir bab dengan pengawasan integritas pengerjaan.
              </p>
            </div>
            <div>
              <a href="<?= BASE_URL ?>/siswa/evaluasi.php" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 12px; padding: 7px 12px;">
                Masuk Evaluasi &rarr;
              </a>
            </div>
          </div>
        </div>

        <!-- Rincian Daftar Seluruh Aktivitas Aktual Siswa -->
        <div style="background: #fff; border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 20px;">
          <h3 style="font-size: 15px; font-weight: 800; color: var(--blue-800); margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
            <i data-feather="list" style="width: 18px; height: 18px; color: var(--blue-600);"></i>
            Daftar Modul & Aktivitas Kelas Anda
          </h3>

          <div style="display: flex; flex-direction: column; gap: 12px;">
            <!-- 1. Materi Pembelajaran -->
            <?php foreach ($allMateri as $mat): ?>
              <?php 
                $isRead = in_array($mat['id'], $readMateriIds);
                $mLink = $mat['media_link'] ?: $mat['youtube_url'];
                $mInfo = get_media_type_info($mLink, $mat['judul']);
              ?>
              <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border: 1px solid var(--line); border-radius: 8px; background: #FAFAFA; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <span style="font-size: 24px; line-height: 1;"><?= $mInfo ? $mInfo['icon'] : '📖' ?></span>
                  <div>
                    <div style="font-size: 13.5px; font-weight: 700; color: var(--ink);">
                      Materi <?= (int)$mat['urutan'] ?> — <?= htmlspecialchars($mat['judul']) ?>
                    </div>
                    <?php if ($mInfo): ?>
                      <div style="font-size: 11.5px; color: <?= $mInfo['color'] ?>; font-weight: 700; margin-top: 3px; display: inline-flex; align-items: center; gap: 4px; background: <?= $mInfo['bg'] ?>; padding: 2px 8px; border-radius: 4px;">
                        <?= htmlspecialchars($mInfo['display']) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                  <?php if ($isRead): ?>
                    <span style="background: var(--success-bg); color: var(--success); font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 999px;">
                      ✓ Selesai Dibaca
                    </span>
                  <?php else: ?>
                    <span style="background: #FEE2E2; color: #DC2626; font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 999px;">
                      Belum Dibaca
                    </span>
                  <?php endif; ?>
                  <a href="<?= BASE_URL ?>/siswa/materi_detail.php?id=<?= $mat['id'] ?>" class="btn btn-outline" style="font-size: 11.5px; padding: 5px 12px;">
                    Pelajari &rarr;
                  </a>
                </div>
              </div>
            <?php endforeach; ?>

            <!-- 2. LKPD Mandiri -->
            <?php foreach ($lkpdList as $lk): ?>
              <?php 
                $sub = $subsByAssessment[$lk['id']] ?? null;
                if (!$sub) {
                    $stText = 'Belum dikerjakan';
                    $stBg = '#FEE2E2'; $stCol = '#DC2626';
                } else {
                    $score = $sub['total_score'];
                    $kkm = (int)($lk['passing_grade'] ?? 0);
                    if (!empty($sub['allow_retake'])) {
                        $stText = 'Izin Retake Aktif (Nilai: ' . ($score !== null ? $score : '-') . ')';
                        $stBg = '#FEF3C7'; $stCol = '#B45309';
                    } elseif ($sub['is_corrected'] != 1 && $score === null) {
                        $stText = 'Sudah dikumpulkan (Sedang dikoreksi)';
                        $stBg = '#E0F2FE'; $stCol = '#0369A1';
                    } elseif ($kkm > 0) {
                        if ($score >= $kkm) {
                            $stText = 'Tuntas (Nilai: ' . $score . ')';
                            $stBg = 'var(--success-bg)'; $stCol = 'var(--success)';
                        } else {
                            $stText = 'Belum tuntas (Nilai: ' . $score . ' / KKM: ' . $kkm . ')';
                            $stBg = '#FEF3C7'; $stCol = '#B45309';
                        }
                    } else {
                        $stText = 'Tuntas (Nilai: ' . $score . ')';
                        $stBg = 'var(--success-bg)'; $stCol = 'var(--success)';
                    }
                }
              ?>
              <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border: 1px solid var(--line); border-radius: 8px; background: #FAFAFA; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <span style="font-size: 22px; line-height: 1;">📝</span>
                  <div>
                    <div style="font-size: 13.5px; font-weight: 700; color: var(--ink);">
                      LKPD <?= (int)$lk['urutan'] ?> — <?= htmlspecialchars($lk['judul']) ?>
                    </div>
                    <div style="font-size: 11.5px; color: var(--muted);">Lembar Kerja Peserta Didik</div>
                  </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                  <span style="background: <?= $stBg ?>; color: <?= $stCol ?>; font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 999px;">
                    <?= $stText ?>
                  </span>
                  <?php if (!$sub): ?>
                    <a href="<?= BASE_URL ?>/siswa/pengerjaan.php?type=lkpd&id=<?= $lk['id'] ?>" class="btn btn-primary" style="font-size: 11.5px; padding: 5px 12px;">
                      Mulai Kerjakan &rarr;
                    </a>
                  <?php else: ?>
                    <a href="<?= BASE_URL ?>/siswa/review_hasil.php?submission_id=<?= $sub['id'] ?>" class="btn btn-outline" style="font-size: 11.5px; padding: 5px 12px;">
                      Lihat Hasil & Pembahasan &rarr;
                    </a>
                    <?php if (!empty($sub['allow_retake'])): ?>
                      <a href="<?= BASE_URL ?>/siswa/pengerjaan.php?type=lkpd&id=<?= $lk['id'] ?>" class="btn btn-primary" style="font-size: 11.5px; padding: 5px 12px; background: #D97706; border-color: #D97706;">
                        Kerjakan Ulang &rarr;
                      </a>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>

            <!-- 3. Latihan Mandiri -->
            <?php foreach ($latihanList as $lat): ?>
              <?php 
                $sub = $subsByAssessment[$lat['id']] ?? null;
                if (!$sub) {
                    $stText = 'Belum dikerjakan';
                    $stBg = '#FEE2E2'; $stCol = '#DC2626';
                } else {
                    $score = $sub['total_score'];
                    if ($sub['is_corrected'] != 1 && $score === null) {
                        $stText = 'Sudah dikumpulkan (Sedang dikoreksi)';
                        $stBg = '#E0F2FE'; $stCol = '#0369A1';
                    } else {
                        $stText = 'Selesai (Nilai: ' . $score . ')';
                        $stBg = 'var(--success-bg)'; $stCol = 'var(--success)';
                    }
                }
              ?>
              <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border: 1px solid var(--line); border-radius: 8px; background: #FAFAFA; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <span style="font-size: 22px; line-height: 1;">✏️</span>
                  <div>
                    <div style="font-size: 13.5px; font-weight: 700; color: var(--ink);">
                      Latihan <?= (int)$lat['urutan'] ?> — <?= htmlspecialchars($lat['judul']) ?>
                    </div>
                    <div style="font-size: 11.5px; color: var(--muted);">Latihan Mandiri Bertingkat</div>
                  </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                  <span style="background: <?= $stBg ?>; color: <?= $stCol ?>; font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 999px;">
                    <?= $stText ?>
                  </span>
                  <?php if (!$sub): ?>
                    <a href="<?= BASE_URL ?>/siswa/pengerjaan.php?type=latihan&id=<?= $lat['id'] ?>" class="btn btn-primary" style="font-size: 11.5px; padding: 5px 12px;">
                      Mulai Latihan &rarr;
                    </a>
                  <?php else: ?>
                    <a href="<?= BASE_URL ?>/siswa/review_hasil.php?submission_id=<?= $sub['id'] ?>" class="btn btn-outline" style="font-size: 11.5px; padding: 5px 12px;">
                      Lihat Hasil & Pembahasan &rarr;
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>

            <!-- 4. Evaluasi Akhir -->
            <?php foreach ($evaluasiList as $ev): ?>
              <?php 
                $sub = $subsByAssessment[$ev['id']] ?? null;
                if ($isEvaluasiLocked) {
                    $stText = 'Terkunci 🔒 (Selesaikan Latihan 1)';
                    $stBg = '#F1F5F9'; $stCol = '#64748B';
                } elseif (!$sub) {
                    $stText = 'Belum dikerjakan';
                    $stBg = '#FEE2E2'; $stCol = '#DC2626';
                } else {
                    $score = $sub['total_score'];
                    if ($sub['is_corrected'] != 1 && $score === null) {
                        $stText = 'Sudah dikumpulkan (Sedang dikoreksi)';
                        $stBg = '#E0F2FE'; $stCol = '#0369A1';
                    } else {
                        $stText = 'Selesai (Nilai: ' . $score . ')';
                        $stBg = 'var(--success-bg)'; $stCol = 'var(--success)';
                    }
                }
              ?>
              <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border: 1px solid var(--line); border-radius: 8px; background: #FAFAFA; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <span style="font-size: 22px; line-height: 1;">🎯</span>
                  <div>
                    <div style="font-size: 13.5px; font-weight: 700; color: var(--ink);">
                      Evaluasi <?= (int)$ev['urutan'] ?> — <?= htmlspecialchars($ev['judul']) ?>
                    </div>
                    <div style="font-size: 11.5px; color: var(--muted);">Evaluasi Akhir Bab Matematika</div>
                  </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                  <span style="background: <?= $stBg ?>; color: <?= $stCol ?>; font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 999px;">
                    <?= $stText ?>
                  </span>
                  <?php if ($isEvaluasiLocked): ?>
                    <button class="btn btn-outline" disabled style="font-size: 11.5px; padding: 5px 12px; opacity: 0.6; cursor: not-allowed;">
                      Terkunci 🔒
                    </button>
                  <?php elseif (!$sub): ?>
                    <a href="<?= BASE_URL ?>/siswa/pengerjaan.php?type=evaluasi&id=<?= $ev['id'] ?>" class="btn btn-primary" style="font-size: 11.5px; padding: 5px 12px;">
                      Masuk Evaluasi &rarr;
                    </a>
                  <?php else: ?>
                    <a href="<?= BASE_URL ?>/siswa/review_hasil.php?submission_id=<?= $sub['id'] ?>" class="btn btn-outline" style="font-size: 11.5px; padding: 5px 12px;">
                      Lihat Hasil & Pembahasan &rarr;
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Jadwal Pelajaran Hari Ini -->
    <div class="glass-card" style="margin-bottom: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
        <div>
          <h2 style="font-size: 18px; font-weight: 700; margin: 0; color: var(--blue-800);">
            <i data-feather="clock" style="width: 18px; height: 18px; vertical-align: middle; margin-right: 6px;"></i>
            Jadwal Pelajaran Hari Ini (<?= $hariIni ?>)
          </h2>
          <p style="font-size: 13px; color: var(--muted); margin: 4px 0 0 0;">
            Kelas Anda: <strong><?= htmlspecialchars($studentClass ?: 'Belum Terdaftar') ?></strong>
          </p>
        </div>
        <?php if ($isAdmitted): ?>
          <a href="<?= BASE_URL ?>/siswa/jadwal.php" class="btn btn-outline" style="font-size: 12.5px;">
            Lihat Jadwal Lengkap &rarr;
          </a>
        <?php endif; ?>
      </div>

      <?php if (!$isAdmitted): ?>
        <div style="text-align: center; padding: 24px; color: var(--muted); background: #F8FAFC; border-radius: var(--radius-sm); border: 1px dashed var(--line);">
          🔒 Jadwal kelas belum muncul hingga keanggotaan kelas disetujui (Admit) oleh Admin sekolah.
        </div>
      <?php elseif (empty($todaySchedules)): ?>
        <p style="color: var(--muted); font-size: 13.5px; margin: 0; padding: 12px 0;">Tidak ada sesi tatap muka untuk hari <?= $hariIni ?>.</p>
      <?php else: ?>
        <div class="data-table-container">
          <table class="table-custom">
            <thead>
              <tr>
                <th>Waktu</th>
                <th>Mata Pelajaran</th>
                <th>Guru Pengajar</th>
                <th>Ruangan</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($todaySchedules as $sch): ?>
                <tr>
                  <td><strong><?= substr($sch['jam_mulai'], 0, 5) ?> - <?= substr($sch['jam_selesai'], 0, 5) ?> WIB</strong></td>
                  <td><?= htmlspecialchars($sch['nama_mapel']) ?> <span style="font-size: 11px; color: var(--muted);">(<?= htmlspecialchars($sch['kode_mapel']) ?>)</span></td>
                  <td><?= htmlspecialchars($sch['nama_guru']) ?></td>
                  <td><span class="badge-role" style="font-size: 11px;"><?= htmlspecialchars($sch['ruangan']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Pintasan Cepat Modul Pembelajaran Bebas -->
    <div class="glass-card">
      <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 16px 0; color: var(--blue-800);">
        Pintasan Modul Pembelajaran
      </h2>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
        <!-- Card 1: Materi -->
        <div style="padding: 20px; border-radius: var(--radius-sm); background: #fff; border: 1px solid var(--line); display: flex; flex-direction: column; justify-content: space-between; opacity: <?= $isAdmitted ? '1' : '0.65' ?>;">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <div style="color: var(--blue-600);"><i data-feather="book-open"></i></div>
              <?php if (!$isAdmitted): ?><span title="Terkunci">🔒</span><?php endif; ?>
            </div>
            <div style="font-weight: 700; color: var(--ink); margin-bottom: 4px;">Materi Pembelajaran</div>
            <div style="font-size: 12.5px; color: var(--muted); margin-bottom: 16px;">
              Pelajari konsep materi dengan dukungan berkas presentasi, modul dokumen, dan video pembelajaran.
            </div>
          </div>
          <div>
            <?php if ($isAdmitted): ?>
              <a href="<?= BASE_URL ?>/siswa/materi.php" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 12.5px;">
                Buka Materi &rarr;
              </a>
            <?php else: ?>
              <button class="btn btn-outline" disabled style="width: 100%; justify-content: center; font-size: 12px; cursor: not-allowed;">
                Menunggu Persetujuan 🔒
              </button>
            <?php endif; ?>
          </div>
        </div>

        <!-- Card 2: LKPD Mandiri -->
        <div style="padding: 20px; border-radius: var(--radius-sm); background: #fff; border: 1px solid var(--line); display: flex; flex-direction: column; justify-content: space-between; opacity: <?= $isAdmitted ? '1' : '0.65' ?>;">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <div style="color: var(--orange-dark);"><i data-feather="file-text"></i></div>
              <?php if (!$isAdmitted): ?><span title="Terkunci">🔒</span><?php endif; ?>
            </div>
            <div style="font-weight: 700; color: var(--ink); margin-bottom: 4px;">LKPD Mandiri</div>
            <div style="font-size: 12.5px; color: var(--muted); margin-bottom: 16px;">
              Lembar Kerja Peserta Didik mandiri dengan opsi jawaban teks, unggah berkas foto, atau foto kamera langsung.
            </div>
          </div>
          <div>
            <?php if ($isAdmitted): ?>
              <a href="<?= BASE_URL ?>/siswa/lkpd.php" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 12.5px;">
                Buka LKPD &rarr;
              </a>
            <?php else: ?>
              <button class="btn btn-outline" disabled style="width: 100%; justify-content: center; font-size: 12px; cursor: not-allowed;">
                Menunggu Persetujuan 🔒
              </button>
            <?php endif; ?>
          </div>
        </div>

        <!-- Card 3: Latihan Soal -->
        <div style="padding: 20px; border-radius: var(--radius-sm); background: #fff; border: 1px solid var(--line); display: flex; flex-direction: column; justify-content: space-between; opacity: <?= $isAdmitted ? '1' : '0.65' ?>;">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <div style="color: var(--blue-800);"><i data-feather="edit-3"></i></div>
              <?php if (!$isAdmitted): ?><span title="Terkunci">🔒</span><?php endif; ?>
            </div>
            <div style="font-weight: 700; color: var(--ink); margin-bottom: 4px;">Latihan Soal Bertingkat</div>
            <div style="font-size: 12.5px; color: var(--muted); margin-bottom: 16px;">
              Uji pemahaman mandiri dengan latihan bertingkat tanpa menunggu penilaian guru.
            </div>
          </div>
          <div>
            <?php if ($isAdmitted): ?>
              <a href="<?= BASE_URL ?>/siswa/latihan.php" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 12.5px;">
                Mulai Latihan &rarr;
              </a>
            <?php else: ?>
              <button class="btn btn-outline" disabled style="width: 100%; justify-content: center; font-size: 12px; cursor: not-allowed;">
                Menunggu Persetujuan 🔒
              </button>
            <?php endif; ?>
          </div>
        </div>

        <!-- Card 4: Evaluasi Akhir -->
        <div style="padding: 20px; border-radius: var(--radius-sm); background: #fff; border: 1px solid var(--line); display: flex; flex-direction: column; justify-content: space-between; opacity: <?= $isAdmitted ? '1' : '0.65' ?>;">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <div style="color: #DC2626;"><i data-feather="target"></i></div>
              <?php if (!$isAdmitted): ?><span title="Terkunci">🔒</span><?php endif; ?>
            </div>
            <div style="font-weight: 700; color: var(--ink); margin-bottom: 4px;">Evaluasi Akhir</div>
            <div style="font-size: 12.5px; color: var(--muted); margin-bottom: 16px;">
              Ujian komprehensif yang otomatis terbuka setelah Anda menyelesaikan Latihan 1.
            </div>
          </div>
          <div>
            <?php if ($isAdmitted): ?>
              <a href="<?= BASE_URL ?>/siswa/evaluasi.php" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 12.5px;">
                Masuk Evaluasi &rarr;
              </a>
            <?php else: ?>
              <button class="btn btn-outline" disabled style="width: 100%; justify-content: center; font-size: 12px; cursor: not-allowed;">
                Menunggu Persetujuan 🔒
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div> <!-- /.content-wrapper -->
</div> <!-- /.app-main -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
