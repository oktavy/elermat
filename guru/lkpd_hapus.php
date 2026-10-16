<?php
/**
 * Penghapusan Modul LKPD Guru (Aman & Terintegrasi)
 * File: guru/lkpd_hapus.php
 * 
 * FITUR:
 * 1. Guru & Admin dapat menghapus lembar LKPD yang pernah dibuat sebelumnya.
 * 2. Konfirmasi penghapusan ("Apakah Anda yakin ingin menghapus LKPD ini?").
 * 3. Mekanisme Penghapusan Aman (Safe Soft Delete):
 *    - Jika LKPD SUDAH memiliki riwayat jawaban/nilai siswa di submissions, LKPD dinonaktifkan (is_active = 0).
 *      Hal ini menjaga keutuhan riwayat nilai siswa agar tidak corrupt/hilang.
 *    - Jika LKPD BELUM pernah dikerjakan oleh siswa, LKPD dan butir soalnya dihapus permanen dari database.
 */

require_once __DIR__ . '/../config/app.php';
require_role(['guru', 'admin']);

$currentUser = auth_user();
$db = getDB();

$lkpdId = !empty($_GET['id']) ? (int)$_GET['id'] : (!empty($_POST['id']) ? (int)$_POST['id'] : 0);

if ($lkpdId <= 0) {
    flash_set('danger', 'ID LKPD tidak valid atau tidak ditemukan.');
    redirect('guru/lkpd.php');
}

// Cek apakah LKPD ada di database
$stmt = $db->prepare("SELECT * FROM assessments WHERE id = ? AND tipe = 'lkpd' LIMIT 1");
$stmt->execute([$lkpdId]);
$lkpd = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lkpd) {
    flash_set('danger', 'Modul LKPD tidak ditemukan.');
    redirect('guru/lkpd.php');
}

// Cek apakah ada konfirmasi penghapusan via POST atau GET ?confirm=1
$isConfirmed = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_delete') 
            || (isset($_GET['confirm']) && $_GET['confirm'] === '1');

if ($isConfirmed) {
    try {
        // Cek riwayat submission siswa
        $stmtCek = $db->prepare("SELECT COUNT(*) FROM submissions WHERE assessment_id = ?");
        $stmtCek->execute([$lkpdId]);
        $subCount = (int)$stmtCek->fetchColumn();

        if ($subCount > 0) {
            // Soft delete: Nonaktifkan modul agar nilai siswa tidak rusak
            $stmtSoft = $db->prepare("UPDATE assessments SET is_active = 0 WHERE id = ? AND tipe = 'lkpd'");
            $stmtSoft->execute([$lkpdId]);

            flash_set('success', "Modul LKPD '{$lkpd['judul']}' berhasil dihapus dari daftar aktif. {$subCount} riwayat nilai siswa tetap tersimpan aman di arsip penilaian.");
        } else {
            // Hard delete: Bersihkan butir soal dan modul karena belum pernah dikerjakan
            $stmtDelQ = $db->prepare("DELETE FROM questions WHERE assessment_id = ?");
            $stmtDelQ->execute([$lkpdId]);

            $stmtDelA = $db->prepare("DELETE FROM assessments WHERE id = ? AND tipe = 'lkpd'");
            $stmtDelA->execute([$lkpdId]);

            flash_set('success', "Modul LKPD '{$lkpd['judul']}' beserta butir soalnya berhasil dihapus secara permanen dari sistem.");
        }
    } catch (Exception $e) {
        flash_set('danger', 'Gagal menghapus modul LKPD: ' . $e->getMessage());
    }

    redirect('guru/lkpd.php');
}

// Tampilkan Halaman Konfirmasi Penghapusan Interaktif
$pageTitle = 'Konfirmasi Hapus LKPD - ElearnMath Guru';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Ambil info pengerjaan untuk ditampilkan di konfirmasi
$stmtCekSubs = $db->prepare("SELECT COUNT(*) FROM submissions WHERE assessment_id = ?");
$stmtCekSubs->execute([$lkpdId]);
$totalSubs = (int)$stmtCekSubs->fetchColumn();

$stmtCekQ = $db->prepare("SELECT COUNT(*) FROM questions WHERE assessment_id = ?");
$stmtCekQ->execute([$lkpdId]);
$totalQuestions = (int)$stmtCekQ->fetchColumn();
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper" style="max-width: 640px; margin: 40px auto;">
    <div class="glass-card" style="text-align: center; padding: 36px 28px; border: 2px solid var(--danger);">
      <div style="width: 64px; height: 64px; border-radius: 50%; background: #FEE2E2; color: #DC2626; font-size: 30px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 18px;">
        ⚠️
      </div>

      <h1 style="font-size: 20px; font-weight: 800; color: #1E293B; margin: 0 0 10px 0;">
        Konfirmasi Penghapusan Modul LKPD
      </h1>

      <p style="font-size: 14px; color: var(--muted); margin: 0 0 20px 0; line-height: 1.6;">
        Apakah Anda yakin ingin menghapus lembar kerja berikut?
      </p>

      <div style="background: #F8FAFC; border: 1px solid var(--line); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; text-align: left;">
        <div style="font-size: 11px; color: var(--muted); font-weight: 700; text-transform: uppercase;">Judul LKPD:</div>
        <div style="font-size: 16px; font-weight: 800; color: var(--blue-800); margin-bottom: 8px;">
          <?= htmlspecialchars($lkpd['judul']) ?>
        </div>
        <div style="display: flex; gap: 16px; font-size: 12.5px; color: var(--muted); flex-wrap: wrap;">
          <span>Target Kelas: <strong><?= htmlspecialchars($lkpd['kelas']) ?></strong></span>
          <span>Jumlah Soal: <strong><?= $totalQuestions ?> Butir</strong></span>
          <span>Pengumpulan Siswa: <strong><?= $totalSubs ?> Siswa</strong></span>
        </div>
      </div>

      <?php if ($totalSubs > 0): ?>
        <div style="background: #FEF3C7; border: 1px solid #FCD34D; border-radius: var(--radius-sm); padding: 12px 16px; margin-bottom: 24px; text-align: left; font-size: 12.5px; color: #92400E; line-height: 1.5;">
          ℹ️ <strong>Perlindungan Data Penilaian:</strong> LKPD ini telah dikerjakan oleh <strong><?= $totalSubs ?> siswa</strong>. Sistem akan menghapus LKPD ini dari tampilan daftar modul aktif, namun arsip nilai dan hasil jawaban siswa tetap aman di database.
        </div>
      <?php else: ?>
        <div style="background: #FEE2E2; border: 1px solid #FECACA; border-radius: var(--radius-sm); padding: 12px 16px; margin-bottom: 24px; text-align: left; font-size: 12.5px; color: #991B1B; line-height: 1.5;">
          🗑️ LKPD ini belum memiliki pengerjaan siswa. Seluruh butir soal dan modul akan dihapus permanen.
        </div>
      <?php endif; ?>

      <form method="POST" action="" style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
        <input type="hidden" name="action" value="confirm_delete">
        <input type="hidden" name="id" value="<?= $lkpdId ?>">

        <a href="<?= BASE_URL ?>/guru/lkpd.php" class="btn btn-outline" style="padding: 10px 24px; font-size: 13.5px;">
          Batalkan
        </a>

        <button type="submit" class="btn btn-primary" style="background: var(--danger); border-color: var(--danger); padding: 10px 24px; font-size: 13.5px; font-weight: 700;">
          Ya, Hapus LKPD Ini
        </button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
