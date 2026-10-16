<?php
/**
 * Halaman Daftar Materi Pelajaran (Mandiri)
 * File: siswa/materi.php
 * FITUR B: Materi dan LKPD dipisah secara mandiri
 */
require_once __DIR__ . '/../config/app.php';
require_role('siswa');

$pageTitle = 'Materi Pembelajaran - ElearnMath';
$currentUser = auth_user();
$db = getDB();

// Cek status Admit Admin
if (strtoupper($currentUser['status_approval'] ?? 'PENDING') !== 'APPROVED') {
    flash_set('warning', 'Akses Kelas Anda sedang menunggu persetujuan (Admit) dari Admin untuk masuk ke kelas tujuan.');
    redirect('siswa/index.php');
}

$studentClass = $currentUser['kelas'] ?? '';

$stmt = $db->prepare("
    SELECT m.*, u.nama_lengkap AS nama_guru 
    FROM materi m
    LEFT JOIN users u ON m.guru_id = u.id
    WHERE (m.kelas = 'Semua Kelas' OR m.kelas = ? OR m.kelas IS NULL OR ? = '')
    ORDER BY m.urutan ASC, m.id ASC
");
$stmt->execute([$studentClass, $studentClass]);
$materials = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="margin-bottom: 24px;">
      <div style="font-size: 12px; font-weight: 700; color: var(--blue-600); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
        Modul Belajar Mandiri
      </div>
      <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
        Materi Pembelajaran Matematika
      </h1>
      <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
        Materi ini bersifat mandiri. Anda dapat membaca materi kapan saja sesuai kecepatan belajar Anda tanpa mengunci pengerjaan lembar kerja (LKPD).
      </p>
    </div>

    <div class="grid-cards">
      <?php foreach ($materials as $index => $mat): ?>
        <?php 
          $subbabList = json_decode($mat['subbab'] ?? '[]', true) ?: [];
          $mediaLink = $mat['media_link'] ?: $mat['youtube_url'];
          $mediaInfo = get_media_type_info($mediaLink, $mat['judul']);
        ?>
        <div class="glass-card" style="display: flex; flex-direction: column; justify-content: space-between;">
          <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 6px;">
              <span class="badge-role" style="font-size: 11px;">Bab <?= $mat['urutan'] ?? ($index + 1) ?></span>
              <?php if ($mediaInfo): ?>
                <span style="font-size: 11px; background: <?= $mediaInfo['bg'] ?>; color: <?= $mediaInfo['color'] ?>; padding: 3px 10px; border-radius: 999px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                  <?= $mediaInfo['badge'] ?>
                </span>
              <?php endif; ?>
            </div>

            <h3 style="font-size: 18px; font-weight: 700; color: var(--blue-800); margin: 0 0 8px 0;">
              <?= htmlspecialchars($mat['judul']) ?>
            </h3>

            <?php if ($mediaInfo): ?>
              <div style="margin-bottom: 10px; font-size: 12.5px; font-weight: 700; color: <?= $mediaInfo['color'] ?>;">
                <?= htmlspecialchars($mediaInfo['display']) ?>
              </div>
            <?php endif; ?>

            <p style="font-size: 13px; color: var(--muted); line-height: 1.5; margin: 0 0 16px 0;">
              <?= htmlspecialchars($mat['deskripsi']) ?>
            </p>

            <?php if (!empty($subbabList)): ?>
              <div style="background: #F8FAFC; border-radius: var(--radius-sm); padding: 12px; margin-bottom: 16px;">
                <div style="font-size: 11.5px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">Sub Topik Bahasan:</div>
                <ul style="margin: 0; padding-left: 18px; font-size: 12px; color: var(--muted);">
                  <?php foreach ($subbabList as $sb): ?>
                    <li><?= htmlspecialchars($sb) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          </div>

          <div style="border-top: 1px solid var(--line); padding-top: 16px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 11.5px; color: var(--muted);">
              Oleh: <?= htmlspecialchars($mat['nama_guru'] ?? 'Guru Matematika') ?>
            </span>
            <a href="<?= BASE_URL ?>/siswa/materi_detail.php?id=<?= $mat['id'] ?>" class="btn btn-primary" style="font-size: 12.5px; padding: 8px 14px;">
              Pelajari &rarr;
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
