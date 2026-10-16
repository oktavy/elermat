<?php
/**
 * API Endpoint Pencatatan Deteksi Keluar Tab (Tab Switching Detection)
 * File: api/log_tab_switch.php
 * FITUR:
 * 1. Menerima event perpindahan tab (visibilitychange / blur) saat siswa mengerjakan soal.
 * 2. Mencatat ke tabel tab_switch_logs (mendukung skema baru dan kolom warisan).
 * 3. Mengirimkan notifikasi realtime kepada guru pengajar.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Silakan login terlebih dahulu.']);
    exit;
}

$user = auth_user();
$userId = (int)$user['id'];
$nama = $user['nama_lengkap'] ?? '';
$kelas = $user['kelas'] ?? '';

// Ambil input dari JSON payload atau POST biasa
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input)) {
    $input = $_POST;
}

$assessmentId = !empty($input['assessment_id']) ? (int)$input['assessment_id'] : null;
$assessmentTitle = trim($input['assessment_title'] ?? 'Ujian Matematika');
$eventReason = trim($input['reason'] ?? 'Pindah tab/jendela peramban saat pengerjaan');
$notes = "Terdeteksi: " . $eventReason;

$db = getDB();

// Tentukan session_type jika assessment_id diketahui
$sessionType = 'LATIHAN';
if ($assessmentId > 0) {
    try {
        $stmtTipe = $db->prepare("SELECT tipe, judul FROM assessments WHERE id = ?");
        $stmtTipe->execute([$assessmentId]);
        $assRow = $stmtTipe->fetch();
        if ($assRow) {
            $sessionType = strtoupper($assRow['tipe']);
            if (empty($assessmentTitle) || $assessmentTitle === 'Ujian Matematika') {
                $assessmentTitle = $assRow['judul'];
            }
        }
    } catch (Exception $e) {}
}

try {
    // Cek apakah sudah ada log pengerjaan hari ini oleh siswa untuk ujian ini
    $stmt = $db->prepare("
        SELECT id, switch_count FROM tab_switch_logs 
        WHERE user_id = ? AND (assessment_id = ? OR assessment_title = ?) AND DATE(last_switched_at) = CURDATE()
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$userId, $assessmentId, $assessmentTitle]);
    $existing = $stmt->fetch();

    $newCount = 1;
    if ($existing) {
        $newCount = (int)$existing['switch_count'] + 1;
        $upd = $db->prepare("
            UPDATE tab_switch_logs 
            SET switch_count = ?, last_switched_at = NOW(), notes = ?
            WHERE id = ?
        ");
        $upd->execute([$newCount, $notes, $existing['id']]);
    } else {
        $ins = $db->prepare("
            INSERT INTO tab_switch_logs 
            (user_id, nama_siswa, kelas, assessment_id, assessment_title, switch_count, last_switched_at, notes, session_type, task_id)
            VALUES (?, ?, ?, ?, ?, 1, NOW(), ?, ?, ?)
        ");
        $ins->execute([$userId, $nama, $kelas, $assessmentId, $assessmentTitle, $notes, $sessionType, $assessmentId]);
    }

    // Kirim notifikasi peringatan ke guru pengajar
    $stmtGuru = $db->query("SELECT id FROM users WHERE role = 'guru'");
    $gurus = $stmtGuru->fetchAll();
    foreach ($gurus as $g) {
        send_notification(
            $g['id'],
            'Peringatan Keluar Tab Ujian',
            "Siswa {$nama} ({$kelas}) terdeteksi meninggalkan tab pengerjaan '{$assessmentTitle}' (Total: {$newCount}x).",
            'warning',
            'guru/deteksi.php'
        );
    }

    echo json_encode([
        'success' => true,
        'count' => $newCount,
        'nama_siswa' => $nama,
        'kelas' => $kelas,
        'assessment_title' => $assessmentTitle,
        'message' => "Peringatan: Aktivitas perpindahan tab ke-{$newCount} telah dicatat oleh sistem pengawas ujian."
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mencatat log perpindahan tab: ' . $e->getMessage()
    ]);
}
