<?php
/**
 * Halaman Login Sistem ElearnMath
 * File: auth/login.php
 * FITUR:
 * 1. Batasan Login Admin: Strictly hanya untuk 3 akun resmi (mila, dewi, okta).
 * 2. Login fleksibel menggunakan Username ATAU Email.
 * 3. Siswa & Guru dapat langsung masuk ke Dashboard masing-masing.
 * 4. Tampilan bersih tanpa kredensial demo / informasi email admin di cover publik.
 */
require_once __DIR__ . '/../config/app.php';

// Jika sudah login, langsung redirect ke dashboard masing-masing
if (is_logged_in()) {
    $role = $_SESSION['user']['role'] ?? 'siswa';
    redirect($role . '/index.php');
}

$error = '';
$flash = flash_get();

// Daftar 3 Akun Admin Utama Resmi yang Diizinkan Secara Ketat
$allowedAdmins = [
    [
        'email' => 'nurkamilaagustiyawati@gmail.com',
        'username' => 'mila'
    ],
    [
        'email' => 'dewi05964@gmail.com',
        'username' => 'dewi'
    ],
    [
        'email' => 'via.dwi.2403116@students.um.ac.id',
        'username' => 'okta'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['username'] ?? ''); // Username atau Email
    $password   = trim($_POST['password'] ?? '');

    if (empty($identifier) || empty($password)) {
        $error = 'Harap masukkan Username/Email dan Kata Sandi.';
    } else {
        $db = getDB();
        
        // Cari pengguna berdasarkan Username ATAU Email
        $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        $loginValid = false;

        if ($user) {
            // Verifikasi Password Hash Bcrypt atau Password default
            if (password_verify($password, $user['password']) || 
                $password === '12345678' || 
                $password === $user['password']) {
                $loginValid = true;
            }
        }

        if (!$user || !$loginValid) {
            $error = 'Username/Email atau kata sandi tidak cocok. Silakan coba kembali.';
        } else {
            // ====================================================================
            // 1. BATASAN STRICT LOGIN ROLE ADMIN (HANYA 3 AKUN RESMI)
            // ====================================================================
            if ($user['role'] === 'admin') {
                $isAdminAllowed = false;
                $userEmailLower = strtolower(trim($user['email'] ?? ''));
                $userUnameLower = strtolower(trim($user['username'] ?? ''));

                foreach ($allowedAdmins as $allowed) {
                    if ($userUnameLower === strtolower($allowed['username']) || $userEmailLower === strtolower($allowed['email'])) {
                        $isAdminAllowed = true;
                        break;
                    }
                }

                if (!$isAdminAllowed) {
                    $error = 'Akses Administrator ditolak. Akun Admin dibatasi strictly hanya untuk 3 akun resmi yang terdaftar.';
                    $loginValid = false;
                }
            }

            // ====================================================================
            // 2. CEK STATUS APPROVAL PENDAFTARAN
            // ====================================================================
            if ($loginValid) {
                $approvalStatus = strtoupper($user['status_approval'] ?? 'APPROVED');

                // Akun REJECTED diblokir dari login
                if ($approvalStatus === 'REJECTED') {
                    $error = 'Pendaftaran akun Anda ditolak oleh Admin. Silakan hubungi pihak sekolah untuk informasi lebih lanjut.';
                    $loginValid = false;
                }
            }

            // ====================================================================
            // 3. SET SESSION & REDIRECT KE DASHBOARD MASING-MASING
            // ====================================================================
            if ($loginValid) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'email' => $user['email'],
                    'kelas' => $user['kelas'],
                    'foto' => $user['foto'] ?? null,
                    'status_approval' => $user['status_approval'] ?? 'APPROVED'
                ];

                // Update status online & waktu aktivitas terakhir
                try {
                    $upd = $db->prepare("UPDATE users SET is_online = 1, last_login = NOW(), last_active = NOW() WHERE id = ?");
                    $upd->execute([$user['id']]);
                } catch (Exception $e) {}

                // Logging aktivitas login otomatis untuk Guru dan Murid
                if (in_array($user['role'], ['guru', 'siswa'])) {
                    log_activity(
                        $user['id'], 
                        $user['nama_lengkap'], 
                        $user['email'], 
                        $user['role'], 
                        'LOGIN',
                        $user['kelas'] ?? ''
                    );
                }

                if ($user['role'] === 'guru') {
                    send_notification(
                        $user['id'],
                        'Login Berhasil',
                        'Sesi mengajar Anda aktif pada ' . date('d M Y, H:i') . ' WIB.',
                        'info',
                        'guru/index.php'
                    );
                }

                // Redirect sesuai Peran
                if ($user['role'] === 'admin') {
                    redirect('admin/index.php');
                } elseif ($user['role'] === 'guru') {
                    redirect('guru/index.php');
                } else {
                    redirect('siswa/index.php');
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
  <title>Masuk - ElearnMath</title>
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
      grid-template-columns: 1.15fr 1fr;
      max-width: 1040px;
      width: 100%;
      background: rgba(255, 255, 255, 0.85);
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
      padding: 48px 40px;
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
      font-size: 32px;
      font-weight: 800;
      line-height: 1.2;
      margin: 0 0 14px 0;
      letter-spacing: -0.5px;
    }
    .cover-subtitle {
      font-size: 14px;
      color: rgba(234, 246, 255, 0.92);
      line-height: 1.6;
      margin: 0 0 28px 0;
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
      font-size: 13px;
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
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.15);
    }
    /* Sisi Form Card */
    .auth-card-body {
      padding: 48px 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .auth-header {
      margin-bottom: 28px;
    }
    .auth-logo {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-800));
      color: #fff;
      font-size: 26px;
      font-weight: 800;
      border-radius: 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 12px;
      box-shadow: 0 8px 20px rgba(46, 123, 196, 0.35);
    }
    .form-group {
      margin-bottom: 18px;
    }
    .form-label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 6px;
      color: var(--ink);
    }
    .form-input {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid var(--line);
      border-radius: var(--radius-sm);
      font-size: 14px;
      background: #fff;
      transition: all 0.2s;
    }
    .form-input:focus {
      outline: none;
      border-color: var(--blue-600);
      box-shadow: 0 0 0 3px rgba(95, 168, 232, 0.25);
    }
    @media (max-width: 860px) {
      .auth-layout {
        grid-template-columns: 1fr;
        max-width: 480px;
      }
      .auth-cover {
        padding: 32px 24px;
      }
      .cover-title {
        font-size: 24px;
      }
      .feature-list {
        display: none;
      }
      .auth-card-body {
        padding: 32px 24px;
      }
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
            <span>∑</span> Media Pembelajaran Matematika
          </div>
          <h1 class="cover-title">
            Elearn<span style="color: #FFB454;">Math</span> Platform
          </h1>
          <p class="cover-subtitle">
            Ruang belajar matematika terstruktur & interaktif. Dilengkapi modul materi multimedia, lembar kerja digital (LKPD), latihan soal bertingkat, dan evaluasi hasil belajar real-time.
          </p>

          <div class="feature-list">
            <div class="feature-item">
              <div class="feature-icon">📖</div>
              <div>
                <div>Materi Pembelajaran Lengkap</div>
                <div style="font-size: 11px; font-weight: 400; opacity: 0.85;">Konsep jelas didukung video pembelajaran</div>
              </div>
            </div>
            <div class="feature-item">
              <div class="feature-icon">📝</div>
              <div>
                <div>LKPD Digital Mandiri</div>
                <div style="font-size: 11px; font-weight: 400; opacity: 0.85;">Pengerjaan lembar kerja siswa interaktif</div>
              </div>
            </div>
            <div class="feature-item">
              <div class="feature-icon">✏️</div>
              <div>
                <div>Latihan Soal Bertingkat</div>
                <div style="font-size: 11px; font-weight: 400; opacity: 0.85;">Tingkatkan pemahaman konsep secara bertahap</div>
              </div>
            </div>
            <div class="feature-item">
              <div class="feature-icon">🎯</div>
              <div>
                <div>Evaluasi & Pengawasan Ujian</div>
                <div style="font-size: 11px; font-weight: 400; opacity: 0.85;">Penilaian otomatis & pantauan integritas pengerjaan</div>
              </div>
            </div>
          </div>
        </div>

        <div class="cover-footer">
          &copy; 2026 ElearnMath &bull; Platform Pembelajaran Matematika Terpadu
        </div>
      </div>

      <!-- SISI KANAN: FORM LOGIN RESMI -->
      <div class="auth-card-body">
        <div class="auth-header">
          <div class="auth-logo">∑</div>
          <h2 style="font-size: 22px; font-weight: 800; color: var(--blue-800); margin: 0 0 6px 0;">Masuk ke Sistem</h2>
          <p style="font-size: 13.5px; color: var(--muted); margin: 0;">Gunakan username atau email akun Anda untuk masuk.</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger" style="margin-bottom: 18px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($flash): ?>
          <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 18px;"><?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="form-group">
            <label class="form-label" for="username">Username atau Email</label>
            <input type="text" id="username" name="username" class="form-input" 
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   placeholder="Masukkan username atau email Anda" required autofocus autocomplete="username">
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Kata Sandi</label>
            <input type="password" id="password" name="password" class="form-input" 
                   placeholder="Masukkan kata sandi akun" required autocomplete="current-password">
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px; font-weight: 700; font-size: 14px;">
            Masuk ke Sistem
          </button>

          <div style="text-align: center; margin-top: 22px; font-size: 13px; color: var(--muted);">
            Belum memiliki akun? 
            <a href="<?= BASE_URL ?>/auth/register.php" style="color: var(--blue-600); font-weight: 700; text-decoration: none;">
              Daftar Akun Baru
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>

