<?php
/**
 * Edit Butir Soal Latihan & Evaluasi
 * File: guru/edit_question.php
 * FITUR GURU: Edit Latihan & Evaluasi (Form edit soal latihan dan evaluasi)
 */
require_once __DIR__ . '/../config/app.php';
require_role(['guru', 'admin']);

$db = getDB();
$questionId = (int)($_GET['id'] ?? 0);

if (!$questionId) {
    flash_set('danger', 'ID Soal tidak valid.');
    redirect('guru/latihan.php');
}

$stmtQ = $db->prepare("
    SELECT q.*, a.tipe AS tipe_assessment, a.judul AS judul_assessment 
    FROM questions q
    JOIN assessments a ON q.assessment_id = a.id
    WHERE q.id = ?
");
$stmtQ->execute([$questionId]);
$question = $stmtQ->fetch();

if (!$question) {
    flash_set('danger', 'Soal tidak ditemukan.');
    redirect('guru/latihan.php');
}

$returnPage = ($question['tipe_assessment'] === 'evaluasi') 
    ? ('guru/evaluasi.php?assessment_id=' . $question['assessment_id']) 
    : ('guru/latihan.php?assessment_id=' . $question['assessment_id']);

// PROSES SIMPAN EDIT SOAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_question') {
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $tipe = $_POST['tipe'] ?? 'pg';
    $opsiA = trim($_POST['opsi_a'] ?? '');
    $opsiB = trim($_POST['opsi_b'] ?? '');
    $opsiC = trim($_POST['opsi_c'] ?? '');
    $opsiD = trim($_POST['opsi_d'] ?? '');
    $kunci = trim($_POST['kunci_jawaban'] ?? '0');
    $fbBenar = trim($_POST['feedback_benar'] ?? '');
    $fbSalah = trim($_POST['feedback_salah'] ?? '');
    $bobot = (float)($_POST['bobot'] ?? 1.0);
    $urutan = (int)($_POST['urutan'] ?? 1);

    if (!empty($pertanyaan)) {
        $upd = $db->prepare("
            UPDATE questions 
            SET tipe = ?, pertanyaan = ?, opsi_a = ?, opsi_b = ?, opsi_c = ?, opsi_d = ?,
                kunci_jawaban = ?, feedback_benar = ?, feedback_salah = ?, bobot = ?, urutan = ?
            WHERE id = ?
        ");
        $upd->execute([$tipe, $pertanyaan, $opsiA, $opsiB, $opsiC, $opsiD, $kunci, $fbBenar, $fbSalah, $bobot, $urutan, $questionId]);

        flash_set('success', 'Butir soal berhasil diperbarui!');
        redirect($returnPage);
    }
}

$pageTitle = 'Edit Soal ' . ucfirst($question['tipe_assessment']) . ' - ElearnMath';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper" style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
      <a href="<?= BASE_URL . '/' . $returnPage ?>" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;">
        &larr; Kembali ke Kelola <?= ucfirst($question['tipe_assessment']) ?>
      </a>
    </div>

    <div class="glass-card">
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 18px;">
        <div>
          <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 4px;">
            Modul: <?= htmlspecialchars($question['judul_assessment']) ?>
          </span>
          <h1 style="font-size: 20px; font-weight: 800; color: var(--blue-800); margin: 0;">
            Edit Butir Soal (Nomor <?= $question['urutan'] ?>)
          </h1>
        </div>
      </div>

      <form method="POST" action="">
        <input type="hidden" name="action" value="update_question">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
          <div>
            <label class="form-label">Tipe Soal</label>
            <select name="tipe" id="tipeSelect" class="form-input" onchange="toggleOptionFields(this.value)">
              <option value="pg" <?= $question['tipe'] === 'pg' ? 'selected' : '' ?>>Pilihan Ganda (PG)</option>
              <option value="essay" <?= $question['tipe'] === 'essay' ? 'selected' : '' ?>>Uraian / Essay</option>
            </select>
          </div>

          <div>
            <label class="form-label">Nomor Urut Soal</label>
            <input type="number" name="urutan" class="form-input" value="<?= $question['urutan'] ?>" required>
          </div>
        </div>

        <div style="margin-bottom: 14px;">
          <label class="form-label">Teks Pertanyaan Soal</label>
          <textarea name="pertanyaan" rows="4" class="form-input" required><?= htmlspecialchars($question['pertanyaan']) ?></textarea>
        </div>

        <!-- Kolom Pilihan Ganda -->
        <div id="pgSection" style="<?= $question['tipe'] === 'essay' ? 'display: none;' : '' ?>">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
            <div>
              <label class="form-label">Opsi A</label>
              <input type="text" name="opsi_a" class="form-input" value="<?= htmlspecialchars($question['opsi_a'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">Opsi B</label>
              <input type="text" name="opsi_b" class="form-input" value="<?= htmlspecialchars($question['opsi_b'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">Opsi C</label>
              <input type="text" name="opsi_c" class="form-input" value="<?= htmlspecialchars($question['opsi_c'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">Opsi D</label>
              <input type="text" name="opsi_d" class="form-input" value="<?= htmlspecialchars($question['opsi_d'] ?? '') ?>">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
            <div>
              <label class="form-label">Kunci Jawaban Benar</label>
              <select name="kunci_jawaban" class="form-input">
                <option value="0" <?= $question['kunci_jawaban'] === '0' ? 'selected' : '' ?>>Opsi A</option>
                <option value="1" <?= $question['kunci_jawaban'] === '1' ? 'selected' : '' ?>>Opsi B</option>
                <option value="2" <?= $question['kunci_jawaban'] === '2' ? 'selected' : '' ?>>Opsi C</option>
                <option value="3" <?= $question['kunci_jawaban'] === '3' ? 'selected' : '' ?>>Opsi D</option>
              </select>
            </div>
            <div>
              <label class="form-label">Bobot Nilai</label>
              <input type="number" step="0.1" name="bobot" class="form-input" value="<?= $question['bobot'] ?>">
            </div>
          </div>

          <div style="margin-bottom: 14px;">
            <label class="form-label">Umpan Balik Jika Siswa Menjawab Benar</label>
            <input type="text" name="feedback_benar" class="form-input" value="<?= htmlspecialchars($question['feedback_benar'] ?? '') ?>" placeholder="Tepat sekali!">
          </div>

          <div style="margin-bottom: 18px;">
            <label class="form-label">Umpan Balik Jika Siswa Menjawab Kurang Tepat</label>
            <input type="text" name="feedback_salah" class="form-input" value="<?= htmlspecialchars($question['feedback_salah'] ?? '') ?>" placeholder="Jawaban yang tepat adalah...">
          </div>
        </div>

        <div style="text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
          <a href="<?= BASE_URL . '/' . $returnPage ?>" class="btn btn-outline">Batal</a>
          <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
            <i data-feather="save"></i> Simpan Perubahan Soal
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleOptionFields(val) {
  document.getElementById('pgSection').style.display = (val === 'pg') ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
