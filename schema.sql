-- =================================================================
-- SKEMA LENGKAP DATABASE ELEARNMATH (XAMPP MYSQL)
-- Database: db_sekolah
-- Jalankan di phpMyAdmin atau MySQL CLI XAMPP
-- =================================================================

CREATE DATABASE IF NOT EXISTS `db_sekolah`
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `db_sekolah`;

DROP TABLE IF EXISTS `tab_switch_logs`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `submission_answers`;
DROP TABLE IF EXISTS `submissions`;
DROP TABLE IF EXISTS `questions`;
DROP TABLE IF EXISTS `assessments`;
DROP TABLE IF EXISTS `materi`;
DROP TABLE IF EXISTS `jadwal_pelajaran`;
DROP TABLE IF EXISTS `class_students`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `users`;

-- 1. TABEL PENGGUNA (users)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'guru', 'siswa', 'staff') NOT NULL DEFAULT 'siswa',
  `status_approval` ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'APPROVED',
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NULL,
  `nisn` VARCHAR(20) NULL,
  `kelas` VARCHAR(50) NULL,
  `target_class_id` INT NULL,
  `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') DEFAULT 'Laki-laki',
  `agama` VARCHAR(30) DEFAULT 'Islam',
  `nama_wali` VARCHAR(100) NULL,
  `asal_sekolah` VARCHAR(100) NULL,
  `alamat` TEXT NULL,
  `bio` TEXT NULL,
  `no_hp` VARCHAR(20) NULL,
  `foto` VARCHAR(255) NULL,
  `mapel` VARCHAR(100) NULL,
  `is_online` TINYINT(1) DEFAULT 0,
  `last_login` DATETIME NULL,
  `last_active` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_role` (`role`),
  INDEX `idx_approval` (`status_approval`),
  INDEX `idx_username` (`username`),
  INDEX `idx_last_active` (`last_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TABEL KELAS (classes)
CREATE TABLE `classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `teacher_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_classes_teacher` FOREIGN KEY (`teacher_id`) 
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABEL RELASI SISWA & KELAS (class_students)
CREATE TABLE `class_students` (
  `class_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  PRIMARY KEY (`class_id`, `student_id`),
  CONSTRAINT `fk_cs_class` FOREIGN KEY (`class_id`) 
    REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cs_student` FOREIGN KEY (`student_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. TABEL JADWAL PELAJARAN (jadwal_pelajaran)
CREATE TABLE `jadwal_pelajaran` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `guru_id` INT NOT NULL,
  `nama_guru` VARCHAR(100) NOT NULL,
  `kode_mapel` VARCHAR(20) NOT NULL,
  `nama_mapel` VARCHAR(100) NOT NULL,
  `kelas` VARCHAR(50) NOT NULL,
  `hari` ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') NOT NULL,
  `jam_mulai` TIME NOT NULL,
  `jam_selesai` TIME NOT NULL,
  `ruangan` VARCHAR(50) DEFAULT 'Ruang Kelas',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_jadwal_kelas` (`kelas`),
  INDEX `idx_jadwal_hari` (`hari`),
  CONSTRAINT `fk_jadwal_guru` FOREIGN KEY (`guru_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. TABEL MATERI (materi)
CREATE TABLE `materi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `guru_id` INT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT NULL,
  `icon` VARCHAR(50) DEFAULT 'book-open',
  `subbab` JSON NULL,
  `konten` LONGTEXT NOT NULL,
  `youtube_url` VARCHAR(255) NULL,
  `file_attachment` VARCHAR(255) NULL,
  `urutan` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_materi_guru` FOREIGN KEY (`guru_id`) 
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. TABEL ASSESSMENTS (assessments: LKPD, LATIHAN 1, 2, 3, EVALUASI)
CREATE TABLE `assessments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipe` ENUM('lkpd', 'latihan', 'evaluasi') NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `urutan` INT DEFAULT 1,
  `deskripsi` TEXT NULL,
  `kelas` VARCHAR(50) DEFAULT 'Semua Kelas',
  `passing_grade` INT DEFAULT 0,
  `duration_minutes` INT DEFAULT 30,
  `deadline` DATETIME NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_assessment_tipe` (`tipe`),
  INDEX `idx_assessment_urutan` (`urutan`),
  CONSTRAINT `fk_assessment_creator` FOREIGN KEY (`created_by`) 
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. TABEL SOAL (questions)
CREATE TABLE `questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `assessment_id` INT NOT NULL,
  `urutan` INT DEFAULT 1,
  `tipe` ENUM('pg', 'essay') NOT NULL,
  `pertanyaan` TEXT NOT NULL,
  `media_url` VARCHAR(255) NULL,
  `opsi_a` TEXT NULL,
  `opsi_b` TEXT NULL,
  `opsi_c` TEXT NULL,
  `opsi_d` TEXT NULL,
  `kunci_jawaban` VARCHAR(50) NULL,
  `feedback_benar` TEXT NULL,
  `feedback_salah` TEXT NULL,
  `bobot` DECIMAL(5,2) DEFAULT 1.0,
  INDEX `idx_question_assessment` (`assessment_id`),
  CONSTRAINT `fk_questions_assessment` FOREIGN KEY (`assessment_id`) 
    REFERENCES `assessments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. TABEL SUBMISSIONS (submissions)
CREATE TABLE `submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `assessment_id` INT NOT NULL,
  `pg_score` DECIMAL(5,2) DEFAULT NULL,
  `essay_score` DECIMAL(5,2) DEFAULT NULL,
  `total_score` DECIMAL(5,2) DEFAULT NULL,
  `ai_suggested_score` DECIMAL(5,2) DEFAULT NULL,
  `status` ENUM('submitted', 'reviewed') DEFAULT 'submitted',
  `is_corrected` TINYINT(1) DEFAULT 0,
  `allow_retake` TINYINT(1) DEFAULT 0,
  `feedback_guru` TEXT NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` DATETIME NULL,
  `reviewed_by` INT NULL,
  INDEX `idx_sub_student` (`student_id`),
  INDEX `idx_sub_assessment` (`assessment_id`),
  CONSTRAINT `fk_sub_student` FOREIGN KEY (`student_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sub_assessment` FOREIGN KEY (`assessment_id`) 
    REFERENCES `assessments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sub_reviewer` FOREIGN KEY (`reviewed_by`) 
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. TABEL JAWABAN SISWA (submission_answers)
CREATE TABLE `submission_answers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `submission_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `jawaban_siswa` TEXT NULL,
  `foto_jawaban` VARCHAR(255) NULL,
  `is_correct` TINYINT(1) DEFAULT NULL,
  `ai_score` DECIMAL(5,2) DEFAULT NULL,
  `ai_feedback` TEXT NULL,
  `teacher_score` DECIMAL(5,2) DEFAULT NULL,
  `teacher_feedback` TEXT NULL,
  CONSTRAINT `fk_sa_submission` FOREIGN KEY (`submission_id`) 
    REFERENCES `submissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sa_question` FOREIGN KEY (`question_id`) 
    REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. TABEL DETEKSI KELUAR TAB (tab_switch_logs)
CREATE TABLE `tab_switch_logs` (
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
  CONSTRAINT `fk_tab_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. TABEL NOTIFIKASI (notifications)
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `judul` VARCHAR(150) NOT NULL,
  `pesan` TEXT NOT NULL,
  `tipe` ENUM('info', 'grade', 'schedule', 'warning') DEFAULT 'info',
  `link` VARCHAR(255) NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notif_user` (`user_id`, `is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. TABEL LOG AKTIVITAS LOGIN GURU (activity_logs)
CREATE TABLE `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NULL,
  `role` VARCHAR(20) NOT NULL,
  `action` VARCHAR(50) NOT NULL DEFAULT 'LOGIN',
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT NULL,
  `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_log_role` (`role`),
  INDEX `idx_log_user` (`user_id`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. TABEL PENGATURAN SISTEM (system_settings)
CREATE TABLE `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `description` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =================================================================
-- SEED DATA AWAL
-- =================================================================

-- 3 AKUN ADMIN RESMI (Password hash bcrypt dari 12345678)
-- 1. nurkamilaagustiyawati@gmail.com | mila | 12345678
-- 2. dewi05964@gmail.com | dewi | 12345678
-- 3. Via.dwi.2403116@students.um.ac.id | okta | 12345678
INSERT INTO `users` (`id`, `username`, `password`, `role`, `status_approval`, `nama_lengkap`, `email`, `kelas`, `jenis_kelamin`, `mapel`, `nisn`, `no_hp`, `foto`) VALUES
(1, 'mila', '$2y$10$bVsAMvjSrr2NXhONHWl88OPkC67Kon1J32qKUwNUMS37qb95zdKzy', 'admin', 'APPROVED', 'Nur Kamila Agustiyawati, S.Pd.', 'nurkamilaagustiyawati@gmail.com', NULL, 'Perempuan', NULL, NULL, '081234567891', NULL),
(2, 'dewi', '$2y$10$bVsAMvjSrr2NXhONHWl88OPkC67Kon1J32qKUwNUMS37qb95zdKzy', 'admin', 'APPROVED', 'Dewi Rahmawati, M.Pd.', 'dewi05964@gmail.com', NULL, 'Perempuan', NULL, NULL, '081234567892', NULL),
(3, 'okta', '$2y$10$bVsAMvjSrr2NXhONHWl88OPkC67Kon1J32qKUwNUMS37qb95zdKzy', 'admin', 'APPROVED', 'Via Dwi Oktavia, S.Mat.', 'Via.dwi.2403116@students.um.ac.id', NULL, 'Perempuan', NULL, NULL, '081234567893', NULL),
(4, 'guru1', '$2y$10$bVsAMvjSrr2NXhONHWl88OPkC67Kon1J32qKUwNUMS37qb95zdKzy', 'guru', 'APPROVED', 'Budi Raharjo, S.Pd.', 'budi@guru.sekolah.id', 'VII-A, VII-B', 'Laki-laki', 'Matematika', NULL, '081234567890', NULL),
(5, 'siswa1', '$2y$10$bVsAMvjSrr2NXhONHWl88OPkC67Kon1J32qKUwNUMS37qb95zdKzy', 'siswa', 'APPROVED', 'Ahmad Faiz', 'ahmad@siswa.sekolah.id', 'VII-A', 'Laki-laki', NULL, '1234567890', '082198765432', NULL),
(6, 'siswa2', '$2y$10$bVsAMvjSrr2NXhONHWl88OPkC67Kon1J32qKUwNUMS37qb95zdKzy', 'siswa', 'APPROVED', 'Siti Nurhaliza', 'siti@siswa.sekolah.id', 'VII-A', 'Perempuan', NULL, '1234567891', '082198765433', NULL);

INSERT INTO `classes` (`id`, `name`, `teacher_id`) VALUES
(1, 'VII-A', 4),
(2, 'VII-B', 4),
(3, 'VII-C', 4);

INSERT INTO `class_students` (`class_id`, `student_id`) VALUES
(1, 5),
(1, 6);

INSERT INTO `jadwal_pelajaran` (`guru_id`, `nama_guru`, `kode_mapel`, `nama_mapel`, `kelas`, `hari`, `jam_mulai`, `jam_selesai`, `ruangan`) VALUES
(4, 'Budi Raharjo, S.Pd.', 'MTK-01', 'Matematika', 'VII-A', 'Senin', '07:00:00', '08:20:00', 'Ruang Kelas VII-A'),
(4, 'Budi Raharjo, S.Pd.', 'MTK-02', 'Matematika', 'VII-B', 'Selasa', '08:20:00', '09:40:00', 'Ruang Kelas VII-B'),
(4, 'Budi Raharjo, S.Pd.', 'MTK-03', 'Matematika', 'VII-C', 'Rabu', '07:00:00', '08:20:00', 'Ruang Kelas VII-C');

INSERT INTO `materi` (`id`, `guru_id`, `judul`, `deskripsi`, `icon`, `subbab`, `konten`, `youtube_url`, `urutan`) VALUES
(1, 4, 'Mengenal Jenis Data', 'Memahami perbedaan data kualitatif dan kuantitatif melalui contoh nyata.', 'bar-chart', '["Data Kualitatif", "Data Kuantitatif", "Contoh Sehari-hari"]', '<p>Data adalah catatan atas kumpulan fakta. Dalam statistika, data dibagi menjadi dua kategori besar:</p><ul><li><b>Data Kualitatif:</b> Data yang berbentuk narasi/kategori deskriptif (misal: warna favorit, tingkat kepuasan).</li><li><b>Data Kuantitatif:</b> Data yang dapat diukur dengan angka matematis (misal: tinggi badan, nilai ujian).</li></ul>', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 1),
(2, 4, 'Penyajian Data', 'Belajar menyajikan data dalam bentuk tabel frekuensi dan diagram.', 'pie-chart', '["Tabel Distribusi", "Diagram Batang", "Diagram Lingkaran"]', '<p>Penyajian data bertujuan agar informasi mudah dipahami oleh pembaca. Metode penyajian yang umum digunakan:</p><ol><li><b>Tabel Distribusi Frekuensi:</b> Mengelompokkan data berdasarkan frekuensi kemunculannya.</li><li><b>Diagram Batang:</b> Cocok untuk membandingkan jumlah antar kategori diskrit.</li><li><b>Diagram Lingkaran (Pie Chart):</b> Sangat baik untuk menampilkan proporsi persentase dari keseluruhan.</li></ol>', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 2),
(3, 4, 'Ukuran Pemusatan Data', 'Mengenal konsep Mean, Median, dan Modus dalam statistika dasar.', 'calculator', '["Mean (Rata-rata)", "Median (Nilai Tengah)", "Modus (Nilai Terbanyak)"]', '<p>Ukuran pemusatan data membantu merangkum sekumpulan data menjadi satu representasi nilai sentral:</p><ul><li><b>Mean:</b> Jumlah seluruh nilai dibagi banyaknya data.</li><li><b>Median:</b> Nilai tengah data setelah diurutkan dari terkecil hingga terbesar.</li><li><b>Modus:</b> Nilai yang paling sering muncul (frekuensi tertinggi).</li></ul>', NULL, 3);

-- Assessments: LKPD, Latihan 1, Latihan 2, Evaluasi Akhir
INSERT INTO `assessments` (`id`, `tipe`, `judul`, `urutan`, `deskripsi`, `kelas`, `passing_grade`, `duration_minutes`, `deadline`, `is_active`, `created_by`) VALUES
(1, 'lkpd', 'Lembar Kerja Peserta Didik (LKPD) Statistika', 1, 'LKPD mandiri untuk menguji pemahaman awal tentang jenis data dan representasi grafik.', 'Semua Kelas', 0, 30, '2026-10-30 23:59:00', 1, 4),
(2, 'latihan', 'Latihan 1: Statistika & Ukuran Pemusatan', 1, 'Uji pemahaman mandiri dengan soal pilihan ganda dan analisa essay singkat.', 'Semua Kelas', 0, 45, '2026-10-30 23:59:00', 1, 4),
(4, 'latihan', 'Latihan 2: Penyajian Data & Diagram Lingkaran', 2, 'Latihan lanjutan untuk penyajian data dalam diagram batang dan diagram lingkaran.', 'Semua Kelas', 0, 45, '2026-11-30 23:59:00', 1, 4),
(3, 'evaluasi', 'Evaluasi Akhir Bab Statistika', 1, 'Evaluasi komprehensif kemampuan analisis penyajian data dan ukuran pemusatan.', 'Semua Kelas', 0, 60, '2026-11-15 23:59:00', 1, 4);

-- Soal LKPD
INSERT INTO `questions` (`assessment_id`, `urutan`, `tipe`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `kunci_jawaban`, `feedback_benar`, `feedback_salah`, `bobot`) VALUES
(1, 1, 'pg', 'Warna kesukaan siswa dalam suatu kelas termasuk jenis data...', 'Data kualitatif', 'Data kuantitatif', 'Data campuran', 'Bukan data', '0', 'Tepat sekali! Warna kesukaan merupakan kategori kualitas, bukan besaran numerik.', 'Kurang tepat. Warna kesukaan tidak diukur dengan angka, jadi termasuk data kualitatif.', 50.0),
(1, 2, 'essay', 'Jelaskan perbedaan mendasar antara data kualitatif dan kuantitatif menurut pemahamanmu, sertakan contohnya!', NULL, NULL, NULL, NULL, 'Kualitatif berupa kata/sifat, kuantitatif berupa angka.', 'Analisis Anda sangat tajam dan jelas.', 'Perhatikan perbedaan angka dan kategori.', 50.0);

-- Soal Latihan 1
INSERT INTO `questions` (`assessment_id`, `urutan`, `tipe`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `kunci_jawaban`, `feedback_benar`, `feedback_salah`, `bobot`) VALUES
(2, 1, 'pg', 'Tinggi badan siswa (dalam cm) termasuk jenis data...', 'Data kualitatif', 'Data kuantitatif', 'Data nominal', 'Bukan data', '1', 'Benar! Tinggi badan dinyatakan dalam angka yang dapat diukur.', 'Jawaban kurang tepat. Angka tinggi badan tergolong data kuantitatif.', 25.0),
(2, 2, 'pg', 'Diagram yang paling tepat untuk menampilkan perbandingan proporsi persentase adalah...', 'Diagram batang', 'Diagram lingkaran', 'Diagram garis', 'Diagram tebar', '1', 'Tepat! Diagram lingkaran sangat efektif menggambarkan proporsi persentase.', 'Jawaban kurang tepat. Diagram lingkaran adalah representasi standar untuk persentase.', 25.0),
(2, 3, 'pg', 'Nilai yang paling sering muncul dalam sekumpulan data disebut...', 'Mean', 'Median', 'Modus', 'Kuartil', '2', 'Benar! Nilai dengan frekuensi terbanyak adalah Modus.', 'Jawaban kurang tepat. Modus adalah ukuran nilai dengan frekuensi kemunculan terbesar.', 25.0),
(2, 4, 'essay', 'Menurutmu mengapa memilih jenis diagram yang tepat penting saat menyajikan data hasil survei kelas? Jelaskan!', NULL, NULL, NULL, NULL, 'Agar data mudah dipahami dan tidak menimbulkan misinterpretasi bagi pembaca.', 'Jawaban komprehensif.', 'Coba jelaskan dampak bagi pembaca.', 25.0);

-- Soal Latihan 2
INSERT INTO `questions` (`assessment_id`, `urutan`, `tipe`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `kunci_jawaban`, `feedback_benar`, `feedback_salah`, `bobot`) VALUES
(4, 1, 'pg', 'Besar sudut pusat untuk suatu kategori data yang bernilai 25% pada diagram lingkaran adalah...', '45 derajat', '90 derajat', '120 derajat', '180 derajat', '1', 'Tepat! 25% dari 360 derajat = 90 derajat.', 'Kurang tepat. Kalikan 25% dengan total 360 derajat lingkaran.', 50.0),
(4, 2, 'essay', 'Jika dalam satu kelas terdapat 40 siswa dan 10 orang gemar matematika, berapa derajat sudut yang terbentuk pada diagram lingkaran dan jelaskan langkah perhitungannya!', NULL, NULL, NULL, NULL, '(10 / 40) x 360 derajat = 90 derajat.', 'Perhitungan sudut dan penjelasannya sangat jelas dan sistematis.', 'Gunakan rumus (Frekuensi / Total) x 360.', 50.0);

-- Soal Evaluasi Akhir
INSERT INTO `questions` (`assessment_id`, `urutan`, `tipe`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `kunci_jawaban`, `feedback_benar`, `feedback_salah`, `bobot`) VALUES
(3, 1, 'essay', 'Sebuah survei mencatat data nilai ulangan matematika dari 5 orang siswa: 80, 90, 70, 90, 85. Tentukan mean dan modusnya secara lengkap beserta cara pengerjaannya!', NULL, NULL, NULL, NULL, 'Mean = (80+90+70+90+85)/5 = 415/5 = 83. Modus = 90 (muncul 2 kali).', 'Perhitungan langkah dan hasil akhir Anda sangat rapi dan akurat!', 'Periksa kembali penjumlahan data ulangan.', 50.0),
(3, 2, 'essay', 'Siswa kelas VII A melakukan pendataan kegiatan ekstrakurikuler yang diminati. Diagram apa yang paling tepat untuk merefleksikan data tersebut dan mengapa?', NULL, NULL, NULL, NULL, 'Diagram batang atau diagram lingkaran, agar perbandingan peminat tiap ekskul terlihat jelas.', 'Penjelasan yang logis dan runtut.', 'Sertakan alasan visualisasinya.', 50.0);

INSERT INTO `notifications` (`user_id`, `judul`, `pesan`, `tipe`, `link`, `is_read`) VALUES
(5, 'Selamat Datang di ElearnMath', 'Selamat bergabung di platform pembelajaran matematika. Cek jadwal pelajaran dan materi baru kamu.', 'info', 'jadwal.php', 0),
(5, 'LKPD Telah Dibuka', 'LKPD Statistika telah tersedia dan dapat dikerjakan secara langsung tanpa syarat.', 'grade', 'lkpd.php', 0),
(6, 'Selamat Datang di ElearnMath', 'Selamat datang Siti! Silakan cek jadwal pelajaran matematika kamu minggu ini.', 'info', 'jadwal.php', 0);

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('gemini_api_key', '', 'API Key Google Gemini untuk koreksi otomatis essay matematika'),
('site_name', 'ElearnMath', 'Nama Platform E-Learning');
