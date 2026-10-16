<?php
/**
 * Edit Profil Siswa dengan Foto (Upload File & Kamera Langsung Webcam/HP)
 * File: siswa/profile.php
 * FITUR SISWA: Edit profil lengkap dengan dukungan 2 cara pasang foto:
 * 1. Upload file gambar dari perangkat
 * 2. Ambil foto langsung melalui Kamera Webcam / Kamera HP
 */
require_once __DIR__ . '/../config/app.php';
require_role('siswa');

$pageTitle = 'Profil & Foto Siswa - ElearnMath';
$currentUser = auth_user();
$db = getDB();
$flash = flash_get();

// Ambil data siswa terbaru dari DB
$stmtS = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmtS->execute([$currentUser['id']]);
$siswa = $stmtS->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $noHp        = trim($_POST['no_hp'] ?? '');
    $alamat      = trim($_POST['alamat'] ?? '');
    $asalSekolah = trim($_POST['asal_sekolah'] ?? '');
    $namaWali    = trim($_POST['nama_wali'] ?? '');
    $jenisKelamin= $_POST['jenis_kelamin'] ?? 'Laki-laki';
    $agama       = trim($_POST['agama'] ?? 'Islam');
    $webcamPhoto = trim($_POST['webcam_photo'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPass = trim($_POST['confirm_password'] ?? '');

    if (empty($namaLengkap) || empty($email)) {
        flash_set('danger', 'Nama lengkap dan Email wajib diisi.');
    } else {
        $fotoPath = $siswa['foto'];
        $uploadDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // CARA 1: Dari Pengambilan Foto Kamera Webcam / HP (Base64)
        if (!empty($webcamPhoto) && preg_match('/^data:image\/(\w+);base64,/', $webcamPhoto, $type)) {
            $data = substr($webcamPhoto, strpos($webcamPhoto, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, jpeg
            if (in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                $decoded = base64_decode($data);
                if ($decoded !== false) {
                    $filename = 'siswa_cam_' . $siswa['id'] . '_' . time() . '.' . $type;
                    file_put_contents($uploadDir . $filename, $decoded);
                    $fotoPath = 'assets/uploads/' . $filename;
                }
            }
        }
        // CARA 2: Dari Unggah File Biasa (File Upload)
        elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $fileInfo = pathinfo($_FILES['foto']['name']);
            $ext = strtolower($fileInfo['extension'] ?? '');

            if (!in_array($ext, $allowedExts)) {
                flash_set('danger', 'Format foto harus berupa JPG, PNG, atau WEBP.');
                redirect('siswa/profile.php');
            } else {
                $filename = 'siswa_file_' . $siswa['id'] . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $filename)) {
                    $fotoPath = 'assets/uploads/' . $filename;
                }
            }
        }

        // Update password jika diisi
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                flash_set('danger', 'Kata sandi baru minimal 6 karakter.');
                redirect('siswa/profile.php');
            } elseif ($newPassword !== $confirmPass) {
                flash_set('danger', 'Konfirmasi kata sandi tidak cocok.');
                redirect('siswa/profile.php');
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updPass = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updPass->execute([$hash, $siswa['id']]);
            }
        }

        // Simpan data profil
        $upd = $db->prepare("
            UPDATE users 
            SET nama_lengkap = ?, email = ?, no_hp = ?, alamat = ?, asal_sekolah = ?, 
                nama_wali = ?, jenis_kelamin = ?, agama = ?, foto = ? 
            WHERE id = ?
        ");
        $upd->execute([
            $namaLengkap, $email, $noHp, $alamat, $asalSekolah, 
            $namaWali, $jenisKelamin, $agama, $fotoPath, $siswa['id']
        ]);

        // Perbarui session
        $_SESSION['user']['nama_lengkap'] = $namaLengkap;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['foto'] = $fotoPath;

        flash_set('success', 'Profil dan foto Anda berhasil diperbarui!');
        redirect('siswa/profile.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div class="content-wrapper" style="max-width: 860px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
      <span class="badge-role" style="background: var(--blue-100); color: var(--blue-800); margin-bottom: 6px;">
        Profil Peserta Didik
      </span>
      <h1 style="font-size: 24px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">
        Pengaturan Profil & Foto Siswa
      </h1>
      <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
        Lengkapi profil diri Anda dan gunakan foto profil terbaru. Anda dapat mengunggah file foto atau memotret langsung dengan kamera perangkat Anda.
      </p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 20px;">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="glass-card">
      <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="webcam_photo" id="hiddenWebcamPhoto">

        <!-- Area Foto Profil & Pilihan Kamera / Upload -->
        <div style="display: flex; gap: 24px; align-items: center; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--line); flex-wrap: wrap;">
          <div>
            <?php if (!empty($siswa['foto'])): ?>
              <img id="avatarPreview" src="<?= BASE_URL . '/' . htmlspecialchars($siswa['foto']) ?>" alt="Foto Profil" style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 3px solid var(--blue-600); box-shadow: var(--glass-shadow);">
            <?php else: ?>
              <div id="avatarFallback" style="width: 110px; height: 110px; border-radius: 50%; background: var(--blue-600); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: 800;">
                <?= strtoupper(substr($siswa['nama_lengkap'], 0, 1)) ?>
              </div>
            <?php endif; ?>
          </div>

          <div style="flex: 1; min-width: 250px;">
            <label style="font-size: 13.5px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 8px;">
              Pilihan Pemasangan Foto Profil:
            </label>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;">
              <!-- Tombol Cara 2: Ambil Foto Langsung Kamera -->
              <button type="button" class="btn btn-primary" id="btnOpenCamModal" style="font-size: 12.5px; padding: 8px 16px;">
                <i data-feather="camera" style="width: 15px; height: 15px;"></i> Ambil Foto via Kamera
              </button>

              <label class="btn btn-outline" style="font-size: 12.5px; padding: 8px 16px; cursor: pointer;">
                <i data-feather="upload" style="width: 15px; height: 15px;"></i> Pilih File dari HP/Laptop
                <input type="file" name="foto" id="fotoInput" accept="image/*" style="display: none;">
              </label>
            </div>

            <div style="font-size: 12px; color: var(--muted);">
              Mendukung foto selfie langsung kamera webcam laptop atau kamera ponsel, serta format JPG, PNG, WEBP.
            </div>
          </div>
        </div>

        <!-- Data Siswa -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
          <div class="form-group">
            <label class="form-label">Nama Lengkap Siswa</label>
            <input type="text" name="nama_lengkap" class="form-input" value="<?= htmlspecialchars($siswa['nama_lengkap']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Alamat Email</label>
            <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($siswa['email']) ?>" required>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
          <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" class="form-input" value="<?= htmlspecialchars($siswa['username']) ?>" readonly style="background: #F1F5F9; cursor: not-allowed;">
          </div>

          <div class="form-group">
            <label class="form-label">Kelas Terdaftar</label>
            <input type="text" class="form-input" value="Kelas <?= htmlspecialchars($siswa['kelas'] ?: '-') ?>" readonly style="background: #F1F5F9; cursor: not-allowed; font-weight: 700; color: var(--blue-800);">
          </div>

          <div class="form-group">
            <label class="form-label">NISN Siswa</label>
            <input type="text" class="form-input" value="<?= htmlspecialchars($siswa['nisn'] ?: '-') ?>" readonly style="background: #F1F5F9; cursor: not-allowed;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
          <div class="form-group">
            <label class="form-label">Nomor WhatsApp / HP</label>
            <input type="tel" name="no_hp" class="form-input" value="<?= htmlspecialchars($siswa['no_hp'] ?: '') ?>" placeholder="08123456789">
          </div>

          <div class="form-group">
            <label class="form-label">Jenis Kelamin</label>
            <select name="jenis_kelamin" class="form-select">
              <option value="Laki-laki" <?= $siswa['jenis_kelamin'] === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
              <option value="Perempuan" <?= $siswa['jenis_kelamin'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
            </select>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
          <div class="form-group">
            <label class="form-label">Asal Sekolah</label>
            <input type="text" name="asal_sekolah" class="form-input" value="<?= htmlspecialchars($siswa['asal_sekolah'] ?: '') ?>" placeholder="Contoh: SMP Negeri 1 Malang">
          </div>

          <div class="form-group">
            <label class="form-label">Nama Orang Tua / Wali</label>
            <input type="text" name="nama_wali" class="form-input" value="<?= htmlspecialchars($siswa['nama_wali'] ?: '') ?>" placeholder="Nama Orang Tua">
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
          <label class="form-label">Alamat Domisili Siswa</label>
          <textarea name="alamat" rows="2" class="form-input" placeholder="Alamat lengkap tempat tinggal..."><?= htmlspecialchars($siswa['alamat'] ?: '') ?></textarea>
        </div>

        <!-- Ganti Password -->
        <div style="background: #F8FAFC; border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 16px; margin-bottom: 24px;">
          <h4 style="font-size: 14px; font-weight: 700; color: var(--blue-800); margin: 0 0 10px 0;">
            Ganti Kata Sandi (Opsional)
          </h4>
          <p style="font-size: 12px; color: var(--muted); margin: 0 0 12px 0;">
            Kosongkan kolom ini jika Anda tidak ingin memperbarui kata sandi login.
          </p>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
              <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px;">Kata Sandi Baru</label>
              <input type="password" name="new_password" class="form-input" placeholder="Minimal 6 karakter" autocomplete="new-password">
            </div>
            <div>
              <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px;">Ulangi Kata Sandi Baru</label>
              <input type="password" name="confirm_password" class="form-input" placeholder="Ketik ulang kata sandi" autocomplete="new-password">
            </div>
          </div>
        </div>

        <div style="text-align: right;">
          <button type="submit" class="btn btn-primary" style="padding: 10px 26px; font-size: 14px; font-weight: 700;">
            <i data-feather="save"></i> Simpan Profil Siswa
          </button>
        </div>
      </form>
    </div>

  </div>
</div>

<!-- Modal Kamera Webcam HTML5 / HP -->
<div id="camModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.7); backdrop-filter: blur(6px); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
  <div class="glass-card" style="background: #fff; width: 100%; max-width: 520px; padding: 24px; border-radius: var(--radius); text-align: center;">
    <h3 style="font-size: 18px; font-weight: 800; color: var(--blue-800); margin: 0 0 14px 0;">
      📸 Ambil Foto Profil Langsung
    </h3>

    <div style="position: relative; width: 100%; max-width: 380px; height: 280px; margin: 0 auto 16px auto; background: #000; border-radius: 16px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
      <video id="webcamVideo" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
      <canvas id="webcamCanvas" style="display: none;"></canvas>
    </div>

    <div style="display: flex; justify-content: center; gap: 12px;">
      <button type="button" class="btn btn-outline" id="btnCloseCam" style="padding: 8px 18px;">
        Batal
      </button>
      <button type="button" class="btn btn-success" id="btnCapturePhoto" style="padding: 8px 24px; font-weight: 700;">
        <i data-feather="aperture"></i> Jepret Foto
      </button>
    </div>
  </div>
</div>

<script>
  // Preview foto dari file upload
  document.getElementById('fotoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(evt) {
        updateAvatarDisplay(evt.target.result);
      };
      reader.readAsDataURL(file);
    }
  });

  function updateAvatarDisplay(src) {
    let preview = document.getElementById('avatarPreview');
    if (!preview) {
      const fallback = document.getElementById('avatarFallback');
      preview = document.createElement('img');
      preview.id = 'avatarPreview';
      preview.style.width = '110px';
      preview.style.height = '110px';
      preview.style.borderRadius = '50%';
      preview.style.objectFit = 'cover';
      preview.style.border = '3px solid var(--blue-600)';
      fallback.parentNode.replaceChild(preview, fallback);
    }
    preview.src = src;
  }

  // Integrasi Kamera Webcam
  const btnOpenCam = document.getElementById('btnOpenCamModal');
  const camModal = document.getElementById('camModal');
  const btnCloseCam = document.getElementById('btnCloseCam');
  const btnCapturePhoto = document.getElementById('btnCapturePhoto');
  const videoElem = document.getElementById('webcamVideo');
  const canvasElem = document.getElementById('webcamCanvas');
  const hiddenPhotoInput = document.getElementById('hiddenWebcamPhoto');

  let streamTrack = null;

  btnOpenCam.addEventListener('click', async function () {
    camModal.style.display = 'flex';
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: { width: { ideal: 640 }, height: { ideal: 640 }, facingMode: 'user' },
        audio: false
      });
      videoElem.srcObject = stream;
      streamTrack = stream.getTracks()[0];
    } catch (err) {
      alert('Gagal mengakses kamera: Pastikan izin kamera aktif pada peramban Anda.');
      camModal.style.display = 'none';
    }
  });

  function stopCamera() {
    if (streamTrack) {
      streamTrack.stop();
      streamTrack = null;
    }
    if (videoElem.srcObject) {
      videoElem.srcObject = null;
    }
    camModal.style.display = 'none';
  }

  btnCloseCam.addEventListener('click', stopCamera);

  btnCapturePhoto.addEventListener('click', function () {
    const ctx = canvasElem.getContext('2d');
    canvasElem.width = videoElem.videoWidth || 480;
    canvasElem.height = videoElem.videoHeight || 480;
    ctx.drawImage(videoElem, 0, 0, canvasElem.width, canvasElem.height);

    const dataUrl = canvasElem.toDataURL('image/jpeg', 0.9);
    hiddenPhotoInput.value = dataUrl;
    updateAvatarDisplay(dataUrl);

    stopCamera();
    alert('Foto berhasil dijepret! Tekan tombol "Simpan Profil Siswa" di bagian bawah untuk menyimpan perubahan.');
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
