<?php
/**
 * Logout Handler
 * File: auth/logout.php
 */
require_once __DIR__ . '/../config/app.php';

if (is_logged_in()) {
    $user = auth_user();
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET is_online = 0, last_active = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: " . BASE_URL . "/auth/login.php");
exit;
