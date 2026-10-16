<?php
/**
 * Kelola Modul Evaluasi & Butir Soal Guru
 * File: guru/evaluasi.php
 * FITUR:
 * 1. Guru dapat Menambah, Mengedit, dan Menghapus Evaluasi serta butir soalnya.
 * 2. Seluruh evaluasi otomatis terhubung dan tampil di halaman Siswa sesuai kelasnya.
 * 3. Alokasi batas waktu timer countdown.
 * 4. PENGHAPUSAN KKM: Aturan KKM 75 dihilangkan.
 */
require_once __DIR__ . '/../config/app.php';
require_role(['guru', 'admin']);

$pageTitle = 'Kelola Evaluasi Akhir - ElearnMath Guru';
$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// Ambil daftar kelas
$classes = $db->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

// 1. TAMBAH MODUL EVALUASI BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_evaluasi') {
    $judul   = trim($_POST['judul'] ?? '');
    $urutan  = (int)($_POST['urutan'] ?? 1);
    $kelas   = trim($_POST['kelas'] ?? 'Semua Kelas');
    $durasi  = (int)($_POST['duration_minutes'] ?? 60);
    $desk    = trim($_POST['deskripsi'] ?? '');

    if (!empty($judul)) {
        $ins = $db->prepare("
            INSERT INTO assessments (tipe, judul, urutan, kelas, duration_minutes, deskripsi, is_active, created_by, created_at)
            VALUES ('evaluasi', ?, ?, ?, ?, ?, 1, ?, NOW())
        ");
        $ins->execute([$judul, $urutan, $kelas, $durasi, $desk, $currentUser['id']]);
        $newAssId = $db->lastInsertId();

        flash_set('success', "Modul evaluasi '{$judul}' berhasil dibuat! Silakan tambahkan butir soal di bawah.");
        redirect('guru/evaluasi.php?assessment_id=' . $newAssId);
    }
}

// 2. EDIT MODUL EVALUASI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_evaluasi') {
    $assId   = (int)$_POST['assessment_id'];
    $judul   = trim($_POST['judul'] ?? '');
    $urutan  = (int)($_POST['urutan'] ?? 1);
    $kelas   = trim($_POST['kelas'] ?? 'Semua Kelas');
    $durasi  = (int)($_POST['duration_minutes'] ?? 60);
    $desk    = trim($_POST['deskripsi'] ?? '');

    if (!empty($judul)) {
        $upd = $db->prepare("
            UPDATE assessments 
            SET judul = ?, urutan = ?, kelas = ?, duration_minutes = ?, deskripsi = ?
            WHERE id = ? AND tipe = 'evaluasi'
        ");
        $upd->execute([$judul, $urutan, $kelas, $durasi, $desk, $assId]);

        flash_set('success', "Pengaturan modul evaluasi berhasil diperbarui!");
        redirect('guru/evaluasi.php?assessment_id=' . $assId);
    }
}

// 3. HAPUS MODUL EVALUASI
if (isset($_GET['delete_evaluasi'])) {
    $delAssId = (int)$_GET['delete_evaluasi'];
    $stmtDel = $db->prepare("DELETE FROM assessments WHERE id = ? AND tipe = 'evaluasi'");
    $stmtDel->execute([$delAssId]);

    flash_set('success', 'Modul evaluasi beserta seluruh butir soalnya berhasil dihapus.');
    redirect('guru/evaluasi.php');
}

// 4. TAMBAH BUTIR SOAL KE EVALUASI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $assId      = (int)$_POST['assessment_id'];
    $tipe       = $_POST['tipe'] ?? 'essay';
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

        flash_set('success', 'Butir soal evaluasi berhasil ditambahkan!');
        redirect('guru/evaluasi.php?assessment_id=' . $assId);
    }
}

// 5. HAPUS BUTIR SOAL
if (isset($_GET['delete_question']) && isset($_GET['assessment_id'])) {
    $qId = (int)$_GET['delete_question'];
    $assId = (int)$_GET['assessment_id'];

    $delQ = $db->prepare("DELETE FROM questions WHERE id = ?");
    $delQ->execute([$qId]);

    flash_set('success', 'Butir soal evaluasi berhasil dihapus.');
    redirect('guru/evaluasi.php?assessment_id=' . $assId);
}

// Ambil Seluruh Daftar Evaluasi
$stmtAllEval = $db->query("
    SELECT a.*, COUNT(q.id) AS total_soal 
    FROM assessments a 
    LEFT JOIN questions q ON a.id = q.assessment_id 
    WHERE a.tipe = 'evaluasi' 
    GROUP BY a.id 
    ORDER BY a.urutan ASC, a.id ASC
");
$allEval = $stmtAllEval->fetchAll();

// Tentukan Evaluasi Terpilih
$activeAssId = !empty($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : ($allEval[0]['id'] ?? 0);
$activeAssessment = null;
$questions = [];

if ($activeAssId > 0) {
    foreach ($allEval as $ev) {
        if ($ev['id'] == $activeAssId) {
            $activeAssessment = $ev;
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
        <span class="badge-role" style="background: var(--blue-800); color: #fff; margin-bottom: 6px;">
          FITUR GURU: Manajemen Evaluasi Akhir
        </span>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
          Kelola Evaluasi Bab & Bank Soal Ujian
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Buat dan kelola modul evaluasi bab matematika, atur butir soal esai atau pilihan ganda, dan atur batas waktu pengerjaan otomatis.
        </p>
      </div>

      <button type="button" class="btn btn-primary" onclick="document.getElementById('modalTambahEval').style.display='flex'">
        <i data-feather="plus-circle" style="width: 16px; height: 16px;"></i> Buat Modul Evaluasi Baru
      </button>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- Navigasi Modul Evaluasi -->
    <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 20px;">
      <?php if (empty($allEval)): ?>
        <div style="color: var(--muted); font-size: 13px;">Belum ada modul evaluasi. Klik tombol di atas untuk membuat evaluasi baru.</div>
      <?php else: ?>
        <?php foreach ($allEval as $ev): ?>
          <?php $isActive = ($ev['id'] == $activeAssId); ?>
          <a href="<?= BASE_URL ?>/guru/evaluasi.php?assessment_id=<?= $ev['id'] ?>" 
             class="btn <?= $isActive ? 'btn-primary' : 'btn-outline' ?>" 
             style="white-space: nowrap; font-size: 13px; padding: 10px 18px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 8px;">
            <span>Evaluasi: <?= htmlspecialchars($ev['judul']) ?></span>
            <span style="font-size: 11px; background: <?= $isActive ? '#fff' : 'var(--blue-100)' ?>; color: <?= $isActive ? 'var(--blue-800)' : 'var(--blue-800)' ?>; padding: 2px 7px; border-radius: 999px; font-weight: 700;">
              <?= $ev['total_soal'] ?> Soal
            </span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if ($activeAssessment): ?>
      <!-- Panel Detail Evaluasi Terpilih -->
      <div class="glass-card" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px; border-bottom: 1px solid var(--line); padding-bottom: 16px; margin-bottom: 16px;">
          <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
              <span class="badge-role" style="font-size: 11px; background: #EFF6FF; color: var(--blue-800);">
                Urutan Ke-<?= $activeAssessment['urutan'] ?>
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
            <button type="button" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;" onclick="document.getElementById('modalEditEval').style.display='flex'">
              <i data-feather="edit-2" style="width: 14px; height: 14px;"></i> Edit Pengaturan Modul
            </button>
            <a href="<?= BASE_URL ?>/guru/evaluasi.php?delete_evaluasi=<?= $activeAssessment['id'] ?>" 
               class="btn btn-danger" style="font-size: 12px; padding: 6px 12px;" 
               onclick="return confirm('Hapus seluruh modul evaluasi ini beserta semua soalnya?');">
              <i data-feather="trash-2" style="width: 14px; height: 14px;"></i> Hapus Modul
            </a>
          </div>
        </div>

        <!-- Tambah Butir Soal Baru ke Evaluasi -->
        <details style="background: #F8FAFC; border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 20px;">
          <summary style="font-size: 14px; font-weight: 700; color: var(--blue-800); cursor: pointer;">
            + Tambah Butir Soal Baru ke Evaluasi Ini
          </summary>
          <div style="margin-top: 14px;">
            <form method="POST" action="">
              <input type="hidden" name="action" value="add_question">
              <input type="hidden" name="assessment_id" value="<?= $activeAssessment['id'] ?>">

              <div style="display: grid; grid-template-columns: 140px 140px 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                  <label class="form-label">Tipe Soal</label>
                  <select name="tipe" id="tipeSoalEvalSelect" class="form-input" onchange="toggleOpsiEvalPG(this.value)">
                    <option value="essay" selected>Uraian / Essay</option>
                    <option value="pg">Pilihan Ganda (PG)</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Nomor Urutan</label>
                  <input type="number" name="urutan_soal" value="<?= count($questions) + 1 ?>" class="form-input" min="1" required>
                </div>
                <div>
                  <label class="form-label">Bobot Nilai</label>
                  <input type="number" step="0.5" name="bobot" value="50.0" class="form-input" required>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Pertanyaan Soal</label>
                <textarea name="pertanyaan" rows="3" class="form-input" placeholder="Tuliskan soal evaluasi matematika komprehensif di sini..." required></textarea>
              </div>

              <div id="opsiEvalPGContainer" style="display: none;">
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
                    <input type="text" name="feedback_benar" class="form-input" placeholder="Jawaban tepat!">
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
          Daftar Butir Soal Evaluasi (<?= count($questions) ?> Soal)
        </h3>

        <?php if (empty($questions)): ?>
          <div style="text-align: center; padding: 24px; color: var(--muted); border: 1px dashed var(--line); border-radius: var(--radius-sm);">
            Belum ada soal pada evaluasi ini. Buka panel "+ Tambah Butir Soal Baru" di atas untuk menambahkan soal.
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
                    <a href="<?= BASE_URL ?>/guru/evaluasi.php?assessment_id=<?= $activeAssessment['id'] ?>&delete_question=<?= $q['id'] ?>" 
                       class="btn btn-outline" style="padding: 4px 8px; font-size: 11px; color: var(--danger); border-color: var(--danger);"
                       onclick="return confirm('Hapus butir soal evaluasi ini?');">
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

<!-- Modal Tambah Evaluasi Baru -->
<div id="modalTambahEval" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center; padding: 20px;">
  <div class="glass-card" style="background: #fff; width: 100%; max-width: 500px; padding: 28px; border-radius: var(--radius);">
    <h3 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0 0 16px 0;">
      Buat Modul Evaluasi Baru
    </h3>
    <form method="POST" action="">
      <input type="hidden" name="action" value="create_evaluasi">

      <div class="form-group">
        <label class="form-label">Judul Evaluasi</label>
        <input type="text" name="judul" class="form-input" placeholder="Contoh: Evaluasi Akhir Bab Statistika & Peluang" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
        <div>
          <label class="form-label">Urutan</label>
          <input type="number" name="urutan" value="<?= count($allEval) + 1 ?>" class="form-input" min="1" required>
        </div>
        <div>
          <label class="form-label">Batas Waktu (Menit)</label>
          <input type="number" name="duration_minutes" value="60" class="form-input" min="5" required>
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
        <label class="form-label">Deskripsi / Petunjuk Ujian</label>
        <textarea name="deskripsi" rows="3" class="form-input" placeholder="Petunjuk pengerjaan evaluasi..."></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('modalTambahEval').style.display='none'">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Modul</button>
      </div>
    </form>
  </div>
</div>

<?php if ($activeAssessment): ?>
  <!-- Modal Edit Evaluasi -->
  <div id="modalEditEval" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center; padding: 20px;">
    <div class="glass-card" style="background: #fff; width: 100%; max-width: 500px; padding: 28px; border-radius: var(--radius);">
      <h3 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0 0 16px 0;">
        Edit Pengaturan Modul Evaluasi
      </h3>
      <form method="POST" action="">
        <input type="hidden" name="action" value="update_evaluasi">
        <input type="hidden" name="assessment_id" value="<?= $activeAssessment['id'] ?>">

        <div class="form-group">
          <label class="form-label">Judul Evaluasi</label>
          <input type="text" name="judul" class="form-input" value="<?= htmlspecialchars($activeAssessment['judul']) ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
          <div>
            <label class="form-label">Urutan</label>
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
          <label class="form-label">Deskripsi / Petunjuk Ujian</label>
          <textarea name="deskripsi" rows="3" class="form-input"><?= htmlspecialchars($activeAssessment['deskripsi']) ?></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
          <button type="button" class="btn btn-outline" onclick="document.getElementById('modalEditEval').style.display='none'">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<script>
  function toggleOpsiEvalPG(val) {
    const container = document.getElementById('opsiEvalPGContainer');
    if (val === 'pg') {
      container.style.display = 'block';
    } else {
      container.style.display = 'none';
    }
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
