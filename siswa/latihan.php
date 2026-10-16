<?php
/**
 * Modul Latihan Mandiri Matematika Siswa
 * File: siswa/latihan.php
 * 
 * PERBAIKAN BUG KRITIS & FITUR:
 * 1. FIX LOGIKA STATUS PENGERJAAN & CEK QUERY AMAN:
 *    - Query riwayat pengerjaan divalidasi dengan if ($data_pengerjaan && is_array($data_pengerjaan)).
 *    - Jika record pengerjaan BELUM ADA di database, status WAJIB 'Belum Dikerjakan'.
 *    - FORM / TOMBOL PENGERJAAN TIDAK TERKUNCI dan mengarah ke:
 *      siswa/pengerjaan.php?type=latihan&id=...
 * 2. LATIHAN BERTINGKAT TANPA KUNCI KOREKSI:
 *    - Latihan 2, 3, dan seterusnya TIDAK DIKUNCI oleh koreksi guru sehingga siswa dapat melanjutkan belajar secara mandiri.
 * 3. BATASAN AKSES KELAS (ADMIT ADMIN):
 *    - Siswa yang belum di-ACC Admin menampilkan banner informatif:
 *      "Akses Kelas Anda sedang menunggu persetujuan (Admit) dari Admin untuk masuk ke kelas tujuan."
 * 4. ROUTING REVIEW & PEMBAHASAN:
 *    - Setelah dikerjakan, tombol mengarah ke siswa/review_hasil.php?submission_id=...
 */

require_once __DIR__ . '/../config/app.php';
require_role('siswa');

$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// 1. Refresh Status Pengguna Langsung dari DB untuk Memeriksa Admit Admin
$stmtUser = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$currentUser['id']]);
$freshUser = $stmtUser->fetch();
if ($freshUser && is_array($freshUser)) {
    $currentUser = array_merge($currentUser, $freshUser);
    $_SESSION['user'] = $currentUser;
}

$isAdmitted = (strtoupper($currentUser['status_approval'] ?? 'PENDING') === 'APPROVED');
$studentClass = $currentUser['kelas'] ?? '';

// 2. Ambil Seluruh Modul Latihan yang Tersedia
$allLatihan = [];
if ($isAdmitted) {
    try {
        $stmtAssList = $db->prepare("
            SELECT a.*, 
                   (SELECT COUNT(*) FROM questions q WHERE q.assessment_id = a.id) AS total_soal
            FROM assessments a
            WHERE a.tipe = 'latihan' 
              AND a.is_active = 1
              AND (a.kelas = 'Semua Kelas' OR a.kelas = ? OR a.kelas IS NULL OR ? = '')
            ORDER BY a.urutan ASC, a.id ASC
        ");
        $stmtAssList->execute([$studentClass, $studentClass]);
        $allLatihan = $stmtAssList->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $allLatihan = [];
    }

    // Fallback jika belum ada latihan terfilter spesifik
    if (empty($allLatihan)) {
        try {
            $allLatihan = $db->query("
                SELECT a.*, 
                       (SELECT COUNT(*) FROM questions q WHERE q.assessment_id = a.id) AS total_soal
                FROM assessments a 
                WHERE a.tipe = 'latihan' AND a.is_active = 1
                ORDER BY a.urutan ASC, a.id ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $allLatihan = [];
        }
    }
}

// 3. Ambil Riwayat Submission Siswa untuk Modul Latihan
$studentSubmissions = [];
if ($isAdmitted) {
    try {
        $stmtSubs = $db->prepare("
            SELECT s.* 
            FROM submissions s
            JOIN assessments a ON s.assessment_id = a.id
            WHERE s.student_id = ? AND a.tipe = 'latihan'
            ORDER BY s.attempt DESC, s.id DESC
        ");
        $stmtSubs->execute([$currentUser['id']]);
        while ($row = $stmtSubs->fetch(PDO::FETCH_ASSOC)) {
            $assId = (int)$row['assessment_id'];
            if (!isset($studentSubmissions[$assId])) {
                $studentSubmissions[$assId] = $row;
            }
        }
    } catch (Exception $e) {
        $studentSubmissions = [];
    }
}

$pageTitle = 'Latihan Soal Matematika - ElearnMath Siswa';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper" style="max-width: 1040px; margin: 0 auto;">

    <!-- Header Judul Halaman -->
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
      <div>
        <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 6px;">
          Latihan Mandiri Bertingkat
        </span>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
          Daftar Latihan Soal Matematika
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Kerjakan modul latihan secara mandiri. Latihan 2, Latihan 3, dan seterusnya dapat langsung dikerjakan tanpa perlu menunggu koreksi guru.
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
            Modul latihan soal mandiri akan otomatis terhubung dan dapat dikerjakan setelah akun dan kelas Anda disetujui (Admit) oleh Admin sekolah.
          </p>
        </div>
      </div>
    <?php endif; ?>

    <!-- Grid Daftar Seluruh Latihan -->
    <?php if ($isAdmitted && empty($allLatihan)): ?>
      <div class="glass-card" style="text-align: center; padding: 48px 24px; border: 1px dashed var(--line);">
        <div style="font-size: 40px; margin-bottom: 12px;">📚</div>
        <h3 style="font-size: 18px; font-weight: 800; color: var(--ink); margin: 0 0 6px 0;">
          Belum Ada Modul Latihan yang Tersedia
        </h3>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0 0 16px 0;">
          Guru mata pelajaran matematika belum mengunggah modul latihan untuk kelas Anda. Silakan cek kembali secara berkala.
        </p>
        <a href="<?= BASE_URL ?>/siswa/materi.php" class="btn btn-primary" style="font-size: 13px;">
          Buka Materi Pembelajaran &rarr;
        </a>
      </div>
    <?php elseif ($isAdmitted): ?>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
        <?php foreach ($allLatihan as $idx => $lat): ?>
          <?php
            $latId = (int)$lat['id'];

            // =========================================================================
            // PERBAIKAN KRITIS: QUERY & VALIDASI PENGERJAAN LATIHAN
            // =========================================================================
            $data_pengerjaan = $studentSubmissions[$latId] ?? null;

            if ($data_pengerjaan === null) {
                try {
                    $stmtCekPengerjaan = $db->prepare("
                        SELECT * FROM submissions 
                        WHERE student_id = ? AND assessment_id = ? 
                        ORDER BY attempt DESC, id DESC 
                        LIMIT 1
                    ");
                    $stmtCekPengerjaan->execute([$currentUser['id'], $latId]);
                    $data_pengerjaan = $stmtCekPengerjaan->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $data_pengerjaan = false;
                }
            }

            // =========================================================================
            // VALIDASI KETAT TIPE DATA ARRAY & NILAI BOOL
            // Mencegah 'Trying to access array offset on value of type bool'
            // dan mencegah tanggal default berubah ke '01 Jan 1970'.
            // =========================================================================
            if ($data_pengerjaan && is_array($data_pengerjaan) && !empty($data_pengerjaan['id'])) {
                $hasSubmitted = true;
                $submissionId = (int)$data_pengerjaan['id'];
                $attemptNumber = (int)($data_pengerjaan['attempt'] ?? 1);
                $isCorrected = ($data_pengerjaan['is_corrected'] == 1 || $data_pengerjaan['total_score'] !== null);
                $totalScore = $data_pengerjaan['total_score'] !== null ? (float)$data_pengerjaan['total_score'] : null;

                $rawSubmitted = !empty($data_pengerjaan['submitted_at']) ? $data_pengerjaan['submitted_at'] : ($data_pengerjaan['created_at'] ?? null);
                if (!empty($rawSubmitted) && $rawSubmitted !== '0000-00-00 00:00:00') {
                    $tanggalPengumpulan = date('d M Y, H:i', strtotime($rawSubmitted)) . ' WIB';
                } else {
                    $tanggalPengumpulan = '-';
                }

                if ($isCorrected) {
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
                // WAJIB: Jika record pengerjaan BELUM ADA di database, tetapkan status WAJIB "Belum Dikerjakan", JANGAN kunci form!
                $data_pengerjaan = null;
                $hasSubmitted = false;
                $submissionId = 0;
                $attemptNumber = 0;
                $isCorrected = false;
                $totalScore = null;
                $tanggalPengumpulan = '-';
                $statusText = 'Belum Dikerjakan';
                $statusBg = '#FEE2E2';
                $statusColor = '#DC2626';
                $statusIcon = '✏️';
            }
          ?>

          <div class="glass-card" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid var(--blue-600); padding: 22px;">
            <div>
              <!-- Header Card Latihan: Nomor Urutan & Badge Status -->
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                <span class="badge-role" style="font-size: 11.5px; background: var(--blue-100); color: var(--blue-800); font-weight: 700;">
                  Latihan <?= (int)$lat['urutan'] ?>
                </span>

                <span style="font-size: 11.5px; font-weight: 700; background: <?= $statusBg ?>; color: <?= $statusColor ?>; padding: 4px 12px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px;">
                  <span><?= $statusIcon ?></span> <?= $statusText ?>
                </span>
              </div>

              <!-- Judul Latihan -->
              <h3 style="font-size: 17.5px; font-weight: 800; color: var(--blue-800); margin: 0 0 8px 0; line-height: 1.4;">
                <?= htmlspecialchars($lat['judul']) ?>
              </h3>

              <!-- Deskripsi Latihan -->
              <p style="font-size: 13px; color: var(--muted); margin: 0 0 16px 0; line-height: 1.55;">
                <?= htmlspecialchars($lat['deskripsi'] ?: 'Latihan mandiri untuk mengasah pemahaman materi dan pemecahan masalah matematika.') ?>
              </p>

              <!-- Parameter & Rincian Modul -->
              <div style="display: flex; flex-direction: column; gap: 6px; padding: 12px 14px; background: #F8FAFC; border: 1px solid var(--line); border-radius: 10px; margin-bottom: 18px; font-size: 12px; color: var(--ink);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                  <span style="color: var(--muted);"><i data-feather="clock" style="width: 13px; height: 13px; vertical-align: middle;"></i> Durasi Pengerjaan:</span>
                  <strong><?= (int)$lat['duration_minutes'] ?> Menit</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                  <span style="color: var(--muted);"><i data-feather="help-circle" style="width: 13px; height: 13px; vertical-align: middle;"></i> Total Pertanyaan:</span>
                  <strong><?= (int)($lat['total_soal'] ?? 0) ?> Butir Soal</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                  <span style="color: var(--muted);"><i data-feather="calendar" style="width: 13px; height: 13px; vertical-align: middle;"></i> Waktu Pengumpulan:</span>
                  <strong><?= $tanggalPengumpulan ?></strong>
                </div>
              </div>
            </div>

            <!-- Bagian Tombol Aksi (Wajib Berfungsi Penuh) -->
            <div style="border-top: 1px solid var(--line); padding-top: 16px;">
              <?php if (!$hasSubmitted): ?>
                <!-- Kondisi: Belum Dikerjakan -> WAJIB BISA DIBUKA & DIKERJAKAN -->
                <a href="<?= BASE_URL ?>/siswa/pengerjaan.php?type=latihan&id=<?= $latId ?>" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 13px; font-weight: 700; padding: 10px 16px;">
                  <i data-feather="edit-3"></i> Mulai Kerjakan Latihan &rarr;
                </a>
              <?php else: ?>
                <!-- Kondisi: Sudah Dikerjakan -> Buka Hasil & Pembahasan -->
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
