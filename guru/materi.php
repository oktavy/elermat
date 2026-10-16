<?php
/**
 * Kelola Materi Pelajaran Guru (Tambah, Edit, Hapus, dan Embed Media Multimedia Dinamis)
 * File: guru/materi.php
 * FITUR:
 * 1. Tambah & Edit Materi: Guru dapat memperbarui materi pembelajaran yang telah dibuat.
 * 2. Tautan Dinamis & Tipe File Spesifik: Mendukung YouTube, Dokumen PowerPoint (.pptx), File PDF, 
 *    Dokumen Word (.docx), Spreadsheet (.xlsx), Google Drive, dan Modul Web dengan label spesifik dan ikon visual.
 */
require_once __DIR__ . '/../config/app.php';
require_role(['guru', 'admin']);

$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// Mode Edit Materi
$editId = !empty($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editData = null;
if ($editId > 0) {
    $stmtEdit = $db->prepare("SELECT * FROM materi WHERE id = ?");
    $stmtEdit->execute([$editId]);
    $editData = $stmtEdit->fetch();
}

// Ambil daftar kelas untuk pilihan target kelas
$classesList = $db->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

// Tambah / Perbarui Materi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_materi') {
    $materiId   = !empty($_POST['materi_id']) ? (int)$_POST['materi_id'] : 0;
    $judul      = trim($_POST['judul'] ?? '');
    $deskripsi  = trim($_POST['deskripsi'] ?? '');
    $kelas      = trim($_POST['kelas'] ?? 'Semua Kelas');
    $konten     = trim($_POST['konten'] ?? '');
    $mediaLink  = trim($_POST['media_link'] ?? '');
    $subbabRaw  = trim($_POST['subbab'] ?? '');
    $urutan     = !empty($_POST['urutan']) ? (int)$_POST['urutan'] : 1;

    $subbabArr = array_filter(array_map('trim', explode("\n", $subbabRaw)));
    $subbabJson = json_encode(array_values($subbabArr));

    if (empty($judul) || empty($konten)) {
        flash_set('danger', 'Judul materi dan konten materi pembelajaran wajib diisi.');
        redirect($materiId ? 'guru/materi.php?edit=' . $materiId : 'guru/materi.php');
    } else {
        if ($materiId > 0) {
            // Update Materi
            $upd = $db->prepare("
                UPDATE materi 
                SET judul = ?, deskripsi = ?, subbab = ?, konten = ?, youtube_url = ?, media_link = ?, urutan = ?, kelas = ?
                WHERE id = ?
            ");
            $upd->execute([$judul, $deskripsi, $subbabJson, $konten, $mediaLink, $mediaLink, $urutan, $kelas, $materiId]);
            flash_set('success', "Materi pembelajaran '{$judul}' berhasil diperbarui!");
        } else {
            // Tambah Materi Baru
            $ins = $db->prepare("
                INSERT INTO materi (guru_id, judul, deskripsi, subbab, konten, youtube_url, media_link, urutan, kelas, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $ins->execute([$currentUser['id'], $judul, $deskripsi, $subbabJson, $konten, $mediaLink, $mediaLink, $urutan, $kelas]);
            flash_set('success', "Materi pembelajaran '{$judul}' berhasil ditambahkan!");
        }
        redirect('guru/materi.php');
    }
}

// Hapus Materi
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM materi WHERE id = ?");
    $stmtDel->execute([$delId]);
    flash_set('success', 'Materi berhasil dihapus dari sistem.');
    redirect('guru/materi.php');
}

// Ambil seluruh daftar materi
$stmt = $db->query("SELECT * FROM materi ORDER BY urutan ASC, id DESC");
$materials = $stmt->fetchAll();

/**
 * Render visual badge & embed multimedia interaktif responsif
 */
function renderMediaBadgeAndEmbed($url, $title = '') {
    if (empty(trim($url ?? ''))) {
        return [
            'badge' => '<span style="color:var(--muted); font-size:12px;">Tanpa Media</span>', 
            'embed' => ''
        ];
    }

    $trimmed = trim($url);
    $mediaInfo = get_media_type_info($trimmed, $title);

    if (!$mediaInfo) {
        return [
            'badge' => '<span style="color:var(--muted); font-size:12px;">Tautan Tidak Dikenal</span>', 
            'embed' => ''
        ];
    }

    $badge = '<span style="font-size:11.5px; background:' . $mediaInfo['bg'] . '; color:' . $mediaInfo['color'] . '; padding:4px 10px; border-radius:999px; font-weight:700; display:inline-flex; align-items:center; gap:5px;">'
        . htmlspecialchars($mediaInfo['badge']) . 
    '</span>';

    $embed = '';

    // 1. YouTube Video Embed
    if ($mediaInfo['type'] === 'youtube' && preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $trimmed, $match)) {
        $ytId = $match[1];
        $embed = '
            <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:12px; margin-top:12px; border:1px solid var(--line);">
                <iframe src="https://www.youtube-nocookie.com/embed/' . htmlspecialchars($ytId) . '" 
                        style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen></iframe>
            </div>';
    }
    // 2. PowerPoint (.pptx / Google Slides)
    elseif ($mediaInfo['type'] === 'powerpoint') {
        $previewUrl = $trimmed;
        if (strpos($trimmed, 'docs.google.com/presentation') !== false && strpos($trimmed, '/embed') === false) {
            $previewUrl = preg_replace('/\/edit.*$/', '/embed', $trimmed);
        }
        $embed = '
            <div style="margin-top:12px; border:1px solid #FECACA; border-radius:12px; overflow:hidden; background:#FEF2F2; padding:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                    <div style="font-weight:700; color:#B91C1C; font-size:13px; display:flex; align-items:center; gap:6px;">
                        <span>📊</span> Dokumen Presentasi PowerPoint (.pptx)
                    </div>
                    <a href="' . htmlspecialchars($trimmed) . '" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="font-size:11.5px; padding:4px 10px; background:#DC2626; border-color:#DC2626;">
                        Buka Slide &rarr;
                    </a>
                </div>
                <iframe src="' . htmlspecialchars($previewUrl) . '" width="100%" height="340" style="border:0; border-radius:8px; background:#fff;"></iframe>
            </div>';
    }
    // 3. File PDF (.pdf)
    elseif ($mediaInfo['type'] === 'pdf') {
        $embed = '
            <div style="margin-top:12px; border:1px solid #FDE68A; border-radius:12px; overflow:hidden; background:#FFFBEB; padding:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                    <div style="font-weight:700; color:#92400E; font-size:13px; display:flex; align-items:center; gap:6px;">
                        <span>📄</span> Berkas File PDF
                    </div>
                    <a href="' . htmlspecialchars($trimmed) . '" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="font-size:11.5px; padding:4px 10px; background:#D97706; border-color:#D97706;">
                        Buka / Unduh File PDF &rarr;
                    </a>
                </div>
                <iframe src="' . htmlspecialchars($trimmed) . '" width="100%" height="340" style="border:0; border-radius:8px; background:#fff;"></iframe>
            </div>';
    }
    // 4. Google Drive
    elseif ($mediaInfo['type'] === 'gdrive') {
        $previewUrl = $trimmed;
        if (strpos($trimmed, '/view') !== false) {
            $previewUrl = str_replace('/view', '/preview', $trimmed);
        }
        $embed = '
            <div style="margin-top:12px; border:1px solid #BAE6FD; border-radius:12px; overflow:hidden; background:#F0F9FF; padding:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                    <div style="font-weight:700; color:#0369A1; font-size:13px; display:flex; align-items:center; gap:6px;">
                        <span>📁</span> Berkas Google Drive
                    </div>
                    <a href="' . htmlspecialchars($trimmed) . '" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="font-size:11.5px; padding:4px 10px;">
                        Buka di Tab Baru &rarr;
                    </a>
                </div>
                <iframe src="' . htmlspecialchars($previewUrl) . '" width="100%" height="340" style="border:0; border-radius:8px; background:#fff;"></iframe>
            </div>';
    }
    // 5. Dokumen Word / Spreadsheet
    elseif (in_array($mediaInfo['type'], ['document', 'spreadsheet'])) {
        $embed = '
            <div style="margin-top:12px; border:1px solid #BAE6FD; border-radius:12px; background:#F0F9FF; padding:14px 16px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                <div>
                    <div style="font-weight:700; font-size:13px; color:#0369A1; display:flex; align-items:center; gap:6px;">
                        <span>' . $mediaInfo['icon'] . '</span> ' . htmlspecialchars($mediaInfo['name']) . '
                    </div>
                    <div style="font-size:11.5px; color:var(--muted); margin-top:2px; word-break:break-all;">' . htmlspecialchars($trimmed) . '</div>
                </div>
                <a href="' . htmlspecialchars($trimmed) . '" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="font-size:12px; padding:6px 14px;">
                    Buka Berkas Dokumen &rarr;
                </a>
            </div>';
    }
    // 6. Modul Web / Lainnya
    else {
        $embed = '
            <div style="margin-top:12px; border:1px solid var(--line); border-radius:12px; background:#F8FAFC; padding:14px 16px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                <div>
                    <div style="font-weight:700; font-size:13px; color:var(--ink); display:flex; align-items:center; gap:6px;">
                        <span>' . $mediaInfo['icon'] . '</span> ' . htmlspecialchars($mediaInfo['name']) . '
                    </div>
                    <div style="font-size:11.5px; color:var(--muted); margin-top:2px; word-break:break-all;">' . htmlspecialchars($trimmed) . '</div>
                </div>
                <a href="' . htmlspecialchars($trimmed) . '" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="font-size:12px; padding:6px 14px;">
                    Kunjungi Sumber &rarr;
                </a>
            </div>';
    }

    return ['badge' => $badge, 'embed' => $embed];
}

$pageTitle = 'Kelola Materi Pembelajaran - ElearnMath Guru';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
      <div>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 4px 0;">
          Kelola Materi Pembelajaran Multimedia
        </h1>
        <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
          Susun, perbarui, dan bagikan materi pembelajaran matematika interaktif dengan dukungan tautan dinamis spesifik (Video YouTube, Dokumen PowerPoint, File PDF, Dokumen Word, dan Modul Web).
        </p>
      </div>
      <?php if ($editData): ?>
        <a href="<?= BASE_URL ?>/guru/materi.php" class="btn btn-outline" style="font-size: 13px;">
          &larr; Batalkan Mode Edit (Buat Baru)
        </a>
      <?php endif; ?>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- Formulir Tambah / Edit Materi -->
    <div class="glass-card" style="margin-bottom: 24px; border: <?= $editData ? '2px solid var(--blue-600)' : '1px solid var(--glass-border)' ?>;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 17px; font-weight: 800; color: var(--blue-800); margin: 0;">
          <?= $editData ? '✏️ Edit Materi Pembelajaran #' . $editData['id'] : '➕ Tambah Materi Pelajaran Baru' ?>
        </h2>
        <?php if ($editData): ?>
          <span style="background: #EFF6FF; color: var(--blue-800); font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 999px;">
            Sedang Mengedit: <?= htmlspecialchars($editData['judul']) ?>
          </span>
        <?php endif; ?>
      </div>

      <form method="POST" action="">
        <input type="hidden" name="action" value="save_materi">
        <?php if ($editData): ?>
          <input type="hidden" name="materi_id" value="<?= $editData['id'] ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; margin-bottom: 14px;">
          <div>
            <label class="form-label">Judul Materi / Bab <span style="color:var(--danger)">*</span></label>
            <input type="text" name="judul" class="form-input" 
                   value="<?= htmlspecialchars($editData['judul'] ?? '') ?>" 
                   placeholder="Contoh: Statistika & Ukuran Pemusatan Data" required>
          </div>
          <div>
            <label class="form-label">Target Kelas</label>
            <select name="kelas" class="form-input">
              <option value="Semua Kelas" <?= (($editData['kelas'] ?? 'Semua Kelas') === 'Semua Kelas') ? 'selected' : '' ?>>Semua Kelas</option>
              <?php foreach ($classesList as $cl): ?>
                <option value="<?= htmlspecialchars($cl) ?>" <?= (($editData['kelas'] ?? '') === $cl) ? 'selected' : '' ?>>
                  Kelas <?= htmlspecialchars($cl) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Nomor Urutan Bab</label>
            <input type="number" name="urutan" class="form-input" 
                   value="<?= htmlspecialchars($editData['urutan'] ?? (count($materials) + 1)) ?>" min="1" required>
          </div>
        </div>

        <div style="margin-bottom: 14px;">
          <label class="form-label">Tautan Pendukung / Media Pembelajaran (YouTube, PowerPoint, PDF, Word, Modul Web)</label>
          <input type="url" name="media_link" class="form-input" 
                 value="<?= htmlspecialchars($editData['media_link'] ?? ($editData['youtube_url'] ?? '')) ?>" 
                 placeholder="Tempel tautan materi: YouTube, Slide PowerPoint, File PDF, Dokumen Word, atau Modul Web">
          <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">
            Sistem secara otomatis mendeteksi tipe konten secara spesifik: <strong>Dokumen PowerPoint (.pptx)</strong>, <strong>File PDF</strong>, <strong>Video YouTube</strong>, <strong>Dokumen Word (.docx)</strong>, <strong>Spreadsheet Excel (.xlsx)</strong>, atau <strong>Modul Web</strong> lengkap dengan ikon visual.
          </small>
        </div>

        <div style="margin-bottom: 14px;">
          <label class="form-label">Deskripsi Singkat</label>
          <input type="text" name="deskripsi" class="form-input" 
                 value="<?= htmlspecialchars($editData['deskripsi'] ?? '') ?>" 
                 placeholder="Ringkasan singkat bahasan materi pembelajaran...">
        </div>

        <div style="margin-bottom: 14px;">
          <label class="form-label">Daftar Subtopik Pembahasan (Tulis satu per baris)</label>
          <?php 
            $subbabText = '';
            if (!empty($editData['subbab'])) {
                $subArr = json_decode($editData['subbab'], true);
                if (is_array($subArr)) {
                    $subbabText = implode("\n", $subArr);
                }
            }
          ?>
          <textarea name="subbab" rows="3" class="form-input" style="width: 100%;" 
                    placeholder="Contoh:&#10;Pengertian Rata-rata (Mean)&#10;Menentukan Median Data Ganjil & Genap&#10;Modus dalam Tabel Frekuensi"><?= htmlspecialchars($subbabText) ?></textarea>
        </div>

        <div style="margin-bottom: 20px;">
          <label class="form-label">Uraian Konten Materi Lengkap (Format HTML / Teks Didukung) <span style="color:var(--danger)">*</span></label>
          <textarea name="konten" rows="8" class="form-input" style="width: 100%; font-family: inherit; line-height: 1.6;" 
                    placeholder="Tuliskan uraian penjelasan materi konsep matematika secara lengkap..." required><?= htmlspecialchars($editData['konten'] ?? '') ?></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
          <?php if ($editData): ?>
            <a href="<?= BASE_URL ?>/guru/materi.php" class="btn btn-outline">Batalkan Edit</a>
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
              <i data-feather="check" style="width: 16px; height: 16px;"></i> Simpan Perubahan Materi
            </button>
          <?php else: ?>
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
              <i data-feather="plus" style="width: 16px; height: 16px;"></i> Terbitkan Materi Baru
            </button>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Pratinjau Media yang Diedit (Jika Ada) -->
    <?php if ($editData && (!empty($editData['youtube_url']) || !empty($editData['media_link']))): ?>
      <?php 
        $mediaPreview = renderMediaBadgeAndEmbed($editData['media_link'] ?? $editData['youtube_url'], $editData['judul']);
      ?>
      <div class="glass-card" style="margin-bottom: 24px;">
        <h3 style="font-size: 15px; font-weight: 800; color: var(--blue-800); margin: 0 0 8px 0; display: flex; align-items: center; gap: 8px;">
          Pratinjau Media Materi: <?= $mediaPreview['badge'] ?>
        </h3>
        <?= $mediaPreview['embed'] ?>
      </div>
    <?php endif; ?>

    <!-- Daftar Seluruh Materi -->
    <div class="glass-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 17px; font-weight: 800; color: var(--blue-800); margin: 0;">
          Daftar Materi Pembelajaran (<?= count($materials) ?>)
        </h2>
      </div>

      <div class="data-table-container">
        <table class="table-custom">
          <thead>
            <tr>
              <th style="width: 50px;">Urutan</th>
              <th>Judul Materi</th>
              <th>Target Kelas</th>
              <th>Jenis Tipe Media Tautan</th>
              <th>Tanggal Dibuat</th>
              <th style="text-align: center; width: 140px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($materials)): ?>
              <tr>
                <td colspan="6" style="text-align: center; color: var(--muted); padding: 32px;">
                  Belum ada materi pembelajaran yang ditambahkan. Silakan gunakan form di atas untuk menerbitkan materi baru.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($materials as $m): ?>
                <?php 
                  $link = $m['media_link'] ?? $m['youtube_url'];
                  $mediaData = renderMediaBadgeAndEmbed($link, $m['judul']);
                ?>
                <tr style="<?= ($editId == $m['id']) ? 'background: #EFF6FF;' : '' ?>">
                  <td style="text-align: center; font-weight: 800; color: var(--blue-800);">
                    #<?= (int)$m['urutan'] ?>
                  </td>
                  <td>
                    <strong><?= htmlspecialchars($m['judul']) ?></strong>
                    <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">
                      <?= htmlspecialchars(mb_strimwidth($m['deskripsi'] ?: 'Uraian modul pembelajaran matematika.', 0, 75, '...')) ?>
                    </div>
                    <?php if ($editId == $m['id']): ?>
                      <span style="font-size: 10.5px; color: var(--blue-600); font-weight: 700; display: block; margin-top: 2px;">(Sedang Diedit)</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge-role" style="font-size: 11px; background: #EFF6FF; color: var(--blue-800);">
                      <?= htmlspecialchars($m['kelas'] ?? 'Semua Kelas') ?>
                    </span>
                  </td>
                  <td><?= $mediaData['badge'] ?></td>
                  <td style="font-size: 12px; color: var(--muted);"><?= date('d M Y', strtotime($m['created_at'])) ?></td>
                  <td style="text-align: center;">
                    <div style="display: flex; gap: 6px; justify-content: center;">
                      <a href="<?= BASE_URL ?>/guru/materi.php?edit=<?= $m['id'] ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 12px;">
                        <i data-feather="edit-2" style="width: 12px; height: 12px;"></i> Edit
                      </a>
                      <a href="<?= BASE_URL ?>/guru/materi.php?delete=<?= $m['id'] ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Apakah Anda yakin ingin menghapus materi ini?')">
                        <i data-feather="trash-2" style="width: 12px; height: 12px;"></i> Hapus
                      </a>
                    </div>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
