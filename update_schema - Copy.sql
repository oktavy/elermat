-- =================================================================
-- SKRIP PEMBARUAN SKEMA BASIS DATA (update_schema.sql)
-- Database: db_sekolah (XAMPP MySQL)
-- =================================================================

USE `db_sekolah`;

-- 1. Tambah Kolom ke Tabel users
SET @col_approval = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'status_approval');
SET @sql_approval = IF(@col_approval = 0, "ALTER TABLE `users` ADD COLUMN `status_approval` ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'APPROVED' AFTER `role`", 'SELECT "Kolom status_approval sudah ada"');
PREPARE stmt_appr FROM @sql_approval;
EXECUTE stmt_appr;
DEALLOCATE PREPARE stmt_appr;

SET @col_target_class = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'target_class_id');
SET @sql_target_class = IF(@col_target_class = 0, "ALTER TABLE `users` ADD COLUMN `target_class_id` INT NULL AFTER `kelas`", 'SELECT "Kolom target_class_id sudah ada"');
PREPARE stmt_tc FROM @sql_target_class;
EXECUTE stmt_tc;
DEALLOCATE PREPARE stmt_tc;

SET @col_bio = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'bio');
SET @sql_bio = IF(@col_bio = 0, "ALTER TABLE `users` ADD COLUMN `bio` TEXT NULL AFTER `alamat`", 'SELECT "Kolom bio sudah ada"');
PREPARE stmt_bio FROM @sql_bio;
EXECUTE stmt_bio;
DEALLOCATE PREPARE stmt_bio;

SET @col_foto = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'foto');
SET @sql_foto = IF(@col_foto = 0, "ALTER TABLE `users` ADD COLUMN `foto` VARCHAR(255) NULL AFTER `no_hp`", 'SELECT "Kolom foto sudah ada"');
PREPARE stmt_foto FROM @sql_foto;
EXECUTE stmt_foto;
DEALLOCATE PREPARE stmt_foto;

-- 2. Tambah Kolom ke Tabel assessments
SET @col_dur = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'assessments' AND COLUMN_NAME = 'duration_minutes');
SET @sql_dur = IF(@col_dur = 0, "ALTER TABLE `assessments` ADD COLUMN `duration_minutes` INT DEFAULT 30 AFTER `passing_grade`", 'SELECT "Kolom duration_minutes sudah ada"');
PREPARE stmt_dur FROM @sql_dur;
EXECUTE stmt_dur;
DEALLOCATE PREPARE stmt_dur;

SET @col_urutan = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'assessments' AND COLUMN_NAME = 'urutan');
SET @sql_urutan = IF(@col_urutan = 0, "ALTER TABLE `assessments` ADD COLUMN `urutan` INT DEFAULT 1 AFTER `judul`", 'SELECT "Kolom urutan sudah ada"');
PREPARE stmt_urutan FROM @sql_urutan;
EXECUTE stmt_urutan;
DEALLOCATE PREPARE stmt_urutan;

SET @col_kelas = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'assessments' AND COLUMN_NAME = 'kelas');
SET @sql_kelas = IF(@col_kelas = 0, "ALTER TABLE `assessments` ADD COLUMN `kelas` VARCHAR(50) DEFAULT 'Semua Kelas' AFTER `deskripsi`", 'SELECT "Kolom kelas sudah ada"');
PREPARE stmt_kelas FROM @sql_kelas;
EXECUTE stmt_kelas;
DEALLOCATE PREPARE stmt_kelas;

SET @col_active = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'assessments' AND COLUMN_NAME = 'is_active');
SET @sql_active = IF(@col_active = 0, "ALTER TABLE `assessments` ADD COLUMN `is_active` TINYINT(1) DEFAULT 1 AFTER `deadline`", 'SELECT "Kolom is_active sudah ada"');
PREPARE stmt_active FROM @sql_active;
EXECUTE stmt_active;
DEALLOCATE PREPARE stmt_active;

-- 3. Tambah Kolom allow_retake ke Tabel submissions
SET @col_retake = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'submissions' AND COLUMN_NAME = 'allow_retake');
SET @sql_retake = IF(@col_retake = 0, "ALTER TABLE `submissions` ADD COLUMN `allow_retake` TINYINT(1) DEFAULT 0 AFTER `is_corrected`", 'SELECT "Kolom allow_retake sudah ada"');
PREPARE stmt_retake FROM @sql_retake;
EXECUTE stmt_retake;
DEALLOCATE PREPARE stmt_retake;

-- 4. Tabel Deteksi Keluar Tab (tab_switch_logs)
CREATE TABLE IF NOT EXISTS `tab_switch_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nama_siswa` VARCHAR(100) NOT NULL,
  `kelas` VARCHAR(50) NULL,
  `assessment_id` INT NULL,
  `assessment_title` VARCHAR(255) NOT NULL,
  `switch_count` INT NOT NULL DEFAULT 1,
  `last_switched_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notes` VARCHAR(255) NULL DEFAULT 'Pindah tab peramban saat pengerjaan',
  INDEX `idx_tab_user` (`user_id`),
  INDEX `idx_tab_assessment` (`assessment_id`),
  CONSTRAINT `fk_tab_user_log` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. BATASAN STRICT LOGIN ADMIN: INSERT / UPDATE 3 AKUN ADMIN RESMI
-- Password default: 12345678 (Bcrypt hash: $2y$10$bVsAMvjSrr2NXhONHWl88OPkC67Kon1J32qKUwNUMS37qb95zdKzy)
INSERT INTO `users` (`username`, `password`, `role`, `status_approval`, `nama_lengkap`, `email`, `jenis_kelamin`, `no_hp`)
VALUES 
('mila', '$2y$10$bVsAMvjSrr2NXhONHWl88OPkC67Kon1J32qKUwNUMS37qb95zdKzy', 'admin', 'APPROVED', 'Nur Kamila Agustiyawati, S.Pd.', 'nurkamilaagustiyawati@gmail.com', 'Perempuan', '081234567891')
ON DUPLICATE KEY UPDATE 
  `password` = VALUES(`password`),
  `role` = 'admin',
  `status_approval` = 'APPROVED',
  `email` = 'nurkamilaagustiyawati@gmail.com',
  `nama_lengkap` = VALUES(`nama_lengkap`);

INSERT INTO `users` (`username`, `password`, `role`, `status_approval`, `nama_lengkap`, `email`, `jenis_kelamin`, `no_hp`)
VALUES 
('dewi', '$2y$10$bVsAMvjSrr2NXhONHWl88OPkC67Kon1J32qKUwNUMS37qb95zdKzy', 'admin', 'APPROVED', 'Dewi Rahmawati, M.Pd.', 'dewi05964@gmail.com', 'Perempuan', '081234567892')
ON DUPLICATE KEY UPDATE 
  `password` = VALUES(`password`),
  `role` = 'admin',
  `status_approval` = 'APPROVED',
  `email` = 'dewi05964@gmail.com',
  `nama_lengkap` = VALUES(`nama_lengkap`);

INSERT INTO `users` (`username`, `password`, `role`, `status_approval`, `nama_lengkap`, `email`, `jenis_kelamin`, `no_hp`)
VALUES 
('okta', '$2y$10$bVsAMvjSrr2NXhONHWl88OPkC67Kon1J32qKUwNUMS37qb95zdKzy', 'admin', 'APPROVED', 'Via Dwi Oktavia, S.Mat.', 'Via.dwi.2403116@students.um.ac.id', 'Perempuan', '081234567893')
ON DUPLICATE KEY UPDATE 
  `password` = VALUES(`password`),
  `role` = 'admin',
  `status_approval` = 'APPROVED',
  `email` = 'Via.dwi.2403116@students.um.ac.id',
  `nama_lengkap` = VALUES(`nama_lengkap`);

-- Pastikan semua pengguna default yang ada status_approval di-set 'APPROVED'
UPDATE `users` SET `status_approval` = 'APPROVED' WHERE `status_approval` IS NULL OR `status_approval` = '';

-- 6. PENGATURAN DURASI DEFAULT DAN SEEDER LATIHAN 1, 2, 3
UPDATE `assessments` SET `duration_minutes` = 30 WHERE `tipe` = 'lkpd' AND (`duration_minutes` IS NULL OR `duration_minutes` = 0);
UPDATE `assessments` SET `duration_minutes` = 45 WHERE `tipe` = 'latihan' AND (`duration_minutes` IS NULL OR `duration_minutes` = 0);
UPDATE `assessments` SET `duration_minutes` = 60 WHERE `tipe` = 'evaluasi' AND (`duration_minutes` IS NULL OR `duration_minutes` = 0);

-- Tambah Latihan 2 jika belum ada untuk simulasi penguncian hirarkis
INSERT INTO `assessments` (`id`, `tipe`, `judul`, `urutan`, `deskripsi`, `kelas`, `passing_grade`, `duration_minutes`, `deadline`, `created_by`)
VALUES (4, 'latihan', 'Latihan 2: Penyajian Data & Diagram Lingkaran', 2, 'Latihan lanjutan untuk penyajian data dalam diagram batang dan lingkaran.', 'Semua Kelas', 0, 45, '2026-11-30 23:59:00', 2)
ON DUPLICATE KEY UPDATE `urutan` = 2, `duration_minutes` = 45;

-- Update Latihan 1 urutan = 1
UPDATE `assessments` SET `urutan` = 1, `judul` = 'Latihan 1: Statistika & Ukuran Pemusatan' WHERE `id` = 2;

-- Tambah soal untuk Latihan 2 jika belum ada
INSERT INTO `questions` (`assessment_id`, `urutan`, `tipe`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `kunci_jawaban`, `feedback_benar`, `feedback_salah`, `bobot`)
SELECT 4, 1, 'pg', 'Besar sudut pusat untuk suatu kategori data yang bernilai 25% pada diagram lingkaran adalah...', '45 derajat', '90 derajat', '120 derajat', '180 derajat', '1', 'Tepat! 25% dari 360 derajat = 90 derajat.', 'Kurang tepat. Kalikan 25% dengan total 360 derajat lingkaran.', 50.0
FROM DUAL WHERE NOT EXISTS (SELECT * FROM `questions` WHERE `assessment_id` = 4 AND `urutan` = 1);

INSERT INTO `questions` (`assessment_id`, `urutan`, `tipe`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `kunci_jawaban`, `feedback_benar`, `feedback_salah`, `bobot`)
SELECT 4, 2, 'essay', 'Jika dalam satu kelas terdapat 40 siswa dan 10 orang gemar matematika, berapa derajat sudut yang terbentuk pada diagram lingkaran dan jelaskan langkah perhitungannya!', NULL, NULL, NULL, NULL, '(10 / 40) x 360 derajat = 90 derajat.', 'Perhitungan sudut dan penjelasannya sangat jelas dan sistematis.', 'Gunakan rumus (Frekuensi / Total) x 360.', 50.0
FROM DUAL WHERE NOT EXISTS (SELECT * FROM `questions` WHERE `assessment_id` = 4 AND `urutan` = 2);

-- Selesai
