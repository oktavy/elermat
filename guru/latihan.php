<?php
/**
 * Manajemen Modul Latihan & Kelola Butir Soal Guru
 * File: guru/latihan.php
 * FITUR:
 * 1. Guru dapat Menambah, Mengedit, dan Menghapus Latihan (Latihan 1, Latihan 2, Latihan 3, dst.).
 * 2. Seluruh latihan otomatis terhubung dan tampil di halaman Siswa sesuai kelasnya.
 * 3. Kelola butir soal (Tambah, Edit, Hapus PG/Essay) dan batas waktu timer.
 * 4. PENGHAPUSAN KKM: Aturan batas KKM 75 dihilangkan.
 */
require_once __DIR__ . '/../config/app.php';
require_role(['guru', 'admin']);

$pageTitle = 'Kelola Latihan Soal - ElearnMath Guru';
$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// Ambil daftar kelas untuk dropdown target kelas
$classes = $db->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

// 1. TAMBAH LATIHAN BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_assessment') {
    $judul   = trim($_POST['judul'] ?? '');
    $urutan  = (int)($_POST['urutan'] ?? 1);
    $kelas   = trim($_POST['kelas'] ?? 'Semua Kelas');
    $durasi  = (int)($_POST['duration_minutes'] ?? 30);
    $desk    = trim($_POST['deskripsi'] ?? '');

    if (!empty($judul)) {
        $ins = $db->prepare("
            INSERT INTO assessments (tipe, judul, urutan, kelas, duration_minutes, deskripsi, is_active, created_by, created_at)
            VALUES ('latihan', ?, ?, ?, ?, ?, 1, ?, NOW())
        ");
        $ins->execute([$judul, $urutan, $kelas, $durasi, $desk, $currentUser['id']]);
        $newAssId = $db->lastInsertId();

        flash_set('success', "Modul latihan '{$judul}' berhasil dibuat! Silakan tambahkan butir soal di bawah.");
        redirect('guru/latihan.php?assessment_id=' . $newAssId);
    }
}

// 2. EDIT MODUL LATIHAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_assessment') {
    $assId   = (int)$_POST['assessment_id'];
    $judul   = trim($_POST['judul'] ?? '');
    $urutan  = (int)($_POST['urutan'] ?? 1);
    $kelas   = trim($_POST['kelas'] ?? 'Semua Kelas');
    $durasi  = (int)($_POST['duration_minutes'] ?? 30);
    $desk    = trim($_POST['deskripsi'] ?? '');

    if (!empty($judul)) {
        $upd = $db->prepare("
            UPDATE assessments 
            SET judul = ?, urutan = ?, kelas = ?, duration_minutes = ?, deskripsi = ?
            WHERE id = ? AND tipe = 'latihan'
        ");
        $upd->execute([$judul, $urutan, $kelas, $durasi, $desk, $assId]);

        flash_set('success', "Pengaturan modul latihan berhasil diperbarui!");
        redirect('guru/latihan.php?assessment_id=' . $assId);
    }
}

// 3. HAPUS MODUL LATIHAN (DENGAN MEKANISME AMAN)
if (isset($_GET['delete_assessment'])) {
    $delAssId = (int)$_GET['delete_assessment'];

    // Cek apakah ada riwayat pengerjaan siswa
    $stmtCek = $db->prepare("SELECT COUNT(*) FROM submissions WHERE assessment_id = ?");
    $stmtCek->execute([$delAssId]);
    $subCount = (int)$stmtCek->fetchColumn();

    if ($subCount > 0) {
        // Safe delete: nonaktifkan modul agar riwayat dan nilai siswa tidak hilang/rusak
        $upd = $db->prepare("UPDATE assessments SET is_active = 0 WHERE id = ? AND tipe = 'latihan'");
        $upd->execute([$delAssId]);
        flash_set('success', "Modul latihan berhasil dinonaktifkan/dihapus dari daftar aktif. {$subCount} riwayat nilai siswa tetap tersimpan aman di database.");
    } else {
        // Hard delete jika belum pernah dikerjakan siswa
        $stmtDelQ = $db->prepare("DELETE FROM questions WHERE assessment_id = ?");
        $stmtDelQ->execute([$delAssId]);
        $stmtDel = $db->prepare("DELETE FROM assessments WHERE id = ? AND tipe = 'latihan'");
        $stmtDel->execute([$delAssId]);
        flash_set('success', 'Modul latihan beserta seluruh butir soalnya berhasil dihapus permanen.');
    }
    redirect('guru/latihan.php');
}

// 4. TAMBAH BUTIR SOAL KE LATIHAN TERPILIH
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $assId      = (int)$_POST['assessment_id'];
    $tipe       = $_POST['tipe'] ?? 'pg';
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $opsiA      = trim($_POST['opsi_a'] ?? '');
    $opsiB      = trim($_POST['opsi_b'] ?? '');
    $opsiC      = trim($_POST['opsi_c'] ?? '');
    $opsiD      = trim($_POST['opsi_d'] ?? '');
    $kunci      = trim($_POST['kunci_jawaban'] ?? '0');
    $fbBenar    = trim($_POST['feedback_benar'] ?? '');
    $fbSalah    = trim($_POST['feedback_salah'] ?? '');
    $bobot      = (float)($_POST['bobot'] ?? 25.0);
    $urutanSoal = (int)($_POST['urutan_soal'] ?? 1);

    if (!empty($pertanyaan)) {
        $insQ = $db->prepare("
            INSERT INTO questions 
            (assessment_id, urutan, tipe, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, kunci_jawaban, feedback_benar, feedback_salah, bobot)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insQ->execute([$assId, $urutanSoal, $tipe, $pertanyaan, $opsiA, $opsiB, $opsiC, $opsiD, $kunci, $fbBenar, $fbSalah, $bobot]);

        flash_set('success', 'Butir soal baru berhasil ditambahkan!');
        redirect('guru/latihan.php?assessment_id=' . $assId);
    }
}

// 5. HAPUS BUTIR SOAL
if (isset($_GET['delete_question']) && isset($_GET['assessment_id'])) {
    $qId = (int)$_GET['delete_question'];
    $assId = (int)$_GET['assessment_id'];

    $delQ = $db->prepare("DELETE FROM questions WHERE id = ?");
    $delQ->execute([$qId]);

    flash_set('success', 'Butir soal berhasil dihapus.');
    redirect('guru/latihan.php?assessment_id=' . $assId);
}

// Ambil Seluruh Daftar Latihan
$stmtAllLatihan = $db->query("
    SELECT a.*, COUNT(q.id) AS total_soal 
    FROM assessments a 
    LEFT JOIN questions q ON a.id = q.assessment_id 
    WHERE a.tipe = 'latihan' 
    GROUP BY a.id 
    ORDER BY a.urutan ASC, a.id ASC
");
$allLatihan = $stmtAllLatihan->fetchAll();

// Tentukan Latihan Terpilih (Active Assessment)
$activeAssId = !empty($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : ($allLatihan[0]['id'] ?? 0);
$activeAssessment = null;
$questions = [];

if ($activeAssId > 0) {
    foreach ($allLatihan as $lat) {
        if ($lat['id'] == $activeAssId) {
            $activeAssessment = $lat;
            break;
        }
    }
    if ($activeAssessment) {
        $stmtQ = $db->prepare("SELECT * FROM questions WHERE assessment_id = ? ORDER BY urutan ASC, id ASC");
        $stmtQ->execute([$activeAssId]);
        $questions = $stmtQ->fetchAll();
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
      <div>
        <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 6px;">
          FITUR GURU: Manajemen Latihan Soal
        </span>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
          Kelola Modul Latihan & Butir Soal (Latihan 1, 2, 3, dst.)
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Buat dan atur latihan bertingkat, kelola bank soal interaktif, dan tentukan alokasi batas waktu timer pengerjaan otomatis siswa.
        </p>
      </div>

      <button type="button" class="btn btn-primary" onclick="document.getElementById('modalTambahLatihan').style.display='flex'">
        <i data-feather="plus-circle" style="width: 16px; height: 16px;"></i> Buat Modul Latihan Baru
      </button>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- Navigasi Modul Latihan (Latihan 1, Latihan 2, Latihan 3, ...) -->
    <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 20px;">
      <?php if (empty($allLatihan)): ?>
        <div style="color: var(--muted); font-size: 13px;">Belum ada latihan yang dibuat. Klik tombol di atas untuk membuat latihan baru.</div>
      <?php else: ?>
        <?php foreach ($allLatihan as $lat): ?>
          <?php $isActive = ($lat['id'] == $activeAssId); ?>
          <a href="<?= BASE_URL ?>/guru/latihan.php?assessment_id=<?= $lat['id'] ?>" 
             class="btn <?= $isActive ? 'btn-primary' : 'btn-outline' ?>" 
             style="white-space: nowrap; font-size: 13px; padding: 10px 18px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 8px;">
            <span>Latihan <?= $lat['urutan'] ?>: <?= htmlspecialchars($lat['judul']) ?></span>
            <span style="font-size: 11px; background: <?= $isActive ? '#fff' : 'var(--blue-100)' ?>; color: <?= $isActive ? 'var(--blue-800)' : 'var(--blue-800)' ?>; padding: 2px 7px; border-radius: 999px; font-weight: 700;">
              <?= $lat['total_soal'] ?> Soal
            </span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if ($activeAssessment): ?>
      <!-- Panel Pengaturan Modul Terpilih -->
      <div class="glass-card" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px; border-bottom: 1px solid var(--line); padding-bottom: 16px; margin-bottom: 16px;">
          <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
              <span class="badge-role" style="font-size: 11px; background: #EFF6FF; color: var(--blue-800);">
                Latihan Ke-<?= $activeAssessment['urutan'] ?>
              </span>
              <span class="badge-role" style="font-size: 11px; background: var(--orange-100); color: var(--orange-dark);">
                <?= htmlspecialchars($activeAssessment['kelas'] ?: 'Semua Kelas') ?>
              </span>
              <span class="badge-role" style="font-size: 11px; background: var(--success-bg); color: var(--success);">
                ⏱️ Durasi: <?= $activeAssessment['duration_minutes'] ?> Menit
              </span>
            </div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
              <?= htmlspecialchars($activeAssessment['judul']) ?>
            </h2>
            <p style="font-size: 13px; color: var(--muted); margin: 0;">
              <?= htmlspecialchars($activeAssessment['deskripsi'] ?: 'Tidak ada deskripsi.') ?>
            </p>
          </div>

          <div style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;" onclick="document.getElementById('modalEditLatihan').style.display='flex'">
              <i data-feather="edit-2" style="width: 14px; height: 14px;"></i> Edit Pengaturan Modul
            </button>
            <a href="<?= BASE_URL ?>/guru/latihan.php?delete_assessment=<?= $activeAssessment['id'] ?>" 
               class="btn btn-danger" style="font-size: 12px; padding: 6px 12px;" 
               onclick="return confirm('Apakah Anda yakin ingin menghapus Latihan ini?');">
              <i data-feather="trash-2" style="width: 14px; height: 14px;"></i> Hapus Modul
            </a>
          </div>
        </div>

        <!-- Tambah Butir Soal Baru -->
        <details style="background: #F8FAFC; border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 20px;">
          <summary style="font-size: 14px; font-weight: 700; color: var(--blue-800); cursor: pointer;">
            + Tambah Butir Soal Baru ke Latihan Ini
          </summary>
          <div style="margin-top: 14px;">
            <form method="POST" action="">
              <input type="hidden" name="action" value="add_question">
              <input type="hidden" name="assessment_id" value="<?= $activeAssessment['id'] ?>">

              <div style="display: grid; grid-template-columns: 140px 140px 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                  <label class="form-label">Tipe Soal</label>
                  <select name="tipe" id="tipeSoalSelect" class="form-input" onchange="toggleOpsiPG(this.value)">
                    <option value="pg">Pilihan Ganda (PG)</option>
                    <option value="essay">Uraian / Essay</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Nomor Urutan</label>
                  <input type="number" name="urutan_soal" value="<?= count($questions) + 1 ?>" class="form-input" min="1" required>
                </div>
                <div>
                  <label class="form-label">Bobot Nilai</label>
                  <input type="number" step="0.5" name="bobot" value="25.0" class="form-input" required>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Pertanyaan Soal</label>
                <textarea name="pertanyaan" rows="3" class="form-input" placeholder="Tuliskan soal latihan matematika di sini..." required></textarea>
              </div>

              <div id="opsiPGContainer">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                  <div>
                    <label class="form-label">Pilihan A</label>
                    <input type="text" name="opsi_a" class="form-input" placeholder="Pilihan A">
                  </div>
                  <div>
                    <label class="form-label">Pilihan B</label>
                    <input type="text" name="opsi_b" class="form-input" placeholder="Pilihan B">
                  </div>
                  <div>
                    <label class="form-label">Pilihan C</label>
                    <input type="text" name="opsi_c" class="form-input" placeholder="Pilihan C">
                  </div>
                  <div>
                    <label class="form-label">Pilihan D</label>
                    <input type="text" name="opsi_d" class="form-input" placeholder="Pilihan D">
                  </div>
                </div>

                <div style="display: grid; grid-template-columns: 200px 1fr; gap: 14px; margin-bottom: 12px;">
                  <div>
                    <label class="form-label">Kunci Jawaban Benar</label>
                    <select name="kunci_jawaban" class="form-input">
                      <option value="0">Opsi A</option>
                      <option value="1">Opsi B</option>
                      <option value="2">Opsi C</option>
                      <option value="3">Opsi D</option>
                    </select>
                  </div>
                  <div>
                    <label class="form-label">Umpan Balik Jika Benar</label>
                    <input type="text" name="feedback_benar" class="form-input" placeholder="Bagus! Langkah jawaban tepat.">
                  </div>
                </div>
              </div>

              <div style="text-align: right;">
                <button type="submit" class="btn btn-success" style="font-size: 13px; padding: 8px 18px;">
                  <i data-feather="check-circle"></i> Simpan Butir Soal
                </button>
              </div>
            </form>
          </div>
        </details>

        <!-- Daftar Butir Soal -->
        <h3 style="font-size: 16px; font-weight: 700; color: var(--blue-800); margin: 0 0 14px 0;">
          Daftar Butir Soal (<?= count($questions) ?> Soal)
        </h3>

        <?php if (empty($questions)): ?>
          <div style="text-align: center; padding: 24px; color: var(--muted); border: 1px dashed var(--line); border-radius: var(--radius-sm);">
            Belum ada soal pada latihan ini. Buka panel "+ Tambah Butir Soal Baru" di atas untuk menambahkan soal.
          </div>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($questions as $idx => $q): ?>
              <div style="background: #fff; border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-weight: 800; color: var(--blue-800); font-size: 14px;">
                      #<?= $q['urutan'] ?: ($idx + 1) ?>
                    </span>
                    <span class="badge-role" style="font-size: 10.5px; background: <?= $q['tipe'] === 'pg' ? '#EFF6FF' : '#FEF3C7' ?>; color: <?= $q['tipe'] === 'pg' ? 'var(--blue-800)' : '#B45309' ?>;">
                      <?= strtoupper($q['tipe']) ?>
                    </span>
                    <span style="font-size: 11px; color: var(--muted);">Bobot: <?= $q['bobot'] ?></span>
                  </div>

                  <div style="display: flex; gap: 6px;">
                    <a href="<?= BASE_URL ?>/guru/edit_question.php?id=<?= $q['id'] ?>" class="btn btn-outline" style="padding: 4px 8px; font-size: 11px;">
                      <i data-feather="edit-2" style="width: 12px; height: 12px;"></i> Edit
                    </a>
                    <a href="<?= BASE_URL ?>/guru/latihan.php?assessment_id=<?= $activeAssessment['id'] ?>&delete_question=<?= $q['id'] ?>" 
                       class="btn btn-outline" style="padding: 4px 8px; font-size: 11px; color: var(--danger); border-color: var(--danger);"
                       onclick="return confirm('Hapus butir soal ini?');">
                      <i data-feather="trash-2" style="width: 12px; height: 12px;"></i>
                    </a>
                  </div>
                </div>

                <div style="font-size: 13.5px; color: var(--ink); margin-bottom: 8px;">
                  <?= nl2br(htmlspecialchars($q['pertanyaan'])) ?>
                </div>

                <?php if ($q['tipe'] === 'pg'): ?>
                  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; font-size: 12.5px; background: #F8FAFC; padding: 10px; border-radius: 6px;">
                    <div style="<?= $q['kunci_jawaban'] === '0' ? 'font-weight:700; color:var(--success);' : '' ?>">A. <?= htmlspecialchars($q['opsi_a'] ?: '-') ?></div>
                    <div style="<?= $q['kunci_jawaban'] === '1' ? 'font-weight:700; color:var(--success);' : '' ?>">B. <?= htmlspecialchars($q['opsi_b'] ?: '-') ?></div>
                    <div style="<?= $q['kunci_jawaban'] === '2' ? 'font-weight:700; color:var(--success);' : '' ?>">C. <?= htmlspecialchars($q['opsi_c'] ?: '-') ?></div>
                    <div style="<?= $q['kunci_jawaban'] === '3' ? 'font-weight:700; color:var(--success);' : '' ?>">D. <?= htmlspecialchars($q['opsi_d'] ?: '-') ?></div>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- Modal Tambah Latihan Baru -->
<div id="modalTambahLatihan" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center; padding: 20px;">
  <div class="glass-card" style="background: #fff; width: 100%; max-width: 500px; padding: 28px; border-radius: var(--radius);">
    <h3 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0 0 16px 0;">
      Buat Modul Latihan Baru
    </h3>
    <form method="POST" action="">
      <input type="hidden" name="action" value="create_assessment">

      <div class="form-group">
        <label class="form-label">Judul Latihan</label>
        <input type="text" name="judul" class="form-input" placeholder="Contoh: Latihan 3: Statistika Lanjutan" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
        <div>
          <label class="form-label">Nomor Urutan Latihan</label>
          <input type="number" name="urutan" value="<?= count($allLatihan) + 1 ?>" class="form-input" min="1" required>
        </div>
        <div>
          <label class="form-label">Batas Waktu (Menit)</label>
          <input type="number" name="duration_minutes" value="45" class="form-input" min="5" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Target Kelas Siswa</label>
        <select name="kelas" class="form-input">
          <option value="Semua Kelas">Semua Kelas</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>">Kelas <?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Deskripsi & Petunjuk Pengerjaan</label>
        <textarea name="deskripsi" rows="3" class="form-input" placeholder="Tuliskan petunjuk pengerjaan soal..."></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('modalTambahLatihan').style.display='none'">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan & Lanjutkan</button>
      </div>
    </form>
  </div>
</div>

<?php if ($activeAssessment): ?>
  <!-- Modal Edit Latihan -->
  <div id="modalEditLatihan" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center; padding: 20px;">
    <div class="glass-card" style="background: #fff; width: 100%; max-width: 500px; padding: 28px; border-radius: var(--radius);">
      <h3 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0 0 16px 0;">
        Edit Pengaturan Modul Latihan
      </h3>
      <form method="POST" action="">
        <input type="hidden" name="action" value="update_assessment">
        <input type="hidden" name="assessment_id" value="<?= $activeAssessment['id'] ?>">

        <div class="form-group">
          <label class="form-label">Judul Latihan</label>
          <input type="text" name="judul" class="form-input" value="<?= htmlspecialchars($activeAssessment['judul']) ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
          <div>
            <label class="form-label">Nomor Urutan Latihan</label>
            <input type="number" name="urutan" value="<?= $activeAssessment['urutan'] ?>" class="form-input" min="1" required>
          </div>
          <div>
            <label class="form-label">Batas Waktu (Menit)</label>
            <input type="number" name="duration_minutes" value="<?= $activeAssessment['duration_minutes'] ?>" class="form-input" min="5" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Target Kelas Siswa</label>
          <select name="kelas" class="form-input">
            <option value="Semua Kelas" <?= $activeAssessment['kelas'] === 'Semua Kelas' ? 'selected' : '' ?>>Semua Kelas</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= $activeAssessment['kelas'] === $c ? 'selected' : '' ?>>Kelas <?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Deskripsi & Petunjuk Pengerjaan</label>
          <textarea name="deskripsi" rows="3" class="form-input"><?= htmlspecialchars($activeAssessment['deskripsi']) ?></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
          <button type="button" class="btn btn-outline" onclick="document.getElementById('modalEditLatihan').style.display='none'">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<script>
  function toggleOpsiPG(val) {
    const container = document.getElementById('opsiPGContainer');
    if (val === 'pg') {
      container.style.display = 'block';
    } else {
      container.style.display = 'none';
    }
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
