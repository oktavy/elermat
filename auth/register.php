<?php
/**
 * Halaman Registrasi Siswa & Guru
 * File: auth/register.php
 * FITUR:
 * 1. Alur Guru: Mendaftar akun tanpa memilih kelas, langsung aktif dan masuk ke Dashboard Guru setelah login.
 *    Pemilihan/pembuatan kelas dilakukan di Dashboard Guru dan menunggu persetujuan admin (Admit).
 * 2. Alur Siswa: Memilih kelas tujuan, langsung masuk ke Dashboard Siswa setelah login.
 *    Fitur isi kelas terkunci hingga disetujui (Admit) Admin.
 * 3. Tampilan terpadu dengan cover/landing visual interaktif tanpa kredensial demo / informasi admin resmi di publik.
 */
require_once __DIR__ . '/../config/app.php';

// Jika sudah login, arahkan ke dashboard masing-masing
if (is_logged_in()) {
    $role = $_SESSION['user']['role'] ?? 'siswa';
    redirect($role . '/index.php');
}

$error = '';
$success = '';
$flash = flash_get();
$db = getDB();

// Ambil daftar kelas yang disetujui dari database untuk pilihan siswa
$classes = [];
try {
    $stmtClasses = $db->query("SELECT id, name FROM classes WHERE status = 'APPROVED' OR status IS NULL ORDER BY name ASC");
    $classes = $stmtClasses->fetchAll();
    if (empty($classes)) {
        $classes = $db->query("SELECT id, name FROM classes ORDER BY name ASC")->fetchAll();
    }
} catch (Exception $e) {
    $classes = [
        ['id' => 1, 'name' => 'VII-A'],
        ['id' => 2, 'name' => 'VII-B'],
        ['id' => 3, 'name' => 'VII-C']
    ];
}

// Proses Registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaLengkap   = trim($_POST['nama_lengkap'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $username      = trim($_POST['username'] ?? '');
    $password      = trim($_POST['password'] ?? '');
    $jenisKelamin  = $_POST['jenis_kelamin'] ?? 'Laki-laki';
    $noHp          = trim($_POST['no_hp'] ?? '');
    $role          = $_POST['role'] ?? 'siswa';
    $targetClassId = !empty($_POST['target_class_id']) ? (int)$_POST['target_class_id'] : null;

    // Batasan Role: HANYA Siswa atau Guru yang boleh mendaftar via web publik
    if (!in_array($role, ['siswa', 'guru'])) {
        $error = 'Pilihan peran pendaftaran tidak valid.';
    } elseif (empty($namaLengkap) || empty($email) || empty($username) || empty($password) || empty($noHp)) {
        $error = 'Semua kolom formulir pendaftaran wajib diisi dengan lengkap.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal harus 6 karakter.';
    } else {
        // Cek duplikasi username atau email
        $chk = $db->prepare("SELECT id, username, email FROM users WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)");
        $chk->execute([$username, $email]);
        $existing = $chk->fetch();

        if ($existing) {
            if (strtolower($existing['username']) === strtolower($username)) {
                $error = "Username '{$username}' sudah terdaftar. Silakan pilih username lain.";
            } else {
                $error = "Alamat email '{$email}' sudah digunakan. Silakan gunakan email lain atau masuk.";
            }
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            if ($role === 'guru') {
                // =========================================================================
                // 1. ALUR PENDAFTARAN GURU (Langsung APPROVED, memilih kelas di Dashboard)
                // =========================================================================
                try {
                    $insGuru = $db->prepare("
                        INSERT INTO users 
                        (username, password, role, status_approval, nama_lengkap, email, kelas, target_class_id, jenis_kelamin, no_hp, created_at)
                        VALUES (?, ?, 'guru', 'APPROVED', ?, ?, NULL, NULL, ?, ?, NOW())
                    ");
                    $insGuru->execute([
                        $username,
                        $hashedPassword,
                        $namaLengkap,
                        $email,
                        $jenisKelamin,
                        $noHp
                    ]);
                    $guruId = $db->lastInsertId();

                    // Notifikasi ke Guru
                    send_notification(
                        $guruId,
                        'Pendaftaran Guru Berhasil',
                        "Selamat datang Bapak/Ibu {$namaLengkap}! Akun pengajar Anda aktif. Anda dapat langsung masuk ke Dashboard Guru untuk mengelola atau memilih kelas.",
                        'info',
                        'guru/index.php'
                    );

                    flash_set('success', "Pendaftaran Guru berhasil! Silakan masuk untuk langsung mengakses Dashboard Guru Anda.");
                    redirect('auth/login.php');
                } catch (Exception $e) {
                    $error = 'Gagal melakukan pendaftaran guru: ' . $e->getMessage();
                }

            } else {
                // =========================================================================
                // 2. ALUR PENDAFTARAN SISWA (Langsung bisa login ke Dashboard, kelas pending)
                // =========================================================================
                try {
                    $db->beginTransaction();

                    $className = '';
                    if ($targetClassId) {
                        $stmtC = $db->prepare("SELECT name FROM classes WHERE id = ?");
                        $stmtC->execute([$targetClassId]);
                        $className = $stmtC->fetchColumn() ?: '';
                    }

                    // Simpan Siswa dengan status PENDING
                    $insSiswa = $db->prepare("
                        INSERT INTO users 
                        (username, password, role, status_approval, nama_lengkap, email, kelas, target_class_id, jenis_kelamin, no_hp, created_at)
                        VALUES (?, ?, 'siswa', 'PENDING', ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $insSiswa->execute([
                        $username,
                        $hashedPassword,
                        $namaLengkap,
                        $email,
                        $className ?: null,
                        $targetClassId ?: null,
                        $jenisKelamin,
                        $noHp
                    ]);
                    $siswaId = $db->lastInsertId();

                    // Notifikasi ke Siswa
                    $pesanSiswa = !empty($className) 
                        ? "Selamat datang {$namaLengkap}! Akun Anda dapat langsung digunakan untuk masuk ke Dashboard Siswa. Akses isi kelas '{$className}' akan aktif setelah disetujui (Admit) oleh Admin."
                        : "Selamat datang {$namaLengkap}! Akun Anda dapat langsung digunakan untuk masuk ke Dashboard Siswa. Silakan pilih kelas tujuan Anda dari Dashboard.";

                    send_notification(
                        $siswaId,
                        'Pendaftaran Siswa Berhasil',
                        $pesanSiswa,
                        'info',
                        'siswa/index.php'
                    );

                    // Notifikasi ke Admin jika kelas diajukan
                    if (!empty($className)) {
                        $stmtAdmins = $db->query("SELECT id FROM users WHERE role = 'admin'");
                        $admins = $stmtAdmins->fetchAll();
                        foreach ($admins as $adm) {
                            send_notification(
                                $adm['id'],
                                'Pendaftaran Siswa Menunggu Admit',
                                "Siswa {$namaLengkap} baru mendaftar untuk kelas {$className}. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).",
                                'info',
                                'admin/admit_user.php'
                            );
                        }
                    }

                    $db->commit();
                    flash_set('success', "Pendaftaran siswa berhasil! Anda dapat langsung masuk ke Dashboard Siswa. Akses isi kelas akan aktif setelah disetujui (Admit) Admin.");
                    redirect('auth/login.php');
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Gagal melakukan pendaftaran siswa: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Akun Baru - ElearnMath</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <style>
    .auth-wrapper {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 20px;
      position: relative;
      z-index: 1;
    }
    .auth-layout {
      display: grid;
      grid-template-columns: 1fr 1.2fr;
      max-width: 1080px;
      width: 100%;
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid var(--glass-border);
      border-radius: 28px;
      box-shadow: 0 25px 60px rgba(27, 78, 128, 0.16);
      overflow: hidden;
    }
    /* Sisi Visual Cover / Landing */
    .auth-cover {
      background: linear-gradient(135deg, #1b4e80 0%, #2e7bc4 60%, #4682B4 100%);
      color: #ffffff;
      padding: 44px 36px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      overflow: hidden;
    }
    .cover-glow-1 {
      position: absolute;
      top: -80px;
      right: -80px;
      width: 280px;
      height: 280px;
      background: rgba(255, 255, 255, 0.12);
      border-radius: 50%;
      pointer-events: none;
    }
    .cover-glow-2 {
      position: absolute;
      bottom: -60px;
      left: -60px;
      width: 240px;
      height: 240px;
      background: rgba(255, 180, 84, 0.18);
      border-radius: 50%;
      pointer-events: none;
    }
    .cover-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255, 255, 255, 0.18);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.25);
      padding: 6px 14px;
      border-radius: 999px;
      font-size: 11.5px;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      color: #EAF6FF;
      margin-bottom: 20px;
    }
    .cover-title {
      font-size: 30px;
      font-weight: 800;
      line-height: 1.25;
      margin: 0 0 14px 0;
      letter-spacing: -0.5px;
    }
    .cover-subtitle {
      font-size: 13.5px;
      color: rgba(234, 246, 255, 0.92);
      line-height: 1.6;
      margin: 0 0 24px 0;
    }
    .feature-list {
      display: grid;
      grid-template-columns: 1fr;
      gap: 12px;
      margin-bottom: 24px;
    }
    .feature-item {
      display: flex;
      align-items: center;
      gap: 12px;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 14px;
      padding: 10px 14px;
      font-size: 12.5px;
      font-weight: 600;
      backdrop-filter: blur(8px);
    }
    .feature-icon {
      width: 32px;
      height: 32px;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      flex-shrink: 0;
    }
    .cover-footer {
      font-size: 12px;
      color: rgba(234, 246, 255, 0.7);
      margin-top: auto;
      padding-top: 16px;
      border-top: 1px solid rgba(255, 255, 255, 0.15);
    }
    /* Sisi Form Card */
    .auth-card-body {
      padding: 40px 36px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      max-height: 90vh;
      overflow-y: auto;
    }
    .auth-header {
      margin-bottom: 20px;
    }
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
    @media (max-width: 900px) {
      .auth-layout {
        grid-template-columns: 1fr;
        max-width: 520px;
      }
      .auth-cover {
        padding: 30px 24px;
      }
      .cover-title {
        font-size: 24px;
      }
      .feature-list {
        display: none;
      }
      .auth-card-body {
        padding: 30px 24px;
        max-height: none;
      }
    }
    @media (max-width: 480px) {
      .form-grid {
        grid-template-columns: 1fr;
      }
    }
    .form-group {
      margin-bottom: 14px;
    }
    .form-label {
      display: block;
      font-size: 12.5px;
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--ink);
    }
    .form-label span.req {
      color: var(--danger);
    }
    .form-input, .form-select {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--line);
      border-radius: var(--radius-sm);
      font-size: 13.5px;
      background: #fff;
      transition: all 0.2s;
    }
    .form-input:focus, .form-select:focus {
      outline: none;
      border-color: var(--blue-600);
      box-shadow: 0 0 0 3px rgba(95, 168, 232, 0.22);
    }
    .role-selector {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 14px;
    }
    .role-option {
      border: 2px solid var(--line);
      border-radius: var(--radius-sm);
      padding: 10px;
      text-align: center;
      cursor: pointer;
      background: #fff;
      transition: all 0.2s;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 3px;
    }
    .role-option input[type="radio"] {
      display: none;
    }
    .role-option.active {
      border-color: var(--blue-600);
      background: #EFF6FF;
      color: var(--blue-800);
    }
    .role-title {
      font-size: 13.5px;
      font-weight: 700;
    }
    .role-desc {
      font-size: 11px;
      color: var(--muted);
    }
    .info-badge-role {
      background: #EFF6FF;
      border: 1px solid #BFDBFE;
      border-radius: var(--radius-sm);
      padding: 10px 14px;
      font-size: 12px;
      color: var(--blue-800);
      line-height: 1.5;
      margin-bottom: 16px;
    }
  </style>
</head>
<body>
  <div class="ambient-bg">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
  </div>

  <div class="auth-wrapper">
    <div class="auth-layout">
      <!-- SISI KIRI: COVER / LANDING VISUAL -->
      <div class="auth-cover">
        <div class="cover-glow-1"></div>
        <div class="cover-glow-2"></div>

        <div>
          <div class="cover-badge">
            <span>∑</span> Pendaftaran Akun Baru
          </div>
          <h1 class="cover-title">
            Bergabung dengan Elearn<span style="color: #FFB454;">Math</span>
          </h1>
          <p class="cover-subtitle">
            Daftarkan diri Anda sebagai Siswa atau Guru untuk merasakan pengalaman belajar matematika yang terstruktur, modern, dan interaktif.
          </p>

          <div class="feature-list">
            <div class="feature-item">
              <div class="feature-icon">🎓</div>
              <div>
                <div>Akses Langsung ke Dashboard</div>
                <div style="font-size: 11px; font-weight: 400; opacity: 0.85;">Masuk ke dashboard utama tanpa hambatan login</div>
              </div>
            </div>
            <div class="feature-item">
              <div class="feature-icon">🏫</div>
              <div>
                <div>Manajemen & Pengajuan Kelas</div>
                <div style="font-size: 11px; font-weight: 400; opacity: 0.85;">Pilih atau kelola kelas dari dashboard pengguna</div>
              </div>
            </div>
            <div class="feature-item">
              <div class="feature-icon">📚</div>
              <div>
                <div>Modul Belajar Mandiri Lengkap</div>
                <div style="font-size: 11px; font-weight: 400; opacity: 0.85;">Materi, LKPD mandiri, latihan & evaluasi akhir</div>
              </div>
            </div>
          </div>
        </div>

        <div class="cover-footer">
          &copy; 2026 ElearnMath &bull; Media Pembelajaran Terpadu
        </div>
      </div>

      <!-- SISI KANAN: FORM REGISTRASI -->
      <div class="auth-card-body">
        <div class="auth-header">
          <h2 style="font-size: 22px; font-weight: 800; color: var(--blue-800); margin: 0 0 4px 0;">Registrasi Akun</h2>
          <p style="font-size: 13px; color: var(--muted); margin: 0;">Lengkapi formulir berikut untuk membuat akun baru.</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger" style="margin-bottom: 16px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          <!-- Pilihan Peran (Siswa / Guru) -->
          <label class="form-label">Daftar Sebagai <span class="req">*</span></label>
          <div class="role-selector">
            <label class="role-option active" id="roleSiswaLabel">
              <input type="radio" name="role" value="siswa" checked onchange="updateRoleUI()">
              <span class="role-title">🎓 Siswa</span>
              <span class="role-desc">Peserta Didik</span>
            </label>
            <label class="role-option" id="roleGuruLabel">
              <input type="radio" name="role" value="guru" onchange="updateRoleUI()">
              <span class="role-title">👨‍🏫 Guru</span>
              <span class="role-desc">Tenaga Pendidik</span>
            </label>
          </div>

          <div id="roleInfoNotice" class="info-badge-role">
            ℹ️ <strong>Alur Siswa:</strong> Anda langsung masuk ke Dashboard Siswa setelah login. Fitur kelas akan terbuka penuh setelah disetujui (Admit) oleh Admin.
          </div>

          <div class="form-group">
            <label class="form-label" for="nama_lengkap">Nama Lengkap <span class="req">*</span></label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-input" 
                   value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" 
                   placeholder="Contoh: Muhammad Farhan" required>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label class="form-label" for="email">Alamat Email <span class="req">*</span></label>
              <input type="email" id="email" name="email" class="form-input" 
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                     placeholder="nama@email.com" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="no_hp">Nomor HP / WhatsApp <span class="req">*</span></label>
              <input type="tel" id="no_hp" name="no_hp" class="form-input" 
                     value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" 
                     placeholder="08123456789" required>
            </div>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label class="form-label" for="username">Username Akun <span class="req">*</span></label>
              <input type="text" id="username" name="username" class="form-input" 
                     value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                     placeholder="farhan123" required autocomplete="username">
            </div>
            <div class="form-group">
              <label class="form-label" for="password">Kata Sandi <span class="req">*</span></label>
              <input type="password" id="password" name="password" class="form-input" 
                     placeholder="Min. 6 Karakter" required autocomplete="new-password">
            </div>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label class="form-label" for="jenis_kelamin">Jenis Kelamin <span class="req">*</span></label>
              <select id="jenis_kelamin" name="jenis_kelamin" class="form-select" required>
                <option value="Laki-laki" <?= (($_POST['jenis_kelamin'] ?? '') === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                <option value="Perempuan" <?= (($_POST['jenis_kelamin'] ?? '') === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
              </select>
            </div>

            <!-- PILIHAN KELAS HANYA UNTUK SISWA (DROPDOWN KELAS) -->
            <div class="form-group" id="siswaClassWrapper">
              <label class="form-label" for="target_class_id">
                Pilihan Kelas Tujuan (Siswa)
              </label>
              <select id="target_class_id" name="target_class_id" class="form-select">
                <option value="">-- Pilih Kelas (Opsional) --</option>
                <?php foreach ($classes as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= (($_POST['target_class_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                    Kelas <?= htmlspecialchars($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; margin-top: 8px; font-weight: 700; font-size: 14px;">
            Daftar Sekarang
          </button>

          <div style="text-align: center; margin-top: 18px; font-size: 13px; color: var(--muted);">
            Sudah memiliki akun terdaftar? 
            <a href="<?= BASE_URL ?>/auth/login.php" style="color: var(--blue-600); font-weight: 700; text-decoration: none;">
              Masuk Sekarang
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function updateRoleUI() {
      const isGuru = document.querySelector('input[name="role"][value="guru"]').checked;
      const siswaLabel = document.getElementById('roleSiswaLabel');
      const guruLabel = document.getElementById('roleGuruLabel');
      const siswaWrapper = document.getElementById('siswaClassWrapper');
      const infoNotice = document.getElementById('roleInfoNotice');

      if (isGuru) {
        guruLabel.classList.add('active');
        siswaLabel.classList.remove('active');
        siswaWrapper.style.display = 'none';
        infoNotice.innerHTML = '👨‍🏫 <strong>Alur Guru:</strong> Anda langsung masuk ke Dashboard Guru setelah login. Anda dapat memilih kelas yang sudah ada atau membuat kelas baru langsung dari Dashboard Guru.';
      } else {
        siswaLabel.classList.add('active');
        guruLabel.classList.remove('active');
        siswaWrapper.style.display = 'block';
        infoNotice.innerHTML = 'ℹ️ <strong>Alur Siswa:</strong> Anda langsung masuk ke Dashboard Siswa setelah login. Fitur kelas akan terbuka penuh setelah disetujui (Admit) oleh Admin.';
      }
    }

    document.addEventListener('DOMContentLoaded', updateRoleUI);
  </script>
</body>
</html>
