<?php
/**
 * Ekspor Data Nilai & Pengerjaan Siswa (Spreadsheet / CSV)
 * File: guru/export_grades.php
 * FITUR H: Ekspor data pengerjaan & nilai ke format file Spreadsheet (CSV/Excel)
 */
require_once __DIR__ . '/../config/app.php';
require_role(['guru', 'admin']);

$currentUser = auth_user();
$db = getDB();

// Filter kelas
$filterKelas = $_GET['kelas'] ?? 'Semua';

$sql = "
    SELECT u.id AS user_id, u.nisn, u.nama_lengkap, u.kelas,
           a.judul AS judul_assessment, a.tipe,
           s.pg_score, s.total_score, s.is_corrected, s.feedback_guru, s.submitted_at
    FROM users u
    JOIN submissions s ON u.id = s.student_id
    JOIN assessments a ON s.assessment_id = a.id
    WHERE u.role = 'siswa'
";
$params = [];

if ($filterKelas !== 'Semua') {
    $sql .= " AND (u.kelas = ? OR u.kelas LIKE ?)";
    $params[] = $filterKelas;
    $params[] = '%' . $filterKelas . '%';
}

$sql .= " ORDER BY u.kelas ASC, u.nama_lengkap ASC, a.tipe ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// PROSES PENGUNDUHAN FILE SPREADSHEET (CSV / EXCEL COMPATIBLE)
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    $filename = "Rekap_Nilai_Siswa_ElearnMath_" . date('Y-m-d_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM untuk kompatibilitas sempurna dengan Microsoft Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Header Kolom Spreadsheet
    fputcsv($output, [
        'No',
        'NISN',
        'Nama Lengkap Siswa',
        'Kelas',
        'Modul Tugas / Evaluasi',
        'Tipe Modul',
        'Skor Pilihan Ganda',
        'Nilai Akhir',
        'Status Koreksi',
        'Catatan Guru',
        'Waktu Pengumpulan'
    ]);

    $no = 1;
    foreach ($data as $row) {
        fputcsv($output, [
            $no++,
            $row['nisn'] ?? '-',
            $row['nama_lengkap'],
            $row['kelas'] ?? '-',
            $row['judul_assessment'],
            strtoupper($row['tipe']),
            $row['pg_score'] !== null ? $row['pg_score'] : '-',
            $row['total_score'] !== null ? $row['total_score'] : 'Belum Dinilai',
            $row['is_corrected'] ? 'Selesai Dikoreksi' : 'Menunggu Koreksi',
            $row['feedback_guru'] ?? '-',
            date('Y-m-d H:i:s', strtotime($row['submitted_at']))
        ]);
    }

    fclose($output);
    exit;
}

$pageTitle = 'Ekspor Nilai Spreadsheet - ElearnMath';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
      <div>
        <span class="badge-role" style="background: var(--success-bg); color: var(--success); margin-bottom: 4px;">
          FITUR H: Spreadsheet & CSV Exporter
        </span>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
          Ekspor Data Pengerjaan & Nilai Siswa
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Unduh seluruh riwayat pengerjaan, skor pilihan ganda, nilai akhir koreksi, dan umpan balik ke dalam format file Microsoft Excel / CSV.
        </p>
      </div>

      <div style="display: flex; gap: 12px; align-items: center;">
        <a href="?kelas=<?= urlencode($filterKelas) ?>&download=csv" class="btn btn-success" style="padding: 10px 20px; font-size: 14px;">
          <i data-feather="download"></i> Unduh File Spreadsheet (.CSV)
        </a>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="glass-card" style="padding: 16px; margin-bottom: 20px;">
      <form method="GET" action="" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
        <label for="kelas" style="font-size: 13px; font-weight: 600; color: var(--ink);">Saring Kelas:</label>
        <select name="kelas" id="kelas" class="form-input" style="width: auto; padding: 6px 12px;" onchange="this.form.submit()">
          <option value="Semua" <?= $filterKelas === 'Semua' ? 'selected' : '' ?>>Semua Kelas</option>
          <option value="VII-A" <?= $filterKelas === 'VII-A' ? 'selected' : '' ?>>Kelas VII-A</option>
          <option value="VII-B" <?= $filterKelas === 'VII-B' ? 'selected' : '' ?>>Kelas VII-B</option>
          <option value="VII-C" <?= $filterKelas === 'VII-C' ? 'selected' : '' ?>>Kelas VII-C</option>
        </select>
        <span style="font-size: 12.5px; color: var(--muted);">Ditemukan: <strong><?= count($data) ?></strong> baris data</span>
      </form>
    </div>

    <!-- Pratinjau Tabel Data -->
    <div class="glass-card">
      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama Siswa</th>
              <th>NISN</th>
              <th>Kelas</th>
              <th>Modul Tugas</th>
              <th>Skor PG</th>
              <th>Nilai Akhir</th>
              <th>Status</th>
              <th>Waktu Kirim</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($data)): ?>
              <tr>
                <td colspan="9" style="text-align: center; padding: 24px; color: var(--muted);">
                  Belum ada data pengerjaan siswa untuk filter yang dipilih.
                </td>
              </tr>
            <?php else: ?>
              <?php $i = 1; foreach ($data as $r): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><strong><?= htmlspecialchars($r['nama_lengkap']) ?></strong></td>
                  <td><?= htmlspecialchars($r['nisn'] ?? '-') ?></td>
                  <td><span class="badge-role" style="font-size: 11px;"><?= htmlspecialchars($r['kelas'] ?? '-') ?></span></td>
                  <td><?= htmlspecialchars($r['judul_assessment']) ?></td>
                  <td><?= $r['pg_score'] !== null ? $r['pg_score'] : '-' ?></td>
                  <td>
                    <?php if ($r['total_score'] !== null): ?>
                      <strong style="color: var(--blue-800); font-size: 14px;"><?= $r['total_score'] ?></strong>
                    <?php else: ?>
                      <span style="color: var(--muted); font-size: 11px;">Belum Dinilai</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?= $r['is_corrected'] ? '<span style="color:var(--success); font-weight:600;">Selesai</span>' : '<span style="color:var(--orange-dark); font-weight:600;">Perlu Review</span>' ?>
                  </td>
                  <td><?= date('d/m/Y H:i', strtotime($r['submitted_at'])) ?></td>
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
