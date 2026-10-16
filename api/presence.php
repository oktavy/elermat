<?php
/**
 * API Heartbeat Presensi Siswa Online
 * File: api/presence.php
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = auth_user();
$userId = $user['id'];

$db = getDB();
$stmt = $db->prepare("UPDATE users SET is_online = 1, last_active = NOW() WHERE id = ?");
$stmt->execute([$userId]);

echo json_encode(['success' => true, 'timestamp' => date('Y-m-d H:i:s')]);
