<?php
/**
 * Rekap Nilai Siswa
 * File: guru/nilai.php
 */
require_once __DIR__ . '/../config/app.php';
require_role(['guru', 'admin']);

$pageTitle = 'Rekap Nilai Siswa - ElearnMath';
$db = getDB();

$stmt = $db->query("
    SELECT u.id, u.username, u.nama_lengkap, u.nisn, u.kelas,
           MAX(CASE WHEN a.tipe = 'lkpd' THEN s.total_score END) AS nilai_lkpd,
           MAX(CASE WHEN a.tipe = 'latihan' THEN s.total_score END) AS nilai_latihan,
           MAX(CASE WHEN a.tipe = 'evaluasi' THEN s.total_score END) AS nilai_evaluasi
    FROM users u
    LEFT JOIN submissions s ON u.id = s.student_id
    LEFT JOIN assessments a ON s.assessment_id = a.id
    WHERE u.role = 'siswa'
    GROUP BY u.id
    ORDER BY u.kelas ASC, u.nama_lengkap ASC
");
$students = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
      <div>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 4px 0;">
          Rekapitulasi Nilai Siswa
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Daftar nilai akhir LKPD, Latihan, dan Evaluasi seluruh siswa yang telah dikoreksi.
        </p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/guru/export_grades.php" class="btn btn-success">
          <i data-feather="download"></i> Ekspor ke Spreadsheet
        </a>
      </div>
    </div>

    <div class="glass-card">
      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th>NISN</th>
              <th>Nama Siswa</th>
              <th>Kelas</th>
              <th>Nilai LKPD</th>
              <th>Nilai Latihan</th>
              <th>Nilai Evaluasi</th>
              <th>Rata-rata</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $st): 
              $validScores = array_filter([$st['nilai_lkpd'], $st['nilai_latihan'], $st['nilai_evaluasi']], fn($v) => $v !== null);
              $avg = count($validScores) > 0 ? round(array_sum($validScores) / count($validScores), 1) : '-';
            ?>
              <tr>
                <td><?= htmlspecialchars($st['nisn'] ?? '-') ?></td>
                <td><strong><?= htmlspecialchars($st['nama_lengkap']) ?></strong></td>
                <td><span class="badge-role" style="font-size: 11px;"><?= htmlspecialchars($st['kelas'] ?? '-') ?></span></td>
                <td><?= $st['nilai_lkpd'] !== null ? $st['nilai_lkpd'] : '<span style="color:var(--muted)">-</span>' ?></td>
                <td><?= $st['nilai_latihan'] !== null ? $st['nilai_latihan'] : '<span style="color:var(--muted)">-</span>' ?></td>
                <td><?= $st['nilai_evaluasi'] !== null ? $st['nilai_evaluasi'] : '<span style="color:var(--muted)">-</span>' ?></td>
                <td>
                  <strong style="color: var(--blue-800); font-size: 15px;">
                    <?= $avg ?>
                  </strong>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
