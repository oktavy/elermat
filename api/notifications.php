<?php
/**
 * API Notifikasi Interaktif
 * File: api/notifications.php
 * FITUR C: Status berubah di database menjadi 'read' saat lonceng dibuka
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = auth_user();
$userId = $user['id'];
$action = $_GET['action'] ?? 'mark_read';
$db = getDB();

if ($action === 'mark_read') {
    // Ubah seluruh notifikasi user yang belum dibaca menjadi sudah dibaca
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $affected = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => 'Notifikasi ditandai sebagai sudah dibaca.',
        'updated_count' => $affected
    ]);
    exit;
}

if ($action === 'count') {
    $count = get_unread_notification_count($userId);
    echo json_encode([
        'success' => true,
        'unread_count' => $count
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
