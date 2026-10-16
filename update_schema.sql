-- =================================================================
-- SKRIP PEMBARUAN STRUKTUR LENGKAP DATABASE ELEARNMATH
-- File: database/update_schema.sql
-- Database: db_sekolah (XAMPP MySQL / MariaDB)
-- Eksekusi di phpMyAdmin atau MySQL CLI
-- Mendukung seluruh fitur: LKPD, Latihan, Retake Timer, Review Hasil,
-- Upload Foto Essay Webcam, Admit Class Admin, & Media Multimedia.
-- =================================================================

USE `db_sekolah`;

-- 1. PEMBARUAN TABEL users (PENGGUNA & OTENTIKASI & ADMIT BY ADMIN)
SET @col_approval = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'status_approval');
SET @sql_approval = IF(@col_approval = 0, 
  "ALTER TABLE `users` ADD COLUMN `status_approval` ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'APPROVED' AFTER `role`", 
  "ALTER TABLE `users` MODIFY COLUMN `status_approval` ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'APPROVED'");
PREPARE stmt_appr FROM @sql_approval;
EXECUTE stmt_appr;
DEALLOCATE PREPARE stmt_appr;

SET @col_target_class = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'target_class_id');
SET @sql_target_class = IF(@col_target_class = 0, 
  "ALTER TABLE `users` ADD COLUMN `target_class_id` INT NULL AFTER `kelas`", 
  'SELECT 1');
PREPARE stmt_tc FROM @sql_target_class;
EXECUTE stmt_tc;
DEALLOCATE PREPARE stmt_tc;

SET @col_no_hp = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'no_hp');
SET @sql_no_hp = IF(@col_no_hp = 0, 
  "ALTER TABLE `users` ADD COLUMN `no_hp` VARCHAR(20) NULL AFTER `alamat`", 
  'SELECT 1');
PREPARE stmt_nh FROM @sql_no_hp;
EXECUTE stmt_nh;
DEALLOCATE PREPARE stmt_nh;

SET @col_bio = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'bio');
SET @sql_bio = IF(@col_bio = 0, 
  "ALTER TABLE `users` ADD COLUMN `bio` TEXT NULL AFTER `alamat`", 
  'SELECT 1');
PREPARE stmt_bio FROM @sql_bio;
EXECUTE stmt_bio;
DEALLOCATE PREPARE stmt_bio;

SET @col_foto = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'foto');
SET @sql_foto = IF(@col_foto = 0, 
  "ALTER TABLE `users` ADD COLUMN `foto` VARCHAR(255) NULL AFTER `no_hp`", 
  'SELECT 1');
PREPARE stmt_foto FROM @sql_foto;
EXECUTE stmt_foto;
DEALLOCATE PREPARE stmt_foto;

SET @col_last_active = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_active');
SET @sql_last_active = IF(@col_last_active = 0, 
  "ALTER TABLE `users` ADD COLUMN `last_active` DATETIME NULL AFTER `last_login`", 
  'SELECT 1');
PREPARE stmt_la FROM @sql_last_active;
EXECUTE stmt_la;
DEALLOCATE PREPARE stmt_la;

SET @col_is_online = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_online');
SET @sql_is_online = IF(@col_is_online = 0, 
  "ALTER TABLE `users` ADD COLUMN `is_online` TINYINT(1) DEFAULT 0 AFTER `mapel`", 
  'SELECT 1');
PREPARE stmt_io FROM @sql_is_online;
EXECUTE stmt_io;
DEALLOCATE PREPARE stmt_io;

-- 2. PEMBARUAN TABEL classes (KELAS & STATUS PERSETUJUAN ADMIN)
SET @col_class_status = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'classes' AND COLUMN_NAME = 'status');
SET @sql_class_status = IF(@col_class_status = 0, 
  "ALTER TABLE `classes` ADD COLUMN `status` ENUM('PENDING_APPROVAL', 'APPROVED') NOT NULL DEFAULT 'APPROVED' AFTER `teacher_id`", 
  "ALTER TABLE `classes` MODIFY COLUMN `status` ENUM('PENDING_APPROVAL', 'APPROVED') NOT NULL DEFAULT 'APPROVED'");
PREPARE stmt_cs FROM @sql_class_status;
EXECUTE stmt_cs;
DEALLOCATE PREPARE stmt_cs;

-- 3. TABEL PERMINTAAN KELAS GURU (teacher_class_requests)
CREATE TABLE IF NOT EXISTS `teacher_class_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `teacher_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `status` ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_tcr` (`teacher_id`, `class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. PEMBARUAN TABEL materi (MULTIMEDIA & MEDIA EMBED & KELAS)
SET @col_mat_media = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'materi' AND COLUMN_NAME = 'media_link');
SET @sql_mat_media = IF(@col_mat_media = 0, 
  "ALTER TABLE `materi` ADD COLUMN `media_link` VARCHAR(500) NULL AFTER `youtube_url`", 
  'SELECT 1');
PREPARE stmt_mm FROM @sql_mat_media;
EXECUTE stmt_mm;
DEALLOCATE PREPARE stmt_mm;

SET @col_mat_kelas = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'materi' AND COLUMN_NAME = 'kelas');
SET @sql_mat_kelas = IF(@col_mat_kelas = 0, 
  "ALTER TABLE `materi` ADD COLUMN `kelas` VARCHAR(50) DEFAULT 'Semua Kelas' AFTER `deskripsi`", 
  'SELECT 1');
PREPARE stmt_mk FROM @sql_mat_kelas;
EXECUTE stmt_mk;
DEALLOCATE PREPARE stmt_mk;

UPDATE `materi` SET `media_link` = `youtube_url` WHERE (`media_link` IS NULL OR `media_link` = '') AND `youtube_url` IS NOT NULL;

-- 5. TABEL RIWAYAT BACA MATERI SISWA (materi_reads)
CREATE TABLE IF NOT EXISTS `materi_reads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `materi_id` INT NOT NULL,
  `read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_reads` (`user_id`, `materi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. PEMBARUAN TABEL assessments (LKPD, LATIHAN, EVALUASI)
SET @col_dur = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'assessments' AND COLUMN_NAME = 'duration_minutes');
SET @sql_dur = IF(@col_dur = 0, 
  "ALTER TABLE `assessments` ADD COLUMN `duration_minutes` INT DEFAULT 30 AFTER `passing_grade`", 
  'SELECT 1');
PREPARE stmt_dur FROM @sql_dur;
EXECUTE stmt_dur;
DEALLOCATE PREPARE stmt_dur;

SET @col_urutan = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'assessments' AND COLUMN_NAME = 'urutan');
SET @sql_urutan = IF(@col_urutan = 0, 
  "ALTER TABLE `assessments` ADD COLUMN `urutan` INT DEFAULT 1 AFTER `judul`", 
  'SELECT 1');
PREPARE stmt_urutan FROM @sql_urutan;
EXECUTE stmt_urutan;
DEALLOCATE PREPARE stmt_urutan;

SET @col_kelas = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'assessments' AND COLUMN_NAME = 'kelas');
SET @sql_kelas = IF(@col_kelas = 0, 
  "ALTER TABLE `assessments` ADD COLUMN `kelas` VARCHAR(50) DEFAULT 'Semua Kelas' AFTER `deskripsi`", 
  'SELECT 1');
PREPARE stmt_kelas FROM @sql_kelas;
EXECUTE stmt_kelas;
DEALLOCATE PREPARE stmt_kelas;

SET @col_active = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'assessments' AND COLUMN_NAME = 'is_active');
SET @sql_active = IF(@col_active = 0, 
  "ALTER TABLE `assessments` ADD COLUMN `is_active` TINYINT(1) DEFAULT 1 AFTER `deadline`", 
  'SELECT 1');
PREPARE stmt_active FROM @sql_active;
EXECUTE stmt_active;
DEALLOCATE PREPARE stmt_active;

-- Standarisasi Durasi LKPD / Latihan / Evaluasi jika masih 0
UPDATE `assessments` SET `duration_minutes` = 30 WHERE `tipe` = 'lkpd' AND (`duration_minutes` IS NULL OR `duration_minutes` = 0);
UPDATE `assessments` SET `duration_minutes` = 45 WHERE `tipe` = 'latihan' AND (`duration_minutes` IS NULL OR `duration_minutes` = 0);
UPDATE `assessments` SET `duration_minutes` = 60 WHERE `tipe` = 'evaluasi' AND (`duration_minutes` IS NULL OR `duration_minutes` = 0);

-- 7. PEMBARUAN TABEL questions (KUNCI JAWABAN & PEMBAHASAN)
ALTER TABLE `questions` MODIFY COLUMN `kunci_jawaban` TEXT NULL;

-- 8. PEMBARUAN TABEL submissions (TIMER, ATTEMPT, RETAKE & STATUS)
SET @col_sub_start = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'submissions' AND COLUMN_NAME = 'start_time');
SET @sql_sub_start = IF(@col_sub_start = 0, 
  "ALTER TABLE `submissions` ADD COLUMN `start_time` DATETIME NULL AFTER `attempt`", 
  'SELECT 1');
PREPARE stmt_sstart FROM @sql_sub_start;
EXECUTE stmt_sstart;
DEALLOCATE PREPARE stmt_sstart;

SET @col_sub_started = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'submissions' AND COLUMN_NAME = 'started_at');
SET @sql_sub_started = IF(@col_sub_started = 0, 
  "ALTER TABLE `submissions` ADD COLUMN `started_at` DATETIME NULL AFTER `start_time`", 
  'SELECT 1');
PREPARE stmt_sstarted FROM @sql_sub_started;
EXECUTE stmt_sstarted;
DEALLOCATE PREPARE stmt_sstarted;

SET @col_attempt = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'submissions' AND COLUMN_NAME = 'attempt');
SET @sql_attempt = IF(@col_attempt = 0, 
  "ALTER TABLE `submissions` ADD COLUMN `attempt` INT NOT NULL DEFAULT 1 AFTER `is_corrected`", 
  'SELECT 1');
PREPARE stmt_att FROM @sql_attempt;
EXECUTE stmt_att;
DEALLOCATE PREPARE stmt_att;

SET @col_retake = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'submissions' AND COLUMN_NAME = 'allow_retake');
SET @sql_retake = IF(@col_retake = 0, 
  "ALTER TABLE `submissions` ADD COLUMN `allow_retake` TINYINT(1) DEFAULT 0 AFTER `is_corrected`", 
  'SELECT 1');
PREPARE stmt_retake FROM @sql_retake;
EXECUTE stmt_retake;
DEALLOCATE PREPARE stmt_retake;

-- 9. PEMBARUAN TABEL submission_answers (JAWABAN ESSAY / FOTO / AI FEEDBACK / GURU)
SET @col_foto_jawaban = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'submission_answers' AND COLUMN_NAME = 'foto_jawaban');
SET @sql_foto_jawaban = IF(@col_foto_jawaban = 0, 
  "ALTER TABLE `submission_answers` ADD COLUMN `foto_jawaban` VARCHAR(255) NULL AFTER `jawaban_siswa`", 
  'SELECT 1');
PREPARE stmt_fj FROM @sql_foto_jawaban;
EXECUTE stmt_fj;
DEALLOCATE PREPARE stmt_fj;

SET @col_ai_score = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'submission_answers' AND COLUMN_NAME = 'ai_score');
SET @sql_ai_score = IF(@col_ai_score = 0, 
  "ALTER TABLE `submission_answers` ADD COLUMN `ai_score` DECIMAL(5,2) DEFAULT NULL AFTER `is_correct`", 
  'SELECT 1');
PREPARE stmt_ais FROM @sql_ai_score;
EXECUTE stmt_ais;
DEALLOCATE PREPARE stmt_ais;

SET @col_ai_fb = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'submission_answers' AND COLUMN_NAME = 'ai_feedback');
SET @sql_ai_fb = IF(@col_ai_fb = 0, 
  "ALTER TABLE `submission_answers` ADD COLUMN `ai_feedback` TEXT NULL AFTER `ai_score`", 
  'SELECT 1');
PREPARE stmt_aifb FROM @sql_ai_fb;
EXECUTE stmt_aifb;
DEALLOCATE PREPARE stmt_aifb;

SET @col_tch_score = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'submission_answers' AND COLUMN_NAME = 'teacher_score');
SET @sql_tch_score = IF(@col_tch_score = 0, 
  "ALTER TABLE `submission_answers` ADD COLUMN `teacher_score` DECIMAL(5,2) DEFAULT NULL AFTER `ai_feedback`", 
  'SELECT 1');
PREPARE stmt_tchs FROM @sql_tch_score;
EXECUTE stmt_tchs;
DEALLOCATE PREPARE stmt_tchs;

SET @col_tch_fb = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'submission_answers' AND COLUMN_NAME = 'teacher_feedback');
SET @sql_tch_fb = IF(@col_tch_fb = 0, 
  "ALTER TABLE `submission_answers` ADD COLUMN `teacher_feedback` TEXT NULL AFTER `teacher_score`", 
  'SELECT 1');
PREPARE stmt_tchfb FROM @sql_tch_fb;
EXECUTE stmt_tchfb;
DEALLOCATE PREPARE stmt_tchfb;

-- 10. PEMBARUAN TABEL tab_switch_logs
SET @col_ts_nama = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'tab_switch_logs' AND COLUMN_NAME = 'nama_siswa');
SET @sql_ts_nama = IF(@col_ts_nama = 0, 
  "ALTER TABLE `tab_switch_logs` ADD COLUMN `nama_siswa` VARCHAR(100) NOT NULL DEFAULT '' AFTER `user_id`", 
  'SELECT 1');
PREPARE stmt_tsn FROM @sql_ts_nama;
EXECUTE stmt_tsn;
DEALLOCATE PREPARE stmt_tsn;

SET @col_ts_kelas = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'tab_switch_logs' AND COLUMN_NAME = 'kelas');
SET @sql_ts_kelas = IF(@col_ts_kelas = 0, 
  "ALTER TABLE `tab_switch_logs` ADD COLUMN `kelas` VARCHAR(50) NULL AFTER `nama_siswa`", 
  'SELECT 1');
PREPARE stmt_tsk FROM @sql_ts_kelas;
EXECUTE stmt_tsk;
DEALLOCATE PREPARE stmt_tsk;

SET @col_ts_ass_id = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'tab_switch_logs' AND COLUMN_NAME = 'assessment_id');
SET @sql_ts_ass_id = IF(@col_ts_ass_id = 0, 
  "ALTER TABLE `tab_switch_logs` ADD COLUMN `assessment_id` INT NULL AFTER `kelas`", 
  'SELECT 1');
PREPARE stmt_tsa FROM @sql_ts_ass_id;
EXECUTE stmt_tsa;
DEALLOCATE PREPARE stmt_tsa;

SET @col_ts_ass_title = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'tab_switch_logs' AND COLUMN_NAME = 'assessment_title');
SET @sql_ts_ass_title = IF(@col_ts_ass_title = 0, 
  "ALTER TABLE `tab_switch_logs` ADD COLUMN `assessment_title` VARCHAR(255) NOT NULL DEFAULT 'Ujian/Modul' AFTER `assessment_id`", 
  'SELECT 1');
PREPARE stmt_tst FROM @sql_ts_ass_title;
EXECUTE stmt_tst;
DEALLOCATE PREPARE stmt_tst;

SET @col_ts_count = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'tab_switch_logs' AND COLUMN_NAME = 'switch_count');
SET @sql_ts_count = IF(@col_ts_count = 0, 
  "ALTER TABLE `tab_switch_logs` ADD COLUMN `switch_count` INT NOT NULL DEFAULT 1 AFTER `assessment_title`", 
  'SELECT 1');
PREPARE stmt_tsc FROM @sql_ts_count;
EXECUTE stmt_tsc;
DEALLOCATE PREPARE stmt_tsc;

SET @col_ts_last = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'tab_switch_logs' AND COLUMN_NAME = 'last_switched_at');
SET @sql_ts_last = IF(@col_ts_last = 0, 
  "ALTER TABLE `tab_switch_logs` ADD COLUMN `last_switched_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `switch_count`", 
  'SELECT 1');
PREPARE stmt_tsl FROM @sql_ts_last;
EXECUTE stmt_tsl;
DEALLOCATE PREPARE stmt_tsl;

SET @col_ts_notes = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'db_sekolah' AND TABLE_NAME = 'tab_switch_logs' AND COLUMN_NAME = 'notes');
SET @sql_ts_notes = IF(@col_ts_notes = 0, 
  "ALTER TABLE `tab_switch_logs` ADD COLUMN `notes` VARCHAR(255) NULL DEFAULT 'Pindah tab peramban saat pengerjaan' AFTER `last_switched_at`", 
  'SELECT 1');
PREPARE stmt_tso FROM @sql_ts_notes;
EXECUTE stmt_tso;
DEALLOCATE PREPARE stmt_tso;

-- 11. BUAT VIEW & ALIAS TABEL PENDUKUNG (users, lkpd, pengerjaan_lkpd, materi, latihan, pengerjaan_latihan, jawaban)
CREATE OR REPLACE VIEW `lkpd` AS 
  SELECT * FROM `assessments` WHERE `tipe` = 'lkpd';

CREATE OR REPLACE VIEW `latihan` AS 
  SELECT * FROM `assessments` WHERE `tipe` = 'latihan';

CREATE OR REPLACE VIEW `evaluasi` AS 
  SELECT * FROM `assessments` WHERE `tipe` = 'evaluasi';

CREATE OR REPLACE VIEW `kelas` AS 
  SELECT * FROM `classes`;

CREATE OR REPLACE VIEW `jawaban` AS 
  SELECT * FROM `submission_answers`;

-- View Relasi Pengerjaan & Jawaban LKPD Siswa
CREATE OR REPLACE VIEW `pengerjaan_lkpd` AS 
  SELECT 
    s.id,
    s.id AS submission_id,
    s.student_id,
    s.assessment_id,
    s.assessment_id AS lkpd_id,
    s.pg_score,
    s.essay_score,
    s.total_score,
    s.ai_suggested_score,
    s.status,
    s.is_corrected,
    s.allow_retake,
    s.attempt,
    s.start_time,
    s.started_at,
    s.submitted_at,
    s.submitted_at AS created_at,
    s.reviewed_at,
    s.reviewed_by,
    s.feedback_guru,
    a.judul AS judul_lkpd,
    a.passing_grade,
    a.duration_minutes,
    a.kelas
  FROM `submissions` s
  JOIN `assessments` a ON s.assessment_id = a.id
  WHERE a.tipe = 'lkpd';

CREATE OR REPLACE VIEW `jawaban_lkpd` AS 
  SELECT 
    sa.id,
    sa.submission_id,
    sa.question_id,
    sa.jawaban_siswa,
    sa.foto_jawaban,
    sa.is_correct,
    sa.ai_score,
    sa.ai_feedback,
    sa.teacher_score,
    sa.teacher_feedback,
    s.student_id,
    s.assessment_id AS lkpd_id,
    s.submitted_at AS created_at
  FROM `submission_answers` sa
  JOIN `submissions` s ON sa.submission_id = s.id
  JOIN `assessments` a ON s.assessment_id = a.id
  WHERE a.tipe = 'lkpd';

-- View Relasi Pengerjaan & Jawaban Latihan Siswa
CREATE OR REPLACE VIEW `pengerjaan_latihan` AS 
  SELECT 
    s.id,
    s.id AS submission_id,
    s.student_id,
    s.assessment_id,
    s.assessment_id AS latihan_id,
    s.pg_score,
    s.essay_score,
    s.total_score,
    s.ai_suggested_score,
    s.status,
    s.is_corrected,
    s.allow_retake,
    s.attempt,
    s.start_time,
    s.started_at,
    s.submitted_at,
    s.submitted_at AS created_at,
    s.reviewed_at,
    s.reviewed_by,
    s.feedback_guru,
    a.judul AS judul_latihan,
    a.passing_grade,
    a.duration_minutes,
    a.kelas
  FROM `submissions` s
  JOIN `assessments` a ON s.assessment_id = a.id
  WHERE a.tipe = 'latihan';

CREATE OR REPLACE VIEW `jawaban_latihan` AS 
  SELECT 
    sa.id,
    sa.submission_id,
    sa.question_id,
    sa.jawaban_siswa,
    sa.foto_jawaban,
    sa.is_correct,
    sa.ai_score,
    sa.ai_feedback,
    sa.teacher_score,
    sa.teacher_feedback,
    s.student_id,
    s.assessment_id AS latihan_id,
    s.submitted_at AS created_at
  FROM `submission_answers` sa
  JOIN `submissions` s ON sa.submission_id = s.id
  JOIN `assessments` a ON s.assessment_id = a.id
  WHERE a.tipe = 'latihan';

-- 12. TABEL teacher_admin_messages (PESAN / LAPORAN GURU KE ADMIN)
CREATE TABLE IF NOT EXISTS `teacher_admin_messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` INT(11) NOT NULL,
  `sender_id` INT(11) NOT NULL,
  `receiver_id` INT(11) DEFAULT NULL,
  `sender_role` ENUM('guru','admin') NOT NULL,
  `judul` VARCHAR(255) DEFAULT NULL,
  `pesan` TEXT NOT NULL,
  `status` ENUM('unread','read','replied') DEFAULT 'unread',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `sender_id` (`sender_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. RESTRIKSI 3 AKUN ADMIN UTAMA RESMI
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

UPDATE `users` 
SET `role` = 'guru' 
WHERE `role` = 'admin' 
  AND LOWER(`username`) NOT IN ('mila', 'dewi', 'okta')
  AND LOWER(`email`) NOT IN ('nurkamilaagustiyawati@gmail.com', 'dewi05964@gmail.com', 'via.dwi.2403116@students.um.ac.id');
