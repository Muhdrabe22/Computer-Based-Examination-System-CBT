<?php
require_once '../includes/config.php';
requireStudentLogin();
$db = getDB();

$studentId = $_SESSION['student_id'];
$student   = $db->prepare("SELECT s.*, d.name AS dept_name, d.code AS dept_code FROM students s JOIN departments d ON s.department_id=d.id WHERE s.id=?");
$student->execute([$studentId]);
$student = $student->fetch();

// Available exams (active, not yet taken or in progress)
$availableExams = $db->prepare("
    SELECT e.*, c.course_code, c.course_title,
           es.id AS session_id, es.status AS session_status,
           r.percentage AS scored_pct, r.grade AS scored_grade, r.status AS result_status
    FROM exams e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN exam_sessions es ON e.id=es.exam_id AND es.student_id=?
    LEFT JOIN results r ON es.id=r.session_id
    WHERE e.status='active'
    ORDER BY e.created_at DESC
");
$availableExams->execute([$studentId]);
$exams = $availableExams->fetchAll();

// Student's past results
$pastResults = $db->prepare("
    SELECT r.*, e.exam_title, c.course_code, es.submitted_at
    FROM results r
    JOIN exam_sessions es ON r.session_id=es.id
    JOIN exams e ON r.exam_id=e.id
    JOIN courses c ON e.course_id=c.id
    WHERE r.student_id=?
    ORDER BY es.submitted_at DESC
    LIMIT 10
");
$pastResults->execute([$studentId]);
$pastResults = $pastResults->fetchAll();

$initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $student['full_name']), 0, 2))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard — HUKP CBT</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="dashboard-layout">

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">📚</div>
    <div class="sidebar-brand">
      <h2>HUKP CBT</h2>
      <span>Student Portal</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <span class="nav-section-label">Navigation</span>
    <a href="dashboard.php" class="nav-item active">
      <span class="nav-icon">🏠</span> Dashboard
    </a>
    <a href="my_results.php" class="nav-item">
      <span class="nav-icon">📊</span> My Results
    </a>
    <a href="profile.php" class="nav-item">
      <span class="nav-icon">⚙️</span> My Profile
    </a>
    <a href="logout.php" class="nav-item" style="color:#f87171">
      <span class="nav-icon">🚪</span> Logout
    </a>
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
      <h1>Welcome, <?= sanitize(explode(' ', $student['full_name'])[0]) ?> 👋</h1>
      <p><?= sanitize($student['dept_name']) ?> — <?= $student['level'] ?></p>
    </div>
    <div class="topbar-actions">
      <span class="topbar-time" id="clock">--:--:--</span>
    </div>
  </div>

  <div class="page-content">

    <!-- Student Info Card -->
    <div class="card mb-6" style="background:linear-gradient(135deg,var(--green-800),var(--green-900));border:none">
      <div class="card-body" style="display:flex;align-items:center;gap:24px;padding:28px">
        <div class="user-avatar gold" style="width:72px;height:72px;font-size:26px;flex-shrink:0"><?= $initials ?></div>
        <div style="flex:1">
          <h2 style="color:white;font-family:var(--font-display);font-size:22px;margin-bottom:4px"><?= sanitize($student['full_name']) ?></h2>
          <p style="color:var(--green-200);font-size:14px"><?= sanitize($student['dept_name']) ?> — <?= $student['level'] ?></p>
        </div>
        <div style="display:flex;gap:24px">
          <div style="text-align:center">
            <div style="font-size:28px;font-weight:700;color:white;font-family:var(--font-display)"><?= count($pastResults) ?></div>
            <div style="font-size:11px;color:var(--green-200);text-transform:uppercase;letter-spacing:.05em">Exams Taken</div>
          </div>
          <div style="text-align:center">
            <?php
            $avgPct = $pastResults ? round(array_sum(array_column($pastResults,'percentage')) / count($pastResults), 1) : 0;
            ?>
            <div style="font-size:28px;font-weight:700;color:var(--gold-300);font-family:var(--font-display)"><?= $avgPct ?>%</div>
            <div style="font-size:11px;color:var(--green-200);text-transform:uppercase;letter-spacing:.05em">Avg. Score</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Available Exams -->
    <div class="card mb-6">
      <div class="card-header">
        <h3>Available Exams</h3>
        <span class="badge badge-green"><?= count(array_filter($exams, fn($e) => !$e['session_id'] || $e['session_status']==='in_progress')) ?> available</span>
      </div>

      <?php if (empty($exams)): ?>
      <div class="empty-state"><div class="icon">📋</div><p>No active exams available at this time.</p></div>
      <?php else: ?>
      <div style="padding:16px;display:flex;flex-direction:column;gap:12px">
        <?php foreach ($exams as $exam): ?>
        <?php
        $taken  = !empty($exam['session_id']) && $exam['session_status'] !== 'in_progress';
        $inProg = !empty($exam['session_id']) && $exam['session_status'] === 'in_progress';
        ?>
        <div style="border:1px solid var(--slate-200);border-radius:var(--radius);padding:20px;display:flex;align-items:center;gap:16px;background:<?= $taken?'var(--slate-50)':'var(--white)' ?>">
          <div style="width:50px;height:50px;background:<?= $taken?'var(--slate-200)':'var(--green-100)' ?>;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
            <?= $taken ? '✅' : '📝' ?>
          </div>
          <div style="flex:1">
            <h4 style="color:var(--slate-800);margin-bottom:4px"><?= sanitize($exam['exam_title']) ?></h4>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
              <span class="badge badge-green"><?= sanitize($exam['course_code']) ?></span>
              <span class="text-sm text-muted"><i class="fas fa-clock"></i> <?= $exam['duration_minutes'] ?> min</span>
              <span class="text-sm text-muted"><i class="fas fa-question-circle"></i> <?= $exam['total_questions'] ?> questions</span>
              <?php if ($taken): ?>
              <span class="badge <?= $exam['result_status']==='pass'?'badge-green':'badge-red' ?>">
                <?= $exam['scored_pct'] ?>% — <?= $exam['scored_grade'] ?> — <?= ucfirst($exam['result_status']) ?>
              </span>
              <?php endif; ?>
            </div>
          </div>
          <div>
            <?php if ($taken): ?>
              <a href="result.php?session_id=<?= $exam['session_id'] ?>" class="btn btn-outline btn-sm">
                <i class="fas fa-chart-bar"></i> View Result
              </a>
            <?php elseif ($inProg): ?>
              <a href="exam.php?exam_id=<?= $exam['id'] ?>" class="btn btn-gold btn-sm">
                <i class="fas fa-play"></i> Continue
              </a>
            <?php else: ?>
              <a href="exam_start.php?exam_id=<?= $exam['id'] ?>" class="btn btn-primary">
                <i class="fas fa-play"></i> Start Exam
              </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Recent Results -->
    <?php if ($pastResults): ?>
    <div class="card">
      <div class="card-header">
        <h3>My Recent Results</h3>
        <a href="my_results.php" class="btn btn-outline btn-sm">View All</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Course</th><th>Score</th><th>Grade</th><th>Status</th><th>Date</th><th>Detail</th></tr>
          </thead>
          <tbody>
            <?php foreach ($pastResults as $r): ?>
            <tr>
              <td>
                <span class="badge badge-green"><?= sanitize($r['course_code']) ?></span>
                <span class="text-sm text-muted ml-1"><?= sanitize(substr($r['exam_title'],0,30)) ?>...</span>
              </td>
              <td><strong><?= $r['percentage'] ?>%</strong> <span class="text-sm text-muted">(<?= $r['correct'] ?>/<?= $r['total_questions'] ?>)</span></td>
              <td>
                <span style="font-size:22px;font-weight:700;font-family:var(--font-display);color:<?= $r['grade']==='F'?'var(--red-600)':'var(--green-600)' ?>"><?= $r['grade'] ?></span>
              </td>
              <td><span class="badge <?= $r['status']==='pass'?'badge-green':'badge-red' ?>"><?= ucfirst($r['status']) ?></span></td>
              <td class="text-sm text-muted"><?= date('M j, Y', strtotime($r['submitted_at'])) ?></td>
              <td><a href="result.php?session_id=<?= $r['session_id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
</div>

<script>
function updateClock() {
  document.getElementById('clock').textContent = new Date().toLocaleTimeString('en-NG', {hour12:false});
}
updateClock(); setInterval(updateClock, 1000);
</script>
</body>
</html>
