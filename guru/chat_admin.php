<?php
/**
 * Fitur Lapor / Chat Admin telah dinonaktifkan / dihapus dari sistem.
 * File: guru/chat_admin.php
 */
require_once __DIR__ . '/../config/app.php';
require_role('guru');

flash_set('warning', 'Fitur Chat / Lapor ke Admin telah dinonaktifkan dari sistem.');
redirect('guru/index.php');
