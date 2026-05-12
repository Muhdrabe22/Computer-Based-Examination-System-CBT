<?php
require_once '../includes/config.php';
requireStudentLogin();
$db = getDB();

$studentId = $_SESSION['student_id'];
$student   = $db->prepare("SELECT s.*, d.name AS dept_name FROM students s JOIN departments d ON s.department_id=d.id WHERE s.id=?");
$student->execute([$studentId]);
$student = $student->fetch();

$results = $db->prepare("
    SELECT r.*, es.submitted_at, es.started_at,
           e.exam_title, e.duration_minutes, e.pass_score,
           c.course_code, c.course_title
    FROM results r
    JOIN exam_sessions es ON r.session_id = es.id
    JOIN exams e ON r.exam_id = e.id
    JOIN courses c ON e.course_id = c.id
    WHERE r.student_id = ?
    ORDER BY es.submitted_at DESC
");
$results->execute([$studentId]);
$results = $results->fetchAll();

// Summary stats
$totalExams  = count($results);
$passed      = count(array_filter($results, fn($r) => $r['status'] === 'pass'));
$avgScore    = $totalExams ? round(array_sum(array_column($results,'percentage')) / $totalExams, 1) : 0;
$bestScore   = $totalExams ? max(array_column($results,'percentage')) : 0;

$initials = strtoupper(implode('', array_map(fn($w)=>$w[0], array_slice(explode(' ', $student['full_name']),0,2))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Results — HUKP CBT</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="dashboard-layout">
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">📚</div>
    <div class="sidebar-brand"><h2>HUKP CBT</h2><span>Student Portal</span></div>
  </div>
  <nav class="sidebar-nav">
    <span class="nav-section-label">Navigation</span>
    <a href="dashboard.php" class="nav-item"><span class="nav-icon">🏠</span> Dashboard</a>
    <a href="my_results.php" class="nav-item active"><span class="nav-icon">📊</span> My Results</a>
    <a href="profile.php" class="nav-item"><span class="nav-icon">⚙️</span> My Profile</a>
    <a href="logout.php" class="nav-item" style="color:#f87171"><span class="nav-icon">🚪</span> Logout</a>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar gold"><?= $initials ?></div>
      <div class="user-info">
        <h4><?= sanitize(explode(' ', $student['full_name'])[0]) ?></h4>
        <span><?= sanitize($student['reg_number']) ?></span>
      </div>
    </div>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">
      <h1>My Results</h1>
      <p>Complete examination history for <?= sanitize($student['full_name']) ?></p>
    </div>
    <div class="topbar-actions">
      <button onclick="window.print()" class="btn btn-outline btn-sm"><i class="fas fa-print"></i> Print</button>
      <span class="topbar-time" id="clock">--:--:--</span>
    </div>
  </div>

  <div class="page-content">

    <!-- Student ID Card -->
    <div class="card mb-6" style="background:linear-gradient(135deg,var(--green-800),var(--green-900));border:none;overflow:hidden;position:relative">
      <div style="position:absolute;top:-60px;right:-60px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(212,160,42,.15) 0%,transparent 70%)"></div>
      <div class="card-body" style="padding:28px;display:flex;align-items:center;gap:24px;position:relative;z-index:1">
        <div class="user-avatar gold" style="width:68px;height:68px;font-size:24px;flex-shrink:0"><?= $initials ?></div>
        <div style="flex:1">
          <h2 style="color:white;font-family:var(--font-display);font-size:20px;margin-bottom:4px"><?= sanitize($student['full_name']) ?></h2>
          <p style="color:var(--green-200);font-size:13px"><?= sanitize($student['reg_number']) ?> • <?= sanitize($student['dept_name']) ?> • <?= $student['level'] ?></p>
        </div>
        <div style="display:flex;gap:32px;text-align:center">
          <div>
            <div style="font-size:30px;font-weight:700;color:white;font-family:var(--font-display)"><?= $totalExams ?></div>
            <div style="font-size:11px;color:var(--green-300);text-transform:uppercase;letter-spacing:.06em">Exams</div>
          </div>
          <div>
            <div style="font-size:30px;font-weight:700;color:var(--gold-300);font-family:var(--font-display)"><?= $avgScore ?>%</div>
            <div style="font-size:11px;color:var(--green-300);text-transform:uppercase;letter-spacing:.06em">Average</div>
          </div>
          <div>
            <div style="font-size:30px;font-weight:700;color:var(--green-200);font-family:var(--font-display)"><?= $passed ?>/<?= $totalExams ?></div>
            <div style="font-size:11px;color:var(--green-300);text-transform:uppercase;letter-spacing:.06em">Passed</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
      <div class="stat-card green">
        <div class="stat-icon green">📝</div>
        <h3><?= $totalExams ?></h3>
        <p>Total Exams Taken</p>
      </div>
      <div class="stat-card gold">
        <div class="stat-icon gold">📊</div>
        <h3><?= $avgScore ?>%</h3>
        <p>Average Score</p>
      </div>
      <div class="stat-card blue">
        <div class="stat-icon blue">🏆</div>
        <h3><?= $bestScore ?>%</h3>
        <p>Best Score</p>
      </div>
      <div class="stat-card <?= $totalExams > 0 && ($passed/$totalExams) >= 0.5 ? 'green' : 'red' ?>">
        <div class="stat-icon <?= $totalExams > 0 && ($passed/$totalExams) >= 0.5 ? 'green' : 'red' ?>">✅</div>
        <h3><?= $totalExams > 0 ? round(($passed/$totalExams)*100) : 0 ?>%</h3>
        <p>Pass Rate (<?= $passed ?>/<?= $totalExams ?>)</p>
      </div>
    </div>

    <!-- Results Table -->
    <div class="card">
      <div class="card-header">
        <h3>Examination History</h3>
        <span class="badge badge-green"><?= $totalExams ?> records</span>
      </div>

      <?php if (empty($results)): ?>
      <div class="empty-state">
        <div class="icon">📋</div>
        <p>You haven't taken any exams yet. <a href="dashboard.php" style="color:var(--green-600)">Go to dashboard</a> to see available exams.</p>
      </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Course</th>
              <th>Exam</th>
              <th>Score</th>
              <th>Percentage</th>
              <th>Grade</th>
              <th>Status</th>
              <th>Correct</th>
              <th>Wrong</th>
              <th>Skipped</th>
              <th>Date</th>
              <th>Detail</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results as $i => $r): ?>
            <tr>
              <td class="text-muted"><?= $i+1 ?></td>
              <td><span class="badge badge-green"><?= sanitize($r['course_code']) ?></span></td>
              <td style="max-width:220px">
                <div style="font-size:13px;color:var(--slate-700)"><?= sanitize(substr($r['exam_title'],0,45)) ?>...</div>
              </td>
              <td>
                <strong><?= $r['raw_score'] ?></strong>
                <span class="text-muted text-sm">/ <?= $r['total_questions'] ?></span>
              </td>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <div style="width:60px;height:7px;background:var(--slate-200);border-radius:4px;overflow:hidden;flex-shrink:0">
                    <div style="width:<?= $r['percentage'] ?>%;height:100%;background:<?= $r['percentage']>=$r['pass_score']?'var(--green-500)':'var(--red-500)' ?>;border-radius:4px"></div>
                  </div>
                  <span style="font-weight:600;font-size:13px"><?= $r['percentage'] ?>%</span>
                </div>
              </td>
              <td>
                <span style="font-size:26px;font-weight:700;font-family:var(--font-display);
                  color:<?= match($r['grade']) { 'A','B'=>'var(--green-600)', 'F'=>'var(--red-600)', default=>'var(--gold-500)' } ?>">
                  <?= $r['grade'] ?>
                </span>
                <div style="font-size:11px;color:var(--slate-400)"><?= getGradeLabel($r['grade']) ?></div>
              </td>
              <td>
                <span class="badge <?= $r['status']==='pass'?'badge-green':'badge-red' ?>">
                  <?= $r['status']==='pass' ? '✓ Pass' : '✗ Fail' ?>
                </span>
              </td>
              <td style="color:var(--green-600);font-weight:700"><?= $r['correct'] ?></td>
              <td style="color:var(--red-600);font-weight:700"><?= $r['wrong'] ?></td>
              <td style="color:var(--slate-400)"><?= $r['skipped'] ?></td>
              <td class="text-sm text-muted"><?= date('M j, Y<\b\r>g:i A', strtotime($r['submitted_at'])) ?></td>
              <td>
                <a href="result.php?session_id=<?= $r['session_id'] ?>" class="btn btn-primary btn-sm">
                  <i class="fas fa-eye"></i> Review
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>
</div>
<script>
function updateClock(){document.getElementById('clock').textContent=new Date().toLocaleTimeString('en-NG',{hour12:false});}
updateClock(); setInterval(updateClock,1000);
</script>
</body>
</html>
