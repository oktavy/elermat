<?php
/**
 * Rekapitulasi & Ekspor Nilai Terstruktur ke Google Sheets / Spreadsheet
 * File: guru/export_spreadsheet.php
 * FITUR:
 * 1. Data rekapitulasi terstruktur per siswa (Nilai LKPD, Latihan 1, Latihan 2, Latihan 3, Evaluasi, Total, & Rata-rata).
 * 2. Ekspor unduhan Spreadsheet (.CSV / .XLSX compatible).
 * 3. Integrasi langsung Google Sheets (Salin format TSV untuk Google Sheets & tautan buka Google Sheets).
 * 4. Susunan kolom standar: [No, Nama Siswa, Kelas, Nilai LKPD, Nilai Latihan 1, Nilai Latihan 2, Nilai Evaluasi, Total/Rata-Rata].
 */
require_once __DIR__ . '/../config/app.php';
require_role(['guru', 'admin']);

$currentUser = auth_user();
$db = getDB();

// Filter Kelas
$filterKelas = $_GET['kelas'] ?? 'Semua';

// 1. Ambil daftar siswa sesuai filter kelas
$sqlSiswa = "SELECT id, nisn, nama_lengkap, kelas FROM users WHERE role = 'siswa'";
$paramsSiswa = [];

if ($filterKelas !== 'Semua') {
    $sqlSiswa .= " AND (kelas = ? OR kelas LIKE ?)";
    $paramsSiswa[] = $filterKelas;
    $paramsSiswa[] = '%' . $filterKelas . '%';
}

$sqlSiswa .= " ORDER BY kelas ASC, nama_lengkap ASC";
$stmtS = $db->prepare($sqlSiswa);
$stmtS->execute($paramsSiswa);
$students = $stmtS->fetchAll();

// 2. Ambil seluruh submission siswa terkelompok
$stmtSubs = $db->query("
    SELECT s.student_id, a.tipe, a.urutan, s.total_score, s.pg_score, s.is_corrected
    FROM submissions s
    JOIN assessments a ON s.assessment_id = a.id
");
$allSubmissions = $stmtSubs->fetchAll();

// Kelompokkan skor berdasarkan [student_id][tipe_urutan]
$scores = [];
foreach ($allSubmissions as $sub) {
    $stId = $sub['student_id'];
    $key = $sub['tipe'] . '_' . ($sub['urutan'] ?? 1);
    
    // Ambil total_score jika ada, jika belum dinilai namun ada pg_score, tampilkan pg_score atau catatan
    $val = null;
    if ($sub['total_score'] !== null) {
        $val = (float)$sub['total_score'];
    } elseif ($sub['pg_score'] !== null) {
        $val = (float)$sub['pg_score'];
    }

    $scores[$stId][$key] = $val;
}

// 3. Susun data matriks terstruktur
$matrixData = [];
$no = 1;

foreach ($students as $st) {
    $stId = $st['id'];
    $stScores = $scores[$stId] ?? [];

    $nlkpd  = $stScores['lkpd_1'] ?? null;
    $nlat1  = $stScores['latihan_1'] ?? null;
    $nlat2  = $stScores['latihan_2'] ?? null;
    $nlat3  = $stScores['latihan_3'] ?? null;
    $neval  = $stScores['evaluasi_1'] ?? null;

    // Hitung total dan rata-rata dari nilai yang sudah masuk
    $availableScores = array_filter([$nlkpd, $nlat1, $nlat2, $nlat3, $neval], function($v) {
        return $v !== null;
    });

    $countScored = count($availableScores);
    $totalVal = $countScored > 0 ? array_sum($availableScores) : null;
    $avgVal   = $countScored > 0 ? round($totalVal / $countScored, 2) : null;

    $matrixData[] = [
        'no'          => $no++,
        'id'          => $stId,
        'nama'        => $st['nama_lengkap'],
        'kelas'       => $st['kelas'] ?: 'VII-A',
        'nisn'        => $st['nisn'] ?: '-',
        'nilai_lkpd'  => $nlkpd,
        'nilai_lat1'  => $nlat1,
        'nilai_lat2'  => $nlat2,
        'nilai_lat3'  => $nlat3,
        'nilai_eval'  => $neval,
        'total'       => $totalVal,
        'rata_rata'   => $avgVal
    ];
}

// =====================================================================
// PROSES PENGUNDUHAN FILE SPREADSHEET (.CSV / EXCEL COMPATIBLE)
// =====================================================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "Rekap_Nilai_Siswa_ElearnMath_" . date('Y-m-d_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM untuk kompatibilitas sempurna Microsoft Excel & Google Sheets
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Baris Header Standar Sesuai Spesifikasi Teknis
    fputcsv($output, [
        'No',
        'Nama Siswa',
        'Kelas',
        'Nilai LKPD',
        'Nilai Latihan 1',
        'Nilai Latihan 2',
        'Nilai Latihan 3',
        'Nilai Evaluasi',
        'Total Nilai',
        'Rata-Rata'
    ]);

    foreach ($matrixData as $row) {
        fputcsv($output, [
            $row['no'],
            $row['nama'],
            $row['kelas'],
            $row['nilai_lkpd'] !== null ? $row['nilai_lkpd'] : '-',
            $row['nilai_lat1'] !== null ? $row['nilai_lat1'] : '-',
            $row['nilai_lat2'] !== null ? $row['nilai_lat2'] : '-',
            $row['nilai_lat3'] !== null ? $row['nilai_lat3'] : '-',
            $row['nilai_eval'] !== null ? $row['nilai_eval'] : '-',
            $row['total'] !== null ? $row['total'] : '-',
            $row['rata_rata'] !== null ? $row['rata_rata'] : '-'
        ]);
    }

    fclose($output);
    exit;
}

// Ambil daftar kelas untuk dropdown filter
$classList = $db->query("SELECT DISTINCT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($classList)) {
    $classList = ['VII-A', 'VII-B', 'VII-C'];
}

$pageTitle = 'Rekapitulasi & Ekspor Nilai Spreadsheet - ElearnMath';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
      <div>
        <span class="badge-role" style="background: var(--success-bg); color: var(--success); margin-bottom: 4px;">
          📊 Ekspor Spreadsheet & Google Sheets
        </span>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
          Rekapitulasi Nilai Siswa Terstruktur
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Rekapitulasi matriks nilai terbagi ke dalam kolom standar: LKPD, Latihan 1, Latihan 2, Latihan 3, Evaluasi, Total, dan Rata-rata.
        </p>
      </div>

      <!-- Tombol Aksi Ekspor -->
      <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <!-- Unduh Spreadsheet CSV -->
        <a href="?kelas=<?= urlencode($filterKelas) ?>&export=csv" class="btn btn-success" style="padding: 10px 18px; font-size: 13px; font-weight: 700; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);">
          <i data-feather="download"></i> Unduh Spreadsheet (.CSV / Excel)
        </a>

        <!-- Salin Cepat untuk Google Sheets -->
        <button type="button" class="btn btn-outline" id="btnCopyGoogleSheets" onclick="copyForGoogleSheets()" style="padding: 10px 18px; font-size: 13px; font-weight: 700; background: #fff;">
          <i data-feather="copy"></i> Salin untuk Google Sheets
        </button>

        <!-- Buka Lembar Google Sheets Baru -->
        <a href="https://sheets.new" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="padding: 10px 18px; font-size: 13px; background: #fff;">
          <i data-feather="external-link"></i> Buka Google Sheets &rarr;
        </a>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="glass-card" style="padding: 16px 20px; margin-bottom: 20px;">
      <form method="GET" action="" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
        <label for="kelas" style="font-size: 13.5px; font-weight: 700; color: var(--ink);">Saring Kelas:</label>
        <select name="kelas" id="kelas" class="form-input" style="width: auto; padding: 7px 14px; font-size: 13.5px;" onchange="this.form.submit()">
          <option value="Semua" <?= $filterKelas === 'Semua' ? 'selected' : '' ?>>Semua Kelas</option>
          <?php foreach ($classList as $cName): ?>
            <option value="<?= htmlspecialchars($cName) ?>" <?= $filterKelas === $cName ? 'selected' : '' ?>>
              Kelas <?= htmlspecialchars($cName) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <span style="font-size: 13px; color: var(--muted); margin-left: auto;">
          Total Siswa: <strong><?= count($matrixData) ?></strong> Orang
        </span>
      </form>
    </div>

    <!-- Pratinjau Tabel Matriks Rekapitulasi Nilai -->
    <div class="glass-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 17px; font-weight: 800; color: var(--blue-800); margin: 0;">
          Matriks Rekapitulasi Nilai Seluruh Siswa
        </h2>
        <span style="font-size: 12px; color: var(--muted);">
          Terakhir diperbarui: <?= date('d M Y, H:i') ?> WIB
        </span>
      </div>

      <div class="data-table-container">
        <table class="table-custom" id="tableRekapNilai">
          <thead>
            <tr>
              <th style="width: 45px; text-align: center;">No</th>
              <th>Nama Siswa</th>
              <th>Kelas</th>
              <th style="text-align: center;">Nilai LKPD</th>
              <th style="text-align: center;">Nilai Latihan 1</th>
              <th style="text-align: center;">Nilai Latihan 2</th>
              <th style="text-align: center;">Nilai Latihan 3</th>
              <th style="text-align: center;">Nilai Evaluasi</th>
              <th style="text-align: center; background: #EFF6FF;">Total Nilai</th>
              <th style="text-align: center; background: #EFF6FF;">Rata-Rata</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($matrixData)): ?>
              <tr>
                <td colspan="10" style="text-align: center; padding: 32px; color: var(--muted);">
                  Belum ada data siswa untuk kelas yang dipilih.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($matrixData as $r): ?>
                <tr>
                  <td style="text-align: center; font-weight: 700; color: var(--muted);"><?= $r['no'] ?></td>
                  <td><strong><?= htmlspecialchars($r['nama']) ?></strong></td>
                  <td><span class="badge-role" style="font-size: 11px;"><?= htmlspecialchars($r['kelas']) ?></span></td>
                  
                  <!-- Nilai LKPD -->
                  <td style="text-align: center;">
                    <?= $r['nilai_lkpd'] !== null ? '<strong>' . $r['nilai_lkpd'] . '</strong>' : '<span style="color:#CBD5E1;">-</span>' ?>
                  </td>

                  <!-- Nilai Latihan 1 -->
                  <td style="text-align: center;">
                    <?= $r['nilai_lat1'] !== null ? '<strong>' . $r['nilai_lat1'] . '</strong>' : '<span style="color:#CBD5E1;">-</span>' ?>
                  </td>

                  <!-- Nilai Latihan 2 -->
                  <td style="text-align: center;">
                    <?= $r['nilai_lat2'] !== null ? '<strong>' . $r['nilai_lat2'] . '</strong>' : '<span style="color:#CBD5E1;">-</span>' ?>
                  </td>

                  <!-- Nilai Latihan 3 -->
                  <td style="text-align: center;">
                    <?= $r['nilai_lat3'] !== null ? '<strong>' . $r['nilai_lat3'] . '</strong>' : '<span style="color:#CBD5E1;">-</span>' ?>
                  </td>

                  <!-- Nilai Evaluasi -->
                  <td style="text-align: center;">
                    <?= $r['nilai_eval'] !== null ? '<strong>' . $r['nilai_eval'] . '</strong>' : '<span style="color:#CBD5E1;">-</span>' ?>
                  </td>

                  <!-- Total Nilai -->
                  <td style="text-align: center; background: #F8FAFC;">
                    <?php if ($r['total'] !== null): ?>
                      <strong style="color: var(--blue-800); font-size: 13.5px;"><?= $r['total'] ?></strong>
                    <?php else: ?>
                      <span style="color: var(--muted); font-size: 11.5px;">-</span>
                    <?php endif; ?>
                  </td>

                  <!-- Rata-Rata -->
                  <td style="text-align: center; background: #F8FAFC;">
                    <?php if ($r['rata_rata'] !== null): ?>
                      <span style="font-weight: 800; font-size: 13.5px; color: <?= $r['rata_rata'] >= 75 ? 'var(--success)' : '#D97706' ?>;">
                        <?= $r['rata_rata'] ?>
                      </span>
                    <?php else: ?>
                      <span style="color: var(--muted); font-size: 11.5px;">Belum Mengerjakan</span>
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

<script>
  // Fungsi Salin Data Tab-Separated (TSV) untuk langsung di-paste ke Google Sheets
  function copyForGoogleSheets() {
    const rows = [
      ["No", "Nama Siswa", "Kelas", "Nilai LKPD", "Nilai Latihan 1", "Nilai Latihan 2", "Nilai Latihan 3", "Nilai Evaluasi", "Total Nilai", "Rata-Rata"]
    ];

    <?php foreach ($matrixData as $m): ?>
      rows.push([
        <?= json_encode($m['no']) ?>,
        <?= json_encode($m['nama']) ?>,
        <?= json_encode($m['kelas']) ?>,
        <?= json_encode($m['nilai_lkpd'] !== null ? $m['nilai_lkpd'] : '-') ?>,
        <?= json_encode($m['nilai_lat1'] !== null ? $m['nilai_lat1'] : '-') ?>,
        <?= json_encode($m['nilai_lat2'] !== null ? $m['nilai_lat2'] : '-') ?>,
        <?= json_encode($m['nilai_lat3'] !== null ? $m['nilai_lat3'] : '-') ?>,
        <?= json_encode($m['nilai_eval'] !== null ? $m['nilai_eval'] : '-') ?>,
        <?= json_encode($m['total'] !== null ? $m['total'] : '-') ?>,
        <?= json_encode($m['rata_rata'] !== null ? $m['rata_rata'] : '-') ?>
      ]);
    <?php endforeach; ?>

    const tsvContent = rows.map(r => r.join("\t")).join("\n");

    navigator.clipboard.writeText(tsvContent).then(() => {
      const btn = document.getElementById('btnCopyGoogleSheets');
      const origHtml = btn.innerHTML;
      btn.innerHTML = '✓ Berhasil Disalin! Tekan Ctrl+V di Google Sheets';
      btn.style.borderColor = 'var(--success)';
      btn.style.color = 'var(--success)';
      setTimeout(() => {
        btn.innerHTML = origHtml;
        btn.style.borderColor = '';
        btn.style.color = '';
      }, 3500);
    }).catch(err => {
      alert('Gagal menyalin data: ' + err);
    });
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
