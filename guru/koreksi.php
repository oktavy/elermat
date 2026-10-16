<?php
/**
 * Modul Penilaian & Koreksi Fleksibel (AI + Guru)
 * File: guru/koreksi.php
 * FITUR GURU:
 * 1. Koreksi AI & Feedback Relevan: Rekomendasi skor awal dan umpan balik otomatis yang relevan dengan jawaban essay.
 * 2. Kontrol Manual Guru: Tinjau dan sesuaikan skor akhir serta umpan balik personal.
 * 3. Izin Pengerjaan Ulang (Retake): Berikan izin siswa mengulang tugas tertentu.
 * 4. PENGHAPUSAN KKM: Sistem KKM 75 ditiadakan, penilaian murni berdasarkan ketuntasan evaluasi guru.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/gemini.php';
require_role(['guru', 'admin']);

$pageTitle = 'Koreksi Fleksibel (AI & Manual) - ElearnMath Guru';
$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

$submissionId = $_GET['submission_id'] ?? null;

// 1. PROSES TOGGLE IZIN PENGERJAAN ULANG (RETAKE HANYA UNTUK LKPD)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_retake') {
    $subId = (int)$_POST['submission_id'];
    $stmtInfo = $db->prepare("SELECT s.student_id, a.judul, a.tipe FROM submissions s JOIN assessments a ON s.assessment_id = a.id WHERE s.id = ?");
    $stmtInfo->execute([$subId]);
    $subInfo = $stmtInfo->fetch();

    if (!$subInfo || $subInfo['tipe'] !== 'lkpd') {
        flash_set('warning', 'Fitur Retake (pengerjaan ulang) hanya berlaku untuk modul LKPD.');
        redirect('guru/koreksi.php?submission_id=' . $subId);
    }

    $setRetake = (int)($_POST['set_retake'] ?? 0);
    $updRetake = $db->prepare("UPDATE submissions SET allow_retake = ? WHERE id = ?");
    $updRetake->execute([$setRetake, $subId]);

    if ($setRetake === 1) {
        send_notification(
            $subInfo['student_id'],
            'Izin Pengerjaan Ulang (Retake LKPD)',
            "Guru pengajar telah mengizinkan Anda untuk mengerjakan ulang LKPD '{$subInfo['judul']}'. Silakan perbaiki jawaban Anda.",
            'info',
            'siswa/lkpd.php'
        );
    }

    flash_set('success', $setRetake === 1 ? 'Izin pengerjaan ulang (Retake) LKPD berhasil diberikan kepada siswa!' : 'Izin pengerjaan ulang (Retake) LKPD berhasil dinonaktifkan.');
    redirect('guru/koreksi.php?submission_id=' . $subId);
}

// 2. PROSES SIMPAN NILAI AKHIR OLEH GURU (KOREKSI MANUAL)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_correction') {
    $subId = (int)$_POST['submission_id'];
    $teacherScores = $_POST['teacher_score'] ?? [];
    $teacherFeedbacks = $_POST['teacher_feedback'] ?? [];
    $finalTotalScore = (float)$_POST['final_total_score'];
    $generalFeedback = trim($_POST['general_feedback'] ?? '');
    $allowRetake = isset($_POST['allow_retake']) ? 1 : 0;

    $db->beginTransaction();
    try {
        // Update tiap detail jawaban siswa
        $stmtUpdAns = $db->prepare("
            UPDATE submission_answers 
            SET teacher_score = ?, teacher_feedback = ?
            WHERE id = ?
        ");
        foreach ($teacherScores as $ansId => $sc) {
            $fb = $teacherFeedbacks[$ansId] ?? '';
            $stmtUpdAns->execute([$sc !== '' ? (float)$sc : null, $fb, $ansId]);
        }

        // Update submission induk (Nilai Akhir Tuntas)
        $stmtUpdSub = $db->prepare("
            UPDATE submissions 
            SET total_score = ?, feedback_guru = ?, is_corrected = 1, allow_retake = ?, reviewed_at = NOW(), reviewed_by = ?
            WHERE id = ?
        ");
        $stmtUpdSub->execute([$finalTotalScore, $generalFeedback, $allowRetake, $currentUser['id'], $subId]);

        // Kirim notifikasi hasil penilaian ke siswa
        $stmtSubInfo = $db->prepare("
            SELECT s.student_id, a.judul 
            FROM submissions s 
            JOIN assessments a ON s.assessment_id = a.id 
            WHERE s.id = ?
        ");
        $stmtSubInfo->execute([$subId]);
        $subInfo = $stmtSubInfo->fetch();

        if ($subInfo) {
            send_notification(
                $subInfo['student_id'],
                'Nilai Tugas Telah Diterbitkan',
                "Tugas '{$subInfo['judul']}' telah selesai dikoreksi oleh guru. Nilai akhir Anda: {$finalTotalScore}.",
                'grade',
                'siswa/hasil.php'
            );
        }

        $db->commit();
        flash_set('success', 'Penilaian dan umpan balik berhasil disimpan! Nilai akhir siswa telah diperbarui.');
        redirect('guru/koreksi.php?submission_id=' . $subId);
    } catch (Exception $e) {
        $db->rollBack();
        flash_set('danger', 'Gagal menyimpan nilai: ' . $e->getMessage());
    }
}

// Ambil daftar seluruh submission siswa
$stmtList = $db->query("
    SELECT s.id, s.submitted_at, s.is_corrected, s.total_score, s.allow_retake,
           u.nama_lengkap AS nama_siswa, u.kelas,
           a.judul AS judul_assessment, a.tipe
    FROM submissions s
    JOIN users u ON s.student_id = u.id
    JOIN assessments a ON s.assessment_id = a.id
    ORDER BY s.is_corrected ASC, s.submitted_at DESC
");
$allSubmissions = $stmtList->fetchAll();

if (!$submissionId && !empty($allSubmissions)) {
    $submissionId = $allSubmissions[0]['id'];
}

$currentSub = null;
$answers = [];
if ($submissionId) {
    $stmtDetail = $db->prepare("
        SELECT s.*, u.nama_lengkap AS nama_siswa, u.kelas, u.nisn,
               a.judul AS judul_assessment, a.tipe AS tipe_assessment
        FROM submissions s
        JOIN users u ON s.student_id = u.id
        JOIN assessments a ON s.assessment_id = a.id
        WHERE s.id = ?
    ");
    $stmtDetail->execute([$submissionId]);
    $currentSub = $stmtDetail->fetch();

    if ($currentSub) {
        $stmtAns = $db->prepare("
            SELECT sa.*, q.pertanyaan, q.tipe AS tipe_soal, q.opsi_a, q.opsi_b, q.opsi_c, q.opsi_d,
                   q.kunci_jawaban, q.feedback_benar, q.feedback_salah
            FROM submission_answers sa
            JOIN questions q ON sa.question_id = q.id
            WHERE sa.submission_id = ?
            ORDER BY q.urutan ASC
        ");
        $stmtAns->execute([$submissionId]);
        $answers = $stmtAns->fetchAll();
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="margin-bottom: 20px;">
      <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 4px;">
        Koreksi AI & Kontrol Manual Guru
      </span>
      <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
        Penilaian Jawaban: Evaluasi Otomatis AI & Kebebasan Koreksi Guru
      </h1>
      <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
        Dapatkan rekomendasi skor dan analisa relevansi jawaban otomatis dari Google Gemini, sesuaikan nilai akhir secara manual, dan kelola izin pengerjaan ulang (Retake) bagi siswa.
      </p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 320px 1fr; gap: 24px; align-items: start;">
      
      <!-- Kolom Kiri: Daftar Pengumpulan Siswa -->
      <div class="glass-card" style="padding: 18px;">
        <h3 style="font-size: 15px; font-weight: 700; color: var(--blue-800); margin: 0 0 12px 0;">
          Daftar Pengerjaan Siswa (<?= count($allSubmissions) ?>)
        </h3>

        <div style="display: flex; flex-direction: column; gap: 8px; max-height: 650px; overflow-y: auto;">
          <?php if (empty($allSubmissions)): ?>
            <div style="color: var(--muted); font-size: 12.5px;">Belum ada pengerjaan yang masuk.</div>
          <?php else: ?>
            <?php foreach ($allSubmissions as $item): ?>
              <?php $isSelected = ($item['id'] == $submissionId); ?>
              <a href="<?= BASE_URL ?>/guru/koreksi.php?submission_id=<?= $item['id'] ?>" 
                 style="text-decoration: none; padding: 12px; border-radius: var(--radius-sm); border: 1px solid <?= $isSelected ? 'var(--blue-600)' : 'var(--line)' ?>; background: <?= $isSelected ? '#EFF6FF' : '#fff' ?>; display: block; transition: all 0.2s;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                  <span style="font-weight: 700; font-size: 13px; color: var(--ink);">
                    <?= htmlspecialchars($item['nama_siswa']) ?>
                  </span>
                  <span class="badge-role" style="font-size: 10px; padding: 2px 6px;">
                    <?= htmlspecialchars($item['kelas'] ?? '-') ?>
                  </span>
                </div>
                <div style="font-size: 11.5px; color: var(--muted); margin-bottom: 4px;">
                  <?= htmlspecialchars($item['judul_assessment']) ?>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                  <span style="color: #94A3B8;"><?= date('d/m H:i', strtotime($item['submitted_at'])) ?></span>
                  <div>
                    <?php if ($item['allow_retake']): ?>
                      <span style="background: #FEF3C7; color: #B45309; font-weight: 700; padding: 1px 5px; border-radius: 4px; font-size: 10px; margin-right: 4px;">RETAKE</span>
                    <?php endif; ?>

                    <?php if ($item['is_corrected']): ?>
                      <span style="color: var(--success); font-weight: 700;">Nilai: <?= $item['total_score'] ?></span>
                    <?php else: ?>
                      <span style="color: var(--orange-dark); font-weight: 600;">Perlu Review</span>
                    <?php endif; ?>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Kolom Kanan: Panel Koreksi Jawaban Siswa -->
      <div>
        <?php if (!$currentSub): ?>
          <div class="glass-card" style="text-align: center; padding: 40px; color: var(--muted);">
            Silakan pilih siswa dari daftar di samping untuk memulai koreksi.
          </div>
        <?php else: ?>
          <div class="glass-card" style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--line); padding-bottom: 14px; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
              <div>
                <h2 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0 0 4px 0;">
                  Lembar Kerja: <?= htmlspecialchars($currentSub['nama_siswa']) ?> (<?= htmlspecialchars($currentSub['kelas'] ?? '-') ?>)
                </h2>
                <div style="font-size: 12.5px; color: var(--muted);">
                  Modul: <strong><?= htmlspecialchars($currentSub['judul_assessment']) ?></strong> &bull; Dikumpulkan: <?= date('d M Y, H:i', strtotime($currentSub['submitted_at'])) ?> WIB
                </div>
              </div>

              <div style="display: flex; align-items: center; gap: 10px;">
                <?php if ($currentSub['is_corrected']): ?>
                  <span style="background: var(--success-bg); color: var(--success); font-weight: 700; padding: 6px 12px; border-radius: var(--radius-sm); font-size: 13px;">
                    Sudah Dinilai: <?= $currentSub['total_score'] ?> / 100
                  </span>
                <?php else: ?>
                  <span style="background: var(--orange-100); color: var(--orange-dark); font-weight: 700; padding: 6px 12px; border-radius: var(--radius-sm); font-size: 13px;">
                    Menunggu Finalisasi Guru
                  </span>
                <?php endif; ?>

                <!-- Tombol Cepat Izin Retake (HANYA UNTUK LKPD) -->
                <?php if ($currentSub['tipe'] === 'lkpd'): ?>
                  <form method="POST" action="" style="margin: 0;">
                    <input type="hidden" name="action" value="toggle_retake">
                    <input type="hidden" name="submission_id" value="<?= $currentSub['id'] ?>">
                    <?php if ($currentSub['allow_retake']): ?>
                      <input type="hidden" name="set_retake" value="0">
                      <button type="submit" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px; border-color: var(--danger); color: var(--danger);" title="Cabut izin pengerjaan ulang">
                        <i data-feather="lock" style="width: 13px; height: 13px;"></i> Kunci (Cabut Retake)
                      </button>
                    <?php else: ?>
                      <input type="hidden" name="set_retake" value="1">
                      <button type="submit" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px; border-color: #B45309; color: #B45309; background: #FEF3C7;" title="Izinkan siswa mengerjakan ulang LKPD ini">
                        <i data-feather="repeat" style="width: 13px; height: 13px;"></i> Izinkan Retake LKPD
                      </button>
                    <?php endif; ?>
                  </form>
                <?php endif; ?>
              </div>
            </div>

            <!-- Form Koreksi Guru -->
            <form method="POST" action="">
              <input type="hidden" name="action" value="save_correction">
              <input type="hidden" name="submission_id" value="<?= $currentSub['id'] ?>">

              <?php foreach ($answers as $idx => $ans): ?>
                <div style="background: #fff; border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 18px; margin-bottom: 18px;">
                  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="font-weight: 700; color: var(--blue-800); font-size: 14px;">
                      Soal Nomor <?= $idx + 1 ?> (<?= strtoupper($ans['tipe_soal']) ?>)
                    </span>
                    <?php if ($ans['tipe_soal'] === 'pg'): ?>
                      <span style="font-size: 12px; font-weight: 700; color: <?= $ans['is_correct'] ? 'var(--success)' : 'var(--danger)' ?>;">
                        <?= $ans['is_correct'] ? '✓ Jawaban Tepat (Skor Otomatis: 100)' : '✗ Jawaban Salah (Skor Otomatis: 0)' ?>
                      </span>
                    <?php endif; ?>
                  </div>

                  <p style="font-size: 14px; margin: 0 0 12px 0; color: var(--ink);">
                    <?= nl2br(htmlspecialchars($ans['pertanyaan'])) ?>
                  </p>

                  <!-- Jawaban Siswa -->
                  <div style="background: #F8FAFC; border-left: 3px solid var(--blue-400); padding: 12px; border-radius: 4px; margin-bottom: 14px;">
                    <div style="font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 4px;">Jawaban Siswa:</div>
                    <div style="font-size: 13.5px; color: var(--ink);">
                      <?php if ($ans['tipe_soal'] === 'pg'): ?>
                        <?php 
                          $opts = [0 => $ans['opsi_a'], 1 => $ans['opsi_b'], 2 => $ans['opsi_c'], 3 => $ans['opsi_d']];
                          $selectedOpt = $opts[$ans['jawaban_siswa']] ?? '-';
                        ?>
                        <strong>Opsi: <?= htmlspecialchars($selectedOpt) ?></strong>
                      <?php else: ?>
                        <?= !empty($ans['jawaban_siswa']) ? nl2br(htmlspecialchars($ans['jawaban_siswa'])) : '<em>(Siswa tidak mengisi uraian teks)</em>' ?>
                      <?php endif; ?>
                    </div>

                    <?php if (!empty($ans['foto_jawaban'])): ?>
                      <div style="margin-top: 12px; padding: 10px; background: #fff; border-radius: 6px; border: 1px solid var(--line);">
                        <span style="font-size: 12px; font-weight: 700; color: var(--blue-800); display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                          <i data-feather="image" style="width: 14px; height: 14px;"></i> Foto Lembar Kerja Jawaban Siswa:
                        </span>
                        <a href="<?= BASE_URL . '/' . ltrim(htmlspecialchars($ans['foto_jawaban']), '/') ?>" target="_blank" title="Klik untuk membuka ukuran penuh">
                          <img src="<?= BASE_URL . '/' . ltrim(htmlspecialchars($ans['foto_jawaban']), '/') ?>" alt="Foto Jawaban Siswa" style="max-height: 280px; max-width: 100%; border-radius: 6px; border: 1px solid #CBD5E1; box-shadow: 0 2px 6px rgba(0,0,0,0.06); object-fit: contain;">
                        </a>
                        <div style="font-size: 11px; color: var(--muted); margin-top: 4px;">
                          🔍 <em>Klik foto untuk membuka resolusi penuh di tab baru</em>
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>

                  <!-- Pedoman Kunci Jawaban & Rubrik untuk Guru -->
                  <?php if (!empty($ans['kunci_jawaban']) || !empty($ans['feedback_benar'])): ?>
                    <div style="background: #F0FDF4; border: 1px dashed #22C55E; padding: 12px 14px; border-radius: 6px; margin-bottom: 14px; font-size: 13px;">
                      <div style="font-weight: 700; color: #15803D; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                        <span>🔑</span> Pedoman Kunci Jawaban & Rubrik:
                      </div>
                      <div style="color: #166534; line-height: 1.5;">
                        <?= nl2br(htmlspecialchars($ans['kunci_jawaban'] ?: $ans['feedback_benar'])) ?>
                      </div>
                      <?php if (!empty($ans['feedback_salah'])): ?>
                        <div style="margin-top: 6px; font-size: 12px; color: #991B1B;">
                          <strong>Catatan Jawaban Salah:</strong> <?= htmlspecialchars($ans['feedback_salah']) ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <?php if ($ans['tipe_soal'] === 'essay'): ?>
                    <!-- KOREKSI AI & GURU UNTUK ESSAY -->
                    <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                      <button type="button" 
                              class="btn btn-outline btn-ai-grade" 
                              style="font-size: 12px; padding: 6px 14px; background: #EFF6FF; border-color: var(--blue-400); color: var(--blue-800);"
                              data-answer-id="<?= $ans['id'] ?>"
                              data-prompt="<?= htmlspecialchars($ans['pertanyaan']) ?>"
                              data-answer="<?= htmlspecialchars($ans['jawaban_siswa']) ?>">
                        🤖 Dapatkan Rekomendasi Skor AI (Gemini)
                      </button>
                    </div>

                    <div style="display: grid; grid-template-columns: 140px 1fr; gap: 14px;">
                      <div>
                        <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">
                          Skor Guru (0-100):
                        </label>
                        <input type="number" 
                               id="score_input_<?= $ans['id'] ?>"
                               name="teacher_score[<?= $ans['id'] ?>]" 
                               value="<?= $ans['teacher_score'] !== null ? $ans['teacher_score'] : ($ans['ai_score'] ?? '') ?>" 
                               min="0" max="100" class="form-input" placeholder="Contoh: 85" required>
                      </div>
                      <div>
                        <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">
                          Catatan / Umpan Balik Guru untuk Butir Ini:
                        </label>
                        <input type="text" 
                               id="feedback_input_<?= $ans['id'] ?>"
                               name="teacher_feedback[<?= $ans['id'] ?>]" 
                               value="<?= htmlspecialchars($ans['teacher_feedback'] ?: ($ans['ai_feedback'] ?? '')) ?>" 
                               class="form-input" placeholder="Tuliskan saran perbaikan atau apresiasi...">
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>

              <!-- Panel Nilai Akhir & Kontrol Retake -->
              <div style="background: var(--blue-bg); border: 2px solid var(--blue-400); border-radius: var(--radius-sm); padding: 20px; margin-top: 24px;">
                <h3 style="font-size: 16px; font-weight: 800; color: var(--blue-800); margin: 0 0 12px 0;">
                  Keputusan Nilai Akhir & Kontrol Guru
                </h3>

                <div style="display: grid; grid-template-columns: 200px 1fr; gap: 16px; margin-bottom: 16px;">
                  <div>
                    <label style="font-size: 13px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 6px;">
                      Nilai Akhir Siswa (0-100):
                    </label>
                    <input type="number" step="0.1" name="final_total_score" 
                           value="<?= $currentSub['total_score'] !== null ? $currentSub['total_score'] : ($currentSub['pg_score'] ?? 80) ?>" 
                           min="0" max="100" class="form-input" style="font-size: 18px; font-weight: 800; color: var(--blue-800);" required>
                  </div>
                  <div>
                    <label style="font-size: 13px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 6px;">
                      Umpan Balik Keseluruhan untuk Siswa:
                    </label>
                    <input type="text" name="general_feedback" 
                           value="<?= htmlspecialchars($currentSub['feedback_guru'] ?? 'Pengerjaan sudah baik, tingkatkan pemahaman pada latihan berikutnya.') ?>" 
                           class="form-input">
                  </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px solid var(--blue-400);">
                  <?php if ($currentSub['tipe'] === 'lkpd'): ?>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer; color: var(--ink);">
                      <input type="checkbox" name="allow_retake" value="1" <?= $currentSub['allow_retake'] ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                      <span>Izinkan Siswa Mengerjakan Ulang LKPD Ini (Status RETAKE)</span>
                    </label>
                  <?php else: ?>
                    <span style="font-size: 12px; color: var(--muted);"><em>(Retake pengerjaan ulang hanya berlaku untuk modul LKPD)</em></span>
                  <?php endif; ?>

                  <button type="submit" class="btn btn-success" style="padding: 10px 24px; font-size: 14px;">
                    <i data-feather="check-circle"></i> Simpan & Publikasikan Nilai
                  </button>
                </div>
              </div>
            </form>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
