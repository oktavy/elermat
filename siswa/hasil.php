<?php
/**
 * Rekap Nilai & Hasil Pengerjaan Siswa (Tanpa Batas KKM)
 * File: siswa/hasil.php
 * FITUR:
 * 1. Rekap seluruh nilai LKPD, Latihan 1, 2, 3, dan Evaluasi.
 * 2. PENGHAPUSAN KKM: Aturan KKM 75 ditiadakan, nilai murni berdasarkan hasil evaluasi guru.
 * 3. Indikator Izin Retake dari Guru.
 */
require_once __DIR__ . '/../config/app.php';
require_role('siswa');

$pageTitle = 'Hasil & Nilai Belajar - ElearnMath';
$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// Cek status Admit Admin
if (strtoupper($currentUser['status_approval'] ?? 'PENDING') !== 'APPROVED') {
    flash_set('warning', 'Akses Kelas Anda sedang menunggu persetujuan (Admit) dari Admin untuk masuk ke kelas tujuan.');
    redirect('siswa/index.php');
}

$studentClass = $currentUser['kelas'] ?? '';

// Ambil seluruh modul dan submission siswa
$stmt = $db->prepare("
    SELECT a.id AS assessment_id, a.tipe, a.judul, a.urutan,
           s.id AS submission_id, s.pg_score, s.essay_score, s.total_score, 
           s.status, s.is_corrected, s.allow_retake, s.feedback_guru, s.submitted_at, s.reviewed_at
    FROM assessments a
    LEFT JOIN submissions s ON a.id = s.assessment_id AND s.student_id = ?
    WHERE (a.kelas = 'Semua Kelas' OR a.kelas = ? OR ? = '')
    ORDER BY FIELD(a.tipe, 'lkpd', 'latihan', 'evaluasi'), a.urutan ASC, a.id ASC
");
$stmt->execute([$currentUser['id'], $studentClass, $studentClass]);
$results = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="margin-bottom: 24px;">
      <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 6px;">
        Transkrip Nilai Siswa
      </span>
      <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
        Rekap Hasil & Nilai Belajar Matematika
      </h1>
      <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
        Pantau nilai LKPD, Latihan Soal, dan Evaluasi Akhir Anda secara transparan beserta umpan balik edukatif dari guru pengajar.
      </p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="glass-card">
      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th>Modul Pembelajaran</th>
              <th>Status Pengumpulan</th>
              <th>Waktu Kirim</th>
              <th>Skor Pilihan Ganda</th>
              <th>Nilai Akhir Siswa</th>
              <th>Status Koreksi Guru</th>
              <th>Catatan & Masukan Guru</th>
              <th style="text-align: center;">Tindakan</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($results)): ?>
              <tr>
                <td colspan="8" style="text-align: center; color: var(--muted); padding: 32px;">
                  Belum ada modul yang terdaftar untuk kelas Anda.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($results as $res): ?>
                <tr>
                  <td>
                    <div style="font-weight: 700; color: var(--ink); font-size: 13.5px;">
                      <?= htmlspecialchars($res['judul']) ?>
                    </div>
                    <span class="badge-role" style="font-size: 10.5px; background: #EFF6FF; color: var(--blue-800);">
                      <?= strtoupper($res['tipe']) ?> <?= $res['urutan'] ? ('#' . $res['urutan']) : '' ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!empty($res['submission_id'])): ?>
                      <span style="color: var(--success); font-weight: 600; display: inline-flex; align-items: center; gap: 4px; font-size: 12.5px;">
                        <i data-feather="check-circle" style="width: 14px; height: 14px;"></i> Terkumpul
                      </span>
                    <?php else: ?>
                      <span style="color: var(--muted); font-size: 12.5px;">Belum Mengerjakan</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size: 12px; color: var(--muted); white-space: nowrap;">
                    <?= !empty($res['submitted_at']) ? date('d/m/Y H:i', strtotime($res['submitted_at'])) : '-' ?>
                  </td>
                  <td style="font-weight: 600; font-size: 13px;">
                    <?= $res['pg_score'] !== null ? ($res['pg_score'] . ' / 100') : '-' ?>
                  </td>
                  <td>
                    <?php if ($res['total_score'] !== null): ?>
                      <span style="font-size: 18px; font-weight: 800; color: var(--blue-800);">
                        <?= $res['total_score'] ?>
                      </span>
                    <?php elseif (!empty($res['submission_id'])): ?>
                      <span style="color: var(--muted); font-size: 12px;">Menunggu Koreksi</span>
                    <?php else: ?>
                      <span style="color: #94A3B8;">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($res['tipe'] === 'lkpd' && $res['allow_retake']): ?>
                      <span style="background: #FEF3C7; color: #B45309; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; display: inline-block;">
                        🔄 IZIN RETAKE AKTIF
                      </span>
                    <?php elseif ($res['is_corrected']): ?>
                      <span style="background: var(--success-bg); color: var(--success); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; display: inline-block;">
                        Tuntas Dinilai
                      </span>
                    <?php elseif (!empty($res['submission_id'])): ?>
                      <span style="background: var(--orange-100); color: var(--orange-dark); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; display: inline-block;">
                        Perlu Koreksi Guru
                      </span>
                    <?php else: ?>
                      <span style="color: var(--muted); font-size: 12px;">Belum Mengirim</span>
                    <?php endif; ?>
                  </td>
                  <td style="max-width: 250px; font-size: 12.5px; color: var(--ink);">
                    <?= !empty($res['feedback_guru']) ? nl2br(htmlspecialchars($res['feedback_guru'])) : '<em style="color:var(--muted);">Belum ada catatan</em>' ?>
                  </td>
                  <td style="text-align: center;">
                    <?php if ($res['tipe'] === 'lkpd' && !empty($res['allow_retake'])): ?>
                      <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
                        <a href="<?= BASE_URL ?>/siswa/review_hasil.php?submission_id=<?= $res['submission_id'] ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 11px;">
                          Tinjau
                        </a>
                        <a href="<?= BASE_URL ?>/siswa/pengerjaan.php?id=<?= $res['assessment_id'] ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 11px; font-weight: 700; background: #D97706; border-color: #D97706;">
                          Retake
                        </a>
                      </div>
                    <?php elseif (!empty($res['submission_id'])): ?>
                      <a href="<?= BASE_URL ?>/siswa/review_hasil.php?submission_id=<?= $res['submission_id'] ?>" class="btn btn-outline" style="padding: 5px 12px; font-size: 11.5px;">
                        Tinjau Pembahasan &rarr;
                      </a>
                    <?php else: ?>
                      <a href="<?= BASE_URL ?>/siswa/pengerjaan.php?id=<?= $res['assessment_id'] ?>" class="btn btn-primary" style="padding: 5px 12px; font-size: 11.5px;">
                        Mulai Kerjakan &rarr;
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
