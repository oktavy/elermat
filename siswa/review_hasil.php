<?php
/**
 * Lembar Tinjauan Hasil & Pembahasan Soal Siswa (Review Hasil Pengerjaan)
 * File: siswa/review_hasil.php
 * 
 * PERBAIKAN BUG KRITIS & FITUR LENGKAP:
 * 1. FIX ERROR/CRASH REVIEW SISWA:
 *    Mencegah crash/fatal error saat siswa membuka riwayat hasil Latihan & Evaluasi.
 *    Penanganan fallback submission yang aman dan perlindungan terhadap null value pada tanggal dan skor.
 * 2. KUNCI JAWABAN & PEMBAHASAN:
 *    Jika jawaban siswa SALAH (atau setelah dinilai AI dan Guru), tampilkan "Kunci Jawaban yang Benar"
 *    beserta penjelasan/pembahasannya tepat di bawah/samping jawaban siswa yang salah.
 * 3. PRATINJAU FOTO JAWABAN ESSAY & WEBCAM:
 *    Menampilkan berkas foto lembar kerja atau foto kamera webcam siswa secara rapi dan dapat diperbesar.
 * 4. DUKUNGAN RETAKE:
 *    Jika guru mengaktifkan izin retake LKPD, tampilkan tombol pengerjaan ulang yang mereset timer.
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

// 2. Ambil Parameter Submission ID atau Assessment ID secara Aman
$subId = !empty($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;
$assessmentId = !empty($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : (!empty($_GET['id']) ? (int)$_GET['id'] : 0);
$reqType = $_GET['type'] ?? $_GET['tipe'] ?? '';

$submission = null;
if ($subId > 0) {
    try {
        $stmtSub = $db->prepare("
            SELECT s.*, 
                   a.tipe AS assessment_tipe, 
                   a.judul AS assessment_judul, 
                   a.passing_grade, 
                   a.duration_minutes, 
                   a.kelas AS assessment_kelas
            FROM submissions s
            JOIN assessments a ON s.assessment_id = a.id
            WHERE s.id = ? AND s.student_id = ?
            LIMIT 1
        ");
        $stmtSub->execute([$subId, $currentUser['id']]);
        $submission = $stmtSub->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $submission = null;
    }
} elseif ($assessmentId > 0) {
    try {
        $stmtSub = $db->prepare("
            SELECT s.*, 
                   a.tipe AS assessment_tipe, 
                   a.judul AS assessment_judul, 
                   a.passing_grade, 
                   a.duration_minutes, 
                   a.kelas AS assessment_kelas
            FROM submissions s
            JOIN assessments a ON s.assessment_id = a.id
            WHERE s.assessment_id = ? AND s.student_id = ?
            ORDER BY s.attempt DESC, s.id DESC
            LIMIT 1
        ");
        $stmtSub->execute([$assessmentId, $currentUser['id']]);
        $submission = $stmtSub->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $submission = null;
    }
}

// Fallback jika belum ditemukan tapi parameter type/tipe tersedia
if (!$submission && !empty($reqType)) {
    try {
        $stmtSub = $db->prepare("
            SELECT s.*, 
                   a.tipe AS assessment_tipe, 
                   a.judul AS assessment_judul, 
                   a.passing_grade, 
                   a.duration_minutes, 
                   a.kelas AS assessment_kelas
            FROM submissions s
            JOIN assessments a ON s.assessment_id = a.id
            WHERE s.student_id = ? AND a.tipe = ?
            ORDER BY s.submitted_at DESC, s.id DESC
            LIMIT 1
        ");
        $stmtSub->execute([$currentUser['id'], $reqType]);
        $submission = $stmtSub->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $submission = null;
    }
}

// Fallback terakhir: Ambil submission paling baru yang pernah dikerjakan siswa
if (!$submission && $subId <= 0 && $assessmentId <= 0 && empty($reqType)) {
    try {
        $stmtSub = $db->prepare("
            SELECT s.*, 
                   a.tipe AS assessment_tipe, 
                   a.judul AS assessment_judul, 
                   a.passing_grade, 
                   a.duration_minutes, 
                   a.kelas AS assessment_kelas
            FROM submissions s
            JOIN assessments a ON s.assessment_id = a.id
            WHERE s.student_id = ?
            ORDER BY s.submitted_at DESC, s.id DESC
            LIMIT 1
        ");
        $stmtSub->execute([$currentUser['id']]);
        $submission = $stmtSub->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $submission = null;
    }
}

// Helper untuk Parsing Opsi Pilihan Ganda & Kunci Jawaban
function parseQuestionOption($val, $q) {
    $opts = [
        0 => $q['opsi_a'] ?? '', 
        1 => $q['opsi_b'] ?? '', 
        2 => $q['opsi_c'] ?? '', 
        3 => $q['opsi_d'] ?? ''
    ];
    $labels = ['A', 'B', 'C', 'D'];
    $valStr = trim((string)$val);

    if ($valStr === '') {
        return ['index' => -1, 'letter' => '-', 'text' => '(Tidak dijawab)'];
    }

    if (is_numeric($valStr)) {
        $idx = (int)$valStr;
        if (isset($opts[$idx])) {
            return ['index' => $idx, 'letter' => $labels[$idx] ?? '?', 'text' => $opts[$idx]];
        }
    }

    $upper = strtoupper($valStr);
    $map = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
    if (isset($map[$upper])) {
        $idx = $map[$upper];
        return ['index' => $idx, 'letter' => $upper, 'text' => $opts[$idx] ?? ''];
    }

    // Cocokkan teks langsung
    foreach ($opts as $i => $optText) {
        if (!empty($optText) && strtolower(trim($optText)) === strtolower($valStr)) {
            return ['index' => $i, 'letter' => $labels[$i], 'text' => $optText];
        }
    }

    return ['index' => -1, 'letter' => '-', 'text' => $valStr];
}

$pageTitle = 'Review Hasil & Pembahasan - ElearnMath Siswa';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper" style="max-width: 980px; margin: 0 auto;">

    <!-- Tombol Navigasi Menu -->
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
      <div style="display: flex; gap: 8px;">
        <a href="<?= BASE_URL ?>/siswa/hasil.php" class="btn btn-outline" style="font-size: 12.5px; padding: 6px 14px;">
          &larr; Rekap Hasil & Nilai
        </a>
        <a href="<?= BASE_URL ?>/siswa/index.php" class="btn btn-outline" style="font-size: 12.5px; padding: 6px 14px;">
          Dashboard
        </a>
      </div>

      <?php if ($submission): ?>
        <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); font-size: 11.5px; padding: 5px 12px;">
          <?= strtoupper($submission['assessment_tipe'] ?? 'MODUL') ?> &bull; Attempt Ke-<?= (int)($submission['attempt'] ?? 1) ?>
        </span>
      <?php endif; ?>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- JIKA RIWAYAT SUBMISSION TIDAK DITEMUKAN (FALLBACK AMAN TANPA CRASH) -->
    <?php if (!$submission): ?>
      <div class="glass-card" style="text-align: center; padding: 48px 24px; border: 1px dashed var(--line); margin-bottom: 24px;">
        <div style="font-size: 40px; margin-bottom: 12px;">📋</div>
        <h2 style="font-size: 20px; font-weight: 800; color: var(--ink); margin: 0 0 8px 0;">
          Riwayat Pengerjaan Belum Ditemukan
        </h2>
        <p style="font-size: 14px; color: var(--muted); max-width: 520px; margin: 0 auto 20px auto; line-height: 1.6;">
          Anda belum pernah mengerjakan modul ini atau lembar jawaban belum tersimpan di server. Silakan buka modul pembelajaran Anda untuk mulai mengerjakan.
        </p>
        <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
          <a href="<?= BASE_URL ?>/siswa/lkpd.php" class="btn btn-primary" style="font-size: 13px;">
            Buka Lembar LKPD &rarr;
          </a>
          <a href="<?= BASE_URL ?>/siswa/latihan.php" class="btn btn-outline" style="font-size: 13px;">
            Buka Latihan Soal &rarr;
          </a>
        </div>
      </div>

    <!-- RIWAYAT PENGERJAAN DITEMUKAN -->
    <?php else: ?>
      <?php
        $assessmentId = (int)$submission['assessment_id'];
        $isScored = ($submission['is_corrected'] == 1 || $submission['total_score'] !== null);
        $allowRetake = (!empty($submission['allow_retake']) && $submission['allow_retake'] == 1);

        // Ambil Butir Soal Modul
        $stmtQ = $db->prepare("SELECT * FROM questions WHERE assessment_id = ? ORDER BY urutan ASC, id ASC");
        $stmtQ->execute([$assessmentId]);
        $questions = $stmtQ->fetchAll();

        // Ambil Jawaban Siswa
        $stmtAns = $db->prepare("SELECT * FROM submission_answers WHERE submission_id = ?");
        $stmtAns->execute([$submission['id']]);
        $savedAnswers = [];
        while ($row = $stmtAns->fetch(PDO::FETCH_ASSOC)) {
            $savedAnswers[$row['question_id']] = $row;
        }

        // Format tanggal pengumpulan aman tanpa bug 01 Jan 1970
        $rawSubDate = !empty($submission['submitted_at']) ? $submission['submitted_at'] : ($submission['created_at'] ?? null);
        $waktuKumpul = (!empty($rawSubDate) && $rawSubDate !== '0000-00-00 00:00:00') ? date('d M Y, H:i', strtotime($rawSubDate)) . ' WIB' : '-';
      ?>

      <!-- SCORECARD HASIL PENGERJAAN -->
      <div class="glass-card" style="margin-bottom: 24px; border-left: 5px solid <?= $isScored ? 'var(--success)' : 'var(--orange-dark)' ?>; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
          <div>
            <span class="badge-role" style="font-size: 11px; margin-bottom: 6px;">
              Lembar Tinjauan Hasil & Pembahasan
            </span>
            <h1 style="font-size: 22px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
              <?= htmlspecialchars($submission['assessment_judul']) ?>
            </h1>
            <div style="font-size: 13px; color: var(--muted); display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
              <span><i data-feather="calendar" style="width: 13px; height: 13px; vertical-align: middle;"></i> Dikumpulkan: <?= $waktuKumpul ?></span>
              <span><i data-feather="repeat" style="width: 13px; height: 13px; vertical-align: middle;"></i> Percobaan: Attempt #<?= (int)($submission['attempt'] ?? 1) ?></span>
            </div>
          </div>

          <div>
            <?php if ($isScored): ?>
              <div style="text-align: right;">
                <span style="font-size: 11.5px; color: var(--muted); display: block; font-weight: 700;">NILAI AKHIR:</span>
                <span style="font-size: 34px; font-weight: 900; color: var(--success); line-height: 1;">
                  <?= $submission['total_score'] ?>
                </span>
                <span style="font-size: 14px; font-weight: 600; color: var(--muted);">/ 100</span>
              </div>
            <?php else: ?>
              <span style="background: var(--orange-100); color: var(--orange-dark); font-weight: 700; padding: 8px 18px; border-radius: 999px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                ⏳ Menunggu Penilaian Guru / AI
              </span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Detail Ringkasan Skor Komponen -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--line);">
          <div style="background: #F8FAFC; border: 1px solid var(--line); border-radius: 10px; padding: 12px 16px;">
            <div style="font-size: 11px; color: var(--muted); font-weight: 700;">SKOR PILIHAN GANDA (PG)</div>
            <div style="font-size: 18px; font-weight: 800; color: var(--blue-800);">
              <?= $submission['pg_score'] !== null ? ($submission['pg_score'] . ' / 100') : '-' ?>
            </div>
          </div>
          <div style="background: #F8FAFC; border: 1px solid var(--line); border-radius: 10px; padding: 12px 16px;">
            <div style="font-size: 11px; color: var(--muted); font-weight: 700;">STATUS KOREKSI GURU</div>
            <div style="font-size: 14px; font-weight: 800; color: <?= !empty($submission['is_corrected']) ? 'var(--success)' : 'var(--orange-dark)' ?>; margin-top: 2px;">
              <?= !empty($submission['is_corrected']) ? '✓ Selesai Dinilai' : 'Menunggu Penilaian' ?>
            </div>
          </div>
          <?php if ($allowRetake): ?>
            <div style="background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 10px; padding: 12px 16px;">
              <div style="font-size: 11px; color: #92400E; font-weight: 700;">IZIN RETAKE (ULANG)</div>
              <div style="font-size: 13px; font-weight: 800; color: #B45309; margin-top: 2px;">
                🔄 Izin Aktif dari Guru
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Banner Catatan & Umpan Balik Guru -->
        <?php if (!empty($submission['feedback_guru'])): ?>
          <div style="margin-top: 18px; padding: 14px 18px; background: #EFF6FF; border-radius: var(--radius-sm); border: 1px solid #BFDBFE;">
            <div style="font-size: 12.5px; font-weight: 700; color: var(--blue-800); margin-bottom: 4px;">💬 Catatan & Umpan Balik Guru Pengajar:</div>
            <div style="font-size: 13.5px; color: var(--ink); line-height: 1.6;">
              <?= nl2br(htmlspecialchars($submission['feedback_guru'])) ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Tombol Pengerjaan Ulang (Jika Diberi Izin Retake) -->
        <?php if ($allowRetake): ?>
          <div style="margin-top: 18px; padding: 16px; background: #FFFBEB; border-radius: 12px; border: 1px solid #F59E0B; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
              <strong style="color: #92400E; font-size: 13.5px;">Guru Mengizinkan Anda Mengerjakan Ulang (Retake):</strong>
              <p style="color: #78350F; font-size: 12.5px; margin: 2px 0 0 0;">Waktu timer pengerjaan akan diatur ulang secara penuh (reset) dan tidak berkurang oleh sesi pertama.</p>
            </div>
            <a href="<?= BASE_URL ?>/siswa/pengerjaan.php?id=<?= $assessmentId ?>" class="btn btn-primary" style="background: #D97706; border-color: #D97706; font-weight: 700; padding: 9px 20px; font-size: 13px;">
              <i data-feather="repeat" style="width: 14px; height: 14px;"></i> Mulai Pengerjaan Ulang (Retake) &rarr;
            </a>
          </div>
        <?php endif; ?>
      </div>

      <!-- TINJAUAN BUTIR SOAL, JAWABAN SISWA, KUNCI JAWABAN YANG BENAR & PEMBAHASAN -->
      <div class="glass-card" style="padding: 24px;">
        <h2 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0 0 20px 0; display: flex; align-items: center; gap: 8px;">
          <i data-feather="check-square" style="width: 20px; height: 20px; color: var(--blue-600);"></i>
          Lembar Koreksi, Kunci Jawaban yang Benar & Pembahasan
        </h2>

        <div style="display: flex; flex-direction: column; gap: 24px;">
          <?php foreach ($questions as $idx => $q): ?>
            <?php 
              $qId = $q['id'];
              $ans = $savedAnswers[$qId] ?? null;
              $isPg = ($q['tipe'] === 'pg');

              // Evaluasi Status Kebenaran Soal
              if ($isPg) {
                  $isCorrect = ($ans && $ans['is_correct'] == 1);
                  $isWrong = ($ans && ($ans['is_correct'] === 0 || $ans['is_correct'] === '0')) || (!$isCorrect && $isScored);
              } else {
                  // Untuk essay, jika ada nilai guru atau AI
                  $teacherSc = ($ans && $ans['teacher_score'] !== null) ? (float)$ans['teacher_score'] : null;
                  $aiSc = ($ans && $ans['ai_score'] !== null) ? (float)$ans['ai_score'] : null;
                  $effectiveSc = $teacherSc !== null ? $teacherSc : $aiSc;
                  $isCorrect = ($effectiveSc !== null && $effectiveSc >= 75);
                  $isWrong = ($effectiveSc !== null && $effectiveSc < 75);
              }

              $cardBorder = $isCorrect ? '#A7F3D0' : ($isWrong ? '#FECACA' : 'var(--line)');
            ?>
            <div style="background: #fff; border: 1px solid <?= $cardBorder ?>; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
              
              <!-- Header Nomor Soal -->
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                <span style="font-weight: 800; color: var(--blue-800); font-size: 14.5px;">
                  Pertanyaan No. <?= $idx + 1 ?> (<?= strtoupper($q['tipe']) ?> &bull; Bobot: <?= $q['bobot'] ?>)
                </span>

                <div>
                  <?php if ($isPg): ?>
                    <?php if (!$isScored): ?>
                      <span style="font-size: 11.5px; font-weight: 600; color: var(--muted); background: #F1F5F9; padding: 4px 10px; border-radius: 999px;">
                        ⏳ Menunggu Koreksi
                      </span>
                    <?php elseif ($isCorrect): ?>
                      <span style="font-size: 12px; font-weight: 800; color: var(--success); background: var(--success-bg); padding: 4px 12px; border-radius: 999px;">
                        ✓ Jawaban Benar
                      </span>
                    <?php else: ?>
                      <span style="font-size: 12px; font-weight: 800; color: var(--danger); background: var(--danger-bg); padding: 4px 12px; border-radius: 999px;">
                        ✗ Jawaban Kurang Tepat
                      </span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span style="font-size: 12px; font-weight: 700; color: var(--blue-800); background: #EFF6FF; padding: 4px 12px; border-radius: 999px;">
                      Skor: <?= ($ans && $ans['teacher_score'] !== null) ? ($ans['teacher_score'] . ' / 100') : ($ans && $ans['ai_score'] !== null ? ('Rekomendasi AI: ' . $ans['ai_score']) : 'Menunggu Koreksi') ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Teks Pertanyaan Soal -->
              <div style="font-size: 14px; color: var(--ink); margin-bottom: 16px; line-height: 1.65;">
                <?= nl2br(htmlspecialchars($q['pertanyaan'])) ?>
              </div>

              <!-- ========================================================== -->
              <!-- 1. JAWABAN YANG DIKIRIMKAN OLEH SISWA                     -->
              <!-- ========================================================== -->
              <div style="background: #F8FAFC; border: 1px solid var(--line); border-radius: 8px; padding: 14px 16px; margin-bottom: 12px;">
                <div style="font-size: 11.5px; font-weight: 700; color: var(--muted); text-transform: uppercase; margin-bottom: 6px;">
                  Jawaban yang Anda Kirimkan:
                </div>

                <?php if ($isPg): ?>
                  <?php
                    $userOpt = parseQuestionOption($ans['jawaban_siswa'] ?? '', $q);
                  ?>
                  <div style="font-size: 14px; font-weight: 700; color: <?= $isCorrect ? 'var(--success)' : ($isWrong ? 'var(--danger)' : 'var(--ink)') ?>;">
                    <span style="font-weight: 800;"><?= $userOpt['letter'] ?>.</span> <?= htmlspecialchars($userOpt['text']) ?>
                  </div>

                <?php else: ?>
                  <!-- Teks Essay Siswa -->
                  <div style="font-size: 13.5px; color: var(--ink); line-height: 1.6;">
                    <?= (!empty($ans['jawaban_siswa']) && trim($ans['jawaban_siswa']) !== '') ? nl2br(htmlspecialchars($ans['jawaban_siswa'])) : '<em style="color:var(--muted);">(Tidak mengisi uraian teks)</em>' ?>
                  </div>

                  <!-- Foto Lembar Kerja Siswa (Upload / Kamera Webcam) -->
                  <?php if (!empty($ans['foto_jawaban'])): ?>
                    <?php 
                      $fotoUrl = BASE_URL . '/' . ltrim(htmlspecialchars($ans['foto_jawaban']), '/');
                      $isPdf = preg_match('/\.pdf$/i', $ans['foto_jawaban']);
                    ?>
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--line);">
                      <div style="font-size: 12px; font-weight: 700; color: var(--blue-800); margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                        <i data-feather="image" style="width: 14px; height: 14px;"></i> Lampiran Foto Lembar Kerja Anda:
                      </div>
                      <?php if ($isPdf): ?>
                        <a href="<?= $fotoUrl ?>" target="_blank" class="btn btn-outline" style="font-size: 12px;">
                          📄 Buka Berkas PDF Lembar Kerja Siswa &rarr;
                        </a>
                      <?php else: ?>
                        <a href="<?= $fotoUrl ?>" target="_blank" title="Klik untuk membuka ukuran penuh">
                          <img src="<?= $fotoUrl ?>" alt="Foto Lembar Kerja Siswa" style="max-height: 220px; max-width: 100%; border-radius: 8px; border: 1px solid var(--line); box-shadow: 0 2px 8px rgba(0,0,0,0.06); cursor: pointer;">
                        </a>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <!-- Rekomendasi Skor & Analisis Evaluasi AI -->
                  <?php if (!empty($ans['ai_score']) || !empty($ans['ai_feedback'])): ?>
                    <div style="margin-top: 12px; padding: 10px 14px; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px;">
                      <div style="font-size: 12px; font-weight: 700; color: #1E40AF; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <span>🤖</span> Analisis Evaluasi AI (Google Gemini):
                        <span style="background: #DBEAFE; color: #1E3A8A; padding: 1px 8px; border-radius: 999px; font-size: 11px; margin-left: auto;">
                          Skor AI: <?= $ans['ai_score'] ?> / 100
                        </span>
                      </div>
                      <div style="font-size: 12.5px; color: #1E3A8A; line-height: 1.5;">
                        <?= nl2br(htmlspecialchars($ans['ai_feedback'] ?? '')) ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- Masukan Khusus Guru Pengajar -->
                  <?php if (!empty($ans['teacher_feedback'])): ?>
                    <div style="margin-top: 10px; padding: 10px 14px; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px;">
                      <div style="font-size: 12px; font-weight: 700; color: #065F46; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <span>👨‍🏫</span> Catatan Guru Pengajar:
                        <?php if ($ans['teacher_score'] !== null): ?>
                          <span style="background: #D1FAE5; color: #065F46; padding: 1px 8px; border-radius: 999px; font-size: 11px; margin-left: auto;">
                            Nilai Guru: <?= $ans['teacher_score'] ?> / 100
                          </span>
                        <?php endif; ?>
                      </div>
                      <div style="font-size: 12.5px; color: #064E3B; line-height: 1.5;">
                        <?= nl2br(htmlspecialchars($ans['teacher_feedback'])) ?>
                      </div>
                    </div>
                  <?php endif; ?>

                <?php endif; ?>
              </div>

              <!-- ========================================================== -->
              <!-- 2. KUNCI JAWABAN YANG BENAR & PEMBAHASAN KONSEP LENGKAP    -->
              <!-- Ditampilkan jika jawaban siswa SALAH atau setelah dinilai  -->
              <!-- ========================================================== -->
              <?php if ($isScored && ($isWrong || !$isPg || !empty($q['feedback_salah']))): ?>
                <div style="background: #FFFBEB; border: 1px solid #FCD34D; border-radius: 10px; padding: 14px 18px; margin-top: 10px;">
                  <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                    <span style="font-size: 18px;">💡</span>
                    <strong style="font-size: 13.5px; color: #92400E;">Kunci Jawaban yang Benar & Pembahasan Konsep:</strong>
                  </div>

                  <div style="font-size: 13.5px; color: #78350F; margin-bottom: 6px; line-height: 1.5;">
                    <?php if ($isPg): ?>
                      <?php 
                        $correctOpt = parseQuestionOption($q['kunci_jawaban'] ?? '0', $q);
                      ?>
                      <strong>Kunci Jawaban: <?= $correctOpt['letter'] ?>. <?= htmlspecialchars($correctOpt['text']) ?></strong>
                    <?php else: ?>
                      <strong>Kunci Jawaban Konsep:</strong> <?= htmlspecialchars($q['kunci_jawaban'] ?: 'Sesuai dengan langkah pengerjaan dan konsep matematika yang diajarkan.') ?>
                    <?php endif; ?>
                  </div>

                  <?php if (!empty($q['feedback_salah'])): ?>
                    <div style="font-size: 13px; color: #92400E; line-height: 1.5; background: #FEF3C7; padding: 10px 14px; border-radius: 6px; margin-top: 6px; border-left: 3px solid #D97706;">
                      <strong>Penjelasan & Pembahasan:</strong> <?= nl2br(htmlspecialchars($q['feedback_salah'])) ?>
                    </div>
                  <?php endif; ?>
                </div>

              <?php elseif ($isCorrect && !empty($q['feedback_benar'])): ?>
                <div style="background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 10px; padding: 12px 16px; margin-top: 10px;">
                  <div style="font-size: 13px; color: #065F46; line-height: 1.5;">
                    <strong>✓ Pembahasan:</strong> <?= htmlspecialchars($q['feedback_benar']) ?>
                  </div>
                </div>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>
        </div>
      </div>

    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
