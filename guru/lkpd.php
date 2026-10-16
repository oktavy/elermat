<?php
/**
 * Manajemen Modul LKPD & Kelola Butir Soal Guru
 * File: guru/lkpd.php
 * FITUR:
 * 1. Guru dapat Menambah, Mengedit, dan Menghapus LKPD.
 * 2. Konfirmasi penghapusan LKPD ("Apakah Anda yakin ingin menghapus LKPD ini?").
 * 3. Mekanisme aman: jika LKPD sudah memiliki submission siswa, data arsip siswa tetap aman.
 * 4. Kelola butir soal (Tambah, Edit, Hapus PG & Essay), kunci jawaban, dan pembahasan.
 */
require_once __DIR__ . '/../config/app.php';
require_role(['guru', 'admin']);

$pageTitle = 'Kelola LKPD - ElearnMath Guru';
$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// Ambil daftar kelas untuk pilihan target kelas
$classes = $db->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

// 1. TAMBAH LKPD BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_assessment') {
    $judul   = trim($_POST['judul'] ?? '');
    $urutan  = (int)($_POST['urutan'] ?? 1);
    $kelas   = trim($_POST['kelas'] ?? 'Semua Kelas');
    $durasi  = (int)($_POST['duration_minutes'] ?? 45);
    $kkm     = (int)($_POST['passing_grade'] ?? 75);
    $desk    = trim($_POST['deskripsi'] ?? '');

    if (!empty($judul)) {
        $ins = $db->prepare("
            INSERT INTO assessments (tipe, judul, urutan, kelas, duration_minutes, passing_grade, deskripsi, is_active, created_by, created_at)
            VALUES ('lkpd', ?, ?, ?, ?, ?, ?, 1, ?, NOW())
        ");
        $ins->execute([$judul, $urutan, $kelas, $durasi, $kkm, $desk, $currentUser['id']]);
        $newAssId = $db->lastInsertId();

        flash_set('success', "Modul LKPD '{$judul}' berhasil dibuat! Silakan kelola butir soal di bawah.");
        redirect('guru/lkpd.php?assessment_id=' . $newAssId);
    }
}

// 2. EDIT MODUL LKPD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_assessment') {
    $assId   = (int)$_POST['assessment_id'];
    $judul   = trim($_POST['judul'] ?? '');
    $urutan  = (int)($_POST['urutan'] ?? 1);
    $kelas   = trim($_POST['kelas'] ?? 'Semua Kelas');
    $durasi  = (int)($_POST['duration_minutes'] ?? 45);
    $kkm     = (int)($_POST['passing_grade'] ?? 75);
    $desk    = trim($_POST['deskripsi'] ?? '');

    if (!empty($judul)) {
        $upd = $db->prepare("
            UPDATE assessments 
            SET judul = ?, urutan = ?, kelas = ?, duration_minutes = ?, passing_grade = ?, deskripsi = ?
            WHERE id = ? AND tipe = 'lkpd'
        ");
        $upd->execute([$judul, $urutan, $kelas, $durasi, $kkm, $desk, $assId]);

        flash_set('success', "Pengaturan modul LKPD berhasil diperbarui!");
        redirect('guru/lkpd.php?assessment_id=' . $assId);
    }
}

// 3. HAPUS MODUL LKPD (DENGAN MEKANISME AMAN)
if (isset($_GET['delete_assessment'])) {
    $delAssId = (int)$_GET['delete_assessment'];

    // Cek apakah ada riwayat pengerjaan siswa
    $stmtCek = $db->prepare("SELECT COUNT(*) FROM submissions WHERE assessment_id = ?");
    $stmtCek->execute([$delAssId]);
    $subCount = (int)$stmtCek->fetchColumn();

    if ($subCount > 0) {
        // Safe delete (soft delete): nonaktifkan modul agar riwayat dan nilai siswa tidak hilang/rusak
        $upd = $db->prepare("UPDATE assessments SET is_active = 0 WHERE id = ? AND tipe = 'lkpd'");
        $upd->execute([$delAssId]);
        flash_set('success', "Modul LKPD berhasil dinonaktifkan/dihapus dari daftar aktif. {$subCount} riwayat nilai siswa tetap tersimpan aman di database.");
    } else {
        // Hard delete jika belum pernah dikerjakan siswa
        $stmtDelQ = $db->prepare("DELETE FROM questions WHERE assessment_id = ?");
        $stmtDelQ->execute([$delAssId]);
        $stmtDel = $db->prepare("DELETE FROM assessments WHERE id = ? AND tipe = 'lkpd'");
        $stmtDel->execute([$delAssId]);
        flash_set('success', 'Modul LKPD beserta seluruh butir soalnya berhasil dihapus permanen.');
    }
    redirect('guru/lkpd.php');
}

// 4. TAMBAH BUTIR SOAL KE LKPD TERPILIH
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

        flash_set('success', 'Butir soal baru berhasil ditambahkan ke LKPD!');
        redirect('guru/lkpd.php?assessment_id=' . $assId);
    }
}

// 5. EDIT BUTIR SOAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_question') {
    $qId        = (int)$_POST['question_id'];
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
        $updQ = $db->prepare("
            UPDATE questions 
            SET urutan = ?, tipe = ?, pertanyaan = ?, opsi_a = ?, opsi_b = ?, opsi_c = ?, opsi_d = ?, kunci_jawaban = ?, feedback_benar = ?, feedback_salah = ?, bobot = ?
            WHERE id = ?
        ");
        $updQ->execute([$urutanSoal, $tipe, $pertanyaan, $opsiA, $opsiB, $opsiC, $opsiD, $kunci, $fbBenar, $fbSalah, $bobot, $qId]);

        flash_set('success', 'Soal LKPD berhasil diperbarui!');
        redirect('guru/lkpd.php?assessment_id=' . $assId);
    }
}

// 6. HAPUS BUTIR SOAL
if (isset($_GET['delete_question'])) {
    $delQId = (int)$_GET['delete_question'];
    $currAssId = (int)($_GET['assessment_id'] ?? 0);

    $stmtDelQ = $db->prepare("DELETE FROM questions WHERE id = ?");
    $stmtDelQ->execute([$delQId]);

    flash_set('success', 'Butir soal berhasil dihapus.');
    redirect('guru/lkpd.php' . ($currAssId ? '?assessment_id=' . $currAssId : ''));
}

// Ambil seluruh modul LKPD aktif
$stmtAll = $db->query("
    SELECT a.*, 
           (SELECT COUNT(*) FROM questions q WHERE q.assessment_id = a.id) AS total_soal,
           (SELECT COUNT(*) FROM submissions s WHERE s.assessment_id = a.id) AS total_subs
    FROM assessments a 
    WHERE a.tipe = 'lkpd' AND a.is_active = 1 
    ORDER BY a.urutan ASC, a.id ASC
");
$allLkpd = $stmtAll->fetchAll();

// Tentukan LKPD aktif yang sedang dikelola
$selectedId = !empty($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : 0;
$activeLkpd = null;

if ($selectedId > 0) {
    foreach ($allLkpd as $l) {
        if ($l['id'] == $selectedId) {
            $activeLkpd = $l;
            break;
        }
    }
}

if (!$activeLkpd && !empty($allLkpd)) {
    $activeLkpd = $allLkpd[0];
    $selectedId = $activeLkpd['id'];
}

// Ambil butir soal dari LKPD aktif
$questions = [];
if ($activeLkpd) {
    $stmtQ = $db->prepare("SELECT * FROM questions WHERE assessment_id = ? ORDER BY urutan ASC, id ASC");
    $stmtQ->execute([$activeLkpd['id']]);
    $questions = $stmtQ->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px;">
      <div>
        <span class="badge-role" style="background: var(--orange-100); color: var(--orange-dark); margin-bottom: 4px;">
          Manajemen Evaluasi & LKPD
        </span>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
          Kelola Modul LKPD (Lembar Kerja Peserta Didik)
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Buat, ubah, atau hapus modul LKPD beserta butir soalnya (PG & Essay). Siswa dapat mengerjakan lembar kerja secara mandiri dengan teks dan foto catatan.
        </p>
      </div>

      <button type="button" class="btn btn-primary" onclick="toggleModal('modalCreateLkpd')">
        <i data-feather="plus-circle"></i> + Buat LKPD Baru
      </button>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- Tabs Pilihan Modul LKPD -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 6px;">
      <?php if (empty($allLkpd)): ?>
        <div style="color: var(--muted); font-size: 13px;">Belum ada modul LKPD. Klik tombol di atas untuk membuat LKPD baru.</div>
      <?php else: ?>
        <?php foreach ($allLkpd as $l): ?>
          <?php $isActiveTab = ($activeLkpd && $activeLkpd['id'] == $l['id']); ?>
          <a href="<?= BASE_URL ?>/guru/lkpd.php?assessment_id=<?= $l['id'] ?>" 
             style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 12px; font-weight: 700; font-size: 13px; text-decoration: none; border: 1px solid <?= $isActiveTab ? 'var(--blue-600)' : 'var(--line)' ?>; background: <?= $isActiveTab ? 'var(--blue-600)' : '#fff' ?>; color: <?= $isActiveTab ? '#fff' : 'var(--ink)' ?>; transition: all 0.15s; white-space: nowrap;">
            <span>LKPD <?= $l['urutan'] ?>: <?= htmlspecialchars($l['judul']) ?></span>
            <span style="font-size: 11px; padding: 2px 7px; border-radius: 999px; background: <?= $isActiveTab ? 'rgba(255,255,255,0.25)' : '#F1F5F9' ?>; color: <?= $isActiveTab ? '#fff' : 'var(--muted)' ?>;">
              <?= $l['total_soal'] ?> Soal
            </span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if ($activeLkpd): ?>
      <!-- Panel Pengaturan Modul LKPD Aktif -->
      <div class="glass-card" style="margin-bottom: 24px; border-left: 4px solid var(--orange);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px;">
          <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
              <span class="badge-role" style="font-size: 11px;">LKPD Urutan Ke-<?= $activeLkpd['urutan'] ?></span>
              <span class="badge-role" style="background: #EFF6FF; color: var(--blue-800); font-size: 11px;">Kelas: <?= htmlspecialchars($activeLkpd['kelas']) ?></span>
              <span class="badge-role" style="background: #FEF3C7; color: #B45309; font-size: 11px;">Durasi: <?= (int)$activeLkpd['duration_minutes'] ?> Menit</span>
              <span class="badge-role" style="background: #D1FAE5; color: #065F46; font-size: 11px;">KKM: <?= (int)($activeLkpd['passing_grade'] ?: 75) ?></span>
            </div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--blue-800); margin: 0 0 4px 0;">
              <?= htmlspecialchars($activeLkpd['judul']) ?>
            </h2>
            <p style="font-size: 13px; color: var(--muted); margin: 0;">
              <?= htmlspecialchars($activeLkpd['deskripsi'] ?: 'Lembar kerja eksplorasi mandiri konsep matematika.') ?>
            </p>
          </div>

          <div style="display: flex; gap: 8px;">
            <a href="<?= BASE_URL ?>/guru/lkpd_edit.php?id=<?= $activeLkpd['id'] ?>" class="btn btn-outline" style="font-size: 12.5px; padding: 7px 14px;">
              <i data-feather="edit-2"></i> Edit LKPD Lengkap
            </a>
            <a href="<?= BASE_URL ?>/guru/lkpd_hapus.php?id=<?= $activeLkpd['id'] ?>" 
               class="btn btn-outline" 
               style="font-size: 12.5px; padding: 7px 14px; border-color: var(--danger); color: var(--danger);">
              <i data-feather="trash-2"></i> Hapus LKPD
            </a>
          </div>
        </div>
      </div>

      <!-- Form Tambah Butir Soal ke LKPD -->
      <div class="glass-card" style="margin-bottom: 24px;">
        <h2 style="font-size: 16px; font-weight: 800; color: var(--blue-800); margin: 0 0 16px 0;">
          + Tambah Butir Soal ke LKPD Ini
        </h2>

        <form method="POST" action="">
          <input type="hidden" name="action" value="add_question">
          <input type="hidden" name="assessment_id" value="<?= $activeLkpd['id'] ?>">

          <div style="display: grid; grid-template-columns: 180px 140px 140px; gap: 14px; margin-bottom: 14px;">
            <div>
              <label class="form-label" style="font-size: 12px;">Tipe Soal:</label>
              <select name="tipe" id="tipeSoalSelect" class="form-input" style="width: 100%;" onchange="toggleOptionFields(this.value)">
                <option value="pg">Pilihan Ganda (PG)</option>
                <option value="essay">Uraian / Essay</option>
              </select>
            </div>
            <div>
              <label class="form-label" style="font-size: 12px;">Nomor Urut:</label>
              <input type="number" name="urutan_soal" class="form-input" value="<?= count($questions) + 1 ?>" min="1" style="width: 100%;">
            </div>
            <div>
              <label class="form-label" style="font-size: 12px;">Bobot Skor:</label>
              <input type="number" name="bobot" class="form-input" value="25" min="1" step="0.5" style="width: 100%;">
            </div>
          </div>

          <div style="margin-bottom: 14px;">
            <label class="form-label" style="font-size: 12px;">Pertanyaan / Instruksi Soal: <span style="color:red;">*</span></label>
            <textarea name="pertanyaan" rows="3" class="form-input" style="width: 100%;" placeholder="Tuliskan teks instruksi, studi kasus, atau pertanyaan LKPD..." required></textarea>
          </div>

          <!-- Opsi PG -->
          <div id="pgOptionsBlock" style="margin-bottom: 16px; background: #F8FAFC; border: 1px solid var(--line); border-radius: 8px; padding: 16px;">
            <div style="font-size: 13px; font-weight: 700; color: var(--blue-800); margin-bottom: 12px;">Pilihan Jawaban (A, B, C, D):</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
              <div>
                <label class="form-label" style="font-size: 11.5px;">Opsi A:</label>
                <input type="text" name="opsi_a" class="form-input" style="width: 100%;" placeholder="Jawaban A">
              </div>
              <div>
                <label class="form-label" style="font-size: 11.5px;">Opsi B:</label>
                <input type="text" name="opsi_b" class="form-input" style="width: 100%;" placeholder="Jawaban B">
              </div>
              <div>
                <label class="form-label" style="font-size: 11.5px;">Opsi C:</label>
                <input type="text" name="opsi_c" class="form-input" style="width: 100%;" placeholder="Jawaban C">
              </div>
              <div>
                <label class="form-label" style="font-size: 11.5px;">Opsi D:</label>
                <input type="text" name="opsi_d" class="form-input" style="width: 100%;" placeholder="Jawaban D">
              </div>
            </div>

            <div>
              <label class="form-label" style="font-size: 12px;">Kunci Jawaban yang Benar:</label>
              <select name="kunci_jawaban" class="form-input" style="width: 220px;">
                <option value="0">Opsi A</option>
                <option value="1">Opsi B</option>
                <option value="2">Opsi C</option>
                <option value="3">Opsi D</option>
              </select>
            </div>
          </div>

          <!-- Feedback & Pembahasan -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
            <div>
              <label class="form-label" style="font-size: 12px;">Feedback / Apresiasi Jawaban Benar:</label>
              <input type="text" name="feedback_benar" class="form-input" style="width: 100%;" placeholder="Contoh: Hebat! Analisis data Anda sangat tepat.">
            </div>
            <div>
              <label class="form-label" style="font-size: 12px;">Kunci / Pembahasan Jika Siswa Salah:</label>
              <input type="text" name="feedback_salah" class="form-input" style="width: 100%;" placeholder="Contoh: Perhatikan kembali langkah tabel frekuensi kumulatif.">
            </div>
          </div>

          <button type="submit" class="btn btn-primary" style="padding: 9px 20px;">
            <i data-feather="plus"></i> Tambahkan Soal ke LKPD
          </button>
        </form>
      </div>

      <!-- Daftar Soal LKPD yang Sudah Dibuat -->
      <div class="glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
          <h2 style="font-size: 16px; font-weight: 800; color: var(--blue-800); margin: 0;">
            Daftar Soal LKPD (<?= count($questions) ?> Butir)
          </h2>
          <span style="font-size: 12px; color: var(--muted);">Total Skor: <?= array_sum(array_column($questions, 'bobot')) ?></span>
        </div>

        <?php if (empty($questions)): ?>
          <div style="text-align: center; color: var(--muted); padding: 32px;">
            Belum ada butir soal pada LKPD ini. Silakan tambahkan butir soal menggunakan formulir di atas.
          </div>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 14px;">
            <?php foreach ($questions as $idx => $q): ?>
              <div style="background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-weight: 800; font-size: 14px; color: var(--blue-800);">No. <?= $idx + 1 ?></span>
                    <span class="badge-role" style="font-size: 11px;"><?= strtoupper($q['tipe']) ?></span>
                    <span style="font-size: 11.5px; color: var(--muted); font-weight: 600;">Bobot: <?= $q['bobot'] ?></span>
                  </div>

                  <div style="display: flex; gap: 6px;">
                    <a href="<?= BASE_URL ?>/guru/lkpd.php?delete_question=<?= $q['id'] ?>&assessment_id=<?= $activeLkpd['id'] ?>" 
                       class="btn btn-outline" 
                       style="font-size: 11.5px; padding: 4px 10px; border-color: var(--danger); color: var(--danger);" 
                       onclick="return confirm('Apakah Anda yakin ingin menghapus butir soal ini?');">
                      Hapus
                    </a>
                  </div>
                </div>

                <div style="font-size: 14px; color: var(--ink); line-height: 1.6; margin-bottom: 12px;">
                  <?= nl2br(htmlspecialchars($q['pertanyaan'])) ?>
                </div>

                <?php if ($q['tipe'] === 'pg'): ?>
                  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px; background: #F8FAFC; padding: 10px 14px; border-radius: 8px; margin-bottom: 10px;">
                    <div><strong>A:</strong> <?= htmlspecialchars($q['opsi_a']) ?></div>
                    <div><strong>B:</strong> <?= htmlspecialchars($q['opsi_b']) ?></div>
                    <div><strong>C:</strong> <?= htmlspecialchars($q['opsi_c']) ?></div>
                    <div><strong>D:</strong> <?= htmlspecialchars($q['opsi_d']) ?></div>
                  </div>
                  <div style="font-size: 12.5px; color: var(--success); font-weight: 700;">
                    ✓ Kunci Jawaban: Opsi <?= ['A','B','C','D'][$q['kunci_jawaban']] ?? '-' ?>
                  </div>
                <?php else: ?>
                  <div style="font-size: 12.5px; color: var(--muted); background: #F8FAFC; padding: 8px 12px; border-radius: 6px; margin-bottom: 8px;">
                    <em>Tipe Essay / Uraian (Siswa dapat mengetik teks, mengunggah foto, atau mengambil foto lewat kamera)</em>
                  </div>
                <?php endif; ?>

                <?php if (!empty($q['feedback_salah'])): ?>
                  <div style="font-size: 12px; color: #92400E; margin-top: 6px;">
                    💡 <strong>Pembahasan:</strong> <?= htmlspecialchars($q['feedback_salah']) ?>
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

<!-- Modal 1: Buat LKPD Baru -->
<div id="modalCreateLkpd" class="modal-overlay">
  <div class="modal-box" style="max-width: 520px; text-align: left;">
    <h3 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0 0 16px 0;">
      Buat Modul LKPD Baru
    </h3>
    <form method="POST" action="">
      <input type="hidden" name="action" value="create_assessment">

      <div style="margin-bottom: 12px;">
        <label class="form-label">Judul LKPD: <span style="color:red;">*</span></label>
        <input type="text" name="judul" class="form-input" style="width: 100%;" placeholder="Contoh: LKPD Statistika: Ukuran Pemusatan Data" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
        <div>
          <label class="form-label">Urutan LKPD:</label>
          <input type="number" name="urutan" class="form-input" style="width: 100%;" value="<?= count($allLkpd) + 1 ?>" min="1" required>
        </div>
        <div>
          <label class="form-label">Target Kelas:</label>
          <select name="kelas" class="form-input" style="width: 100%;">
            <option value="Semua Kelas">Semua Kelas</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
        <div>
          <label class="form-label">Durasi (Menit):</label>
          <input type="number" name="duration_minutes" class="form-input" style="width: 100%;" value="45" min="5" required>
        </div>
        <div>
          <label class="form-label">KKM / Nilai Ketuntasan:</label>
          <input type="number" name="passing_grade" class="form-input" style="width: 100%;" value="75" min="0" max="100">
        </div>
      </div>

      <div style="margin-bottom: 18px;">
        <label class="form-label">Deskripsi / Petunjuk Belajar:</label>
        <textarea name="deskripsi" rows="3" class="form-input" style="width: 100%;" placeholder="Tuliskan deskripsi kompetensi atau petunjuk pengerjaan LKPD..."></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" class="btn btn-outline" onclick="toggleModal('modalCreateLkpd')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan & Buat LKPD</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal 2: Edit LKPD -->
<?php if ($activeLkpd): ?>
<div id="modalEditLkpd" class="modal-overlay">
  <div class="modal-box" style="max-width: 520px; text-align: left;">
    <h3 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0 0 16px 0;">
      Edit Modul LKPD
    </h3>
    <form method="POST" action="">
      <input type="hidden" name="action" value="update_assessment">
      <input type="hidden" name="assessment_id" value="<?= $activeLkpd['id'] ?>">

      <div style="margin-bottom: 12px;">
        <label class="form-label">Judul LKPD: <span style="color:red;">*</span></label>
        <input type="text" name="judul" class="form-input" style="width: 100%;" value="<?= htmlspecialchars($activeLkpd['judul']) ?>" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
        <div>
          <label class="form-label">Urutan LKPD:</label>
          <input type="number" name="urutan" class="form-input" style="width: 100%;" value="<?= (int)$activeLkpd['urutan'] ?>" min="1" required>
        </div>
        <div>
          <label class="form-label">Target Kelas:</label>
          <select name="kelas" class="form-input" style="width: 100%;">
            <option value="Semua Kelas" <?= $activeLkpd['kelas'] === 'Semua Kelas' ? 'selected' : '' ?>>Semua Kelas</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= $activeLkpd['kelas'] === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
        <div>
          <label class="form-label">Durasi (Menit):</label>
          <input type="number" name="duration_minutes" class="form-input" style="width: 100%;" value="<?= (int)$activeLkpd['duration_minutes'] ?>" min="5" required>
        </div>
        <div>
          <label class="form-label">KKM / Nilai Ketuntasan:</label>
          <input type="number" name="passing_grade" class="form-input" style="width: 100%;" value="<?= (int)($activeLkpd['passing_grade'] ?: 75) ?>" min="0" max="100">
        </div>
      </div>

      <div style="margin-bottom: 18px;">
        <label class="form-label">Deskripsi / Petunjuk Belajar:</label>
        <textarea name="deskripsi" rows="3" class="form-input" style="width: 100%;"><?= htmlspecialchars($activeLkpd['deskripsi']) ?></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" class="btn btn-outline" onclick="toggleModal('modalEditLkpd')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
  function toggleOptionFields(tipe) {
    const pgBlock = document.getElementById('pgOptionsBlock');
    if (pgBlock) {
      pgBlock.style.display = (tipe === 'pg') ? 'block' : 'none';
    }
  }

  function toggleModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.classList.toggle('show');
  }

  function openEditLkpdModal() {
    toggleModal('modalEditLkpd');
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
