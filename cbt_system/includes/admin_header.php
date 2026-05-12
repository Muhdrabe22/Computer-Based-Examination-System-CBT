<?php
// Admin sidebar header — included at the top of every admin page
// Expects: $pageTitle, $pageSubtitle (optional), $currentPage variables set before include
requireAdminLogin();
$adminName = $_SESSION['admin_name'] ?? 'Administrator';
$adminRole = $_SESSION['admin_role'] ?? 'admin';
$initials   = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', $adminName))));
$initials   = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'Admin' ?> — HUKP CBT System</title>
<link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="dashboard-layout">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">🎓</div>
    <div class="sidebar-brand">
      <h2>HUKP CBT</h2>
      <span>Admin Panel</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <span class="nav-section-label">Main</span>
    <a href="<?= BASE_URL ?>admin/dashboard.php" class="nav-item <?= ($currentPage??'')=='dashboard'?'active':'' ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>

    <span class="nav-section-label">Examination</span>
    <a href="<?= BASE_URL ?>admin/exams.php" class="nav-item <?= ($currentPage??'')=='exams'?'active':'' ?>">
      <span class="nav-icon">📋</span> Manage Exams
    </a>
    <a href="<?= BASE_URL ?>admin/questions.php" class="nav-item <?= ($currentPage??'')=='questions'?'active':'' ?>">
      <span class="nav-icon">❓</span> Question Bank
    </a>
    <a href="<?= BASE_URL ?>admin/results.php" class="nav-item <?= ($currentPage??'')=='results'?'active':'' ?>">
      <span class="nav-icon">📈</span> Results & Reports
    </a>

    <span class="nav-section-label">Users</span>
    <a href="<?= BASE_URL ?>admin/students.php" class="nav-item <?= ($currentPage??'')=='students'?'active':'' ?>">
      <span class="nav-icon">👨‍🎓</span> Students
    </a>
    <?php if ($adminRole === 'superadmin'): ?>
    <a href="<?= BASE_URL ?>admin/admins.php" class="nav-item <?= ($currentPage??'')=='admins'?'active':'' ?>">
      <span class="nav-icon">👥</span> Admin Users
    </a>
    <?php endif; ?>

    <span class="nav-section-label">Settings</span>
    <a href="<?= BASE_URL ?>admin/courses.php" class="nav-item <?= ($currentPage??'')=='courses'?'active':'' ?>">
      <span class="nav-icon">📚</span> Courses
    </a>
    <a href="<?= BASE_URL ?>admin/profile.php" class="nav-item <?= ($currentPage??'')=='profile'?'active':'' ?>">
      <span class="nav-icon">⚙️</span> My Profile
    </a>
    <a href="<?= BASE_URL ?>admin/logout.php" class="nav-item" style="color:#f87171">
      <span class="nav-icon">🚪</span> Logout
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar gold"><?= $initials ?></div>
      <div class="user-info">
        <h4><?= sanitize(explode(' ', $adminName)[0] . ' ' . (explode(' ', $adminName)[1] ?? '')) ?></h4>
        <span><?= ucfirst($adminRole) ?></span>
      </div>
    </div>
  </div>
</aside>

<!-- MAIN -->
<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">
      <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
      <?php if (!empty($pageSubtitle)): ?>
      <p><?= $pageSubtitle ?></p>
      <?php endif; ?>
    </div>
    <div class="topbar-actions">
      <span class="topbar-time" id="clock">--:--:--</span>
      <a href="<?= BASE_URL ?>admin/logout.php" class="btn btn-outline btn-sm">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>
  <div class="page-content">
