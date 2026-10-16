<?php
/**
 * Halaman Baca Materi Detail dengan Media Embed Multimedia Dinamis
 * File: siswa/materi_detail.php
 * FITUR:
 * - Menampilkan uraian materi mandiri.
 * - Embed media interaktif responsif (YouTube, Google Drive, PDF, atau Web Luar).
 */
require_once __DIR__ . '/../config/app.php';
require_role('siswa');

$currentUser = auth_user();
// Cek status Admit Admin
if (strtoupper($currentUser['status_approval'] ?? 'PENDING') !== 'APPROVED') {
    flash_set('warning', 'Akses materi terkunci hingga disetujui (Admit) oleh Admin.');
    redirect('siswa/index.php');
}

$id = !empty($_GET['id']) ? (int)$_GET['id'] : 1;
$db = getDB();

$stmt = $db->prepare("
    SELECT m.*, u.nama_lengkap AS nama_guru 
    FROM materi m
    LEFT JOIN users u ON m.guru_id = u.id
    WHERE m.id = ?
");
$stmt->execute([$id]);
$materi = $stmt->fetch();

if (!$materi) {
    redirect('siswa/materi.php');
}

// Catat progres membaca materi oleh siswa
try {
    $insRead = $db->prepare("
        INSERT INTO materi_reads (user_id, materi_id, read_at) 
        VALUES (?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE read_at = NOW()
    ");
    $insRead->execute([$currentUser['id'], $materi['id']]);
} catch (Exception $e) {}

$pageTitle = htmlspecialchars($materi['judul']) . ' - ElearnMath';
$subbabList = json_decode($materi['subbab'] ?? '[]', true) ?: [];

/**
 * Render embed media dinamis untuk siswa
 */
function renderStudentMediaEmbed($url, $judul = '') {
    if (empty($url)) return '';

    $trimmed = trim($url);
    $mediaInfo = get_media_type_info($trimmed, $judul);
    $displayTitle = $mediaInfo['display'] ?? htmlspecialchars($judul ?: 'Media Pembelajaran');

    // 1. YouTube Video
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $trimmed, $match)) {
        $ytId = $match[1];
        return '
            <div style="background:#fff; border:1px solid var(--line); border-radius:16px; padding:20px; margin-bottom:24px; box-shadow:0 4px 15px rgba(0,0,0,0.03);">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:11px; background:#FEE2E2; color:#DC2626; padding:3px 10px; border-radius:999px; font-weight:700;">
                            ▶️ Video Pembelajaran
                        </span>
                        <strong style="font-size:14px; color:var(--blue-800);">' . htmlspecialchars($displayTitle) . '</strong>
                    </div>
                </div>
                <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:12px; border:1px solid var(--line);">
                    <iframe src="https://www.youtube-nocookie.com/embed/' . htmlspecialchars($ytId) . '" 
                            style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen></iframe>
                </div>
            </div>';
    }

    // 2. PowerPoint
    if (preg_match('/(\.pptx?|\.ppsx?|docs\.google\.com\/presentation|slides\.google\.com|presentation)/i', $trimmed)) {
        $previewUrl = $trimmed;
        if (strpos($trimmed, 'docs.google.com/presentation') !== false && strpos($trimmed, '/embed') === false) {
            $previewUrl = preg_replace('/\/edit.*$/', '/embed', $trimmed);
        }
        return '
            <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:16px; padding:20px; margin-bottom:24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
                    <div style="font-weight:700; color:#B91C1C; font-size:14px; display:flex; align-items:center; gap:8px;">
                        <span>📊</span> ' . htmlspecialchars($displayTitle) . '
                    </div>
                    <a href="' . htmlspecialchars($trimmed) . '" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="font-size:12px; padding:6px 14px; background:#DC2626; border-color:#DC2626;">
                        Buka Slide Presentasi &rarr;
                    </a>
                </div>
                <iframe src="' . htmlspecialchars($previewUrl) . '" width="100%" height="450" style="border:0; border-radius:12px; background:#fff;"></iframe>
            </div>';
    }

    // 3. Dokumen PDF
    if (preg_match('/\.pdf($|\?)/i', $trimmed)) {
        return '
            <div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:16px; padding:20px; margin-bottom:24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
                    <div style="font-weight:700; color:#92400E; font-size:14px; display:flex; align-items:center; gap:8px;">
                        <span>📄</span> ' . htmlspecialchars($displayTitle) . '
                    </div>
                    <a href="' . htmlspecialchars($trimmed) . '" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="font-size:12px; padding:6px 14px;">
                        Unduh / Buka Dokumen &rarr;
                    </a>
                </div>
                <iframe src="' . htmlspecialchars($trimmed) . '" width="100%" height="450" style="border:0; border-radius:12px; background:#fff;"></iframe>
            </div>';
    }

    // 4. Google Drive
    if (strpos($trimmed, 'drive.google.com') !== false) {
        $previewUrl = $trimmed;
        if (strpos($trimmed, '/view') !== false) {
            $previewUrl = str_replace('/view', '/preview', $trimmed);
        }
        return '
            <div style="background:#F0F9FF; border:1px solid #BAE6FD; border-radius:16px; padding:20px; margin-bottom:24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
                    <div style="font-weight:700; color:#0369A1; font-size:14px; display:flex; align-items:center; gap:8px;">
                        <span>📁</span> ' . htmlspecialchars($displayTitle) . '
                    </div>
                    <a href="' . htmlspecialchars($trimmed) . '" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="font-size:12px; padding:5px 12px;">
                        Buka Dokumen Google Drive &rarr;
                    </a>
                </div>
                <iframe src="' . htmlspecialchars($previewUrl) . '" width="100%" height="450" style="border:0; border-radius:12px; background:#fff;"></iframe>
            </div>';
    }

    // 5. Dokumen Word / Office
    if (preg_match('/(\.docx?|\.rtf|\.odt|docs\.google\.com\/document)/i', $trimmed)) {
        return '
            <div style="background:#F0F9FF; border:1px solid #BAE6FD; border-radius:16px; padding:20px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
                <div>
                    <div style="font-weight:700; font-size:14px; color:#0369A1; margin-bottom:4px;">
                        <span>📝</span> ' . htmlspecialchars($displayTitle) . '
                    </div>
                    <div style="font-size:12px; color:var(--muted);">Berkas dokumen pendukung pembelajaran matematika</div>
                </div>
                <a href="' . htmlspecialchars($trimmed) . '" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="font-size:12.5px; padding:7px 16px;">
                    Buka Dokumen &rarr;
                </a>
            </div>';
    }

    // 6. Web Luar / Referensi Eksternal
    return '
        <div style="background:#F8FAFC; border:1px solid var(--line); border-radius:16px; padding:20px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
            <div>
                <div style="font-weight:700; font-size:14px; color:var(--ink); margin-bottom:4px;">
                    <span>' . ($mediaInfo['icon'] ?? '🌐') . '</span> ' . htmlspecialchars($displayTitle) . '
                </div>
                <div style="font-size:12.5px; color:var(--muted);">Sumber pembelajaran dan materi tambahan interaktif</div>
            </div>
            <a href="' . htmlspecialchars($trimmed) . '" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="font-size:13px; padding:8px 16px;">
                Kunjungi Sumber Pembelajaran &rarr;
            </a>
        </div>';
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper" style="max-width: 920px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
      <a href="<?= BASE_URL ?>/siswa/materi.php" class="btn btn-outline" style="font-size: 12.5px; padding: 6px 14px;">
        &larr; Kembali ke Daftar Materi
      </a>
    </div>

    <div class="glass-card" style="padding: 32px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <span class="badge-role" style="font-size: 11px;">Materi Belajar Mandiri #<?= (int)$materi['urutan'] ?></span>
      </div>

      <h1 style="font-size: 26px; font-weight: 800; color: var(--blue-800); margin: 0 0 10px 0;">
        <?= htmlspecialchars($materi['judul']) ?>
      </h1>

      <div style="font-size: 13px; color: var(--muted); margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--line);">
        Disusun oleh: <strong><?= htmlspecialchars($materi['nama_guru'] ?? 'Guru Matematika') ?></strong> &bull; Diterbitkan: <?= date('d M Y', strtotime($materi['created_at'])) ?>
      </div>

      <?php if (!empty($subbabList)): ?>
        <div style="background: #EFF6FF; border-left: 4px solid var(--blue-600); border-radius: 4px; padding: 14px 18px; margin-bottom: 24px;">
          <div style="font-size: 13px; font-weight: 700; color: var(--blue-800); margin-bottom: 6px;">Cakupan Topik Pembahasan:</div>
          <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: var(--ink); line-height: 1.6;">
            <?php foreach ($subbabList as $sb): ?>
              <li><?= htmlspecialchars($sb) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Embed Media Multimedia Dinamis (YouTube, Google Drive, PDF, Web) -->
      <?php 
        $mediaUrl = $materi['media_link'] ?? $materi['youtube_url'];
        echo renderStudentMediaEmbed($mediaUrl, $materi['judul']);
      ?>

      <!-- Konten Utama Materi -->
      <div style="font-size: 15px; line-height: 1.85; color: var(--ink); margin-bottom: 32px;">
        <?= $materi['konten'] ?>
      </div>

      <div style="border-top: 1px solid var(--line); padding-top: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <span style="font-size: 13.5px; color: var(--muted);">
          Sudah memahami materi ini? Lanjutkan latihan mandiri:
        </span>
        <div style="display: flex; gap: 10px;">
          <a href="<?= BASE_URL ?>/siswa/lkpd.php" class="btn btn-outline" style="font-size: 13px;">
            Buka LKPD Mandiri &rarr;
          </a>
          <a href="<?= BASE_URL ?>/siswa/latihan.php" class="btn btn-primary" style="font-size: 13px;">
            Mulai Latihan Soal &rarr;
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
