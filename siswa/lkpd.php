<?php
/**
 * Lembar Kerja Peserta Didik (LKPD) Siswa - Dashboard & Modul Interaktif
 * File: siswa/lkpd.php
 * 
 * PERBAIKAN BUG KRITIS:
 * 1. FIX WARNING LINE 191: Validasi ketat pengecekan record pengerjaan LKPD (if ($data_pengerjaan && is_array($data_pengerjaan))).
 *    Mencegah warning 'Trying to access array offset on value of type bool' dan mencegah tanggal berubah menjadi '01 Jan 1970'.
 * 2. STATUS DEFAULT & AKSES FORM: Jika belum ada record jawaban di database (jawaban_lkpd / pengerjaan_lkpd / submissions),
 *    status WAJIB berstatus 'Belum Dikerjakan', dan tombol/form pengerjaan LKPD WAJIB BISA DIBUKA & DIKERJAKAN.
 * 3. BATASAN AKSES KELAS (ADMIT ADMIN): Siswa yang belum di-ACC Admin menampilkan banner informatif dan modul terkunci.
 * 4. DUKUNGAN RETAKE: Jika guru mengizinkan retake, tombol pengerjaan ulang aktif dan timer direset penuh.
 */

require_once __DIR__ . '/../config/app.php';
require_role('siswa');

$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// 1. Refresh Status Pengguna Langsung dari Database (Memeriksa Admit Admin)
$stmtUser = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$currentUser['id']]);
$freshUser = $stmtUser->fetch();
if ($freshUser && is_array($freshUser)) {
    $currentUser = array_merge($currentUser, $freshUser);
    $_SESSION['user'] = $currentUser;
}

$isAdmitted = (strtoupper($currentUser['status_approval'] ?? 'PENDING') === 'APPROVED');
$studentClass = $currentUser['kelas'] ?? '';

// 2. Ambil Seluruh Daftar LKPD yang Tersedia untuk Kelas Siswa
$allLkpd = [];
if ($isAdmitted) {
    try {
        $stmtLkpd = $db->prepare("
            SELECT a.*, 
                   (SELECT COUNT(*) FROM questions q WHERE q.assessment_id = a.id) AS total_soal
            FROM assessments a
            WHERE a.tipe = 'lkpd' 
              AND a.is_active = 1
              AND (a.kelas = 'Semua Kelas' OR a.kelas = ? OR a.kelas IS NULL OR ? = '')
            ORDER BY a.urutan ASC, a.id ASC
        ");
        $stmtLkpd->execute([$studentClass, $studentClass]);
        $allLkpd = $stmtLkpd->fetchAll();
    } catch (Exception $e) {
        $allLkpd = [];
    }

    // Fallback jika belum ada LKPD terfilter kelas, ambil seluruh LKPD aktif
    if (empty($allLkpd)) {
        try {
            $allLkpd = $db->query("
                SELECT a.*, 
                       (SELECT COUNT(*) FROM questions q WHERE q.assessment_id = a.id) AS total_soal
                FROM assessments a
                WHERE a.tipe = 'lkpd' AND a.is_active = 1
                ORDER BY a.urutan ASC, a.id ASC
            ")->fetchAll();
        } catch (Exception $e) {
            $allLkpd = [];
        }
    }
}

// 3. Ambil Seluruh Riwayat Pengerjaan LKPD Siswa
$studentSubmissions = [];
if ($isAdmitted) {
    try {
        $stmtSubs = $db->prepare("
            SELECT s.* 
            FROM submissions s
            JOIN assessments a ON s.assessment_id = a.id
            WHERE s.student_id = ? AND a.tipe = 'lkpd'
            ORDER BY s.attempt DESC, s.id DESC
        ");
        $stmtSubs->execute([$currentUser['id']]);
        while ($row = $stmtSubs->fetch()) {
            $assId = (int)$row['assessment_id'];
            if (!isset($studentSubmissions[$assId])) {
                $studentSubmissions[$assId] = $row;
            }
        }
    } catch (Exception $e) {
        $studentSubmissions = [];
    }
}

$pageTitle = 'Lembar Kerja Peserta Didik (LKPD) - ElearnMath Siswa';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper" style="max-width: 1040px; margin: 0 auto;">

    <!-- Header Judul Halaman -->
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
      <div>
        <span class="badge-role" style="background: var(--orange-100); color: var(--orange-dark); margin-bottom: 6px;">
          Lembar Kerja Siswa Mandiri
        </span>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
          Lembar Kerja Peserta Didik (LKPD) Matematika
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Kerjakan modul LKPD mandiri secara cermat. Anda dapat menjawab soal essay menggunakan uraian teks, unggah foto catatan, atau foto kamera langsung.
        </p>
      </div>

      <a href="<?= BASE_URL ?>/siswa/index.php" class="btn btn-outline" style="font-size: 12.5px; padding: 7px 14px;">
        &larr; Kembali ke Dashboard
      </a>
    </div>

    <!-- Flash Message Notification -->
    <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- Banner Batasan Akses Belum Di-Admit Admin -->
    <?php if (!$isAdmitted): ?>
      <div style="background: #FFFBEB; border: 2px solid #F59E0B; border-radius: 16px; padding: 20px 24px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.1); display: flex; align-items: flex-start; gap: 16px;">
        <div style="font-size: 32px; line-height: 1;">⏳</div>
        <div>
          <h3 style="font-size: 16px; font-weight: 800; color: #92400E; margin: 0 0 6px 0;">
            Akses Kelas Anda sedang menunggu persetujuan (Admit) dari Admin untuk masuk ke kelas tujuan.
          </h3>
          <p style="font-size: 13.5px; color: #B45309; margin: 0; line-height: 1.6;">
            Modul LKPD mandiri akan otomatis terhubung dan dapat dikerjakan setelah akun dan kelas Anda disetujui (Admit) oleh Admin sekolah.
          </p>
        </div>
      </div>
    <?php endif; ?>

    <!-- Daftar LKPD (Grid View) -->
    <?php if ($isAdmitted && empty($allLkpd)): ?>
      <div class="glass-card" style="text-align: center; padding: 48px 24px; border: 1px dashed var(--line);">
        <div style="font-size: 40px; margin-bottom: 12px;">📝</div>
        <h3 style="font-size: 18px; font-weight: 800; color: var(--ink); margin: 0 0 6px 0;">
          Belum Ada Modul LKPD yang Tersedia
        </h3>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0 0 16px 0;">
          Guru mata pelajaran matematika belum mengunggah lembar LKPD untuk kelas Anda. Silakan periksa kembali secara berkala.
        </p>
        <a href="<?= BASE_URL ?>/siswa/materi.php" class="btn btn-primary" style="font-size: 13px;">
          Buka Materi Pembelajaran &rarr;
        </a>
      </div>
    <?php elseif ($isAdmitted): ?>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
        <?php foreach ($allLkpd as $idx => $lkpd): ?>
          <?php
            $lkpdId = (int)$lkpd['id'];

            // =========================================================================
            // PERBAIKAN KRITIS LINE 191 (AMBIL & VALIDASI PENGERJAAN LKPD)
            // =========================================================================
            // Query pengecekan pengerjaan LKPD siswa dari submissions / pengerjaan_lkpd
            $data_pengerjaan = $studentSubmissions[$lkpdId] ?? null;

            // Jika belum ada di cache memory, jalankan query pengecekan aman
            if ($data_pengerjaan === null) {
                try {
                    $stmtCekPengerjaan = $db->prepare("
                        SELECT * FROM submissions 
                        WHERE student_id = ? AND assessment_id = ? 
                        ORDER BY attempt DESC, id DESC 
                        LIMIT 1
                    ");
                    $stmtCekPengerjaan->execute([$currentUser['id'], $lkpdId]);
                    $data_pengerjaan = $stmtCekPengerjaan->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $data_pengerjaan = false;
                }
            }

            // =========================================================================
            // LINE 191 & SOLUSI: VALIDASI KETAT TIPE DATA ARRAY & NILAI BOOL
            // Mencegah 'Trying to access array offset on value of type bool'
            // dan mencegah tanggal default berubah ke '01 Jan 1970'.
            // =========================================================================
            if ($data_pengerjaan && is_array($data_pengerjaan) && !empty($data_pengerjaan['id'])) {
                // Siswa SUDAH mengumpulkan LKPD ini
                $hasSubmitted = true;
                $submissionId = (int)$data_pengerjaan['id'];
                $attemptNumber = (int)($data_pengerjaan['attempt'] ?? 1);
                $isCorrected = ($data_pengerjaan['is_corrected'] == 1 || $data_pengerjaan['total_score'] !== null);
                $allowRetake = (!empty($data_pengerjaan['allow_retake']) && $data_pengerjaan['allow_retake'] == 1);
                $totalScore = $data_pengerjaan['total_score'] !== null ? (float)$data_pengerjaan['total_score'] : null;

                // Format tanggal pengumpulan aman tanpa bug 01 Jan 1970
                $rawSubmitted = !empty($data_pengerjaan['submitted_at']) ? $data_pengerjaan['submitted_at'] : ($data_pengerjaan['created_at'] ?? null);
                if (!empty($rawSubmitted) && $rawSubmitted !== '0000-00-00 00:00:00') {
                    $tanggalPengumpulan = date('d M Y, H:i', strtotime($rawSubmitted)) . ' WIB';
                } else {
                    $tanggalPengumpulan = '-';
                }

                // Status Badge & Warna
                if ($allowRetake) {
                    $statusText = 'Izin Retake Aktif';
                    $statusBg = '#FEF3C7';
                    $statusColor = '#B45309';
                    $statusIcon = '🔄';
                } elseif ($isCorrected) {
                    $statusText = 'Selesai Dinilai (Nilai: ' . $totalScore . ')';
                    $statusBg = 'var(--success-bg)';
                    $statusColor = 'var(--success)';
                    $statusIcon = '✓';
                } else {
                    $statusText = 'Menunggu Penilaian Guru';
                    $statusBg = '#E0F2FE';
                    $statusColor = '#0369A1';
                    $statusIcon = '⏳';
                }
            } else {
                // WAJIB: JIKA BELUM ADA RECORD JAWABAN, STATUS HARUS "Belum Dikerjakan"
                // Tombol/form pengerjaan LKPD WAJIB BISA DIBUKA & DIKERJAKAN
                $data_pengerjaan = null;
                $hasSubmitted = false;
                $submissionId = 0;
                $attemptNumber = 0;
                $isCorrected = false;
                $allowRetake = false;
                $totalScore = null;
                $tanggalPengumpulan = '-';
                $statusText = 'Belum Dikerjakan';
                $statusBg = '#FEE2E2';
                $statusColor = '#DC2626';
                $statusIcon = '✏️';
            }
          ?>

          <div class="glass-card" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid var(--orange-dark); padding: 22px;">
            <div>
              <!-- Header Card LKPD: Nomor Urutan & Badge Status -->
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                <span class="badge-role" style="font-size: 11.5px; background: var(--orange-100); color: var(--orange-dark); font-weight: 700;">
                  LKPD <?= (int)$lkpd['urutan'] ?>
                </span>

                <span style="font-size: 11.5px; font-weight: 700; background: <?= $statusBg ?>; color: <?= $statusColor ?>; padding: 4px 12px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px;">
                  <span><?= $statusIcon ?></span> <?= $statusText ?>
                </span>
              </div>

              <!-- Judul LKPD -->
              <h3 style="font-size: 17.5px; font-weight: 800; color: var(--blue-800); margin: 0 0 8px 0; line-height: 1.4;">
                <?= htmlspecialchars($lkpd['judul']) ?>
              </h3>

              <!-- Deskripsi LKPD -->
              <p style="font-size: 13px; color: var(--muted); margin: 0 0 16px 0; line-height: 1.55;">
                <?= htmlspecialchars($lkpd['deskripsi'] ?: 'Lembar kerja mandiri untuk mengeksplorasi konsep pemecahan masalah matematika.') ?>
              </p>

              <!-- Parameter & Rincian Modul -->
              <div style="display: flex; flex-direction: column; gap: 6px; padding: 12px 14px; background: #F8FAFC; border: 1px solid var(--line); border-radius: 10px; margin-bottom: 18px; font-size: 12px; color: var(--ink);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                  <span style="color: var(--muted);"><i data-feather="clock" style="width: 13px; height: 13px; vertical-align: middle;"></i> Durasi Pengerjaan:</span>
                  <strong><?= (int)$lkpd['duration_minutes'] ?> Menit</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                  <span style="color: var(--muted);"><i data-feather="help-circle" style="width: 13px; height: 13px; vertical-align: middle;"></i> Total Pertanyaan:</span>
                  <strong><?= (int)($lkpd['total_soal'] ?? 0) ?> Butir Soal</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                  <span style="color: var(--muted);"><i data-feather="calendar" style="width: 13px; height: 13px; vertical-align: middle;"></i> Waktu Pengumpulan:</span>
                  <strong><?= $tanggalPengumpulan ?></strong>
                </div>
                <?php if ($hasSubmitted && $attemptNumber > 0): ?>
                  <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--muted);"><i data-feather="repeat" style="width: 13px; height: 13px; vertical-align: middle;"></i> Percobaan:</span>
                    <span class="badge-role" style="font-size: 10.5px; padding: 2px 8px;">Attempt #<?= $attemptNumber ?></span>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Bagian Tombol Aksi (Wajib Berfungsi Penuh) -->
            <div style="border-top: 1px solid var(--line); padding-top: 16px;">
              <?php if (!$hasSubmitted): ?>
                <!-- Kondisi: Belum Dikerjakan -> WAJIB BISA DIBUKA & DIKERJAKAN -->
                <a href="<?= BASE_URL ?>/siswa/pengerjaan.php?type=lkpd&id=<?= $lkpdId ?>" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 13px; font-weight: 700; padding: 10px 16px;">
                  <i data-feather="edit-3"></i> Mulai Kerjakan LKPD &rarr;
                </a>
              <?php elseif ($allowRetake): ?>
                <!-- Kondisi: Siswa Diberikan Izin Retake oleh Guru -->
                <div style="display: flex; flex-direction: column; gap: 8px;">
                  <a href="<?= BASE_URL ?>/siswa/pengerjaan.php?type=lkpd&id=<?= $lkpdId ?>" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 13px; font-weight: 700; padding: 10px 16px; background: #D97706; border-color: #D97706;">
                    <i data-feather="repeat"></i> Mulai Pengerjaan Ulang (Retake) &rarr;
                  </a>
                  <a href="<?= BASE_URL ?>/siswa/review_hasil.php?submission_id=<?= $submissionId ?>" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 12px; padding: 7px 12px;">
                    Tinjau Jawaban Sebelumnya &rarr;
                  </a>
                </div>
              <?php else: ?>
                <!-- Kondisi: Sudah Dikerjakan & Tidak Ada Izin Retake -> Buka Hasil & Pembahasan -->
                <a href="<?= BASE_URL ?>/siswa/review_hasil.php?submission_id=<?= $submissionId ?>" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 13px; font-weight: 700; padding: 9px 16px;">
                  <i data-feather="eye"></i> Tinjau Hasil & Pembahasan &rarr;
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
