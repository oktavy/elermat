<?php
/**
 * Edit Profil Administrator
 * File: admin/profile.php
 * FITUR: Kelola profil lengkap admin beserta upload foto profil dan ganti kata sandi.
 */
require_once __DIR__ . '/../config/app.php';
require_role('admin');

$pageTitle = 'Edit Profil Admin - ElearnMath';
$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// Ambil data admin terbaru dari DB
$stmtA = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmtA->execute([$currentUser['id']]);
$admin = $stmtA->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
    $noHp        = trim($_POST['no_hp'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPass = trim($_POST['confirm_password'] ?? '');

    if (empty($namaLengkap)) {
        flash_set('danger', 'Nama lengkap tidak boleh kosong.');
    } else {
        $fotoPath = $admin['foto'];

        // Proses Upload Foto Profil
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $fileInfo = pathinfo($_FILES['foto']['name']);
            $ext = strtolower($fileInfo['extension'] ?? '');

            if (!in_array($ext, $allowedExts)) {
                flash_set('danger', 'Format foto harus berupa JPG, PNG, atau WEBP.');
                redirect('admin/profile.php');
            } else {
                $uploadDir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = 'admin_' . $admin['id'] . '_' . time() . '.' . $ext;
                $targetFile = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
                    $fotoPath = 'assets/uploads/' . $filename;
                }
            }
        }

        // Cek ganti password
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                flash_set('danger', 'Kata sandi baru minimal 6 karakter.');
                redirect('admin/profile.php');
            } elseif ($newPassword !== $confirmPass) {
                flash_set('danger', 'Konfirmasi kata sandi baru tidak cocok.');
                redirect('admin/profile.php');
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updPass = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updPass->execute([$hash, $admin['id']]);
            }
        }

        // Simpan Data Profil
        $upd = $db->prepare("UPDATE users SET nama_lengkap = ?, no_hp = ?, foto = ? WHERE id = ?");
        $upd->execute([$namaLengkap, $noHp, $fotoPath, $admin['id']]);

        // Perbarui session
        $_SESSION['user']['nama_lengkap'] = $namaLengkap;
        $_SESSION['user']['foto'] = $fotoPath;

        flash_set('success', 'Profil Administrator berhasil diperbarui!');
        redirect('admin/profile.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper" style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
      <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 6px;">
        Akun Administrator
      </span>
      <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
        Pengaturan Profil Administrator
      </h1>
      <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
        Perbarui informasi profil admin, unggah foto resmi, dan ubah kata sandi akun sistem.
      </p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="glass-card">
      <form method="POST" action="" enctype="multipart/form-data">
        <div style="display: flex; gap: 24px; align-items: center; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--line); flex-wrap: wrap;">
          <div>
            <?php if (!empty($admin['foto'])): ?>
              <img id="avatarPreview" src="<?= BASE_URL . '/' . htmlspecialchars($admin['foto']) ?>" alt="Foto Admin" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--blue-600); box-shadow: var(--glass-shadow);">
            <?php else: ?>
              <div id="avatarFallback" style="width: 100px; height: 100px; border-radius: 50%; background: var(--blue-600); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: 800;">
                <?= strtoupper(substr($admin['nama_lengkap'], 0, 1)) ?>
              </div>
            <?php endif; ?>
          </div>

          <div style="flex: 1;">
            <label style="font-size: 13px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 6px;">Ganti Foto Profil</label>
            <input type="file" name="foto" id="fotoInput" accept="image/*" class="form-input" style="padding: 8px;">
            <div style="font-size: 11.5px; color: var(--muted); margin-top: 4px;">
              Format: JPG, PNG, atau WEBP. Maksimal ukuran 3MB.
            </div>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
          <div class="form-group">
            <label class="form-label">Nama Lengkap Admin</label>
            <input type="text" name="nama_lengkap" class="form-input" value="<?= htmlspecialchars($admin['nama_lengkap']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Alamat Email (Resmi Admin)</label>
            <input type="email" class="form-input" value="<?= htmlspecialchars($admin['email']) ?>" readonly style="background: #F1F5F9; cursor: not-allowed;" title="Email akun admin resmi dikunci untuk keamanan sistem">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
          <div class="form-group">
            <label class="form-label">Username Admin</label>
            <input type="text" class="form-input" value="<?= htmlspecialchars($admin['username']) ?>" readonly style="background: #F1F5F9; cursor: not-allowed;">
          </div>

          <div class="form-group">
            <label class="form-label">Nomor HP / WhatsApp</label>
            <input type="tel" name="no_hp" class="form-input" value="<?= htmlspecialchars($admin['no_hp'] ?: '') ?>" placeholder="08123456789">
          </div>
        </div>

        <div style="background: #F8FAFC; border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 16px; margin-top: 20px; margin-bottom: 24px;">
          <h4 style="font-size: 14px; font-weight: 700; color: var(--blue-800); margin: 0 0 10px 0;">
            Ganti Kata Sandi (Opsional)
          </h4>
          <p style="font-size: 12px; color: var(--muted); margin: 0 0 12px 0;">
            Biarkan kosong jika Anda tidak ingin mengubah kata sandi akun admin ini.
          </p>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
              <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px;">Kata Sandi Baru</label>
              <input type="password" name="new_password" class="form-input" placeholder="Minimal 6 karakter" autocomplete="new-password">
            </div>
            <div>
              <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px;">Konfirmasi Kata Sandi Baru</label>
              <input type="password" name="confirm_password" class="form-input" placeholder="Ketik ulang sandi baru" autocomplete="new-password">
            </div>
          </div>
        </div>

        <div style="text-align: right;">
          <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 14px;">
            <i data-feather="save"></i> Simpan Perubahan Profil
          </button>
        </div>
      </form>
    </div>

  </div>
</div>

<script>
  document.getElementById('fotoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(evt) {
        let preview = document.getElementById('avatarPreview');
        if (!preview) {
          const fallback = document.getElementById('avatarFallback');
          preview = document.createElement('img');
          preview.id = 'avatarPreview';
          preview.style.width = '100px';
          preview.style.height = '100px';
          preview.style.borderRadius = '50%';
          preview.style.objectFit = 'cover';
          preview.style.border = '3px solid var(--blue-600)';
          fallback.parentNode.replaceChild(preview, fallback);
        }
        preview.src = evt.target.result;
      };
      reader.readAsDataURL(file);
    }
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
