<?php
/**
 * Konfigurasi Aplikasi & Helper Functions
 * File: config/app.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

// Base URL deteksi otomatis
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// Normalisasi path root project
$parts = explode('/', trim($scriptDir, '/'));
$baseDir = !empty($parts[0]) ? '/' . $parts[0] : '';
define('BASE_URL', $protocol . '://' . $host . $baseDir);

/**
 * Autentikasi & Session Helper
 */
function auth_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function has_role($role) {
    if (!is_logged_in()) return false;
    if (is_array($role)) {
        return in_array($_SESSION['user']['role'], $role);
    }
    return $_SESSION['user']['role'] === $role;
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }
}

function require_role($roles) {
    require_login();
    if (!has_role($roles)) {
        header("Location: " . BASE_URL . "/auth/login.php?error=unauthorized");
        exit;
    }
}

function redirect($path) {
    header("Location: " . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function flash_set($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function flash_get() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Logging Aktivitas Login Pengguna (Guru & Murid) ke Admin
 */
function log_activity($userId, $nama, $email, $role, $action = 'LOGIN', $kelas = null) {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Browser';

    if ($kelas === null) {
        try {
            $stmtK = $db->prepare("SELECT kelas FROM users WHERE id = ?");
            $stmtK->execute([$userId]);
            $kelas = $stmtK->fetchColumn() ?: null;
        } catch (Exception $e) {
            $kelas = null;
        }
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, nama, email, role, kelas, action, ip_address, user_agent, login_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $nama, $email, $role, $kelas, $action, trim($ip), $ua]);
    } catch (Exception $e) {
        // Fallback jika tabel belum memiliki kolom kelas
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, nama, email, role, action, ip_address, user_agent, login_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $nama, $email, $role, $action, trim($ip), $ua]);
    }
}


/**
 * FITUR C: Kirim Notifikasi Interaktif
 */
function send_notification($userId, $judul, $pesan, $tipe = 'info', $link = null) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, judul, pesan, tipe, link, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, 0, NOW())
    ");
    return $stmt->execute([$userId, $judul, $pesan, $tipe, $link]);
}

/**
 * FITUR C: Hitung Notifikasi Belum Dibaca
 */
function get_unread_notification_count($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Get setting value
 */
function get_setting($key, $default = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

/**
 * Deteksi jenis konten tautan/link pembelajaran untuk Dashboard Murid
 * (PowerPoint, Video, PDF, Dokumen, Website, dsb.)
 */
function get_media_type_info($url, $title = '') {
    if (empty(trim($url ?? ''))) {
        return null;
    }
    $url = trim($url);
    $lower = strtolower($url);

    // 1. YouTube & Video
    if (preg_match('/(youtube\.com|youtu\.be)/i', $lower)) {
        $name = 'Video Pembelajaran YouTube';
        $icon = '🎬';
        $full = !empty($title) ? "🎬 {$name} — {$title}" : "🎬 {$name}";
        return [
            'type' => 'youtube',
            'name' => $name,
            'icon' => $icon,
            'display' => $full,
            'badge' => "{$icon} {$name}",
            'url' => $url,
            'bg' => '#FEE2E2',
            'color' => '#DC2626'
        ];
    } elseif (preg_match('/(vimeo\.com|\.mp4|\.mkv|\.webm|video)/i', $lower)) {
        $name = 'Video Pembelajaran';
        $icon = '▶️';
        $full = !empty($title) ? "▶️ {$name} — {$title}" : "▶️ {$name}";
        return [
            'type' => 'video',
            'name' => $name,
            'icon' => $icon,
            'display' => $full,
            'badge' => "{$icon} {$name}",
            'url' => $url,
            'bg' => '#FEE2E2',
            'color' => '#DC2626'
        ];
    }

    // 2. PowerPoint (.pptx / .ppt)
    if (preg_match('/(\.pptx?|\.ppsx?|docs\.google\.com\/presentation|slides\.google\.com|presentation)/i', $lower)) {
        $name = 'Dokumen PowerPoint (.pptx)';
        $icon = '📊';
        $full = !empty($title) ? "📊 {$name} — {$title}" : "📊 {$name}";
        return [
            'type' => 'powerpoint',
            'name' => $name,
            'icon' => $icon,
            'display' => $full,
            'badge' => "{$icon} {$name}",
            'url' => $url,
            'bg' => '#FEF2F2',
            'color' => '#B91C1C'
        ];
    }

    // 3. File PDF (.pdf)
    if (preg_match('/(\.pdf|drive\.google\.com\/file\/d\/[^\/]+\/preview|\/pdf)/i', $lower)) {
        $name = 'File PDF';
        $icon = '📄';
        $full = !empty($title) ? "📄 {$name} — {$title}" : "📄 {$name}";
        return [
            'type' => 'pdf',
            'name' => $name,
            'icon' => $icon,
            'display' => $full,
            'badge' => "{$icon} {$name}",
            'url' => $url,
            'bg' => '#FEF3C7',
            'color' => '#B45309'
        ];
    }

    // 4. Dokumen Word (.docx / .doc)
    if (preg_match('/(\.docx?|\.rtf|\.odt|docs\.google\.com\/document)/i', $lower)) {
        $name = 'Dokumen Word (.docx)';
        $icon = '📝';
        $full = !empty($title) ? "📝 {$name} — {$title}" : "📝 {$name}";
        return [
            'type' => 'document',
            'name' => $name,
            'icon' => $icon,
            'display' => $full,
            'badge' => "{$icon} {$name}",
            'url' => $url,
            'bg' => '#E0F2FE',
            'color' => '#0369A1'
        ];
    }

    // 5. Dokumen Spreadsheet (.xlsx / .csv)
    if (preg_match('/(\.xlsx?|\.csv|docs\.google\.com\/spreadsheets)/i', $lower)) {
        $name = 'Spreadsheet Excel (.xlsx)';
        $icon = '📈';
        $full = !empty($title) ? "📈 {$name} — {$title}" : "📈 {$name}";
        return [
            'type' => 'spreadsheet',
            'name' => $name,
            'icon' => $icon,
            'display' => $full,
            'badge' => "{$icon} {$name}",
            'url' => $url,
            'bg' => '#ECFDF5',
            'color' => '#065F46'
        ];
    }

    // 6. Google Drive umum
    if (strpos($lower, 'drive.google.com') !== false) {
        $name = 'Berkas Google Drive';
        $icon = '📁';
        $full = !empty($title) ? "📁 {$name} — {$title}" : "📁 {$name}";
        return [
            'type' => 'gdrive',
            'name' => $name,
            'icon' => $icon,
            'display' => $full,
            'badge' => "{$icon} {$name}",
            'url' => $url,
            'bg' => '#E0F2FE',
            'color' => '#0369A1'
        ];
    }

    // 7. Modul Web / Artikel Eksternal
    if (preg_match('/^https?:\/\//i', $lower)) {
        $name = 'Modul Web';
        $icon = '🌐';
        $full = !empty($title) ? "🌐 {$name} — {$title}" : "🌐 {$name} Pembelajaran";
        return [
            'type' => 'website',
            'name' => $name,
            'icon' => $icon,
            'display' => $full,
            'badge' => "{$icon} {$name}",
            'url' => $url,
            'bg' => '#F1F5F9',
            'color' => '#475569'
        ];
    }

    // 8. Tautan Lainnya
    $name = 'Tautan Sumber Materi';
    $icon = '🔗';
    $full = !empty($title) ? "🔗 {$name} — {$title}" : "🔗 {$name}";
    return [
        'type' => 'link',
        'name' => $name,
        'icon' => $icon,
        'display' => $full,
        'badge' => "{$icon} {$name}",
        'url' => $url,
        'bg' => '#F1F5F9',
        'color' => '#64748B'
    ];
}

