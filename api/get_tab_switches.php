<?php
/**
 * API Mengambil Log Deteksi Keluar Tab Real-time
 * File: api/get_tab_switches.php
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';

if (!is_logged_in() || !has_role(['admin', 'guru'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUser = auth_user();
$db = getDB();

$kelasFilter = trim($_GET['kelas'] ?? '');
$params = [];
$sql = "
    SELECT t.*, u.foto
    FROM tab_switch_logs t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE DATE(t.last_switched_at) = CURDATE()
";

if (!empty($kelasFilter)) {
    $sql .= " AND t.kelas = ?";
    $params[] = $kelasFilter;
} elseif ($currentUser['role'] === 'guru' && !empty($currentUser['kelas'])) {
    $guruClasses = array_filter(array_map('trim', explode(',', $currentUser['kelas'])));
    if (!empty($guruClasses)) {
        $placeholders = implode(',', array_fill(0, count($guruClasses), '?'));
        $sql .= " AND t.kelas IN ($placeholders)";
        foreach ($guruClasses as $gc) {
            $params[] = $gc;
        }
    }
}

$sql .= " ORDER BY t.last_switched_at DESC LIMIT 15";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count' => count($logs),
        'data' => $logs
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
