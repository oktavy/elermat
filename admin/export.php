<?php
/**
 * Ekspor Data Pengerjaan & Nilai Siswa (Admin)
 * File: admin/export.php
 * FITUR H: Ekspor data pengerjaan & nilai ke format file Spreadsheet (CSV/Excel)
 */
require_once __DIR__ . '/../config/app.php';
require_role('admin');

$db = getDB();

$sql = "
    SELECT u.id AS user_id, u.nisn, u.nama_lengkap, u.kelas,
           a.judul AS judul_assessment, a.tipe,
           s.pg_score, s.total_score, s.is_corrected, s.feedback_guru, s.submitted_at
    FROM users u
    JOIN submissions s ON u.id = s.student_id
    JOIN assessments a ON s.assessment_id = a.id
    WHERE u.role = 'siswa'
    ORDER BY u.kelas ASC, u.nama_lengkap ASC, a.tipe ASC
";
$stmt = $db->query($sql);
$data = $stmt->fetchAll();

// PROSES UNDUH SPREADSHEET (CSV)
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    $filename = "Rekapitulasi_Nilai_Sekolah_" . date('Y-m-d_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 Excel

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
            $row['is_corrected'] ? 'Selesai' : 'Perlu Review',
            $row['feedback_guru'] ?? '-',
            date('Y-m-d H:i:s', strtotime($row['submitted_at']))
        ]);
    }

    fclose($output);
    exit;
}

$pageTitle = 'Ekspor Nilai Keseluruhan - ElearnMath Admin';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
      <div>
        <span class="badge-role" style="background: var(--success-bg); color: var(--success); margin-bottom: 4px;">
          FITUR H: Admin Spreadsheet Exporter
        </span>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
          Ekspor Data Nilai & Pengerjaan Siswa
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Unduh rekapitulasi data nilai seluruh kelas ke dalam file spreadsheet (.CSV) yang kompatibel dengan Microsoft Excel dan Google Sheets.
        </p>
      </div>

      <a href="?download=csv" class="btn btn-success" style="padding: 10px 22px; font-size: 14px;">
        <i data-feather="download"></i> Unduh File Spreadsheet (.CSV)
      </a>
    </div>

    <div class="glass-card">
      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th>No</th>
              <th>NISN</th>
              <th>Nama Siswa</th>
              <th>Kelas</th>
              <th>Modul Tugas</th>
              <th>Skor PG</th>
              <th>Nilai Akhir</th>
              <th>Status</th>
              <th>Waktu Kirim</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; foreach ($data as $r): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($r['nisn'] ?? '-') ?></td>
                <td><strong><?= htmlspecialchars($r['nama_lengkap']) ?></strong></td>
                <td><span class="badge-role" style="font-size: 11px;"><?= htmlspecialchars($r['kelas'] ?? '-') ?></span></td>
                <td><?= htmlspecialchars($r['judul_assessment']) ?></td>
                <td><?= $r['pg_score'] !== null ? $r['pg_score'] : '-' ?></td>
                <td><strong><?= $r['total_score'] !== null ? $r['total_score'] : '<span style="color:var(--muted)">Belum</span>' ?></strong></td>
                <td><?= $r['is_corrected'] ? '<span style="color:var(--success)">Selesai</span>' : '<span style="color:var(--orange-dark)">Perlu Koreksi</span>' ?></td>
                <td><?= date('d/m/Y H:i', strtotime($r['submitted_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
