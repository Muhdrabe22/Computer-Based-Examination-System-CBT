<?php
require_once 'includes/config.php';

// Redirect already logged-in users
if (isAdminLoggedIn()) redirect(BASE_URL . 'admin/dashboard.php');
if (isStudentLoggedIn()) redirect(BASE_URL . 'student/dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HUKP CBT System — Hassan Usman Katsina Polytechnic</title>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  .landing-body { min-height:100vh; background: var(--green-900); display:flex; flex-direction:column; }
  .landing-nav {
    padding:18px 60px; display:flex; align-items:center; justify-content:space-between;
    border-bottom:1px solid rgba(255,255,255,.08);
  }
  .landing-hero {
    flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center;
    text-align:center; padding:80px 40px;
    background: radial-gradient(ellipse at 50% 30%, rgba(33,122,80,.25) 0%, transparent 65%);
    position:relative; overflow:hidden;
  }
  .landing-hero::before {
    content:''; position:absolute; top:0; left:0; right:0; bottom:0;
    background-image: radial-gradient(rgba(255,255,255,.03) 1px, transparent 1px);
    background-size:28px 28px;
  }
  .hero-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(212,160,42,.15); border:1px solid rgba(212,160,42,.3);
    color:var(--gold-200); padding:7px 18px; border-radius:30px; font-size:13px;
    margin-bottom:28px; position:relative; z-index:1;
  }
  .hero-title {
    font-family:var(--font-display); font-size:52px; font-weight:700; color:white;
    line-height:1.15; margin-bottom:20px; position:relative; z-index:1;
    max-width:750px;
  }
  .hero-title span { color:var(--gold-300); }
  .hero-sub {
    font-size:18px; color:var(--green-200); max-width:560px; margin:0 auto 44px;
    position:relative; z-index:1; line-height:1.65;
  }
  .hero-btns { display:flex; gap:16px; flex-wrap:wrap; justify-content:center; position:relative; z-index:1; }
  .portal-cards {
    display:grid; grid-template-columns:1fr 1fr; gap:24px; padding:60px; max-width:900px; margin:0 auto; width:100%;
  }
  .portal-card {
    background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1);
    border-radius:var(--radius-xl); padding:40px; text-align:center;
    transition:all var(--transition); cursor:default;
    backdrop-filter:blur(10px);
  }
  .portal-card:hover { background:rgba(255,255,255,.09); border-color:rgba(255,255,255,.2); transform:translateY(-3px); }
  .portal-icon { font-size:48px; margin-bottom:18px; }
  .portal-card h3 { font-family:var(--font-display); color:white; font-size:22px; margin-bottom:10px; }
  .portal-card p { color:var(--green-200); font-size:14px; line-height:1.6; margin-bottom:24px; }
  .landing-footer {
    padding:20px 60px; border-top:1px solid rgba(255,255,255,.08);
    display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;
  }
  .landing-footer p { color:var(--green-400); font-size:13px; }
  @media(max-width:700px){
    .landing-nav { padding:16px 24px; } .portal-cards { grid-template-columns:1fr; padding:24px; }
    .hero-title { font-size:32px; } .landing-footer { padding:20px 24px; }
  }
</style>
</head>
<body class="landing-body">

<!-- NAV -->
<nav class="landing-nav">
  <div style="display:flex;align-items:center;gap:12px">
    <div style="width:40px;height:40px;background:linear-gradient(135deg,var(--gold-300),var(--gold-500));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px">🎓</div>
    <div>
      <div style="font-family:var(--font-display);color:white;font-size:16px;line-height:1.1">HUKP CBT System</div>
      <div style="font-size:11px;color:var(--green-300)">ICT Department, Katsina</div>
    </div>
  </div>
  <div style="display:flex;gap:10px">
    <a href="student/login.php" class="btn btn-outline" style="color:var(--green-200);border-color:rgba(255,255,255,.2)">
      <i class="fas fa-user-graduate"></i> Student Login
    </a>
    <a href="admin/login.php" class="btn btn-gold btn-sm">
      <i class="fas fa-lock"></i> Admin
    </a>
  </div>
</nav>

<!-- HERO -->
<section class="landing-hero">
  <div class="hero-badge">
    <span style="width:8px;height:8px;background:var(--green-400);border-radius:50%;display:inline-block;animation:pulse 2s infinite"></span>
    System is Online & Ready
  </div>
  <h1 class="hero-title">Computer Based <span>Examination</span> System</h1>
  <p class="hero-sub">
    A secure, automated digital examination platform for Hassan Usman Katsina Polytechnic — ICT Department. Take exams online with instant results.
  </p>
  <div class="hero-btns">
    <a href="student/login.php" class="btn btn-primary btn-lg">
      <i class="fas fa-play-circle"></i> Student Portal
    </a>
    <a href="admin/login.php" class="btn btn-outline btn-lg" style="color:white;border-color:rgba(255,255,255,.3)">
      <i class="fas fa-cogs"></i> Admin Portal
    </a>
  </div>
</section>

<!-- PORTAL CARDS -->
<div class="portal-cards">
  <div class="portal-card">
    <div class="portal-icon">👨‍🎓</div>
    <h3>Student Portal</h3>
    <p>Access your examinations, take timed CBT tests, and view instant automated results with detailed answer reviews.</p>
    <a href="student/login.php" class="btn btn-primary btn-full">
      <i class="fas fa-sign-in-alt"></i> Student Login
    </a>
  </div>
  <div class="portal-card">
    <div class="portal-icon">👨‍🏫</div>
    <h3>Admin / Lecturer Portal</h3>
    <p>Create and manage examinations, upload questions, manage students, and generate comprehensive performance reports.</p>
    <a href="admin/login.php" class="btn btn-gold btn-full">
      <i class="fas fa-sign-in-alt"></i> Admin Login
    </a>
  </div>
</div>

<!-- FEATURES STRIP -->
<div style="background:rgba(255,255,255,.03);border-top:1px solid rgba(255,255,255,.07);border-bottom:1px solid rgba(255,255,255,.07);padding:28px 60px">
  <div style="display:flex;justify-content:center;gap:48px;flex-wrap:wrap">
    <?php $features = [
      ['⚡','Automated Grading','Instant results after submission'],
      ['⏱️','Live Timer','Countdown with auto-submit'],
      ['🔒','Secure Platform','IP logging & session control'],
      ['📊','Detailed Reports','Grade distribution & analytics'],
      ['📱','Mobile Friendly','Works on all screen sizes'],
      ['🔀','Randomization','Shuffled questions & options'],
    ]; ?>
    <?php foreach ($features as [$icon,$title,$desc]): ?>
    <div style="text-align:center;min-width:120px">
      <div style="font-size:26px;margin-bottom:8px"><?= $icon ?></div>
      <div style="font-size:13px;font-weight:600;color:white;margin-bottom:3px"><?= $title ?></div>
      <div style="font-size:11px;color:var(--green-300)"><?= $desc ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- FOOTER -->
<footer class="landing-footer">
  <p>© <?= date('Y') ?> HUKP CBT System — Hassan Usman Katsina Polytechnic, ICT Department</p>
  <p style="color:var(--green-500)">Katsina State, Nigeria 🇳🇬</p>
</footer>

<style>
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
</style>
</body>
</html>
