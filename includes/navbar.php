<?php
/**
 * Navbar Template dengan Lonceng Notifikasi Interaktif
 * File: includes/navbar.php
 * FITUR C: Notifikasi Lonceng + Titik Merah (Unread) + Dropdown
 */
require_once __DIR__ . '/../config/app.php';

$userId = $currentUser['id'] ?? 0;

// Refresh data user terbaru dari DB (agar update foto langsung terlihat)
$db = getDB();
$stmtUser = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$dbUser = $stmtUser->fetch();
if ($dbUser) {
    $currentUser['foto'] = $dbUser['foto'];
    $currentUser['nama_lengkap'] = $dbUser['nama_lengkap'];
}

$unreadCount = get_unread_notification_count($userId);

// Ambil 6 notifikasi terbaru pengguna
$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 6
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// Tentukan tautan profil sesuai peran
$profileLinks = [
    'admin' => BASE_URL . '/admin/profile.php',
    'guru'  => BASE_URL . '/guru/profile.php',
    'siswa' => BASE_URL . '/siswa/profile.php'
];
$myProfileUrl = $profileLinks[$currentUser['role'] ?? 'siswa'] ?? (BASE_URL . '/siswa/profile.php');
?>

<header class="app-navbar">
  <div class="nav-page-title">
    <?= htmlspecialchars($pageTitle ?? 'ElearnMath') ?>
  </div>

  <div class="nav-right-actions">
    <!-- FITUR C: Ikon Lonceng Notifikasi & Badge Titik Merah -->
    <div style="position: relative;">
      <button type="button" class="bell-btn" id="bellNotifBtn" title="Notifikasi">
        <i data-feather="bell" style="width: 20px; height: 20px;"></i>
        <?php if ($unreadCount > 0): ?>
          <span class="red-dot-badge" id="redDotBadge" title="<?= $unreadCount ?> notifikasi belum dibaca"></span>
        <?php endif; ?>
      </button>

      <!-- Dropdown Notifikasi -->
      <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-header">
          <span>Notifikasi</span>
          <?php if ($unreadCount > 0): ?>
            <span style="font-size: 11px; background: #E3543F; color: #fff; padding: 2px 8px; border-radius: 999px;">
              <?= $unreadCount ?> Baru
            </span>
          <?php endif; ?>
        </div>

        <div class="notif-list">
          <?php if (empty($notifications)): ?>
            <div class="notif-empty">Belum ada notifikasi baru.</div>
          <?php else: ?>
            <?php foreach ($notifications as $n): ?>
              <a href="<?= !empty($n['link']) ? htmlspecialchars(BASE_URL . '/' . ltrim($n['link'], '/')) : 'javascript:void(0)' ?>" 
                 class="notif-item <?= $n['is_read'] == 0 ? 'unread' : '' ?>">
                <div class="notif-item-title"><?= htmlspecialchars($n['judul']) ?></div>
                <div class="notif-item-desc"><?= htmlspecialchars($n['pesan']) ?></div>
                <div class="notif-item-time"><?= date('d M Y, H:i', strtotime($n['created_at'])) ?> WIB</div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- User Profile pill dengan Foto Profil -->
    <a href="<?= $myProfileUrl ?>" style="text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 5px 14px; background: #fff; border: 1px solid var(--line); border-radius: 999px; transition: transform 0.15s;" title="Kelola Profil">
      <?php if (!empty($currentUser['foto'])): ?>
        <img src="<?= BASE_URL . '/' . htmlspecialchars($currentUser['foto']) ?>" alt="Foto" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 1px solid var(--blue-400);">
      <?php else: ?>
        <div style="width: 30px; height: 30px; border-radius: 50%; background: var(--blue-600); color:#fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">
          <?= strtoupper(substr($currentUser['nama_lengkap'] ?? 'U', 0, 1)) ?>
        </div>
      <?php endif; ?>
      <span style="font-size: 13px; font-weight: 600; color: var(--ink);">
        <?= htmlspecialchars($currentUser['nama_lengkap'] ?? 'Pengguna') ?>
      </span>
    </a>
  </div>
</header>
