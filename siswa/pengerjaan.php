<?php
/**
 * Lembar Pengerjaan Soal Siswa (LKPD, Latihan, & Evaluasi)
 * File: siswa/pengerjaan.php
 * 
 * FITUR LENGKAP & PERBAIKAN BUG KRITIS:
 * 1. TOMBOL MULAI & TIMER START:
 *    Timer TIDAK PERNAH berjalan saat halaman pertama kali dibuka.
 *    Waktu/timer pengerjaan HANYA BISA MULAI BERJALAN saat siswa secara eksplisit mengklik tombol "Mulai Pengerjaan" pada pop-up konfirmasi.
 * 2. FIX BUG RETAKE TIMER (WAKTU LANGSUNG HABIS):
 *    Waktu pengerjaan retake JUGA HANYA BERJALAN jika siswa sudah menekan tombol "Mulai Pengerjaan" retake.
 *    Saat siswa diizinkan melakukan 'Retake' (pengerjaan ulang), sistem WAJIB melakukan RESET / UPDATE timestamp start_time
 *    di session ($_SESSION['start_time'], $_SESSION[$startSessionKey]) dan database untuk sesi retake baru.
 *    JANGAN gunakan timestamp pengerjaan pertama agar waktu TIDAK LANGSUNG HABIS seketika saat retake dibuka.
 * 3. FITUR SOAL ESSAY (UPLOAD FOTO & WEBCAM):
 *    Pada tiap butir soal essay, sediakan 3 opsi terintegrasi: Teks Uraian ATAU Unggah Berkas Foto (JPG, PNG, PDF) ATAU Ambil Foto Langsung via Webcam/Kamera HP.
 *    Tersedia pratinjau foto rapi sebelum dikirim dan tombol hapus/ganti foto.
 * 4. KUNCI JAWABAN & PEMBAHASAN:
 *    Tersedia lengkap di lembar review hasil setelah dikoreksi.
 * 5. DETEKSI PINDAH TAB (TAB SWITCH LOGS):
 *    Merekam aktivitas siswa keluar tab/browser untuk menjaga integritas ujian.
 * 6. BATASAN AKSES KELAS (ADMIT ADMIN):
 *    Hanya siswa yang telah berstatus APPROVED yang dapat membuka dan mengerjakan lembar soal.
 */

require_once __DIR__ . '/../config/app.php';
require_role('siswa');

$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// 1. Refresh Status Pengguna & Cek Status Persetujuan Admin (Admit Class)
$stmtUser = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$currentUser['id']]);
$freshUser = $stmtUser->fetch();
if ($freshUser && is_array($freshUser)) {
    $currentUser = array_merge($currentUser, $freshUser);
    $_SESSION['user'] = $currentUser;
}

if (strtoupper($currentUser['status_approval'] ?? 'PENDING') !== 'APPROVED') {
    flash_set('warning', 'Akses Kelas Anda sedang menunggu persetujuan (Admit) dari Admin untuk masuk ke kelas tujuan.');
    redirect('siswa/index.php');
}

$studentClass = $currentUser['kelas'] ?? '';

// 2. Tentukan Assessment yang Akan Dikerjakan
$assessmentId = !empty($_GET['id']) ? (int)$_GET['id'] : 0;
$assessmentTipe = $_GET['type'] ?? $_GET['tipe'] ?? '';

if ($assessmentId > 0) {
    $stmtAss = $db->prepare("SELECT * FROM assessments WHERE id = ? LIMIT 1");
    $stmtAss->execute([$assessmentId]);
    $assessment = $stmtAss->fetch();
} elseif (!empty($assessmentTipe)) {
    $stmtAss = $db->prepare("
        SELECT * FROM assessments 
        WHERE tipe = ? AND is_active = 1 
          AND (kelas = 'Semua Kelas' OR kelas = ? OR kelas IS NULL OR ? = '') 
        ORDER BY urutan ASC, id ASC LIMIT 1
    ");
    $stmtAss->execute([$assessmentTipe, $studentClass, $studentClass]);
    $assessment = $stmtAss->fetch();

    if (!$assessment) {
        // Fallback ke assessment aktif pertama sesuai tipe tanpa batasan kelas
        $stmtFallback = $db->prepare("
            SELECT * FROM assessments 
            WHERE tipe = ? AND is_active = 1 
            ORDER BY urutan ASC, id ASC LIMIT 1
        ");
        $stmtFallback->execute([$assessmentTipe]);
        $assessment = $stmtFallback->fetch();
    }
} else {
    // Default buka LKPD aktif pertama
    $assessment = $db->query("SELECT * FROM assessments WHERE tipe = 'lkpd' AND is_active = 1 ORDER BY urutan ASC, id ASC LIMIT 1")->fetch();
}

if (!$assessment) {
    flash_set('danger', 'Modul pembelajaran tidak ditemukan atau belum aktif.');
    redirect('siswa/index.php');
}

$assessmentId = (int)$assessment['id'];

// 3. Hirarki Penguncian Evaluasi: Wajib Menyelesaikan Latihan 1 Terlebih Dahulu
$isLocked = false;
$lockReason = '';

if ($assessment['tipe'] === 'evaluasi') {
    $stmtLat1 = $db->prepare("SELECT a.id, a.judul FROM assessments a WHERE a.tipe = 'latihan' AND a.urutan = 1 LIMIT 1");
    $stmtLat1->execute();
    $lat1 = $stmtLat1->fetch();

    if ($lat1) {
        $stmtSubLat1 = $db->prepare("SELECT id FROM submissions WHERE student_id = ? AND assessment_id = ? AND status IN ('submitted', 'reviewed')");
        $stmtSubLat1->execute([$currentUser['id'], $lat1['id']]);
        $hasSubmittedLat1 = $stmtSubLat1->fetch();

        if (!$hasSubmittedLat1) {
            $isLocked = true;
            $lockReason = "Modul Evaluasi Akhir terkunci. Anda wajib menyelesaikan dan mengumpulkan " . htmlspecialchars($lat1['judul']) . " terlebih dahulu.";
        }
    }
}

// 4. Cek Submission Siswa untuk Modul Ini (Ambil Attempt Terakhir)
$stmtSub = $db->prepare("
    SELECT * FROM submissions 
    WHERE student_id = ? AND assessment_id = ? 
    ORDER BY attempt DESC, id DESC 
    LIMIT 1
");
$stmtSub->execute([$currentUser['id'], $assessmentId]);
$existingSub = $stmtSub->fetch();

$hasSubmitted = ($existingSub !== null && is_array($existingSub) && in_array($existingSub['status'] ?? '', ['submitted', 'reviewed']));
$isScored = ($existingSub && ($existingSub['is_corrected'] == 1 || $existingSub['total_score'] !== null));

// Aturan Retake: Berlaku jika guru memberikan allow_retake = 1
$allowRetake = ($existingSub && !empty($existingSub['allow_retake']) && $existingSub['allow_retake'] == 1);
$canAttempt = (!$hasSubmitted || $allowRetake);

// Jika sudah dikumpulkan dan tidak ada izin retake, alihkan langsung ke lembar review hasil
if ($hasSubmitted && !$allowRetake && !$isLocked) {
    redirect('siswa/review_hasil.php?submission_id=' . $existingSub['id']);
}

// =========================================================================
// 5. KONTROL MULAI PENGERJAAN & PERBAIKAN BUG RETAKE TIMER
// =========================================================================
$startSessionKey = 'exam_start_time_' . $currentUser['id'] . '_' . $assessmentId;
$retakeActiveKey = 'retake_active_' . $currentUser['id'] . '_' . $assessmentId;

// JIKA INI SESI RETAKE BARU:
// Pastikan timer TIDAK langsung berjalan dari submission lama!
// Hapus timer lama jika siswa belum mengklik tombol "Mulai Pengerjaan Ulang (Retake)" untuk sesi ini.
if ($allowRetake && empty($_SESSION[$retakeActiveKey])) {
    unset($_SESSION[$startSessionKey]);
    unset($_SESSION['start_time']);
}

// Cek apakah siswa sudah mengklik tombol mulai pengerjaan
$hasStarted = isset($_SESSION[$startSessionKey]) && (!empty($_SESSION[$startSessionKey]));

// PROSES KLIK TOMBOL "MULAI PENGERJAAN" / "MULAI PENGERJAAN ULANG (RETAKE)"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start_exam' && $canAttempt && !$isLocked) {
    $now = time();
    $nowSql = date('Y-m-d H:i:s', $now);

    // 1. Reset / Update Session Start Time
    $_SESSION[$startSessionKey] = $now;
    $_SESSION['start_time'] = $now; // Set standard session start_time
    $_SESSION[$retakeActiveKey] = $now;

    // 2. Jika ini Retake: WAJIB Reset / Update start_time di database agar timer TIDAK langsung habis
    if ($allowRetake && $existingSub) {
        try {
            $updRetakeTime = $db->prepare("
                UPDATE submissions 
                SET start_time = ?, started_at = ? 
                WHERE id = ?
            ");
            $updRetakeTime->execute([$nowSql, $nowSql, $existingSub['id']]);
        } catch (Exception $e) {}
    }

    // 3. Beri tanda ke JavaScript untuk menghapus localStorage timer lama
    $_SESSION['timer_reset_needed_' . $assessmentId] = true;

    redirect('siswa/pengerjaan.php?id=' . $assessmentId);
}

// Ambil Butir Soal Modul
$stmtQ = $db->prepare("SELECT * FROM questions WHERE assessment_id = ? ORDER BY urutan ASC, id ASC");
$stmtQ->execute([$assessmentId]);
$questions = $stmtQ->fetchAll();

// =========================================================================
// 6. PROSES PENGUMPULAN JAWABAN SISWA (TEKS / UPLOAD FOTO / KAMERA WEBCAM)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_assessment' && $canAttempt && !$isLocked) {
    $answers       = $_POST['answers'] ?? [];
    $cameraAnswers = $_POST['camera_answers'] ?? [];
    $correctPgCount = 0;
    $totalPgCount   = 0;

    $uploadDir = __DIR__ . '/../assets/uploads/jawaban/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $db->beginTransaction();
    try {
        // Hitung nomor attempt baru
        $prevAttempt = 0;
        if ($existingSub) {
            $stmtAtt = $db->prepare("SELECT MAX(attempt) FROM submissions WHERE student_id = ? AND assessment_id = ?");
            $stmtAtt->execute([$currentUser['id'], $assessmentId]);
            $prevAttempt = (int)$stmtAtt->fetchColumn();
        }
        $currentAttempt = max(1, $prevAttempt + 1);

        $startTimeTimestamp = $_SESSION[$startSessionKey] ?? time();
        $startTimeSql = date('Y-m-d H:i:s', $startTimeTimestamp);

        // 1. Simpan Baris Submission Baru ke Database
        $insSub = $db->prepare("
            INSERT INTO submissions 
            (student_id, assessment_id, status, is_corrected, allow_retake, attempt, start_time, started_at, submitted_at)
            VALUES (?, ?, 'submitted', 0, 0, ?, ?, ?, NOW())
        ");
        $insSub->execute([
            $currentUser['id'], 
            $assessmentId, 
            $currentAttempt,
            $startTimeSql,
            $startTimeSql
        ]);
        $submissionId = $db->lastInsertId();

        // 2. Simpan Setiap Butir Jawaban Siswa
        $insAns = $db->prepare("
            INSERT INTO submission_answers (submission_id, question_id, jawaban_siswa, foto_jawaban, is_correct)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($questions as $q) {
            $qId = $q['id'];
            $ansText = trim($answers[$qId] ?? '');
            $photoPath = null;
            $isCorrect = null;

            // A. Penanganan Unggah Berkas Foto / PDF
            if (isset($_FILES['file_answers']['name'][$qId]) && $_FILES['file_answers']['error'][$qId] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['file_answers']['name'][$qId], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
                if (in_array($ext, $allowed)) {
                    $newFilename = 'ans_' . $currentUser['id'] . '_' . $qId . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    $dest = $uploadDir . $newFilename;
                    if (move_uploaded_file($_FILES['file_answers']['tmp_name'][$qId], $dest)) {
                        $photoPath = 'assets/uploads/jawaban/' . $newFilename;
                    }
                }
            }

            // B. Penanganan Foto Kamera Webcam Langsung (Base64)
            if (empty($photoPath) && !empty($cameraAnswers[$qId])) {
                $rawBase64 = $cameraAnswers[$qId];
                if (preg_match('/^data:image\/(\w+);base64,/', $rawBase64)) {
                    $data = substr($rawBase64, strpos($rawBase64, ',') + 1);
                    $decoded = base64_decode($data);
                    if ($decoded !== false) {
                        $newFilename = 'cam_' . $currentUser['id'] . '_' . $qId . '_' . time() . '_' . rand(100, 999) . '.jpg';
                        $dest = $uploadDir . $newFilename;
                        if (file_put_contents($dest, $decoded)) {
                            $photoPath = 'assets/uploads/jawaban/' . $newFilename;
                        }
                    }
                }
            }

            // C. Evaluasi Skor Otomatis Pilihan Ganda (PG)
            if ($q['tipe'] === 'pg') {
                $totalPgCount++;
                $kunciDb = trim((string)$q['kunci_jawaban']);
                if ($ansText !== '' && ((string)$ansText === $kunciDb || strtoupper($ansText) === strtoupper($kunciDb))) {
                    $isCorrect = 1;
                    $correctPgCount++;
                } else {
                    $isCorrect = 0;
                }
            }

            $insAns->execute([$submissionId, $qId, $ansText, $photoPath, $isCorrect]);
        }

        // Kalkulasi Skor PG
        $pgScore = ($totalPgCount > 0) ? round(($correctPgCount / $totalPgCount) * 100, 2) : null;
        
        // Cek jika seluruh butir soal hanya PG (tanpa essay), langsung tuntas dinilai
        $hasEssay = false;
        foreach ($questions as $q) {
            if ($q['tipe'] === 'essay') { 
                $hasEssay = true; 
                break; 
            }
        }

        if (!$hasEssay && $pgScore !== null) {
            $updScore = $db->prepare("UPDATE submissions SET pg_score = ?, total_score = ?, is_corrected = 1 WHERE id = ?");
            $updScore->execute([$pgScore, $pgScore, $submissionId]);
        } else {
            $updScore = $db->prepare("UPDATE submissions SET pg_score = ? WHERE id = ?");
            $updScore->execute([$pgScore, $submissionId]);
        }

        // Kirim Notifikasi ke Guru Pengajar
        try {
            $stmtGuru = $db->query("SELECT id FROM users WHERE role = 'guru'");
            foreach ($stmtGuru->fetchAll() as $g) {
                send_notification(
                    $g['id'],
                    'Pengumpulan Tugas Siswa',
                    "Siswa {$currentUser['nama_lengkap']} ({$currentUser['kelas']}) telah mengumpulkan '{$assessment['judul']}' (Percobaan Ke-{$currentAttempt}).",
                    'grade',
                    'guru/koreksi.php?submission_id=' . $submissionId
                );
            }
        } catch (Exception $ex) {}

        $db->commit();

        // Bersihkan seluruh session timer pengerjaan
        unset($_SESSION[$startSessionKey]);
        unset($_SESSION['start_time']);
        unset($_SESSION[$retakeActiveKey]);
        unset($_SESSION['timer_reset_needed_' . $assessmentId]);

        flash_set('success', 'Jawaban Anda berhasil dikumpulkan! Anda dapat meninjau hasil dan pembahasan di bawah.');
        redirect('siswa/review_hasil.php?submission_id=' . $submissionId);
    } catch (Exception $e) {
        $db->rollBack();
        flash_set('danger', 'Gagal mengumpulkan lembar jawaban: ' . $e->getMessage());
    }
}

$pageTitle = htmlspecialchars($assessment['judul']) . ' - ElearnMath Siswa';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$resetStorage = false;
if (!empty($_SESSION['timer_reset_needed_' . $assessmentId])) {
    $resetStorage = true;
    unset($_SESSION['timer_reset_needed_' . $assessmentId]);
}
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper" style="max-width: 980px; margin: 0 auto;">

    <!-- Navigasi Menu Atas -->
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
      <a href="<?= BASE_URL ?>/siswa/<?= $assessment['tipe'] ?>.php" class="btn btn-outline" style="font-size: 12.5px; padding: 6px 14px;">
        &larr; Kembali ke Menu <?= strtoupper($assessment['tipe']) ?>
      </a>
      <span class="badge-role" style="font-size: 11.5px; background: var(--blue-100); color: var(--blue-800); padding: 5px 12px;">
        <?= strtoupper($assessment['tipe']) ?> <?= $assessment['urutan'] ? ('#' . $assessment['urutan']) : '' ?>
      </span>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- ============================================================== -->
    <!-- KONDISI 1: MODUL TERKUNCI (PRASYARAT LATIHAN 1)                -->
    <!-- ============================================================== -->
    <?php if ($isLocked): ?>
      <div class="glass-card" style="text-align: center; padding: 50px 24px; border: 2px dashed #CBD5E1; background: #F8FAFC;">
        <div style="width: 70px; height: 70px; border-radius: 50%; background: #FEE2E2; color: #DC2626; font-size: 32px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 18px;">
          🔒
        </div>
        <h2 style="font-size: 22px; font-weight: 800; color: #1E293B; margin: 0 0 8px 0;">
          Modul Ini Masih Terkunci
        </h2>
        <p style="font-size: 14.5px; color: var(--muted); max-width: 560px; margin: 0 auto 24px auto; line-height: 1.6;">
          <?= htmlspecialchars($lockReason) ?>
        </p>
        <a href="<?= BASE_URL ?>/siswa/latihan.php" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
          <i data-feather="edit-3"></i> Kerjakan Latihan 1 Sekarang &rarr;
        </a>
      </div>

    <!-- ============================================================== -->
    <!-- KONDISI 2: POP-UP KONFIRMASI MULAI (TIMER BELUM JALAN)        -->
    <!-- TIMER HANYA BISA MULAI SETELAH TOMBOL DI BAWAH DITEKAN         -->
    <!-- ============================================================== -->
    <?php elseif (!$hasStarted): ?>
      <?php
        $startBtnText = 'Mulai Pengerjaan';
        if ($allowRetake) {
            $nextAttemptNum = ($existingSub ? (int)$existingSub['attempt'] + 1 : 2);
            $startBtnText = 'Mulai Pengerjaan Ulang (Retake Attempt #' . $nextAttemptNum . ')';
        } elseif ($assessment['tipe'] === 'latihan') {
            $startBtnText = 'Mulai Pengerjaan Latihan';
        } elseif ($assessment['tipe'] === 'lkpd') {
            $startBtnText = 'Mulai Pengerjaan LKPD';
        } elseif ($assessment['tipe'] === 'evaluasi') {
            $startBtnText = 'Mulai Pengerjaan Evaluasi';
        }
      ?>
      <div class="glass-card" style="text-align: center; padding: 48px 24px; border: 2px solid var(--blue-600); background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
        <div style="width: 72px; height: 72px; border-radius: 20px; background: #EFF6FF; color: var(--blue-800); font-size: 32px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
          ⏱️
        </div>

        <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 10px;">
          Konfirmasi Memulai Pengerjaan
        </span>

        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 10px 0;">
          <?= htmlspecialchars($assessment['judul']) ?>
        </h1>

        <p style="font-size: 14px; color: var(--muted); max-width: 580px; margin: 0 auto 24px auto; line-height: 1.6;">
          <?= htmlspecialchars($assessment['deskripsi'] ?: 'Kerjakan lembar soal matematika ini dengan cermat, teliti, dan jujur.') ?>
        </p>

        <!-- Informasi Parameter Ujian (Timer Masih Belum Aktif) -->
        <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; margin-bottom: 30px;">
          <div style="background: #F8FAFC; border: 1px solid var(--line); border-radius: 12px; padding: 14px 22px; min-width: 140px;">
            <div style="font-size: 11px; color: var(--muted); font-weight: 700;">DURASI PENGERJAAN</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--blue-800);"><?= (int)$assessment['duration_minutes'] ?> Menit</div>
          </div>
          <div style="background: #F8FAFC; border: 1px solid var(--line); border-radius: 12px; padding: 14px 22px; min-width: 140px;">
            <div style="font-size: 11px; color: var(--muted); font-weight: 700;">STATUS TIMER</div>
            <div style="font-size: 17px; font-weight: 800; color: #64748B;">00:00 (Belum Berjalan)</div>
          </div>
          <div style="background: #F8FAFC; border: 1px solid var(--line); border-radius: 12px; padding: 14px 22px; min-width: 140px;">
            <div style="font-size: 11px; color: var(--muted); font-weight: 700;">JUMLAH SOAL</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--ink);"><?= count($questions) ?> Butir</div>
          </div>
        </div>

        <div style="background: #FEF3C7; border: 1px solid #FCD34D; border-radius: var(--radius-sm); padding: 16px 22px; max-width: 640px; margin: 0 auto 30px auto; text-align: left; font-size: 13px; color: #92400E; line-height: 1.6;">
          📌 <strong>Petunjuk Penting Pengerjaan:</strong>
          <ul style="margin: 8px 0 0 0; padding-left: 20px;">
            <li>Waktu/timer pengerjaan <strong>HANYA BISA MULAI BERJALAN</strong> saat Anda secara eksplisit mengklik tombol di bawah.</li>
            <?php if ($allowRetake): ?>
              <li><strong>Pengerjaan Ulang (Retake):</strong> Waktu timer Anda diatur ulang secara penuh (reset) dan tidak menggunakan sisa waktu dari percobaan pertama.</li>
            <?php endif; ?>
            <li>Pada setiap butir soal essay, Anda dapat menjawab menggunakan <strong>Uraian Teks</strong>, <strong>Unggah Berkas Foto Lembar Kerja</strong>, atau <strong>Ambil Foto Langsung via Webcam/Kamera HP</strong>.</li>
            <li>Dilarang berpindah tab browser atau meminimize jendela selama pengerjaan berlangsung demi integritas ujian.</li>
          </ul>
        </div>

        <form method="POST" action="">
          <input type="hidden" name="action" value="start_exam">
          <button type="submit" class="btn btn-primary" style="padding: 14px 44px; font-size: 16px; font-weight: 800; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);">
            🚀 <?= htmlspecialchars($startBtnText) ?>
          </button>
        </form>
      </div>

    <!-- ============================================================== -->
    <!-- KONDISI 3: LEMBAR PENGERJAAN SOAL (TIMER BERJALAN AKTIF)      -->
    <!-- ============================================================== -->
    <?php else: ?>
      <?php $serverStartTimestamp = (int)($_SESSION[$startSessionKey] ?? time()); ?>

      <!-- Header Ujian & Floating Countdown Timer -->
      <div class="glass-card" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; position: sticky; top: 16px; z-index: 10; box-shadow: 0 10px 30px rgba(0,0,0,0.08);">
        <div>
          <h2 style="font-size: 19px; font-weight: 800; color: var(--blue-800); margin: 0 0 4px 0;">
            <?= htmlspecialchars($assessment['judul']) ?>
          </h2>
          <div style="font-size: 12.5px; color: var(--muted);">
            Selesaikan seluruh butir soal sebelum batas waktu pengerjaan berakhir.
          </div>
        </div>

        <!-- Floating Countdown Timer Badge -->
        <div id="examTimerBadge" 
             data-duration-minutes="<?= (int)$assessment['duration_minutes'] ?>"
             data-start-time="<?= $serverStartTimestamp ?>"
             data-assessment-id="ass_<?= $assessmentId ?>"
             data-reset-needed="<?= $resetStorage ? '1' : '0' ?>"
             style="background: #EFF6FF; border: 2px solid var(--blue-600); border-radius: 999px; padding: 8px 20px; display: flex; align-items: center; gap: 8px;">
          <i data-feather="clock" style="width: 18px; height: 18px; color: var(--blue-800);"></i>
          <span style="font-size: 12px; font-weight: 700; color: var(--muted);">Sisa Waktu:</span>
          <span id="examTimerText" style="font-size: 18px; font-weight: 800; color: var(--blue-800);">
            --:--
          </span>
        </div>
      </div>

      <!-- Formulir Pengerjaan Soal (Multipart Form) -->
      <form id="assessmentForm" method="POST" action="" enctype="multipart/form-data" data-track-tab="true" data-assessment-id="<?= $assessmentId ?>" data-assessment-title="<?= htmlspecialchars($assessment['judul']) ?>">
        <input type="hidden" name="action" value="submit_assessment">

        <div style="display: flex; flex-direction: column; gap: 24px; margin-bottom: 28px;">
          <?php foreach ($questions as $idx => $q): ?>
            <div class="glass-card question-block" data-type="<?= $q['tipe'] ?>" style="padding: 24px;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                <span style="font-weight: 800; font-size: 15px; color: var(--blue-800);">
                  Pertanyaan <?= $idx + 1 ?> dari <?= count($questions) ?>
                </span>
                <span class="badge-role" style="font-size: 11px; background: <?= $q['tipe'] === 'pg' ? 'var(--blue-100)' : 'var(--orange-100)' ?>; color: <?= $q['tipe'] === 'pg' ? 'var(--blue-800)' : 'var(--orange-dark)' ?>;">
                  <?= strtoupper($q['tipe']) ?> (Bobot: <?= $q['bobot'] ?>)
                </span>
              </div>

              <!-- Isi Teks Soal -->
              <div style="font-size: 14.5px; color: var(--ink); margin-bottom: 18px; line-height: 1.65;">
                <?= nl2br(htmlspecialchars($q['pertanyaan'])) ?>
              </div>

              <!-- A. TIPE PILIHAN GANDA (PG) -->
              <?php if ($q['tipe'] === 'pg'): ?>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                  <?php 
                    $opts = [0 => $q['opsi_a'], 1 => $q['opsi_b'], 2 => $q['opsi_c'], 3 => $q['opsi_d']];
                    $optLabels = ['A', 'B', 'C', 'D'];
                  ?>
                  <?php foreach ($opts as $val => $text): ?>
                    <label style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border: 1px solid var(--line); border-radius: var(--radius-sm); cursor: pointer; background: #fff; transition: all 0.15s;">
                      <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $val ?>" required style="width: 18px; height: 18px;">
                      <span style="font-weight: 800; color: var(--blue-800);"><?= $optLabels[$val] ?>.</span>
                      <span style="font-size: 14px; color: var(--ink);"><?= htmlspecialchars($text) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>

              <!-- B. TIPE ESSAY: JAWAB DENGAN TEKS ATAU UPLOAD FOTO ATAU KAMERA/WEBCAM -->
              <?php else: ?>
                <div class="essay-box" style="background: #FAFAFA; border: 1px solid var(--line); border-radius: 12px; padding: 20px;">
                  
                  <!-- 1. Opsi Teks Uraian Jawaban -->
                  <div style="margin-bottom: 18px;">
                    <label class="form-label" style="font-weight: 700; font-size: 13px; color: var(--blue-800); display: flex; align-items: center; gap: 6px;">
                      <i data-feather="edit-3" style="width: 15px; height: 15px;"></i>
                      <span>Ketik Uraian Teks Jawaban (Penjelasan / Analisis / Langkah Pengerjaan):</span>
                    </label>
                    <textarea name="answers[<?= $q['id'] ?>]" rows="4" class="form-input" placeholder="Tuliskan uraian langkah pengerjaan, analisis, atau rumus jawaban Anda di sini..." style="width: 100%; line-height: 1.5;"></textarea>
                  </div>

                  <!-- 2. Opsi Lampirkan Foto (Upload Berkas ATAU Ambil Foto Kamera/Webcam) -->
                  <div style="border-top: 1px dashed #CBD5E1; padding-top: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                      <label class="form-label" style="font-weight: 700; font-size: 13px; color: var(--ink); margin: 0;">
                        📷 Lampiran Foto Jawaban / Lembar Kerja (Opsional/Pendukung):
                      </label>
                      <span style="font-size: 11.5px; color: var(--muted);">Format Berkas: JPG, PNG, PDF</span>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 12px;">
                      <!-- Tombol 1: Upload File Foto dari Perangkat -->
                      <label class="btn btn-outline" style="font-size: 12px; padding: 8px 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; background: #fff;">
                        <i data-feather="upload" style="width: 14px; height: 14px;"></i> Upload Foto Lembar Kerja
                        <input type="file" name="file_answers[<?= $q['id'] ?>]" accept="image/*,.pdf" style="display: none;" onchange="previewUpload(this, 'preview_box_<?= $q['id'] ?>', 'img_preview_<?= $q['id'] ?>', <?= $q['id'] ?>)">
                      </label>

                      <!-- Tombol 2: Ambil Foto Langsung Lewat Kamera Webcam / HP -->
                      <button type="button" class="btn btn-primary" onclick="openInlineCamera(<?= $q['id'] ?>)" style="font-size: 12px; padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
                        <i data-feather="camera" style="width: 14px; height: 14px;"></i> Ambil Foto Lewat Kamera
                      </button>

                      <!-- Tombol 3: Hapus Foto Terpasang -->
                      <button type="button" class="btn btn-outline" id="btn_clear_photo_<?= $q['id'] ?>" onclick="clearPhoto(<?= $q['id'] ?>)" style="font-size: 12px; padding: 8px 14px; color: var(--danger); border-color: var(--danger); display: none;">
                        Hapus Foto
                      </button>
                    </div>

                    <!-- Hidden Input untuk Menyimpan Base64 Tangkapan Kamera Webcam -->
                    <input type="hidden" name="camera_answers[<?= $q['id'] ?>]" id="cam_input_<?= $q['id'] ?>">

                    <!-- Box Kamera Webcam Inline -->
                    <div id="cam_box_<?= $q['id'] ?>" style="display: none; margin-bottom: 14px; background: #000; padding: 14px; border-radius: 10px;">
                      <video id="video_cam_<?= $q['id'] ?>" autoplay playsinline style="width: 100%; max-width: 480px; border-radius: 8px; background: #000; display: block; margin: 0 auto;"></video>
                      <canvas id="canvas_cam_<?= $q['id'] ?>" style="display: none;"></canvas>
                      <div style="margin-top: 12px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-success" id="btn_snap_<?= $q['id'] ?>" onclick="captureInlineCamera(<?= $q['id'] ?>)" style="font-size: 12.5px; padding: 7px 18px; font-weight: 700;">
                          📸 Jepret Foto Lembar Jawaban
                        </button>
                        <button type="button" class="btn btn-outline" id="btn_stop_cam_<?= $q['id'] ?>" onclick="stopInlineCamera(<?= $q['id'] ?>)" style="font-size: 12px; padding: 7px 14px; color: #fff; border-color: #fff;">
                          Tutup Kamera
                        </button>
                      </div>
                    </div>

                    <!-- Box Pratinjau Foto Rapi -->
                    <div id="preview_box_<?= $q['id'] ?>" style="display: none; margin-top: 10px; background: #fff; border: 1px solid var(--line); border-radius: 8px; padding: 12px;">
                      <div style="font-size: 12px; font-weight: 700; color: var(--success); margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                        <i data-feather="check-circle" style="width: 14px; height: 14px;"></i> Foto Jawaban Berhasil Terpasang (Pratinjau sebelum dikirim):
                      </div>
                      <img id="img_preview_<?= $q['id'] ?>" src="" alt="Pratinjau Foto Jawaban" style="max-height: 220px; max-width: 100%; border-radius: 6px; border: 1px solid var(--line); box-shadow: 0 2px 8px rgba(0,0,0,0.06); cursor: pointer;" onclick="window.open(this.src)">
                    </div>
                  </div>

                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Tombol Pengumpulan Jawaban -->
        <div style="text-align: right; margin-bottom: 40px;">
          <button type="button" id="btnTriggerSubmit" class="btn btn-success" style="padding: 14px 38px; font-size: 15px; font-weight: 800; box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);">
            <i data-feather="send"></i> Kumpulkan Lembar Jawaban &rarr;
          </button>
        </div>
      </form>

      <!-- Modal Pop-up Konfirmasi Pengumpulan -->
      <div id="submitConfirmModal" class="modal-overlay">
        <div class="modal-box">
          <div class="modal-icon warning">⚠️</div>
          <div class="modal-title">Konfirmasi Pengumpulan Jawaban</div>
          <div class="modal-desc">
            Apakah Anda yakin ingin mengumpulkan seluruh lembar jawaban ini?<br>
            <strong>Pastikan seluruh butir soal telah diperiksa sebelum mengirim.</strong>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-outline" id="btnConfirmCancel">Periksa Kembali</button>
            <button type="button" class="btn btn-success" id="btnConfirmYes">Ya, Kumpulkan Jawaban</button>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<script>
  // =========================================================================
  // 1. COUNTDOWN TIMER SINKRON & PERBAIKAN BUG TIMER RETAKE
  // =========================================================================
  document.addEventListener('DOMContentLoaded', function() {
    const timerBadge = document.getElementById('examTimerBadge');
    const timerDisplay = document.getElementById('examTimerText');
    const form = document.getElementById('assessmentForm');

    if (timerBadge && timerDisplay && form) {
      const durationMinutes = parseInt(timerBadge.dataset.durationMinutes, 10) || 30;
      const serverStartTime = parseInt(timerBadge.dataset.startTime, 10) || 0;
      const storageKey = 'exam_timer_start_' + (timerBadge.dataset.assessmentId || 'assessment');
      const resetNeeded = timerBadge.dataset.resetNeeded === '1';

      if (resetNeeded) {
        localStorage.removeItem(storageKey);
      }

      // Prioritaskan serverStartTime untuk mencegah bug timer retake
      let startTimestamp = serverStartTime > 0 ? (serverStartTime * 1000) : null;
      if (!startTimestamp || resetNeeded) {
        const saved = localStorage.getItem(storageKey);
        if (saved && !resetNeeded) {
          startTimestamp = parseInt(saved, 10);
        } else {
          startTimestamp = Date.now();
          localStorage.setItem(storageKey, startTimestamp);
        }
      } else {
        localStorage.setItem(storageKey, startTimestamp);
      }

      const endTime = startTimestamp + (durationMinutes * 60 * 1000);

      function updateCountdown() {
        const remainingMs = endTime - Date.now();

        if (remainingMs <= 0) {
          clearInterval(timerInterval);
          localStorage.removeItem(storageKey);
          timerDisplay.innerText = "00:00 (Waktu Habis!)";
          alert("Waktu pengerjaan telah habis! Lembar jawaban Anda sedang dikumpulkan secara otomatis oleh sistem.");
          form.submit();
        } else {
          const totalSecs = Math.floor(remainingMs / 1000);
          const mins = Math.floor(totalSecs / 60);
          const secs = totalSecs % 60;
          timerDisplay.innerText = String(mins).padStart(2, '0') + ":" + String(secs).padStart(2, '0');

          if (mins < 5) {
            timerBadge.style.backgroundColor = '#FEE2E2';
            timerBadge.style.borderColor = '#DC2626';
            timerDisplay.style.color = '#DC2626';
          }
        }
      }

      updateCountdown();
      const timerInterval = setInterval(updateCountdown, 1000);

      // Bersihkan localStorage saat form dikirimkan
      form.addEventListener('submit', function() {
        localStorage.removeItem(storageKey);
      });
    }

    // Modal Konfirmasi Pengumpulan
    const btnTrigger = document.getElementById('btnTriggerSubmit');
    const modal = document.getElementById('submitConfirmModal');
    const btnCancel = document.getElementById('btnConfirmCancel');
    const btnYes = document.getElementById('btnConfirmYes');

    if (btnTrigger && modal) {
      btnTrigger.addEventListener('click', function() {
        modal.classList.add('active');
      });
      if (btnCancel) {
        btnCancel.addEventListener('click', function() {
          modal.classList.remove('active');
        });
      }
      if (btnYes) {
        btnYes.addEventListener('click', function() {
          btnYes.disabled = true;
          btnYes.innerText = 'Mengumpulkan...';
          form.submit();
        });
      }
    }
  });

  // =========================================================================
  // 2. PRATINJAU UPLOAD FOTO LEMBAR KERJA
  // =========================================================================
  function previewUpload(input, boxId, imgId, qId) {
    const box = document.getElementById(boxId);
    const img = document.getElementById(imgId);
    const clearBtn = document.getElementById('btn_clear_photo_' + qId);
    if (!box || !img) return;

    if (input.files && input.files[0]) {
      const file = input.files[0];
      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
          img.src = e.target.result;
          box.style.display = 'block';
          if (clearBtn) clearBtn.style.display = 'inline-flex';
        };
        reader.readAsDataURL(file);
      } else {
        img.src = '';
        box.style.display = 'block';
        img.alt = 'Berkas Terlampir: ' + file.name;
        if (clearBtn) clearBtn.style.display = 'inline-flex';
      }
      const camInput = document.getElementById('cam_input_' + qId);
      if (camInput) camInput.value = '';
    }
  }

  function clearPhoto(qId) {
    const box = document.getElementById('preview_box_' + qId);
    const img = document.getElementById('img_preview_' + qId);
    const clearBtn = document.getElementById('btn_clear_photo_' + qId);
    const fileInput = document.querySelector(`input[name="file_answers[${qId}]"]`);
    const camInput = document.getElementById('cam_input_' + qId);

    if (fileInput) fileInput.value = '';
    if (camInput) camInput.value = '';
    if (img) img.src = '';
    if (box) box.style.display = 'none';
    if (clearBtn) clearBtn.style.display = 'none';
  }

  // =========================================================================
  // 3. KAMERA WEBCAM LANGSUNG INLINE
  // =========================================================================
  const activeStreams = {};

  async function openInlineCamera(qId) {
    const video = document.getElementById(`video_cam_${qId}`);
    const camBox = document.getElementById(`cam_box_${qId}`);

    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'environment' }
      });
      activeStreams[qId] = stream;
      if (video) video.srcObject = stream;
      if (camBox) camBox.style.display = 'block';
    } catch (err) {
      alert('Gagal mengakses kamera: Pastikan browser Anda mengizinkan izin akses kamera perangkat.');
    }
  }

  function captureInlineCamera(qId) {
    const video = document.getElementById(`video_cam_${qId}`);
    const canvas = document.getElementById(`canvas_cam_${qId}`);
    const hiddenInput = document.getElementById(`cam_input_${qId}`);
    const previewBox = document.getElementById(`preview_box_${qId}`);
    const previewImg = document.getElementById(`img_preview_${qId}`);
    const clearBtn = document.getElementById('btn_clear_photo_${qId}');
    const fileInput = document.querySelector(`input[name="file_answers[${qId}]"]`);

    if (video && canvas && hiddenInput) {
      canvas.width = video.videoWidth || 640;
      canvas.height = video.videoHeight || 480;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

      const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
      hiddenInput.value = dataUrl;

      if (fileInput) fileInput.value = '';

      if (previewImg) previewImg.src = dataUrl;
      if (previewBox) previewBox.style.display = 'block';
      if (clearBtn) clearBtn.style.display = 'inline-flex';

      stopInlineCamera(qId);
    }
  }

  function stopInlineCamera(qId) {
    if (activeStreams[qId]) {
      activeStreams[qId].getTracks().forEach(track => track.stop());
      delete activeStreams[qId];
    }
    const camBox = document.getElementById(`cam_box_${qId}`);
    if (camBox) camBox.style.display = 'none';
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
