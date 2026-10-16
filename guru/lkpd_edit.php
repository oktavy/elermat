<?php
/**
 * Panel Edit Lembar LKPD & Butir Soal Guru
 * File: guru/lkpd_edit.php
 * 
 * FITUR:
 * 1. Guru dapat mengedit informasi umum LKPD (Judul, Target Kelas, Urutan, Durasi, KKM, Deskripsi).
 * 2. Guru dapat mengedit butir soal (Pilihan Ganda & Essay), kunci jawaban, opsi, bobot, dan pembahasan.
 * 3. Guru dapat menambah butir soal baru atau menghapus butir soal yang tidak diperlukan.
 * 4. Guru dapat menghapus LKPD secara aman melalui tautan ke guru/lkpd_hapus.php.
 */

require_once __DIR__ . '/../config/app.php';
require_role(['guru', 'admin']);

$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

$lkpdId = !empty($_GET['id']) ? (int)$_GET['id'] : (!empty($_POST['assessment_id']) ? (int)$_POST['assessment_id'] : 0);

if ($lkpdId <= 0) {
    flash_set('danger', 'ID LKPD tidak valid.');
    redirect('guru/lkpd.php');
}

// Ambil data LKPD
$stmtLkpd = $db->prepare("SELECT * FROM assessments WHERE id = ? AND tipe = 'lkpd' LIMIT 1");
$stmtLkpd->execute([$lkpdId]);
$lkpd = $stmtLkpd->fetch(PDO::FETCH_ASSOC);

if (!$lkpd) {
    flash_set('danger', 'Modul LKPD tidak ditemukan.');
    redirect('guru/lkpd.php');
}

// Ambil daftar kelas untuk dropdown target kelas
$classesList = $db->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

// =========================================================================
// 1. PROSES UPDATE INFORMASI UMUM LKPD
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_lkpd_info') {
    $judul    = trim($_POST['judul'] ?? '');
    $kelas    = trim($_POST['kelas'] ?? 'Semua Kelas');
    $urutan   = (int)($_POST['urutan'] ?? 1);
    $durasi   = (int)($_POST['duration_minutes'] ?? 30);
    $kkm      = (int)($_POST['passing_grade'] ?? 0);
    $desk     = trim($_POST['deskripsi'] ?? '');

    if (!empty($judul)) {
        $upd = $db->prepare("
            UPDATE assessments 
            SET judul = ?, kelas = ?, urutan = ?, duration_minutes = ?, passing_grade = ?, deskripsi = ?
            WHERE id = ? AND tipe = 'lkpd'
        ");
        $upd->execute([$judul, $kelas, $urutan, $durasi, $kkm, $desk, $lkpdId]);

        flash_set('success', "Pengaturan Lembar LKPD '{$judul}' berhasil diperbarui!");
        redirect('guru/lkpd_edit.php?id=' . $lkpdId);
    } else {
        flash_set('danger', 'Judul LKPD tidak boleh kosong.');
    }
}

// =========================================================================
// 2. PROSES TAMBAH BUTIR SOAL BARU KE LKPD
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $tipe       = $_POST['tipe'] ?? 'pg';
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $opsiA      = trim($_POST['opsi_a'] ?? '');
    $opsiB      = trim($_POST['opsi_b'] ?? '');
    $opsiC      = trim($_POST['opsi_c'] ?? '');
    $opsiD      = trim($_POST['opsi_d'] ?? '');
    $kunci      = ($tipe === 'essay') ? trim($_POST['kunci_jawaban_essay'] ?? '') : trim($_POST['kunci_jawaban'] ?? '0');
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
        $insQ->execute([$lkpdId, $urutanSoal, $tipe, $pertanyaan, $opsiA, $opsiB, $opsiC, $opsiD, $kunci, $fbBenar, $fbSalah, $bobot]);

        flash_set('success', 'Butir soal baru berhasil ditambahkan ke LKPD!');
        redirect('guru/lkpd_edit.php?id=' . $lkpdId);
    } else {
        flash_set('danger', 'Isi teks pertanyaan soal wajib diisi.');
    }
}

// =========================================================================
// 3. PROSES UPDATE BUTIR SOAL YANG ADA
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_question') {
    $qId        = (int)($_POST['question_id'] ?? 0);
    $tipe       = $_POST['tipe'] ?? 'pg';
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $opsiA      = trim($_POST['opsi_a'] ?? '');
    $opsiB      = trim($_POST['opsi_b'] ?? '');
    $opsiC      = trim($_POST['opsi_c'] ?? '');
    $opsiD      = trim($_POST['opsi_d'] ?? '');
    $kunci      = ($tipe === 'essay') ? trim($_POST['kunci_jawaban_essay'] ?? '') : trim($_POST['kunci_jawaban'] ?? '0');
    $fbBenar    = trim($_POST['feedback_benar'] ?? '');
    $fbSalah    = trim($_POST['feedback_salah'] ?? '');
    $bobot      = (float)($_POST['bobot'] ?? 25.0);
    $urutanSoal = (int)($_POST['urutan_soal'] ?? 1);

    if ($qId > 0 && !empty($pertanyaan)) {
        $updQ = $db->prepare("
            UPDATE questions 
            SET urutan = ?, tipe = ?, pertanyaan = ?, opsi_a = ?, opsi_b = ?, opsi_c = ?, opsi_d = ?, kunci_jawaban = ?, feedback_benar = ?, feedback_salah = ?, bobot = ?
            WHERE id = ? AND assessment_id = ?
        ");
        $updQ->execute([$urutanSoal, $tipe, $pertanyaan, $opsiA, $opsiB, $opsiC, $opsiD, $kunci, $fbBenar, $fbSalah, $bobot, $qId, $lkpdId]);

        flash_set('success', "Soal nomor {$urutanSoal} berhasil diperbarui!");
        redirect('guru/lkpd_edit.php?id=' . $lkpdId . '#soal_' . $qId);
    } else {
        flash_set('danger', 'Isi teks pertanyaan soal wajib diisi.');
    }
}

// =========================================================================
// 4. PROSES HAPUS BUTIR SOAL
// =========================================================================
if (isset($_GET['delete_q'])) {
    $delQId = (int)$_GET['delete_q'];
    if ($delQId > 0) {
        $stmtDelQ = $db->prepare("DELETE FROM questions WHERE id = ? AND assessment_id = ?");
        $stmtDelQ->execute([$delQId, $lkpdId]);
        flash_set('success', 'Butir soal berhasil dihapus dari LKPD.');
    }
    redirect('guru/lkpd_edit.php?id=' . $lkpdId);
}

// Ambil seluruh butir soal LKPD saat ini
$stmtQuestions = $db->prepare("SELECT * FROM questions WHERE assessment_id = ? ORDER BY urutan ASC, id ASC");
$stmtQuestions->execute([$lkpdId]);
$questions = $stmtQuestions->fetchAll();

$pageTitle = 'Edit Lembar LKPD: ' . htmlspecialchars($lkpd['judul']) . ' - ElearnMath Guru';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper" style="max-width: 1040px; margin: 0 auto;">
    
    <!-- Navigasi Atas & Judul Halaman -->
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
      <div>
        <a href="<?= BASE_URL ?>/guru/lkpd.php?assessment_id=<?= $lkpd['id'] ?>" class="btn btn-outline" style="font-size: 12px; padding: 6px 14px; margin-bottom: 8px;">
          &larr; Kembali ke Kelola LKPD
        </a>
        <h1 style="font-size: 22px; font-weight: 800; color: var(--blue-800); margin: 0 0 4px 0;">
          Panel Edit Lembar LKPD: <?= htmlspecialchars($lkpd['judul']) ?>
        </h1>
        <p style="font-size: 13px; color: var(--muted); margin: 0;">
          Ubah konfigurasi modul, perbarui teks pertanyaan, pilihan opsi, kunci jawaban, dan pembahasan butir soal.
        </p>
      </div>

      <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); font-size: 12px; padding: 6px 14px;">
          Urutan Ke-<?= (int)$lkpd['urutan'] ?> &bull; <?= count($questions) ?> Butir Soal
        </span>
        <a href="<?= BASE_URL ?>/guru/lkpd_hapus.php?id=<?= $lkpd['id'] ?>" class="btn btn-outline" style="font-size: 12px; padding: 6px 14px; border-color: var(--danger); color: var(--danger);">
          <i data-feather="trash-2" style="width: 14px; height: 14px;"></i> Hapus LKPD Ini
        </a>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- ============================================================== -->
    <!-- 1. FORM EDIT PENGATURAN UMUM LKPD                              -->
    <!-- ============================================================== -->
    <div class="glass-card" style="margin-bottom: 24px; border-top: 4px solid var(--orange-dark); padding: 24px;">
      <h2 style="font-size: 16px; font-weight: 800; color: var(--blue-800); margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
        <i data-feather="settings" style="width: 18px; height: 18px; color: var(--blue-600);"></i>
        Pengaturan Umum Lembar LKPD
      </h2>

      <form method="POST" action="">
        <input type="hidden" name="action" value="update_lkpd_info">
        <input type="hidden" name="assessment_id" value="<?= $lkpd['id'] ?>">

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 14px; margin-bottom: 14px;">
          <div>
            <label class="form-label" style="font-size: 12px; font-weight: 700;">Judul Modul LKPD:</label>
            <input type="text" name="judul" class="form-input" required value="<?= htmlspecialchars($lkpd['judul']) ?>" placeholder="Misal: LKPD Statistika - Pemusatan Data">
          </div>
          <div>
            <label class="form-label" style="font-size: 12px; font-weight: 700;">Target Kelas:</label>
            <select name="kelas" class="form-input" required>
              <option value="Semua Kelas" <?= $lkpd['kelas'] === 'Semua Kelas' ? 'selected' : '' ?>>Semua Kelas</option>
              <?php foreach ($classesList as $cName): ?>
                <option value="<?= htmlspecialchars($cName) ?>" <?= $lkpd['kelas'] === $cName ? 'selected' : '' ?>>
                  Kelas <?= htmlspecialchars($cName) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label" style="font-size: 12px; font-weight: 700;">Nomor Urut:</label>
            <input type="number" name="urutan" class="form-input" min="1" value="<?= (int)$lkpd['urutan'] ?>">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
          <div>
            <label class="form-label" style="font-size: 12px; font-weight: 700;">Durasi Pengerjaan (Menit):</label>
            <input type="number" name="duration_minutes" class="form-input" min="5" value="<?= (int)$lkpd['duration_minutes'] ?>">
          </div>
          <div>
            <label class="form-label" style="font-size: 12px; font-weight: 700;">KKM / Standar Minimal (0 jika tanpa KKM):</label>
            <input type="number" name="passing_grade" class="form-input" min="0" max="100" value="<?= (int)$lkpd['passing_grade'] ?>">
          </div>
        </div>

        <div style="margin-bottom: 16px;">
          <label class="form-label" style="font-size: 12px; font-weight: 700;">Deskripsi & Petunjuk LKPD:</label>
          <textarea name="deskripsi" rows="2" class="form-input" style="width: 100%;"><?= htmlspecialchars($lkpd['deskripsi']) ?></textarea>
        </div>

        <div style="text-align: right;">
          <button type="submit" class="btn btn-primary" style="padding: 9px 24px; font-size: 13px; font-weight: 700;">
            <i data-feather="save" style="width: 14px; height: 14px;"></i> Simpan Pengaturan LKPD
          </button>
        </div>
      </form>
    </div>

    <!-- ============================================================== -->
    <!-- 2. DAFTAR & EDIT BUTIR SOAL YANG ADA                           -->
    <!-- ============================================================== -->
    <div class="glass-card" style="margin-bottom: 24px; padding: 24px;">
      <h2 style="font-size: 16px; font-weight: 800; color: var(--blue-800); margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
        <i data-feather="list" style="width: 18px; height: 18px; color: var(--blue-600);"></i>
        Daftar Butir Soal LKPD (<?= count($questions) ?> Soal)
      </h2>

      <?php if (empty($questions)): ?>
        <p style="color: var(--muted); font-size: 13px; margin: 0;">
          Belum ada butir soal dalam LKPD ini. Gunakan formulir di bawah untuk menambahkan soal baru.
        </p>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 20px;">
          <?php foreach ($questions as $idx => $q): ?>
            <div id="soal_<?= $q['id'] ?>" style="background: #FAFAFA; border: 1px solid var(--line); border-radius: 12px; padding: 20px;">
              <form method="POST" action="">
                <input type="hidden" name="action" value="update_question">
                <input type="hidden" name="assessment_id" value="<?= $lkpdId ?>">
                <input type="hidden" name="question_id" value="<?= $q['id'] ?>">

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                  <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-weight: 800; font-size: 14px; color: var(--blue-800);">
                      Soal Nomor <?= $idx + 1 ?>
                    </span>
                    <span class="badge-role" style="font-size: 11px; background: <?= $q['tipe'] === 'pg' ? 'var(--blue-100)' : 'var(--orange-100)' ?>; color: <?= $q['tipe'] === 'pg' ? 'var(--blue-800)' : 'var(--orange-dark)' ?>;">
                      <?= strtoupper($q['tipe']) ?>
                    </span>
                  </div>

                  <a href="<?= BASE_URL ?>/guru/lkpd_edit.php?id=<?= $lkpdId ?>&delete_q=<?= $q['id'] ?>" 
                     class="btn btn-outline" 
                     style="font-size: 11.5px; padding: 4px 10px; border-color: var(--danger); color: var(--danger);"
                     onclick="return confirm('Apakah Anda yakin ingin menghapus soal nomor <?= $idx + 1 ?> ini?')">
                    <i data-feather="trash-2" style="width: 12px; height: 12px;"></i> Hapus Soal
                  </a>
                </div>

                <div style="display: grid; grid-template-columns: 140px 140px 1fr; gap: 12px; margin-bottom: 12px;">
                  <div>
                    <label class="form-label" style="font-size: 11.5px;">Tipe Soal:</label>
                    <select name="tipe" class="form-input" style="width: 100%;" onchange="toggleQTypeFields(this, 'optBox_<?= $q['id'] ?>', 'keyBox_<?= $q['id'] ?>')">
                      <option value="pg" <?= $q['tipe'] === 'pg' ? 'selected' : '' ?>>Pilihan Ganda (PG)</option>
                      <option value="essay" <?= $q['tipe'] === 'essay' ? 'selected' : '' ?>>Uraian / Essay</option>
                    </select>
                  </div>
                  <div>
                    <label class="form-label" style="font-size: 11.5px;">Nomor Urut:</label>
                    <input type="number" name="urutan_soal" class="form-input" value="<?= (int)$q['urutan'] ?>">
                  </div>
                  <div>
                    <label class="form-label" style="font-size: 11.5px;">Bobot Skor Soal:</label>
                    <input type="number" step="0.5" name="bobot" class="form-input" value="<?= (float)$q['bobot'] ?>">
                  </div>
                </div>

                <div style="margin-bottom: 12px;">
                  <label class="form-label" style="font-size: 12px; font-weight: 700;">Teks Pertanyaan Soal:</label>
                  <textarea name="pertanyaan" rows="3" class="form-input" required style="width: 100%;"><?= htmlspecialchars($q['pertanyaan']) ?></textarea>
                </div>

                <!-- Bagian Opsi PG -->
                <div id="optBox_<?= $q['id'] ?>" style="display: <?= $q['tipe'] === 'pg' ? 'block' : 'none' ?>; background: #fff; border: 1px solid var(--line); border-radius: 8px; padding: 14px; margin-bottom: 12px;">
                  <div style="font-size: 12px; font-weight: 700; color: var(--blue-800); margin-bottom: 10px;">Pilihan Jawaban (A, B, C, D):</div>
                  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                    <div>
                      <label class="form-label" style="font-size: 11px;">Opsi A:</label>
                      <input type="text" name="opsi_a" class="form-input" value="<?= htmlspecialchars($q['opsi_a'] ?? '') ?>">
                    </div>
                    <div>
                      <label class="form-label" style="font-size: 11px;">Opsi B:</label>
                      <input type="text" name="opsi_b" class="form-input" value="<?= htmlspecialchars($q['opsi_b'] ?? '') ?>">
                    </div>
                    <div>
                      <label class="form-label" style="font-size: 11px;">Opsi C:</label>
                      <input type="text" name="opsi_c" class="form-input" value="<?= htmlspecialchars($q['opsi_c'] ?? '') ?>">
                    </div>
                    <div>
                      <label class="form-label" style="font-size: 11px;">Opsi D:</label>
                      <input type="text" name="opsi_d" class="form-input" value="<?= htmlspecialchars($q['opsi_d'] ?? '') ?>">
                    </div>
                  </div>

                  <div>
                    <label class="form-label" style="font-size: 11.5px; font-weight: 700;">Kunci Jawaban PG yang Benar:</label>
                    <select name="kunci_jawaban" class="form-input" style="width: 200px;">
                      <option value="0" <?= ((string)$q['kunci_jawaban'] === '0' || strtoupper($q['kunci_jawaban']) === 'A') ? 'selected' : '' ?>>Opsi A</option>
                      <option value="1" <?= ((string)$q['kunci_jawaban'] === '1' || strtoupper($q['kunci_jawaban']) === 'B') ? 'selected' : '' ?>>Opsi B</option>
                      <option value="2" <?= ((string)$q['kunci_jawaban'] === '2' || strtoupper($q['kunci_jawaban']) === 'C') ? 'selected' : '' ?>>Opsi C</option>
                      <option value="3" <?= ((string)$q['kunci_jawaban'] === '3' || strtoupper($q['kunci_jawaban']) === 'D') ? 'selected' : '' ?>>Opsi D</option>
                    </select>
                  </div>
                </div>

                <!-- Bagian Kunci Essay -->
                <div id="keyBox_<?= $q['id'] ?>" style="display: <?= $q['tipe'] === 'essay' ? 'block' : 'none' ?>; margin-bottom: 12px;">
                  <label class="form-label" style="font-size: 12px; font-weight: 700;">Referensi Kunci Jawaban Essay / Kata Kunci Penilaian AI:</label>
                  <textarea name="kunci_jawaban_essay" rows="2" class="form-input" style="width: 100%;"><?= htmlspecialchars($q['kunci_jawaban'] ?? '') ?></textarea>
                </div>

                <!-- Feedback & Pembahasan Soal -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                  <div>
                    <label class="form-label" style="font-size: 11.5px; font-weight: 700; color: var(--success);">Feedback / Apresiasi Jawaban Benar:</label>
                    <textarea name="feedback_benar" rows="2" class="form-input" style="width: 100%;"><?= htmlspecialchars($q['feedback_benar'] ?? '') ?></textarea>
                  </div>
                  <div>
                    <label class="form-label" style="font-size: 11.5px; font-weight: 700; color: #B45309;">Kunci & Penjelasan Saat Jawaban Siswa Salah:</label>
                    <textarea name="feedback_salah" rows="2" class="form-input" style="width: 100%;"><?= htmlspecialchars($q['feedback_salah'] ?? '') ?></textarea>
                  </div>
                </div>

                <div style="text-align: right;">
                  <button type="submit" class="btn btn-primary" style="padding: 7px 18px; font-size: 12.5px; font-weight: 700;">
                    <i data-feather="check" style="width: 13px; height: 13px;"></i> Simpan Perubahan Soal Ini
                  </button>
                </div>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ============================================================== -->
    <!-- 3. FORM TAMBAH BUTIR SOAL BARU KE LKPD                         -->
    <!-- ============================================================== -->
    <div class="glass-card" style="border-top: 4px solid var(--blue-600); padding: 24px;">
      <h2 style="font-size: 16px; font-weight: 800; color: var(--blue-800); margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
        <i data-feather="plus-circle" style="width: 18px; height: 18px; color: var(--blue-600);"></i>
        + Tambah Butir Soal Baru ke LKPD Ini
      </h2>

      <form method="POST" action="">
        <input type="hidden" name="action" value="add_question">
        <input type="hidden" name="assessment_id" value="<?= $lkpdId ?>">

        <div style="display: grid; grid-template-columns: 160px 140px 1fr; gap: 14px; margin-bottom: 14px;">
          <div>
            <label class="form-label" style="font-size: 12px; font-weight: 700;">Tipe Soal:</label>
            <select name="tipe" id="newSoalType" class="form-input" style="width: 100%;" onchange="toggleNewQType(this.value)">
              <option value="pg">Pilihan Ganda (PG)</option>
              <option value="essay">Uraian / Essay</option>
            </select>
          </div>
          <div>
            <label class="form-label" style="font-size: 12px; font-weight: 700;">Nomor Urut:</label>
            <input type="number" name="urutan_soal" class="form-input" value="<?= count($questions) + 1 ?>">
          </div>
          <div>
            <label class="form-label" style="font-size: 12px; font-weight: 700;">Bobot Skor Soal:</label>
            <input type="number" step="0.5" name="bobot" class="form-input" value="25">
          </div>
        </div>

        <div style="margin-bottom: 14px;">
          <label class="form-label" style="font-size: 12px; font-weight: 700;">Teks Pertanyaan Soal: <span style="color:red;">*</span></label>
          <textarea name="pertanyaan" rows="3" class="form-input" required placeholder="Tuliskan butir soal atau studi kasus matematika di sini..." style="width: 100%;"></textarea>
        </div>

        <!-- Box Opsi PG Baru -->
        <div id="newPgOptionsBox" style="background: #F8FAFC; border: 1px solid var(--line); border-radius: 10px; padding: 16px; margin-bottom: 16px;">
          <div style="font-size: 13px; font-weight: 700; color: var(--blue-800); margin-bottom: 12px;">Pilihan Jawaban (A, B, C, D):</div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
            <div>
              <label class="form-label" style="font-size: 11.5px;">Opsi A:</label>
              <input type="text" name="opsi_a" class="form-input" placeholder="Jawaban A">
            </div>
            <div>
              <label class="form-label" style="font-size: 11.5px;">Opsi B:</label>
              <input type="text" name="opsi_b" class="form-input" placeholder="Jawaban B">
            </div>
            <div>
              <label class="form-label" style="font-size: 11.5px;">Opsi C:</label>
              <input type="text" name="opsi_c" class="form-input" placeholder="Jawaban C">
            </div>
            <div>
              <label class="form-label" style="font-size: 11.5px;">Opsi D:</label>
              <input type="text" name="opsi_d" class="form-input" placeholder="Jawaban D">
            </div>
          </div>

          <div>
            <label class="form-label" style="font-size: 12px; font-weight: 700;">Kunci Jawaban yang Benar:</label>
            <select name="kunci_jawaban" class="form-input" style="width: 220px;">
              <option value="0">Opsi A</option>
              <option value="1">Opsi B</option>
              <option value="2">Opsi C</option>
              <option value="3">Opsi D</option>
            </select>
          </div>
        </div>

        <!-- Box Essay Baru (Tersembunyi Awalnya) -->
        <div id="newEssayBox" style="display: none; margin-bottom: 16px;">
          <label class="form-label" style="font-size: 12px; font-weight: 700;">Referensi Kunci Jawaban Essay / Kata Kunci Penilaian AI:</label>
          <textarea name="kunci_jawaban_essay" rows="2" class="form-input" style="width: 100%;" placeholder="Contoh: Kunci konsep: Mean = jumlah/n, modus = nilai frekuensi tertinggi..."></textarea>
        </div>

        <!-- Feedback & Pembahasan -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
          <div>
            <label class="form-label" style="font-size: 12px; font-weight: 700; color: var(--success);">Feedback / Apresiasi Jawaban Benar:</label>
            <textarea name="feedback_benar" rows="2" class="form-input" style="width: 100%;" placeholder="Tepat sekali! Pemahaman konsep Anda sangat akurat."></textarea>
          </div>
          <div>
            <label class="form-label" style="font-size: 12px; font-weight: 700; color: #B45309;">Kunci & Penjelasan Saat Jawaban Siswa Salah:</label>
            <textarea name="feedback_salah" rows="2" class="form-input" style="width: 100%;" placeholder="Jawaban kurang tepat. Perhatikan langkah perhitungan..."></textarea>
          </div>
        </div>

        <div style="text-align: right;">
          <button type="submit" class="btn btn-success" style="padding: 10px 28px; font-size: 13.5px; font-weight: 800;">
            <i data-feather="plus" style="width: 14px; height: 14px;"></i> Tambahkan Soal ke LKPD
          </button>
        </div>
      </form>
    </div>

  </div>
</div>

<script>
  function toggleQTypeFields(selectElem, optionsBoxId, keyBoxId) {
    const isPg = selectElem.value === 'pg';
    const optBox = document.getElementById(optionsBoxId);
    const keyBox = document.getElementById(keyBoxId);
    if (optBox) {
      optBox.style.display = isPg ? 'block' : 'none';
    }
    if (keyBox) {
      keyBox.style.display = isPg ? 'none' : 'block';
    }
  }

  function toggleNewQType(val) {
    const pgBox = document.getElementById('newPgOptionsBox');
    const essayBox = document.getElementById('newEssayBox');
    if (val === 'pg') {
      if (pgBox) pgBox.style.display = 'block';
      if (essayBox) essayBox.style.display = 'none';
    } else {
      if (pgBox) pgBox.style.display = 'none';
      if (essayBox) essayBox.style.display = 'block';
    }
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
