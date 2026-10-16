<?php
/**
 * Konfigurasi Koneksi Database MySQL (XAMPP)
 * File: config/database.php
 * 
 * Pengaturan standar XAMPP:
 * Host: localhost
 * User: root
 * Pass: "" (kosong)
 * DbName: db_sekolah
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_sekolah');
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Coba buat database jika belum ada
            if ($e->getCode() == 1049) {
                try {
                    $tempPdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
                    $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                    return $pdo;
                } catch (PDOException $ex) {
                    die("Gagal inisialisasi database: " . $ex->getMessage());
                }
            }
            die("Koneksi Database XAMPP Gagal: Pastikan Apache & MySQL di XAMPP Control Panel sudah aktif. <br>Error: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Inisialisasi variabel global PDO untuk kemudahan penggunaan
$pdo = getDB();
