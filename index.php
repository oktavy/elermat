<?php
/**
 * Root Entry Point Router
 * File: index.php
 */
require_once __DIR__ . '/config/app.php';

if (is_logged_in()) {
    $user = auth_user();
    if ($user['role'] === 'admin') {
        redirect('admin/index.php');
    } elseif ($user['role'] === 'guru') {
        redirect('guru/index.php');
    } else {
        redirect('siswa/index.php');
    }
} else {
    redirect('auth/login.php');
}
