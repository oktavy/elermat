-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2026 at 11:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_sekolah`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` varchar(20) NOT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `action` varchar(50) NOT NULL DEFAULT 'LOGIN',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `nama`, `email`, `role`, `kelas`, `action`, `ip_address`, `user_agent`, `login_time`) VALUES
(1, 3, 'Ibu Mila, S.Pd.', 'mila@guru.sekolah.id', 'guru', NULL, 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:17:36'),
(2, 3, 'Ibu Mila, S.Pd.', 'mila@guru.sekolah.id', 'guru', NULL, 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:40:51'),
(3, 3, 'Ibu Mila, S.Pd.', 'mila@guru.sekolah.id', 'guru', NULL, 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 05:40:00'),
(4, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', NULL, 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 06:46:29'),
(5, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', NULL, 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 07:03:35'),
(6, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', NULL, 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 08:09:21'),
(7, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', NULL, 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 08:12:50'),
(8, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', NULL, 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 08:33:36'),
(9, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', NULL, 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 08:34:03'),
(10, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', NULL, 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:23:34'),
(11, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', NULL, 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-01 09:30:19'),
(12, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', NULL, 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 06:57:00'),
(13, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', NULL, 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 07:02:17'),
(14, 21, 'ML', 'mila@gmail.com', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 17:27:28'),
(15, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 17:31:15'),
(16, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 17:32:01'),
(17, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 17:36:48'),
(18, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 18:25:29'),
(19, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 18:29:14'),
(20, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 18:34:10'),
(21, 23, 'KM', 'KM@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 18:35:47'),
(22, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 20:00:49'),
(23, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 20:02:06'),
(24, 21, 'ML', 'mila@gmail.com', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 20:04:33'),
(25, 21, 'ML', 'mila@gmail.com', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 20:06:27'),
(26, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-14 17:20:06'),
(27, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-14 17:25:34'),
(28, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-14 17:27:57'),
(30, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 06:02:19'),
(32, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 06:08:24'),
(34, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 06:47:23'),
(35, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 06:49:17'),
(36, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 06:55:04'),
(37, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 06:58:20'),
(38, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 06:59:33'),
(39, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 07:00:13'),
(40, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 07:02:42'),
(41, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 07:05:03'),
(42, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 07:42:14'),
(43, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 07:46:15'),
(44, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 07:49:09'),
(45, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 07:51:30'),
(46, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 08:23:58'),
(47, 9, 'KAMILA', 'KAMILA@GMAIL.COM', 'guru', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 08:29:38'),
(48, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 08:29:46'),
(49, 10, 'MIMIL', 'MIMIL@GMAIL.COM', 'siswa', 'VII-A', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 08:36:48'),
(50, 43, 'P', 'P@GMAIL.COM', 'siswa', '', 'LOGIN', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 09:09:05');

-- --------------------------------------------------------

--
-- Table structure for table `answers_grading`
--

CREATE TABLE `answers_grading` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `student_answer` text DEFAULT NULL,
  `ai_feedback` text DEFAULT NULL,
  `ai_score` decimal(5,2) DEFAULT NULL,
  `guru_feedback` text DEFAULT NULL,
  `final_score` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int(11) NOT NULL,
  `tipe` enum('lkpd','latihan','evaluasi') NOT NULL,
  `judul` varchar(255) NOT NULL,
  `urutan` int(11) DEFAULT 1,
  `deskripsi` text DEFAULT NULL,
  `kelas` varchar(50) DEFAULT 'Semua Kelas',
  `passing_grade` int(11) DEFAULT 0,
  `duration_minutes` int(11) DEFAULT 30,
  `deadline` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessments`
--

INSERT INTO `assessments` (`id`, `tipe`, `judul`, `urutan`, `deskripsi`, `kelas`, `passing_grade`, `duration_minutes`, `deadline`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'lkpd', 'Lembar Kerja Peserta Didik (LKPD) Statistika', 1, 'LKPD mandiri untuk menguji pemahaman awal tentang jenis data dan representasi grafik.', 'Semua Kelas', 0, 30, '2026-10-30 23:59:00', 1, NULL, '2026-09-30 17:09:24'),
(2, 'latihan', 'Latihan 1: Statistika & Ukuran Pemusatan', 1, 'Uji pemahaman mandiri dengan soal pilihan ganda dan analisa essay singkat.', 'Semua Kelas', 0, 50, '2026-10-30 23:59:00', 1, NULL, '2026-09-30 17:09:24'),
(3, 'evaluasi', 'Evaluasi Akhir Bab Statistika', 1, 'Evaluasi komprehensif kemampuan analisis penyajian data dan ukuran pemusatan.', 'Semua Kelas', 0, 50, '2026-11-15 23:59:00', 1, NULL, '2026-09-30 17:09:24');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `status` enum('PENDING_APPROVAL','APPROVED') NOT NULL DEFAULT 'APPROVED',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `name`, `teacher_id`, `status`, `created_at`) VALUES
(1, 'VII-A', NULL, 'APPROVED', '2026-09-30 17:09:23'),
(2, 'VII-B', NULL, 'APPROVED', '2026-09-30 17:09:23'),
(3, 'VII-C', 3, 'APPROVED', '2026-09-30 17:09:23');

-- --------------------------------------------------------

--
-- Table structure for table `class_students`
--

CREATE TABLE `class_students` (
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_students`
--

INSERT INTO `class_students` (`class_id`, `student_id`) VALUES
(1, 4),
(1, 5),
(1, 10),
(1, 21),
(1, 23);

-- --------------------------------------------------------

--
-- Stand-in structure for view `evaluasi`
-- (See below for the actual view)
--
CREATE TABLE `evaluasi` (
`id` int(11)
,`tipe` enum('lkpd','latihan','evaluasi')
,`judul` varchar(255)
,`urutan` int(11)
,`deskripsi` text
,`kelas` varchar(50)
,`passing_grade` int(11)
,`duration_minutes` int(11)
,`deadline` datetime
,`is_active` tinyint(1)
,`created_by` int(11)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_pelajaran`
--

CREATE TABLE `jadwal_pelajaran` (
  `id` int(11) NOT NULL,
  `guru_id` int(11) NOT NULL,
  `nama_guru` varchar(100) NOT NULL,
  `kode_mapel` varchar(20) NOT NULL,
  `nama_mapel` varchar(100) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `ruangan` varchar(50) DEFAULT 'Ruang Kelas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_pelajaran`
--

INSERT INTO `jadwal_pelajaran` (`id`, `guru_id`, `nama_guru`, `kode_mapel`, `nama_mapel`, `kelas`, `hari`, `jam_mulai`, `jam_selesai`, `ruangan`, `created_at`) VALUES
(2, 3, 'Ibu Mila, S.Pd.', 'MTK-02', 'Matematika', 'VII-B', 'Selasa', '08:20:00', '09:40:00', 'Ruang Kelas VII-B', '2026-09-30 17:09:24'),
(3, 3, 'Ibu Mila, S.Pd.', 'MTK-03', 'Matematika', 'VII-C', 'Rabu', '07:00:00', '08:20:00', 'Ruang Kelas VII-C', '2026-09-30 17:09:24'),
(5, 9, 'KAMILA', 'MTK-01', 'Matematika', 'VII-A', 'Senin', '07:00:00', '08:20:00', 'Ruang Kelas VII-A', '2026-10-01 06:43:46');

-- --------------------------------------------------------

--
-- Stand-in structure for view `jawaban`
-- (See below for the actual view)
--
CREATE TABLE `jawaban` (
`id` int(11)
,`submission_id` int(11)
,`question_id` int(11)
,`jawaban_siswa` text
,`foto_jawaban` varchar(255)
,`is_correct` tinyint(1)
,`ai_score` decimal(5,2)
,`ai_feedback` text
,`teacher_score` decimal(5,2)
,`teacher_feedback` text
,`student_id` int(11)
,`assessment_id` int(11)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `jawaban_latihan`
-- (See below for the actual view)
--
CREATE TABLE `jawaban_latihan` (
`id` int(11)
,`submission_id` int(11)
,`question_id` int(11)
,`jawaban_siswa` text
,`foto_jawaban` varchar(255)
,`is_correct` tinyint(1)
,`ai_score` decimal(5,2)
,`ai_feedback` text
,`teacher_score` decimal(5,2)
,`teacher_feedback` text
,`student_id` int(11)
,`latihan_id` int(11)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `jawaban_lkpd`
-- (See below for the actual view)
--
CREATE TABLE `jawaban_lkpd` (
`id` int(11)
,`submission_id` int(11)
,`question_id` int(11)
,`jawaban_siswa` text
,`foto_jawaban` varchar(255)
,`is_correct` tinyint(1)
,`ai_score` decimal(5,2)
,`ai_feedback` text
,`teacher_score` decimal(5,2)
,`teacher_feedback` text
,`student_id` int(11)
,`lkpd_id` int(11)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `kelas`
-- (See below for the actual view)
--
CREATE TABLE `kelas` (
`id` int(11)
,`name` varchar(50)
,`teacher_id` int(11)
,`status` enum('PENDING_APPROVAL','APPROVED')
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `latihan`
-- (See below for the actual view)
--
CREATE TABLE `latihan` (
`id` int(11)
,`tipe` enum('lkpd','latihan','evaluasi')
,`judul` varchar(255)
,`urutan` int(11)
,`deskripsi` text
,`kelas` varchar(50)
,`passing_grade` int(11)
,`duration_minutes` int(11)
,`deadline` datetime
,`is_active` tinyint(1)
,`created_by` int(11)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `lkpd`
-- (See below for the actual view)
--
CREATE TABLE `lkpd` (
`id` int(11)
,`tipe` enum('lkpd','latihan','evaluasi')
,`judul` varchar(255)
,`urutan` int(11)
,`deskripsi` text
,`kelas` varchar(50)
,`passing_grade` int(11)
,`duration_minutes` int(11)
,`deadline` datetime
,`is_active` tinyint(1)
,`created_by` int(11)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `materi`
--

CREATE TABLE `materi` (
  `id` int(11) NOT NULL,
  `guru_id` int(11) DEFAULT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kelas` varchar(50) DEFAULT 'Semua Kelas',
  `icon` varchar(50) DEFAULT 'book-open',
  `subbab` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`subbab`)),
  `konten` longtext NOT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `media_link` varchar(500) DEFAULT NULL,
  `file_attachment` varchar(255) DEFAULT NULL,
  `urutan` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materi`
--

INSERT INTO `materi` (`id`, `guru_id`, `judul`, `deskripsi`, `kelas`, `icon`, `subbab`, `konten`, `youtube_url`, `media_link`, `file_attachment`, `urutan`, `created_at`) VALUES
(1, NULL, 'Mengenal Jenis Data', 'Memahami perbedaan data kualitatif dan kuantitatif melalui contoh nyata.', 'Semua Kelas', 'bar-chart', '[\"Data Kualitatif\",\"Data Kuantitatif\",\"Contoh Sehari-hari\"]', '<p>Data adalah catatan atas kumpulan fakta. Dalam statistika, data dibagi menjadi dua kategori besar:</p><ul><li><b>Data Kualitatif:</b> Data yang berbentuk narasi/kategori deskriptif (misal: warna favorit, tingkat kepuasan).</li><li><b>Data Kuantitatif:</b> Data yang dapat diukur dengan angka matematis (misal: tinggi badan, nilai ujian).</li></ul>', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', NULL, 1, '2026-09-30 17:09:24'),
(2, NULL, 'Penyajian Data', 'Belajar menyajikan data dalam bentuk tabel frekuensi dan diagram.', 'Semua Kelas', 'pie-chart', '[\"Tabel Distribusi\", \"Diagram Batang\", \"Diagram Lingkaran\"]', '<p>Penyajian data bertujuan agar informasi mudah dipahami oleh pembaca. Metode penyajian yang umum digunakan:</p><ol><li><b>Tabel Distribusi Frekuensi:</b> Mengelompokkan data berdasarkan frekuensi kemunculannya.</li><li><b>Diagram Batang:</b> Cocok untuk membandingkan jumlah antar kategori diskrit.</li><li><b>Diagram Lingkaran (Pie Chart):</b> Sangat baik untuk menampilkan proporsi persentase dari keseluruhan.</li></ol>', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', NULL, 2, '2026-09-30 17:09:24'),
(3, NULL, 'Ukuran Pemusatan Data', 'Mengenal konsep Mean, Median, dan Modus dalam statistika dasar.', 'Semua Kelas', 'calculator', '[\"Mean (Rata-rata)\", \"Median (Nilai Tengah)\", \"Modus (Nilai Terbanyak)\"]', '<p>Ukuran pemusatan data membantu merangkum sekumpulan data menjadi satu representasi nilai sentral:</p><ul><li><b>Mean:</b> Jumlah seluruh nilai dibagi banyaknya data.</li><li><b>Median:</b> Nilai tengah data setelah diurutkan dari terkecil hingga terbesar.</li><li><b>Modus:</b> Nilai yang paling sering muncul (frekuensi tertinggi).</li></ul>', NULL, NULL, NULL, 3, '2026-09-30 17:09:24'),
(5, 9, 'DATA', 'DATA', 'Semua Kelas', 'book-open', '[\"DATA\"]', 'DATA', 'https://canva.link/k16htg6af9qxd3v', 'https://canva.link/k16htg6af9qxd3v', NULL, 99, '2026-10-01 07:59:54');

-- --------------------------------------------------------

--
-- Table structure for table `materi_reads`
--

CREATE TABLE `materi_reads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `materi_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materi_reads`
--

INSERT INTO `materi_reads` (`id`, `user_id`, `materi_id`, `read_at`) VALUES
(1, 10, 1, '2026-10-16 07:46:24'),
(2, 10, 5, '2026-10-16 07:46:35'),
(3, 10, 2, '2026-10-16 07:51:46'),
(4, 10, 3, '2026-10-16 07:51:56');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `tipe` enum('info','grade','schedule','warning') DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `judul`, `pesan`, `tipe`, `link`, `is_read`, `created_at`) VALUES
(1, 4, 'Selamat Datang di ElearnMath', 'Selamat bergabung di platform pembelajaran matematika. Cek jadwal pelajaran dan materi baru kamu.', 'info', 'jadwal.php', 1, '2026-09-30 17:09:24'),
(2, 4, 'LKPD Telah Dibuka', 'LKPD Statistika telah tersedia dan dapat dikerjakan secara langsung tanpa syarat.', 'grade', 'lkpd.php', 1, '2026-09-30 17:09:24'),
(3, 5, 'Selamat Datang di ElearnMath', 'Selamat datang Siti! Silakan cek jadwal pelajaran matematika kamu minggu ini.', 'info', 'jadwal.php', 0, '2026-09-30 17:09:24'),
(4, 3, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 30 Sep 2026, 20:17 WIB.', 'info', 'guru/index.php', 1, '2026-09-30 18:17:36'),
(6, 3, 'Pengumpulan LKPD Baru', 'Siswa Ahmad Faiz telah mengumpulkan LKPD Statistika.', 'grade', 'guru/koreksi.php', 1, '2026-09-30 18:38:38'),
(8, 3, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 30 Sep 2026, 20:40 WIB.', 'info', 'guru/index.php', 1, '2026-09-30 18:40:51'),
(9, 4, 'Nilai Tugas Telah Diterbitkan', 'Tugas \'Lembar Kerja Peserta Didik (LKPD) Statistika\' telah selesai dikoreksi oleh guru. Nilai akhir Anda: 50.', 'grade', 'siswa/hasil.php', 1, '2026-09-30 18:42:06'),
(10, 4, 'Nilai Tugas Telah Diterbitkan', 'Tugas \'Lembar Kerja Peserta Didik (LKPD) Statistika\' telah selesai dikoreksi oleh guru. Nilai akhir Anda: 50.', 'grade', 'siswa/hasil.php', 1, '2026-09-30 18:42:17'),
(11, 4, 'Nilai Tugas Telah Diterbitkan', 'Tugas \'Evaluasi Akhir Bab Statistika\' telah selesai dikoreksi oleh guru. Nilai akhir Anda: 80.', 'grade', 'siswa/hasil.php', 1, '2026-09-30 18:42:38'),
(12, 4, 'Nilai Tugas Telah Diterbitkan', 'Tugas \'Latihan Soal Statistika & Pemusatan Data\' telah selesai dikoreksi oleh guru. Nilai akhir Anda: 50.', 'grade', 'siswa/hasil.php', 1, '2026-09-30 18:43:06'),
(13, 3, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 01 Oct 2026, 07:40 WIB.', 'info', 'guru/index.php', 1, '2026-10-01 05:40:00'),
(14, 9, 'Pendaftaran Diterima', 'Selamat datang KAMILA! Pendaftaran akun Anda sebagai guru di kelas VII-A sedang menunggu persetujuan (Admit) oleh Admin.', 'info', NULL, 1, '2026-10-01 06:41:23'),
(15, 1, 'Pendaftaran Pengguna Baru Menunggu Admit', 'KAMILA baru saja mendaftar sebagai guru untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui.', 'info', 'admin/admit_user.php', 0, '2026-10-01 06:41:23'),
(16, 3, 'Pendaftaran Pengguna Baru Menunggu Admit', 'KAMILA baru saja mendaftar sebagai guru untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui.', 'info', 'admin/admit_user.php', 1, '2026-10-01 06:41:23'),
(17, 7, 'Pendaftaran Pengguna Baru Menunggu Admit', 'KAMILA baru saja mendaftar sebagai guru untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui.', 'info', 'admin/admit_user.php', 0, '2026-10-01 06:41:23'),
(18, 8, 'Pendaftaran Pengguna Baru Menunggu Admit', 'KAMILA baru saja mendaftar sebagai guru untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui.', 'info', 'admin/admit_user.php', 0, '2026-10-01 06:41:23'),
(19, 10, 'Pendaftaran Diterima', 'Selamat datang MIMIL! Pendaftaran akun Anda sebagai siswa di kelas VII-A sedang menunggu persetujuan (Admit) oleh Admin.', 'info', NULL, 1, '2026-10-01 06:42:03'),
(20, 1, 'Pendaftaran Pengguna Baru Menunggu Admit', 'MIMIL baru saja mendaftar sebagai siswa untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui.', 'info', 'admin/admit_user.php', 0, '2026-10-01 06:42:03'),
(21, 3, 'Pendaftaran Pengguna Baru Menunggu Admit', 'MIMIL baru saja mendaftar sebagai siswa untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui.', 'info', 'admin/admit_user.php', 1, '2026-10-01 06:42:03'),
(22, 7, 'Pendaftaran Pengguna Baru Menunggu Admit', 'MIMIL baru saja mendaftar sebagai siswa untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui.', 'info', 'admin/admit_user.php', 0, '2026-10-01 06:42:03'),
(23, 8, 'Pendaftaran Pengguna Baru Menunggu Admit', 'MIMIL baru saja mendaftar sebagai siswa untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui.', 'info', 'admin/admit_user.php', 0, '2026-10-01 06:42:03'),
(24, 9, 'Pendaftaran Disetujui (Admit)', 'Selamat KAMILA! Pendaftaran Anda telah disetujui oleh Admin. Anda kini resmi tergabung di kelas VII-A.', 'info', 'guru/index.php', 1, '2026-10-01 06:43:12'),
(25, 10, 'Pendaftaran Disetujui (Admit)', 'Selamat MIMIL! Pendaftaran Anda telah disetujui oleh Admin. Anda kini resmi tergabung di kelas VII-A.', 'info', 'siswa/index.php', 1, '2026-10-01 06:43:15'),
(26, 9, 'Jadwal Mengajar Baru', 'Administrator telah menambahkan jadwal mengajar baru untuk kelas VII-A pada hari Senin.', 'schedule', 'guru/jadwal.php', 1, '2026-10-01 06:43:46'),
(27, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 01 Oct 2026, 08:46 WIB.', 'info', 'guru/index.php', 1, '2026-10-01 06:46:29'),
(28, 9, 'Pengumpulan Latihan Baru', 'Siswa MIMIL (VII-A) telah mengumpulkan \'Latihan 1: Statistika & Ukuran Pemusatan\'.', 'grade', 'guru/koreksi.php?submission_id=4', 1, '2026-10-01 06:55:17'),
(29, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 01 Oct 2026, 09:03 WIB.', 'info', 'guru/index.php', 1, '2026-10-01 07:03:35'),
(30, 10, 'Nilai Tugas Telah Diterbitkan', 'Tugas \'Latihan 1: Statistika & Ukuran Pemusatan\' telah selesai dikoreksi oleh guru. Nilai akhir Anda: 35.', 'grade', 'siswa/hasil.php', 1, '2026-10-01 07:56:14'),
(31, 10, 'Izin Pengerjaan Ulang (Retake)', 'Guru pengajar telah mengizinkan Anda untuk mengerjakan ulang tugas \'Latihan 1: Statistika & Ukuran Pemusatan\'. Silakan perbaiki jawaban Anda sekarang.', 'info', 'siswa/latihan.php', 1, '2026-10-01 07:56:21'),
(32, 1, 'Pengumpulan Latihan Baru', 'Siswa MIMIL (VII-A) telah mengumpulkan \'Latihan 1: Statistika & Ukuran Pemusatan\'.', 'grade', 'guru/koreksi.php?submission_id=5', 0, '2026-10-01 08:05:05'),
(33, 9, 'Pengumpulan Latihan Baru', 'Siswa MIMIL (VII-A) telah mengumpulkan \'Latihan 1: Statistika & Ukuran Pemusatan\'.', 'grade', 'guru/koreksi.php?submission_id=5', 1, '2026-10-01 08:05:05'),
(34, 1, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 1x).', 'warning', 'guru/deteksi.php', 0, '2026-10-01 08:05:05'),
(35, 9, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 1x).', 'warning', 'guru/deteksi.php', 1, '2026-10-01 08:05:05'),
(36, 1, 'Peringatan Keluar Tab Ujian', 'Siswa Siti Nurhaliza (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 1x).', 'warning', 'guru/deteksi.php', 0, '2026-10-01 08:08:58'),
(37, 9, 'Peringatan Keluar Tab Ujian', 'Siswa Siti Nurhaliza (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 1x).', 'warning', 'guru/deteksi.php', 1, '2026-10-01 08:08:58'),
(38, 1, 'Peringatan Keluar Tab Ujian', 'Siswa Siti Nurhaliza (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 2x).', 'warning', 'guru/deteksi.php', 0, '2026-10-01 08:09:04'),
(39, 9, 'Peringatan Keluar Tab Ujian', 'Siswa Siti Nurhaliza (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 2x).', 'warning', 'guru/deteksi.php', 1, '2026-10-01 08:09:04'),
(40, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 01 Oct 2026, 10:09 WIB.', 'info', 'guru/index.php', 1, '2026-10-01 08:09:21'),
(41, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 01 Oct 2026, 10:12 WIB.', 'info', 'guru/index.php', 1, '2026-10-01 08:12:50'),
(42, 10, 'Nilai Tugas Telah Diterbitkan', 'Tugas \'Latihan 1: Statistika & Ukuran Pemusatan\' telah selesai dikoreksi oleh guru. Nilai akhir Anda: 50.', 'grade', 'siswa/hasil.php', 1, '2026-10-01 08:15:07'),
(43, 14, 'Pendaftaran Diterima', 'Selamat datang KAMIL! Pendaftaran akun Anda sebagai guru di kelas VII-B sedang menunggu persetujuan (Admit) oleh Admin.', 'info', NULL, 0, '2026-10-01 08:17:30'),
(44, 3, 'Pendaftaran Pengguna Baru Menunggu Admit', 'KAMIL baru saja mendaftar sebagai guru untuk kelas VII-B. Buka menu Persetujuan Pengguna untuk menyetujui.', 'info', 'admin/admit_user.php', 1, '2026-10-01 08:17:30'),
(45, 7, 'Pendaftaran Pengguna Baru Menunggu Admit', 'KAMIL baru saja mendaftar sebagai guru untuk kelas VII-B. Buka menu Persetujuan Pengguna untuk menyetujui.', 'info', 'admin/admit_user.php', 0, '2026-10-01 08:17:30'),
(46, 8, 'Pendaftaran Pengguna Baru Menunggu Admit', 'KAMIL baru saja mendaftar sebagai guru untuk kelas VII-B. Buka menu Persetujuan Pengguna untuk menyetujui.', 'info', 'admin/admit_user.php', 0, '2026-10-01 08:17:30'),
(47, 14, 'Pendaftaran Disetujui (Admit)', 'Selamat KAMIL! Pendaftaran Anda telah disetujui oleh Admin. Anda kini resmi tergabung di kelas VII-B.', 'info', 'guru/index.php', 0, '2026-10-01 08:18:09'),
(48, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 01 Oct 2026, 10:33 WIB.', 'info', 'guru/index.php', 1, '2026-10-01 08:33:36'),
(49, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 01 Oct 2026, 10:34 WIB.', 'info', 'guru/index.php', 1, '2026-10-01 08:34:03'),
(50, 1, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 2: Penyajian Data & Diagram Lingkaran\' (Total: 1x).', 'warning', 'guru/deteksi.php', 0, '2026-10-01 09:23:20'),
(51, 9, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 2: Penyajian Data & Diagram Lingkaran\' (Total: 1x).', 'warning', 'guru/deteksi.php', 1, '2026-10-01 09:23:20'),
(52, 14, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 2: Penyajian Data & Diagram Lingkaran\' (Total: 1x).', 'warning', 'guru/deteksi.php', 0, '2026-10-01 09:23:20'),
(53, 1, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 2: Penyajian Data & Diagram Lingkaran\' (Total: 2x).', 'warning', 'guru/deteksi.php', 0, '2026-10-01 09:23:25'),
(54, 9, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 2: Penyajian Data & Diagram Lingkaran\' (Total: 2x).', 'warning', 'guru/deteksi.php', 1, '2026-10-01 09:23:25'),
(55, 14, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 2: Penyajian Data & Diagram Lingkaran\' (Total: 2x).', 'warning', 'guru/deteksi.php', 0, '2026-10-01 09:23:25'),
(56, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 01 Oct 2026, 11:23 WIB.', 'info', 'guru/index.php', 1, '2026-10-01 09:23:34'),
(57, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 01 Oct 2026, 11:30 WIB.', 'info', 'guru/index.php', 1, '2026-10-01 09:30:19'),
(58, 10, 'Izin Pengerjaan Ulang (Retake)', 'Guru pengajar telah mengizinkan Anda untuk mengerjakan ulang tugas \'Latihan 1: Statistika & Ukuran Pemusatan\'. Silakan perbaiki jawaban Anda sekarang.', 'info', 'siswa/latihan.php', 1, '2026-10-01 09:30:38'),
(59, 1, 'Pengumpulan Latihan Baru', 'Siswa MIMIL (VII-A) telah mengumpulkan \'Latihan 1: Statistika & Ukuran Pemusatan\'.', 'grade', 'guru/koreksi.php?submission_id=6', 0, '2026-10-01 09:30:56'),
(60, 9, 'Pengumpulan Latihan Baru', 'Siswa MIMIL (VII-A) telah mengumpulkan \'Latihan 1: Statistika & Ukuran Pemusatan\'.', 'grade', 'guru/koreksi.php?submission_id=6', 1, '2026-10-01 09:30:56'),
(61, 14, 'Pengumpulan Latihan Baru', 'Siswa MIMIL (VII-A) telah mengumpulkan \'Latihan 1: Statistika & Ukuran Pemusatan\'.', 'grade', 'guru/koreksi.php?submission_id=6', 0, '2026-10-01 09:30:56'),
(62, 1, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 2x).', 'warning', 'guru/deteksi.php', 0, '2026-10-01 09:30:56'),
(63, 9, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 2x).', 'warning', 'guru/deteksi.php', 1, '2026-10-01 09:30:56'),
(64, 14, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 2x).', 'warning', 'guru/deteksi.php', 0, '2026-10-01 09:30:56'),
(65, 1, 'Peringatan Keluar Tab Ujian', 'Siswa Siti Nurhaliza (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 3x).', 'warning', 'guru/deteksi.php', 0, '2026-10-01 09:33:08'),
(66, 9, 'Peringatan Keluar Tab Ujian', 'Siswa Siti Nurhaliza (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 3x).', 'warning', 'guru/deteksi.php', 1, '2026-10-01 09:33:08'),
(67, 14, 'Peringatan Keluar Tab Ujian', 'Siswa Siti Nurhaliza (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 3x).', 'warning', 'guru/deteksi.php', 0, '2026-10-01 09:33:08'),
(68, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 02 Oct 2026, 08:57 WIB.', 'info', 'guru/index.php', 1, '2026-10-02 06:57:00'),
(69, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 02 Oct 2026, 09:02 WIB.', 'info', 'guru/index.php', 1, '2026-10-02 07:02:17'),
(70, 10, 'Nilai Tugas Telah Diterbitkan', 'Tugas \'Latihan 1: Statistika & Ukuran Pemusatan\' telah selesai dikoreksi oleh guru. Nilai akhir Anda: 50.', 'grade', 'siswa/hasil.php', 1, '2026-10-02 07:03:36'),
(71, 21, 'Pendaftaran Siswa Berhasil', 'Selamat datang ML! Akun Anda dapat langsung digunakan untuk masuk ke Dashboard Siswa. Akses isi kelas \'VII-A\' akan aktif setelah disetujui (Admit) oleh Admin.', 'info', 'siswa/index.php', 1, '2026-10-08 17:27:08'),
(72, 3, 'Pendaftaran Siswa Menunggu Admit', 'Siswa ML baru mendaftar untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 1, '2026-10-08 17:27:08'),
(73, 7, 'Pendaftaran Siswa Menunggu Admit', 'Siswa ML baru mendaftar untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-08 17:27:08'),
(74, 8, 'Pendaftaran Siswa Menunggu Admit', 'Siswa ML baru mendaftar untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-08 17:27:08'),
(75, 1, 'Pengumpulan Latihan Baru', 'Siswa ML (VII-A) telah mengumpulkan \'Latihan 1: Statistika & Ukuran Pemusatan\'.', 'grade', 'guru/koreksi.php?submission_id=7', 0, '2026-10-08 17:28:07'),
(76, 9, 'Pengumpulan Latihan Baru', 'Siswa ML (VII-A) telah mengumpulkan \'Latihan 1: Statistika & Ukuran Pemusatan\'.', 'grade', 'guru/koreksi.php?submission_id=7', 1, '2026-10-08 17:28:07'),
(77, 14, 'Pengumpulan Latihan Baru', 'Siswa ML (VII-A) telah mengumpulkan \'Latihan 1: Statistika & Ukuran Pemusatan\'.', 'grade', 'guru/koreksi.php?submission_id=7', 0, '2026-10-08 17:28:07'),
(78, 1, 'Peringatan Keluar Tab Ujian', 'Siswa ML (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 1x).', 'warning', 'guru/deteksi.php', 0, '2026-10-08 17:28:08'),
(79, 9, 'Peringatan Keluar Tab Ujian', 'Siswa ML (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 1x).', 'warning', 'guru/deteksi.php', 1, '2026-10-08 17:28:08'),
(80, 14, 'Peringatan Keluar Tab Ujian', 'Siswa ML (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 1x).', 'warning', 'guru/deteksi.php', 0, '2026-10-08 17:28:08'),
(81, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 08 Oct 2026, 19:32 WIB.', 'info', 'guru/index.php', 1, '2026-10-08 17:32:01'),
(82, 3, 'Permohonan Mengampu Kelas Guru', 'Guru KAMILA mengajukan untuk mengampu kelas \'VII-B\'. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php?tab=classes', 1, '2026-10-08 17:33:02'),
(83, 7, 'Permohonan Mengampu Kelas Guru', 'Guru KAMILA mengajukan untuk mengampu kelas \'VII-B\'. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php?tab=classes', 0, '2026-10-08 17:33:02'),
(84, 8, 'Permohonan Mengampu Kelas Guru', 'Guru KAMILA mengajukan untuk mengampu kelas \'VII-B\'. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php?tab=classes', 0, '2026-10-08 17:33:02'),
(85, 3, 'Pengajuan Kelas Guru Baru', 'Guru KAMILA mengajukan pembuatan kelas baru \'VII-J\'. Buka menu Persetujuan Pengguna untuk menyetujui (Admit Class).', 'info', 'admin/admit_user.php?tab=classes', 1, '2026-10-08 17:33:12'),
(86, 7, 'Pengajuan Kelas Guru Baru', 'Guru KAMILA mengajukan pembuatan kelas baru \'VII-J\'. Buka menu Persetujuan Pengguna untuk menyetujui (Admit Class).', 'info', 'admin/admit_user.php?tab=classes', 0, '2026-10-08 17:33:12'),
(87, 8, 'Pengajuan Kelas Guru Baru', 'Guru KAMILA mengajukan pembuatan kelas baru \'VII-J\'. Buka menu Persetujuan Pengguna untuk menyetujui (Admit Class).', 'info', 'admin/admit_user.php?tab=classes', 0, '2026-10-08 17:33:12'),
(88, 21, 'Pendaftaran Disetujui (Admit Class)', 'Selamat ML! Pendaftaran Anda telah disetujui (Admit) oleh Admin. Anda kini resmi aktif di kelas VII-A dan seluruh fitur pembelajaran telah terbuka.', 'info', 'siswa/index.php', 0, '2026-10-08 17:38:30'),
(89, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 08 Oct 2026, 20:29 WIB.', 'info', 'guru/index.php', 1, '2026-10-08 18:29:14'),
(90, 23, 'Pendaftaran Siswa Berhasil', 'Selamat datang KM! Akun Anda dapat langsung digunakan untuk masuk ke Dashboard Siswa. Akses isi kelas \'VII-A\' akan aktif setelah disetujui (Admit) oleh Admin.', 'info', 'siswa/index.php', 0, '2026-10-08 18:35:10'),
(91, 3, 'Pendaftaran Siswa Menunggu Admit', 'Siswa KM baru mendaftar untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-08 18:35:10'),
(92, 7, 'Pendaftaran Siswa Menunggu Admit', 'Siswa KM baru mendaftar untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-08 18:35:10'),
(93, 8, 'Pendaftaran Siswa Menunggu Admit', 'Siswa KM baru mendaftar untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-08 18:35:10'),
(94, 1, 'Peringatan Keluar Tab Ujian', 'Siswa KM (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 1x).', 'warning', 'guru/deteksi.php', 0, '2026-10-08 18:36:18'),
(95, 9, 'Peringatan Keluar Tab Ujian', 'Siswa KM (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 1x).', 'warning', 'guru/deteksi.php', 1, '2026-10-08 18:36:18'),
(96, 14, 'Peringatan Keluar Tab Ujian', 'Siswa KM (VII-A) terdeteksi meninggalkan tab browser saat mengerjakan \'Latihan 1: Statistika & Ukuran Pemusatan\' (Total: 1x).', 'warning', 'guru/deteksi.php', 0, '2026-10-08 18:36:18'),
(97, 3, 'Pengajuan Kelas Murid', 'Murid KM mengajukan masuk ke kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-08 20:00:40'),
(98, 7, 'Pengajuan Kelas Murid', 'Murid KM mengajukan masuk ke kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-08 20:00:40'),
(99, 8, 'Pengajuan Kelas Murid', 'Murid KM mengajukan masuk ke kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-08 20:00:40'),
(100, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 08 Oct 2026, 22:02 WIB.', 'info', 'guru/index.php', 1, '2026-10-08 20:02:06'),
(101, 21, 'Izin Pengerjaan Ulang (Retake)', 'Guru pengajar telah mengizinkan Anda untuk mengerjakan ulang tugas \'Latihan 1: Statistika & Ukuran Pemusatan\'. Silakan perbaiki jawaban Anda sekarang.', 'info', 'siswa/latihan.php', 0, '2026-10-08 20:04:21'),
(102, 23, 'Pendaftaran Disetujui (Admit Class)', 'Selamat KM! Pendaftaran Anda telah disetujui (Admit) oleh Admin. Anda kini resmi aktif di kelas VII-A dan seluruh fitur pembelajaran telah terbuka.', 'info', 'siswa/index.php', 0, '2026-10-08 20:05:20'),
(103, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 14 Oct 2026, 19:25 WIB.', 'info', 'guru/index.php', 1, '2026-10-14 17:25:34'),
(104, 10, 'Izin Pengerjaan Ulang (Retake)', 'Guru pengajar telah mengizinkan Anda untuk mengerjakan ulang tugas \'Latihan 1: Statistika & Ukuran Pemusatan\'. Silakan perbaiki jawaban Anda sekarang.', 'info', 'siswa/latihan.php', 1, '2026-10-14 17:26:14'),
(106, 3, 'Pendaftaran Siswa Menunggu Admit', 'Siswa P baru mendaftar untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-14 17:31:35'),
(107, 7, 'Pendaftaran Siswa Menunggu Admit', 'Siswa P baru mendaftar untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-14 17:31:35'),
(108, 8, 'Pendaftaran Siswa Menunggu Admit', 'Siswa P baru mendaftar untuk kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-14 17:31:35'),
(109, 3, 'Pengajuan Kelas Murid', 'Murid P mengajukan masuk ke kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-14 17:32:08'),
(110, 7, 'Pengajuan Kelas Murid', 'Murid P mengajukan masuk ke kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-14 17:32:08'),
(111, 8, 'Pengajuan Kelas Murid', 'Murid P mengajukan masuk ke kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-14 17:32:08'),
(112, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 16 Oct 2026, 08:08 WIB.', 'info', 'guru/index.php', 1, '2026-10-16 06:08:24'),
(113, 3, 'Pengajuan Kelas Murid', 'Murid P mengajukan masuk ke kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-16 06:47:04'),
(114, 7, 'Pengajuan Kelas Murid', 'Murid P mengajukan masuk ke kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-16 06:47:04'),
(115, 8, 'Pengajuan Kelas Murid', 'Murid P mengajukan masuk ke kelas VII-A. Buka menu Persetujuan Pengguna untuk menyetujui (Admit).', 'info', 'admin/admit_user.php', 0, '2026-10-16 06:47:04'),
(116, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 16 Oct 2026, 08:47 WIB.', 'info', 'guru/index.php', 1, '2026-10-16 06:47:23'),
(117, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 16 Oct 2026, 08:59 WIB.', 'info', 'guru/index.php', 1, '2026-10-16 06:59:33'),
(118, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 16 Oct 2026, 09:02 WIB.', 'info', 'guru/index.php', 1, '2026-10-16 07:02:42'),
(119, 22, 'Pendaftaran Ditolak', 'Mohon maaf, pendaftaran akun Anda ditolak oleh Admin. Alasan: Pendaftaran tidak memenuhi kriteria verifikasi sekolah.', 'warning', NULL, 0, '2026-10-16 07:41:36'),
(120, 9, 'Pengajuan Kelas Ditolak', 'Mohon maaf, permohonan keanggotaan/pembuatan kelas \'VII-J\' ditolak oleh Admin.', 'warning', 'guru/index.php', 1, '2026-10-16 07:41:44'),
(121, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 16 Oct 2026, 09:42 WIB.', 'info', 'guru/index.php', 1, '2026-10-16 07:42:14'),
(122, 21, 'Nilai Tugas Telah Diterbitkan', 'Tugas \'Latihan 1: Statistika & Ukuran Pemusatan\' telah selesai dikoreksi oleh guru. Nilai akhir Anda: 0.', 'grade', 'siswa/hasil.php', 0, '2026-10-16 07:45:33'),
(123, 21, 'Nilai Tugas Telah Diterbitkan', 'Tugas \'Latihan 1: Statistika & Ukuran Pemusatan\' telah selesai dikoreksi oleh guru. Nilai akhir Anda: 45.', 'grade', 'siswa/hasil.php', 0, '2026-10-16 07:45:53'),
(124, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 16 Oct 2026, 09:49 WIB.', 'info', 'guru/index.php', 0, '2026-10-16 07:49:09'),
(125, 1, 'Pengumpulan Tugas Siswa', 'Siswa MIMIL (VII-A) telah mengumpulkan \'Lembar Kerja Peserta Didik (LKPD) Statistika\' (Percobaan Ke-1).', 'grade', 'guru/koreksi.php?submission_id=8', 0, '2026-10-16 08:20:02'),
(126, 9, 'Pengumpulan Tugas Siswa', 'Siswa MIMIL (VII-A) telah mengumpulkan \'Lembar Kerja Peserta Didik (LKPD) Statistika\' (Percobaan Ke-1).', 'grade', 'guru/koreksi.php?submission_id=8', 0, '2026-10-16 08:20:02'),
(127, 14, 'Pengumpulan Tugas Siswa', 'Siswa MIMIL (VII-A) telah mengumpulkan \'Lembar Kerja Peserta Didik (LKPD) Statistika\' (Percobaan Ke-1).', 'grade', 'guru/koreksi.php?submission_id=8', 0, '2026-10-16 08:20:02'),
(128, 1, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab pengerjaan \'Evaluasi Akhir Bab Statistika\' (Total: 1x).', 'warning', 'guru/deteksi.php', 0, '2026-10-16 08:21:07'),
(129, 9, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab pengerjaan \'Evaluasi Akhir Bab Statistika\' (Total: 1x).', 'warning', 'guru/deteksi.php', 0, '2026-10-16 08:21:07'),
(130, 14, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab pengerjaan \'Evaluasi Akhir Bab Statistika\' (Total: 1x).', 'warning', 'guru/deteksi.php', 0, '2026-10-16 08:21:07'),
(131, 1, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab pengerjaan \'Evaluasi Akhir Bab Statistika\' (Total: 2x).', 'warning', 'guru/deteksi.php', 0, '2026-10-16 08:21:43'),
(132, 9, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab pengerjaan \'Evaluasi Akhir Bab Statistika\' (Total: 2x).', 'warning', 'guru/deteksi.php', 0, '2026-10-16 08:21:43'),
(133, 14, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab pengerjaan \'Evaluasi Akhir Bab Statistika\' (Total: 2x).', 'warning', 'guru/deteksi.php', 0, '2026-10-16 08:21:43'),
(134, 1, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab pengerjaan \'Evaluasi Akhir Bab Statistika\' (Total: 3x).', 'warning', 'guru/deteksi.php', 0, '2026-10-16 08:22:40'),
(135, 9, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab pengerjaan \'Evaluasi Akhir Bab Statistika\' (Total: 3x).', 'warning', 'guru/deteksi.php', 0, '2026-10-16 08:22:40'),
(136, 14, 'Peringatan Keluar Tab Ujian', 'Siswa MIMIL (VII-A) terdeteksi meninggalkan tab pengerjaan \'Evaluasi Akhir Bab Statistika\' (Total: 3x).', 'warning', 'guru/deteksi.php', 0, '2026-10-16 08:22:40'),
(137, 1, 'Pengumpulan Tugas Siswa', 'Siswa MIMIL (VII-A) telah mengumpulkan \'Evaluasi Akhir Bab Statistika\' (Percobaan Ke-1).', 'grade', 'guru/koreksi.php?submission_id=9', 0, '2026-10-16 08:23:13'),
(138, 9, 'Pengumpulan Tugas Siswa', 'Siswa MIMIL (VII-A) telah mengumpulkan \'Evaluasi Akhir Bab Statistika\' (Percobaan Ke-1).', 'grade', 'guru/koreksi.php?submission_id=9', 0, '2026-10-16 08:23:13'),
(139, 14, 'Pengumpulan Tugas Siswa', 'Siswa MIMIL (VII-A) telah mengumpulkan \'Evaluasi Akhir Bab Statistika\' (Percobaan Ke-1).', 'grade', 'guru/koreksi.php?submission_id=9', 0, '2026-10-16 08:23:13'),
(140, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 16 Oct 2026, 10:23 WIB.', 'info', 'guru/index.php', 0, '2026-10-16 08:23:58'),
(141, 10, 'Nilai Tugas Telah Diterbitkan', 'Tugas \'Evaluasi Akhir Bab Statistika\' telah selesai dikoreksi oleh guru. Nilai akhir Anda: 80.', 'grade', 'siswa/hasil.php', 0, '2026-10-16 08:25:46'),
(142, 10, 'Nilai Tugas Telah Diterbitkan', 'Tugas \'Lembar Kerja Peserta Didik (LKPD) Statistika\' telah selesai dikoreksi oleh guru. Nilai akhir Anda: 70.', 'grade', 'siswa/hasil.php', 0, '2026-10-16 08:27:09'),
(143, 9, 'Login Berhasil', 'Sesi mengajar Anda aktif pada 16 Oct 2026, 10:29 WIB.', 'info', 'guru/index.php', 0, '2026-10-16 08:29:38'),
(144, 43, 'Pendaftaran Siswa Berhasil', 'Selamat datang P! Akun Anda dapat langsung digunakan untuk masuk ke Dashboard Siswa. Silakan pilih kelas tujuan Anda dari Dashboard.', 'info', 'siswa/index.php', 0, '2026-10-16 09:08:54');

-- --------------------------------------------------------

--
-- Stand-in structure for view `pengerjaan_latihan`
-- (See below for the actual view)
--
CREATE TABLE `pengerjaan_latihan` (
`id` int(11)
,`submission_id` int(11)
,`student_id` int(11)
,`assessment_id` int(11)
,`latihan_id` int(11)
,`pg_score` decimal(5,2)
,`essay_score` decimal(5,2)
,`total_score` decimal(5,2)
,`ai_suggested_score` decimal(5,2)
,`status` enum('submitted','reviewed')
,`is_corrected` tinyint(1)
,`allow_retake` tinyint(1)
,`attempt` int(11)
,`start_time` datetime
,`started_at` datetime
,`submitted_at` timestamp
,`created_at` timestamp
,`reviewed_at` datetime
,`reviewed_by` int(11)
,`feedback_guru` text
,`judul_latihan` varchar(255)
,`passing_grade` int(11)
,`duration_minutes` int(11)
,`kelas` varchar(50)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `pengerjaan_lkpd`
-- (See below for the actual view)
--
CREATE TABLE `pengerjaan_lkpd` (
`id` int(11)
,`submission_id` int(11)
,`student_id` int(11)
,`assessment_id` int(11)
,`lkpd_id` int(11)
,`pg_score` decimal(5,2)
,`essay_score` decimal(5,2)
,`total_score` decimal(5,2)
,`ai_suggested_score` decimal(5,2)
,`status` enum('submitted','reviewed')
,`is_corrected` tinyint(1)
,`allow_retake` tinyint(1)
,`attempt` int(11)
,`start_time` datetime
,`started_at` datetime
,`submitted_at` timestamp
,`created_at` timestamp
,`reviewed_at` datetime
,`reviewed_by` int(11)
,`feedback_guru` text
,`judul_lkpd` varchar(255)
,`passing_grade` int(11)
,`duration_minutes` int(11)
,`kelas` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `urutan` int(11) DEFAULT 1,
  `tipe` enum('pg','essay') NOT NULL,
  `pertanyaan` text NOT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `opsi_a` text DEFAULT NULL,
  `opsi_b` text DEFAULT NULL,
  `opsi_c` text DEFAULT NULL,
  `opsi_d` text DEFAULT NULL,
  `kunci_jawaban` text DEFAULT NULL,
  `feedback_benar` text DEFAULT NULL,
  `feedback_salah` text DEFAULT NULL,
  `bobot` decimal(5,2) DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `assessment_id`, `urutan`, `tipe`, `pertanyaan`, `media_url`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `kunci_jawaban`, `feedback_benar`, `feedback_salah`, `bobot`) VALUES
(1, 1, 1, 'pg', 'Warna kesukaan siswa dalam suatu kelas termasuk jenis data...', NULL, 'Data kualitatif', 'Data kuantitatif', 'Data campuran', 'Bukan data', '0', 'Tepat sekali! Warna kesukaan merupakan kategori kualitas, bukan besaran numerik.', 'Kurang tepat. Warna kesukaan tidak diukur dengan angka, jadi termasuk data kualitatif.', 50.00),
(2, 1, 2, 'essay', 'Jelaskan perbedaan mendasar antara data kualitatif dan kuantitatif menurut pemahamanmu, sertakan contohnya!', NULL, NULL, NULL, NULL, NULL, 'Kualitatif berupa kata/sifat, kuantitatif berupa a', 'Analisis Anda sangat tajam dan jelas.', 'Perhatikan perbedaan angka dan kategori.', 50.00),
(3, 2, 1, 'pg', 'Tinggi badan siswa (dalam cm) termasuk jenis data...', NULL, 'Data kualitatif', 'Data kuantitatif', 'Data nominal', 'Bukan data', '1', 'Benar! Tinggi badan dinyatakan dalam angka yang dapat diukur.', 'Jawaban kurang tepat. Angka tinggi badan tergolong data kuantitatif.', 25.00),
(4, 2, 2, 'pg', 'Diagram yang paling tepat untuk menampilkan perbandingan proporsi persentase adalah...', NULL, 'Diagram batang', 'Diagram lingkaran', 'Diagram garis', 'Diagram tebar', '1', 'Tepat! Diagram lingkaran sangat efektif menggambarkan proporsi persentase.', 'Jawaban kurang tepat. Diagram lingkaran adalah representasi standar untuk persentase.', 25.00),
(5, 2, 3, 'pg', 'Nilai yang paling sering muncul dalam sekumpulan data disebut...', NULL, 'Mean', 'Median', 'Modus', 'Kuartil', '2', 'Benar! Nilai dengan frekuensi terbanyak adalah Modus.', 'Jawaban kurang tepat. Modus adalah ukuran nilai dengan frekuensi kemunculan terbesar.', 25.00),
(6, 2, 4, 'essay', 'Menurutmu mengapa memilih jenis diagram yang tepat penting saat menyajikan data hasil survei kelas? Jelaskan!', NULL, NULL, NULL, NULL, NULL, 'Agar data mudah dipahami dan tidak menimbulkan mis', 'Jawaban komprehensif.', 'Coba jelaskan dampak bagi pembaca.', 25.00),
(7, 3, 1, 'essay', 'Sebuah survei mencatat data nilai ulangan matematika dari 5 orang siswa: 80, 90, 70, 90, 85. Tentukan mean dan modusnya secara lengkap beserta cara pengerjaannya!', NULL, NULL, NULL, NULL, NULL, 'Mean = (80+90+70+90+85)/5 = 415/5 = 83. Modus = 90', 'Perhitungan langkah dan hasil akhir Anda sangat rapi dan akurat!', 'Periksa kembali penjumlahan data ulangan.', 50.00),
(8, 3, 2, 'essay', 'Siswa kelas VII A melakukan pendataan kegiatan ekstrakurikuler yang diminati. Diagram apa yang paling tepat untuk merefleksikan data tersebut dan mengapa?', NULL, NULL, NULL, NULL, NULL, 'Diagram batang atau diagram lingkaran, agar perban', 'Penjelasan yang logis dan runtut.', 'Sertakan alasan visualisasinya.', 50.00),
(9, 1, 99, 'essay', 'APA', NULL, '', '', '', '', '0', '', '', 1.00);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_type` enum('LKPD','LATIHAN','EVALUASI') NOT NULL,
  `task_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `status` enum('IN_PROGRESS','SUBMITTED','GRADED','NEEDS_RETAKE') DEFAULT 'IN_PROGRESS',
  `started_at` datetime DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `time_limit_minutes` int(11) DEFAULT 60
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `pg_score` decimal(5,2) DEFAULT NULL,
  `essay_score` decimal(5,2) DEFAULT NULL,
  `total_score` decimal(5,2) DEFAULT NULL,
  `ai_suggested_score` decimal(5,2) DEFAULT NULL,
  `status` enum('submitted','reviewed') DEFAULT 'submitted',
  `is_corrected` tinyint(1) DEFAULT 0,
  `allow_retake` tinyint(1) DEFAULT 0,
  `attempt` int(11) NOT NULL DEFAULT 1,
  `start_time` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `feedback_guru` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`id`, `student_id`, `assessment_id`, `pg_score`, `essay_score`, `total_score`, `ai_suggested_score`, `status`, `is_corrected`, `allow_retake`, `attempt`, `start_time`, `started_at`, `feedback_guru`, `submitted_at`, `reviewed_at`, `reviewed_by`) VALUES
(1, 4, 3, NULL, NULL, 80.00, NULL, 'submitted', 1, 0, 1, NULL, NULL, 'Pengerjaan sudah baik, tingkatkan pemahaman pada latihan berikutnya.', '2026-09-30 18:37:28', '2026-10-01 01:42:38', 3),
(2, 4, 2, 0.00, NULL, 50.00, NULL, 'submitted', 1, 0, 1, NULL, NULL, 'Pengerjaan sudah baik, tingkatkan pemahaman pada latihan berikutnya.', '2026-09-30 18:37:50', '2026-10-01 01:43:06', 3),
(3, 4, 1, 0.00, NULL, 50.00, NULL, 'submitted', 1, 0, 1, NULL, NULL, 'Pengerjaan sudah baik, tingkatkan pemahaman pada latihan berikutnya.', '2026-09-30 18:38:38', '2026-10-01 01:42:17', 3),
(6, 10, 2, 33.33, NULL, 50.00, NULL, 'submitted', 1, 0, 1, NULL, NULL, 'Pengerjaan sudah baik, tingkatkan pemahaman pada latihan berikutnya.', '2026-10-01 09:30:56', '2026-10-02 14:03:36', 9),
(7, 21, 2, 0.00, NULL, 45.00, NULL, 'submitted', 1, 0, 1, NULL, NULL, 'Pengerjaan sudah baik, tingkatkan pemahaman pada latihan berikutnya.', '2026-10-08 17:28:07', '2026-10-16 14:45:53', 9),
(8, 10, 1, 0.00, NULL, 70.00, NULL, 'submitted', 1, 0, 1, '2026-10-16 10:18:36', '2026-10-16 10:18:36', 'Pengerjaan sudah baik, tingkatkan pemahaman pada latihan berikutnya.', '2026-10-16 08:20:02', '2026-10-16 15:27:09', 9),
(9, 10, 3, NULL, NULL, 80.00, NULL, 'submitted', 1, 0, 1, '2026-10-16 10:20:53', '2026-10-16 10:20:53', 'Pengerjaan sudah baik, tingkatkan pemahaman pada latihan berikutnya.', '2026-10-16 08:23:13', '2026-10-16 15:25:46', 9);

-- --------------------------------------------------------

--
-- Table structure for table `submission_answers`
--

CREATE TABLE `submission_answers` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `jawaban_siswa` text DEFAULT NULL,
  `foto_jawaban` varchar(255) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `ai_score` decimal(5,2) DEFAULT NULL,
  `ai_feedback` text DEFAULT NULL,
  `teacher_score` decimal(5,2) DEFAULT NULL,
  `teacher_feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submission_answers`
--

INSERT INTO `submission_answers` (`id`, `submission_id`, `question_id`, `jawaban_siswa`, `foto_jawaban`, `is_correct`, `ai_score`, `ai_feedback`, `teacher_score`, `teacher_feedback`) VALUES
(1, 1, 7, 'bnnn', NULL, NULL, 60.00, 'Penilaian awal otomatis (estimasi kelengkapan teks). Harap Guru melakukan peninjauan dan penyesuaian nilai akhir secara manual.', 60.00, 'Penilaian awal otomatis (estimasi kelengkapan teks). Harap Guru melakukan peninjauan dan penyesuaian nilai akhir secara manual.'),
(2, 1, 8, ' bb', NULL, NULL, 60.00, 'Penilaian awal otomatis (estimasi kelengkapan teks). Harap Guru melakukan peninjauan dan penyesuaian nilai akhir secara manual.', 60.00, 'Penilaian awal otomatis (estimasi kelengkapan teks). Harap Guru melakukan peninjauan dan penyesuaian nilai akhir secara manual.'),
(3, 2, 3, '0', NULL, 0, NULL, NULL, NULL, NULL),
(4, 2, 4, '2', NULL, 0, NULL, NULL, NULL, NULL),
(5, 2, 5, '0', NULL, 0, NULL, NULL, NULL, NULL),
(6, 2, 6, 'bnb', NULL, NULL, 60.00, 'Penilaian awal otomatis (estimasi kelengkapan teks). Harap Guru melakukan peninjauan dan penyesuaian nilai akhir secara manual.', 60.00, 'Penilaian awal otomatis (estimasi kelengkapan teks). Harap Guru melakukan peninjauan dan penyesuaian nilai akhir secara manual.'),
(7, 3, 1, '1', NULL, 0, NULL, NULL, NULL, NULL),
(8, 3, 2, 'nsjnujs', 'assets/uploads/lkpd_4_2_1790793518.png', NULL, 60.00, 'Penilaian awal otomatis (estimasi kelengkapan teks). Harap Guru melakukan peninjauan dan penyesuaian nilai akhir secara manual.', 60.00, 'Penilaian awal otomatis (estimasi kelengkapan teks). Harap Guru melakukan peninjauan dan penyesuaian nilai akhir secara manual.'),
(17, 6, 3, '0', NULL, 0, NULL, NULL, NULL, NULL),
(18, 6, 4, '1', NULL, 1, NULL, NULL, NULL, NULL),
(19, 6, 5, '1', NULL, 0, NULL, NULL, NULL, NULL),
(20, 6, 6, 'njn jk', NULL, NULL, 70.00, 'Penjelasan umum sudah baik, namun dapat diperkuat dengan terminologi matematika yang lebih spesifik. Evaluasi awal AI disajikan sebagai acuan; Guru dapat menyesuaikan skor dan catatan akhir.', 70.00, 'Penjelasan umum sudah baik, namun dapat diperkuat dengan terminologi matematika yang lebih spesifik. Evaluasi awal AI disajikan sebagai acuan; Guru dapat menyesuaikan skor dan catatan akhir.'),
(21, 7, 3, '', NULL, 0, NULL, NULL, NULL, NULL),
(22, 7, 4, '', NULL, 0, NULL, NULL, NULL, NULL),
(23, 7, 5, '', NULL, 0, NULL, NULL, NULL, NULL),
(24, 7, 6, '', NULL, NULL, 20.00, 'Siswa tidak mengisi uraian teks jawaban. Silakan periksa lampiran foto lembar kerja jika tersedia.', 20.00, 'Siswa tidak mengisi uraian teks jawaban. Silakan periksa lampiran foto lembar kerja jika tersedia.'),
(25, 8, 1, '1', NULL, 0, NULL, NULL, NULL, NULL),
(26, 8, 2, 'n sm', 'assets/uploads/jawaban/cam_10_2_1792138802_451.jpg', NULL, 70.00, 'Penjelasan umum sudah baik, namun dapat diperkuat dengan terminologi matematika yang lebih spesifik. Evaluasi awal AI disajikan sebagai acuan; Guru dapat menyesuaikan skor dan catatan akhir.', 70.00, 'Penjelasan umum sudah baik, namun dapat diperkuat dengan terminologi matematika yang lebih spesifik. Evaluasi awal AI disajikan sebagai acuan; Guru dapat menyesuaikan skor dan catatan akhir.'),
(27, 8, 9, 'nejjdkdskkdsks', 'assets/uploads/jawaban/ans_10_9_1792138802_375.jpg', NULL, 70.00, 'Penjelasan umum sudah baik, namun dapat diperkuat dengan terminologi matematika yang lebih spesifik. Evaluasi awal AI disajikan sebagai acuan; Guru dapat menyesuaikan skor dan catatan akhir.', 70.00, 'Penjelasan umum sudah baik, namun dapat diperkuat dengan terminologi matematika yang lebih spesifik. Evaluasi awal AI disajikan sebagai acuan; Guru dapat menyesuaikan skor dan catatan akhir.'),
(28, 9, 7, 'Mean (rata-rata) = 83\r\nModus = 90', NULL, NULL, 90.00, 'Langkah perhitungan matematis atau komponen angka disertakan dengan runtut. Konsep matematika yang relevan (perhitungan rata-rata (mean), nilai terbanyak (modus)) telah diidentifikasi dengan tepat. Evaluasi awal AI disajikan sebagai acuan; Guru dapat menyesuaikan skor dan catatan akhir.', 90.00, 'Langkah perhitungan matematis atau komponen angka disertakan dengan runtut. Konsep matematika yang relevan (perhitungan rata-rata (mean), nilai terbanyak (modus)) telah diidentifikasi dengan tepat. Evaluasi awal AI disajikan sebagai acuan; Guru dapat menyesuaikan skor dan catatan akhir.'),
(29, 9, 8, 'Jawaban: Diagram batang.\r\nAlasan: Diagram batang paling tepat digunakan untuk menyajikan data kegiatan ekstrakurikuler yang diminati siswa karena data tersebut berupa kategori, seperti Pramuka, futsal, basket, dan tari. Diagram batang memudahkan kita membandingkan jumlah siswa yang memilih setiap kegiatan ekstrakurikuler sehingga kegiatan yang paling banyak dan paling sedikit diminati dapat diketahui dengan mudah.', NULL, NULL, 95.00, 'Konsep matematika yang relevan (representasi visual diagram batang/lingkaran, konsep dasar himpunan data) telah diidentifikasi dengan tepat. Evaluasi awal AI disajikan sebagai acuan; Guru dapat menyesuaikan skor dan catatan akhir.', 95.00, 'Konsep matematika yang relevan (representasi visual diagram batang/lingkaran, konsep dasar himpunan data) telah diidentifikasi dengan tepat. Evaluasi awal AI disajikan sebagai acuan; Guru dapat menyesuaikan skor dan catatan akhir.');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`) VALUES
(1, 'gemini_api_key', '', 'API Key Google Gemini untuk koreksi otomatis essay matematika'),
(2, 'lkpd_passing_grade', '75', 'Standar Ketuntasan Minimal LKPD'),
(3, 'site_name', 'ElearnMath', 'Nama Platform E-Learning');

-- --------------------------------------------------------

--
-- Table structure for table `tab_switch_logs`
--

CREATE TABLE `tab_switch_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL DEFAULT '',
  `kelas` varchar(50) DEFAULT NULL,
  `assessment_id` int(11) DEFAULT NULL,
  `assessment_title` varchar(255) NOT NULL DEFAULT 'Ujian/Modul',
  `switch_count` int(11) NOT NULL DEFAULT 1,
  `last_switched_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` varchar(255) DEFAULT 'Pindah tab peramban saat pengerjaan',
  `session_type` enum('LKPD','LATIHAN','EVALUASI') DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  `switch_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tab_switch_logs`
--

INSERT INTO `tab_switch_logs` (`id`, `user_id`, `nama_siswa`, `kelas`, `assessment_id`, `assessment_title`, `switch_count`, `last_switched_at`, `notes`, `session_type`, `task_id`, `switch_time`) VALUES
(1, 10, 'MIMIL', 'VII-A', 2, 'Latihan 1: Statistika & Ukuran Pemusatan', 2, '2026-10-01 09:30:56', 'Pindah tab peramban saat pengerjaan', 'LKPD', 0, '2026-10-01 15:05:05'),
(2, 5, 'Siti Nurhaliza', 'VII-A', 2, 'Latihan 1: Statistika & Ukuran Pemusatan', 3, '2026-10-01 09:33:08', 'Pindah tab peramban saat pengerjaan', 'LKPD', 0, '2026-10-01 15:08:58'),
(3, 10, 'MIMIL', 'VII-A', 4, 'Latihan 2: Penyajian Data & Diagram Lingkaran', 2, '2026-10-01 09:23:25', 'Pindah tab peramban saat pengerjaan', NULL, NULL, '2026-10-01 16:23:20'),
(4, 21, 'ML', 'VII-A', 2, 'Latihan 1: Statistika & Ukuran Pemusatan', 1, '2026-10-08 17:28:08', 'Pindah tab peramban saat pengerjaan', NULL, NULL, '2026-10-09 00:28:08'),
(5, 23, 'KM', 'VII-A', 2, 'Latihan 1: Statistika & Ukuran Pemusatan', 1, '2026-10-08 18:36:18', 'Pindah tab peramban saat pengerjaan', NULL, NULL, '2026-10-09 01:36:18'),
(6, 10, 'MIMIL', 'VII-A', 3, 'Evaluasi Akhir Bab Statistika', 3, '2026-10-16 08:22:40', 'Terdeteksi: Pindah tab/jendela peramban saat pengerjaan', 'EVALUASI', 3, '2026-10-16 15:21:07');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_admin_messages`
--

CREATE TABLE `teacher_admin_messages` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `sender_role` enum('guru','admin') NOT NULL,
  `judul` varchar(255) DEFAULT NULL,
  `pesan` text NOT NULL,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_class_requests`
--

CREATE TABLE `teacher_class_requests` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_class_requests`
--

INSERT INTO `teacher_class_requests` (`id`, `teacher_id`, `class_id`, `status`, `created_at`) VALUES
(1, 9, 2, 'PENDING', '2026-10-08 17:33:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','guru','siswa','staff') NOT NULL DEFAULT 'siswa',
  `status_approval` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'APPROVED',
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `target_class_id` int(11) DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT 'Laki-laki',
  `agama` varchar(30) DEFAULT 'Islam',
  `nama_wali` varchar(100) DEFAULT NULL,
  `asal_sekolah` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `mapel` varchar(100) DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `last_active` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo` varchar(255) DEFAULT 'default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `status_approval`, `nama_lengkap`, `email`, `nisn`, `kelas`, `target_class_id`, `jenis_kelamin`, `agama`, `nama_wali`, `asal_sekolah`, `alamat`, `bio`, `no_hp`, `foto`, `mapel`, `is_online`, `last_login`, `last_active`, `created_at`, `photo`) VALUES
(1, 'admin', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'guru', 'APPROVED', 'Administrator Utama', 'admin@students.um.ac.id', NULL, NULL, NULL, 'Laki-laki', 'Islam', NULL, NULL, NULL, NULL, '081234567800', NULL, NULL, 1, '2026-10-01 01:47:33', '2026-10-01 01:47:33', '2026-09-30 17:09:23', 'default.png'),
(3, 'mila', '$2y$10$bVsAMvjSrr2NXhONHWl88OPkC67Kon1J32qKUwNUMS37qb95zdKzy', 'admin', 'APPROVED', 'Nur Kamila Agustiyawati, S.Pd.', 'nurkamilaagustiyawati@gmail.com', NULL, 'VII-A, VII-B, VII-C', NULL, 'Perempuan', 'Islam', NULL, NULL, NULL, NULL, '081237922336', 'assets/uploads/admin_3_1790836732.jpeg', 'Matematika', 0, '2026-10-16 15:56:04', '2026-10-16 16:08:27', '2026-09-30 17:09:23', 'default.png'),
(4, 'siswa1', '$2y$10$d/mI2eNnQn1g2aU3wE4r5.K1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z', 'siswa', 'APPROVED', 'Ahmad Faiz', 'ahmad@siswa.sekolah.id', '1234567890', 'VII-A', NULL, 'Laki-laki', 'Islam', NULL, NULL, NULL, NULL, '082198765432', NULL, NULL, 0, '2026-10-02 14:07:56', '2026-10-02 14:19:36', '2026-09-30 17:09:23', 'default.png'),
(5, 'siswa2', '$2y$10$d/mI2eNnQn1g2aU3wE4r5.K1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z', 'siswa', 'APPROVED', 'Siti Nurhaliza', 'siti@siswa.sekolah.id', '1234567891', 'VII-A', NULL, 'Perempuan', 'Islam', NULL, NULL, NULL, NULL, '082198765433', NULL, NULL, 0, '2026-10-01 16:32:44', '2026-10-01 16:33:32', '2026-09-30 17:09:23', 'default.png'),
(7, 'dewi', '$2y$10$bVsAMvjSrr2NXhONHWl88OPkC67Kon1J32qKUwNUMS37qb95zdKzy', 'admin', 'APPROVED', 'Dewi Rahmawati, M.Pd.', 'dewi05964@gmail.com', NULL, NULL, NULL, 'Perempuan', 'Islam', NULL, NULL, NULL, NULL, '081234567892', NULL, NULL, 0, NULL, NULL, '2026-10-01 05:45:53', 'default.png'),
(8, 'okta', '$2y$10$bVsAMvjSrr2NXhONHWl88OPkC67Kon1J32qKUwNUMS37qb95zdKzy', 'admin', 'APPROVED', 'Via Dwi Oktavia, S.Mat.', 'Via.dwi.2403116@students.um.ac.id', NULL, NULL, NULL, 'Perempuan', 'Islam', NULL, NULL, NULL, NULL, '081234567893', NULL, NULL, 0, NULL, NULL, '2026-10-01 05:45:53', 'default.png'),
(9, 'KAMILA', '$2y$10$VkcA6mtkL6AztyhcQFk5UOskCTfvgPiGaANzLpzTnv.mxLASVWYti', 'guru', 'APPROVED', 'KAMILA', 'KAMILA@GMAIL.COM', NULL, 'VII-A', 1, 'Perempuan', 'Islam', NULL, NULL, '', '', '0812346778', 'assets/uploads/guru_9_1790841441.jpeg', 'Matematika', 0, '2026-10-16 15:29:38', '2026-10-16 15:29:43', '2026-10-01 06:41:23', 'default.png'),
(10, 'MIMIL', '$2y$10$GehVeL628rm5oz0a5HgbBe5y.3iKyZeKnuWw8SAtO2NVL.W2tQp2m', 'siswa', 'APPROVED', 'MIMIL', 'MIMIL@GMAIL.COM', NULL, 'VII-A', 1, 'Perempuan', 'Islam', '', '', '', NULL, '086368728729', 'assets/uploads/siswa_file_10_1790837836.jpeg', NULL, 0, '2026-10-16 15:36:48', '2026-10-16 15:55:52', '2026-10-01 06:42:03', 'default.png'),
(14, 'KAMIL', '$2y$10$hRgomEWjnjTtWjetcd.xl.uZYzX0KPVqDe4P2T6mJsIihyTy9ItZ2', 'guru', 'APPROVED', 'KAMIL', 'KAMIL@GMAIL.COM', NULL, 'VII-B', 2, 'Laki-laki', 'Islam', NULL, NULL, NULL, NULL, '08208973783', NULL, NULL, 0, NULL, NULL, '2026-10-01 08:17:30', 'default.png'),
(21, 'ML', '$2y$10$vfA8r9B2mThFIEuK0GQFOO9hOvpYsTB1B8pGRhzvC46X.EyUpKFeK', 'siswa', 'APPROVED', 'ML', 'mila@gmail.com', NULL, 'VII-A', 1, 'Perempuan', 'Islam', NULL, NULL, NULL, NULL, '081237922336', NULL, NULL, 1, '2026-10-09 03:06:27', '2026-10-09 03:06:27', '2026-10-08 17:27:08', 'default.png'),
(22, 'test_pending', 'test', 'siswa', 'REJECTED', 'Test Siswa Pending', 'pending@test.com', NULL, '7A', NULL, 'Laki-laki', 'Islam', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-10-08 18:15:10', 'default.png'),
(23, '12345678', '$2y$10$GXhWu1o/xsPn951e1COZb./OkJdndwbbir0AsgosEv2UNp/7.sRzq', 'siswa', 'APPROVED', 'KM', 'KM@GMAIL.COM', NULL, 'VII-A', 1, 'Perempuan', 'Islam', NULL, NULL, NULL, NULL, '081237922336', NULL, NULL, 0, '2026-10-09 01:35:47', '2026-10-09 03:00:44', '2026-10-08 18:35:10', 'default.png'),
(43, 'P', '$2y$10$XT3QDCZbICn0d3XCrwh7.uuLCmyMQjQVW1VJttg.LmtMMCc.x616K', 'siswa', 'PENDING', 'P', 'P@GMAIL.COM', NULL, NULL, NULL, 'Laki-laki', 'Islam', NULL, NULL, NULL, NULL, '276626781881', NULL, NULL, 1, '2026-10-16 16:09:05', '2026-10-16 16:31:24', '2026-10-16 09:08:54', 'default.png');

-- --------------------------------------------------------

--
-- Structure for view `evaluasi`
--
DROP TABLE IF EXISTS `evaluasi`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `evaluasi`  AS SELECT `assessments`.`id` AS `id`, `assessments`.`tipe` AS `tipe`, `assessments`.`judul` AS `judul`, `assessments`.`urutan` AS `urutan`, `assessments`.`deskripsi` AS `deskripsi`, `assessments`.`kelas` AS `kelas`, `assessments`.`passing_grade` AS `passing_grade`, `assessments`.`duration_minutes` AS `duration_minutes`, `assessments`.`deadline` AS `deadline`, `assessments`.`is_active` AS `is_active`, `assessments`.`created_by` AS `created_by`, `assessments`.`created_at` AS `created_at` FROM `assessments` WHERE `assessments`.`tipe` = 'evaluasi' ;

-- --------------------------------------------------------

--
-- Structure for view `jawaban`
--
DROP TABLE IF EXISTS `jawaban`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `jawaban`  AS SELECT `sa`.`id` AS `id`, `sa`.`submission_id` AS `submission_id`, `sa`.`question_id` AS `question_id`, `sa`.`jawaban_siswa` AS `jawaban_siswa`, `sa`.`foto_jawaban` AS `foto_jawaban`, `sa`.`is_correct` AS `is_correct`, `sa`.`ai_score` AS `ai_score`, `sa`.`ai_feedback` AS `ai_feedback`, `sa`.`teacher_score` AS `teacher_score`, `sa`.`teacher_feedback` AS `teacher_feedback`, `s`.`student_id` AS `student_id`, `s`.`assessment_id` AS `assessment_id`, `s`.`submitted_at` AS `created_at` FROM (`submission_answers` `sa` join `submissions` `s` on(`sa`.`submission_id` = `s`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `jawaban_latihan`
--
DROP TABLE IF EXISTS `jawaban_latihan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `jawaban_latihan`  AS SELECT `sa`.`id` AS `id`, `sa`.`submission_id` AS `submission_id`, `sa`.`question_id` AS `question_id`, `sa`.`jawaban_siswa` AS `jawaban_siswa`, `sa`.`foto_jawaban` AS `foto_jawaban`, `sa`.`is_correct` AS `is_correct`, `sa`.`ai_score` AS `ai_score`, `sa`.`ai_feedback` AS `ai_feedback`, `sa`.`teacher_score` AS `teacher_score`, `sa`.`teacher_feedback` AS `teacher_feedback`, `s`.`student_id` AS `student_id`, `s`.`assessment_id` AS `latihan_id`, `s`.`submitted_at` AS `created_at` FROM ((`submission_answers` `sa` join `submissions` `s` on(`sa`.`submission_id` = `s`.`id`)) join `assessments` `a` on(`s`.`assessment_id` = `a`.`id`)) WHERE `a`.`tipe` = 'latihan' ;

-- --------------------------------------------------------

--
-- Structure for view `jawaban_lkpd`
--
DROP TABLE IF EXISTS `jawaban_lkpd`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `jawaban_lkpd`  AS SELECT `sa`.`id` AS `id`, `sa`.`submission_id` AS `submission_id`, `sa`.`question_id` AS `question_id`, `sa`.`jawaban_siswa` AS `jawaban_siswa`, `sa`.`foto_jawaban` AS `foto_jawaban`, `sa`.`is_correct` AS `is_correct`, `sa`.`ai_score` AS `ai_score`, `sa`.`ai_feedback` AS `ai_feedback`, `sa`.`teacher_score` AS `teacher_score`, `sa`.`teacher_feedback` AS `teacher_feedback`, `s`.`student_id` AS `student_id`, `s`.`assessment_id` AS `lkpd_id`, `s`.`submitted_at` AS `created_at` FROM ((`submission_answers` `sa` join `submissions` `s` on(`sa`.`submission_id` = `s`.`id`)) join `assessments` `a` on(`s`.`assessment_id` = `a`.`id`)) WHERE `a`.`tipe` = 'lkpd' ;

-- --------------------------------------------------------

--
-- Structure for view `kelas`
--
DROP TABLE IF EXISTS `kelas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `kelas`  AS SELECT `classes`.`id` AS `id`, `classes`.`name` AS `name`, `classes`.`teacher_id` AS `teacher_id`, `classes`.`status` AS `status`, `classes`.`created_at` AS `created_at` FROM `classes` ;

-- --------------------------------------------------------

--
-- Structure for view `latihan`
--
DROP TABLE IF EXISTS `latihan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `latihan`  AS SELECT `assessments`.`id` AS `id`, `assessments`.`tipe` AS `tipe`, `assessments`.`judul` AS `judul`, `assessments`.`urutan` AS `urutan`, `assessments`.`deskripsi` AS `deskripsi`, `assessments`.`kelas` AS `kelas`, `assessments`.`passing_grade` AS `passing_grade`, `assessments`.`duration_minutes` AS `duration_minutes`, `assessments`.`deadline` AS `deadline`, `assessments`.`is_active` AS `is_active`, `assessments`.`created_by` AS `created_by`, `assessments`.`created_at` AS `created_at` FROM `assessments` WHERE `assessments`.`tipe` = 'latihan' ;

-- --------------------------------------------------------

--
-- Structure for view `lkpd`
--
DROP TABLE IF EXISTS `lkpd`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `lkpd`  AS SELECT `assessments`.`id` AS `id`, `assessments`.`tipe` AS `tipe`, `assessments`.`judul` AS `judul`, `assessments`.`urutan` AS `urutan`, `assessments`.`deskripsi` AS `deskripsi`, `assessments`.`kelas` AS `kelas`, `assessments`.`passing_grade` AS `passing_grade`, `assessments`.`duration_minutes` AS `duration_minutes`, `assessments`.`deadline` AS `deadline`, `assessments`.`is_active` AS `is_active`, `assessments`.`created_by` AS `created_by`, `assessments`.`created_at` AS `created_at` FROM `assessments` WHERE `assessments`.`tipe` = 'lkpd' ;

-- --------------------------------------------------------

--
-- Structure for view `pengerjaan_latihan`
--
DROP TABLE IF EXISTS `pengerjaan_latihan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `pengerjaan_latihan`  AS SELECT `s`.`id` AS `id`, `s`.`id` AS `submission_id`, `s`.`student_id` AS `student_id`, `s`.`assessment_id` AS `assessment_id`, `s`.`assessment_id` AS `latihan_id`, `s`.`pg_score` AS `pg_score`, `s`.`essay_score` AS `essay_score`, `s`.`total_score` AS `total_score`, `s`.`ai_suggested_score` AS `ai_suggested_score`, `s`.`status` AS `status`, `s`.`is_corrected` AS `is_corrected`, `s`.`allow_retake` AS `allow_retake`, `s`.`attempt` AS `attempt`, `s`.`start_time` AS `start_time`, `s`.`started_at` AS `started_at`, `s`.`submitted_at` AS `submitted_at`, `s`.`submitted_at` AS `created_at`, `s`.`reviewed_at` AS `reviewed_at`, `s`.`reviewed_by` AS `reviewed_by`, `s`.`feedback_guru` AS `feedback_guru`, `a`.`judul` AS `judul_latihan`, `a`.`passing_grade` AS `passing_grade`, `a`.`duration_minutes` AS `duration_minutes`, `a`.`kelas` AS `kelas` FROM (`submissions` `s` join `assessments` `a` on(`s`.`assessment_id` = `a`.`id`)) WHERE `a`.`tipe` = 'latihan' ;

-- --------------------------------------------------------

--
-- Structure for view `pengerjaan_lkpd`
--
DROP TABLE IF EXISTS `pengerjaan_lkpd`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `pengerjaan_lkpd`  AS SELECT `s`.`id` AS `id`, `s`.`id` AS `submission_id`, `s`.`student_id` AS `student_id`, `s`.`assessment_id` AS `assessment_id`, `s`.`assessment_id` AS `lkpd_id`, `s`.`pg_score` AS `pg_score`, `s`.`essay_score` AS `essay_score`, `s`.`total_score` AS `total_score`, `s`.`ai_suggested_score` AS `ai_suggested_score`, `s`.`status` AS `status`, `s`.`is_corrected` AS `is_corrected`, `s`.`allow_retake` AS `allow_retake`, `s`.`attempt` AS `attempt`, `s`.`start_time` AS `start_time`, `s`.`started_at` AS `started_at`, `s`.`submitted_at` AS `submitted_at`, `s`.`submitted_at` AS `created_at`, `s`.`reviewed_at` AS `reviewed_at`, `s`.`reviewed_by` AS `reviewed_by`, `s`.`feedback_guru` AS `feedback_guru`, `a`.`judul` AS `judul_lkpd`, `a`.`passing_grade` AS `passing_grade`, `a`.`duration_minutes` AS `duration_minutes`, `a`.`kelas` AS `kelas` FROM (`submissions` `s` join `assessments` `a` on(`s`.`assessment_id` = `a`.`id`)) WHERE `a`.`tipe` = 'lkpd' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_role` (`role`),
  ADD KEY `idx_log_user` (`user_id`);

--
-- Indexes for table `answers_grading`
--
ALTER TABLE `answers_grading`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assessment_tipe` (`tipe`),
  ADD KEY `fk_assessment_creator` (`created_by`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `fk_classes_teacher` (`teacher_id`);

--
-- Indexes for table `class_students`
--
ALTER TABLE `class_students`
  ADD PRIMARY KEY (`class_id`,`student_id`),
  ADD KEY `fk_cs_student` (`student_id`);

--
-- Indexes for table `jadwal_pelajaran`
--
ALTER TABLE `jadwal_pelajaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jadwal_kelas` (`kelas`),
  ADD KEY `idx_jadwal_hari` (`hari`),
  ADD KEY `fk_jadwal_guru` (`guru_id`);

--
-- Indexes for table `materi`
--
ALTER TABLE `materi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_materi_guru` (`guru_id`);

--
-- Indexes for table `materi_reads`
--
ALTER TABLE `materi_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_materi` (`user_id`,`materi_id`),
  ADD KEY `materi_id` (`materi_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`,`is_read`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_question_assessment` (`assessment_id`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sub_student` (`student_id`),
  ADD KEY `idx_sub_assessment` (`assessment_id`),
  ADD KEY `fk_sub_reviewer` (`reviewed_by`);

--
-- Indexes for table `submission_answers`
--
ALTER TABLE `submission_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sa_submission` (`submission_id`),
  ADD KEY `fk_sa_question` (`question_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `tab_switch_logs`
--
ALTER TABLE `tab_switch_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `teacher_admin_messages`
--
ALTER TABLE `teacher_admin_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tam_teacher` (`teacher_id`),
  ADD KEY `idx_tam_sender` (`sender_id`),
  ADD KEY `idx_tam_created` (`created_at`);

--
-- Indexes for table `teacher_class_requests`
--
ALTER TABLE `teacher_class_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_teacher_class` (`teacher_id`,`class_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `answers_grading`
--
ALTER TABLE `answers_grading`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jadwal_pelajaran`
--
ALTER TABLE `jadwal_pelajaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `materi`
--
ALTER TABLE `materi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `materi_reads`
--
ALTER TABLE `materi_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `submission_answers`
--
ALTER TABLE `submission_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tab_switch_logs`
--
ALTER TABLE `tab_switch_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teacher_admin_messages`
--
ALTER TABLE `teacher_admin_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teacher_class_requests`
--
ALTER TABLE `teacher_class_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `answers_grading`
--
ALTER TABLE `answers_grading`
  ADD CONSTRAINT `answers_grading_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `fk_assessment_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `fk_classes_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `class_students`
--
ALTER TABLE `class_students`
  ADD CONSTRAINT `fk_cs_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cs_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `jadwal_pelajaran`
--
ALTER TABLE `jadwal_pelajaran`
  ADD CONSTRAINT `fk_jadwal_guru` FOREIGN KEY (`guru_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `materi`
--
ALTER TABLE `materi`
  ADD CONSTRAINT `fk_materi_guru` FOREIGN KEY (`guru_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `materi_reads`
--
ALTER TABLE `materi_reads`
  ADD CONSTRAINT `materi_reads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `materi_reads_ibfk_2` FOREIGN KEY (`materi_id`) REFERENCES `materi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `fk_questions_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `fk_sub_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sub_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sub_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `submission_answers`
--
ALTER TABLE `submission_answers`
  ADD CONSTRAINT `fk_sa_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sa_submission` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tab_switch_logs`
--
ALTER TABLE `tab_switch_logs`
  ADD CONSTRAINT `tab_switch_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_class_requests`
--
ALTER TABLE `teacher_class_requests`
  ADD CONSTRAINT `teacher_class_requests_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_class_requests_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
