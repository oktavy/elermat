<?php
/**
 * Fitur Pesan Guru telah dinonaktifkan / dihapus dari sistem.
 * File: admin/guru_messages.php
 */
require_once __DIR__ . '/../config/app.php';
require_role('admin');

flash_set('warning', 'Fitur Pesan Guru telah dinonaktifkan dari sistem.');
redirect('admin/index.php');
