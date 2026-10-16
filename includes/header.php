<?php
/**
 * Header Template
 * File: includes/header.php
 */
require_once __DIR__ . '/../config/app.php';
$currentUser = auth_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'ElearnMath - Pembelajaran Matematika') ?></title>
  
  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Feather Icons / Lucide SVG Icons -->
  <script src="https://unpkg.com/feather-icons"></script>
  
  <!-- Custom Glassmorphism CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  
  <script>
    window.APP_BASE_URL = "<?= BASE_URL ?>";
  </script>
</head>
<body>
  <!-- Ambient background glow orbs -->
  <div class="ambient-bg">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
  </div>

  <div class="app-shell">
