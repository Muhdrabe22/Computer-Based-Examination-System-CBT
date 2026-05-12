<?php
require_once '../includes/config.php';
$currentPage = 'dashboard';
$pageTitle = 'Dashboard';
$pageSubtitle = 'HUKP ICT Department — Computer Based Examination System';

$db = getDB();

// Stats
$totalStudents = $db->query("SELECT COUNT(*) FROM students WHERE is_active=1")->fetchColumn();
$totalExams    = $db->query("SELECT COUNT(*) FROM exams")->fetchColumn();
$activeExams   = $db->query("SELECT COUNT(*) FROM exams WHERE status='active'")->fetchColumn();
$totalResults  = $db->query("SELECT COUNT(*) FROM results")->fetchColumn();
$avgScore      = $db->query("SELECT AVG(percentage) FROM results")->fetchColumn() ?: 0;
$passRate      = $db->query("SELECT (COUNT(CASE WHEN status='pass' THEN 1 END)/COUNT(*))*100 FROM results")->fetchColumn() ?: 0;

// Recent exams
$recentExams = $db->query("
    SELECT e.*, c.course_code, c.course_title,
           COUNT(DISTINCT r.id) AS attempts
    FROM exams e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN results r ON e.id = r.exam_id
    GROUP BY e.id
    ORDER BY e.created_at DESC LIMIT 6
")->fetchAll();

// Recent results
$recentResults = $db->query("
    SELECT r.*, s.full_name, s.reg_number, c.course_code
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN exams e ON r.exam_id = e.id
    JOIN courses c ON e.course_id = c.id
    ORDER BY r.submitted_at DESC LIMIT 8
")->fetchAll();

include '../includes/admin_header.php';
?>

<!-- Stats Row -->
<div class="stats-grid">
  <div class="stat-card green">
    <div class="stat-icon green">👨‍🎓</div>
    <h3><?= number_format($totalStudents) ?></h3>
    <p>Registered Students</p>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold">📋</div>
    <h3><?= number_format($totalExams) ?></h3>
    <p>Total Exams</p>
    <div class="stat-change up"><i class="fas fa-circle" style="font-size:8px;color:var(--green-500)"></i><?= $activeExams ?> active now</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon blue">📝</div>
    <h3><?= number_format($totalResults) ?></h3>
    <p>Exam Attempts</p>
  </div>
  <div class="stat-card red">
    <div class="stat-icon red">📈</div>
    <h3><?= round($avgScore, 1) ?>%</h3>
    <p>Average Score</p>
    <div class="stat-change <?= $passRate >= 50 ? 'up' : 'down' ?>">
      <i class="fas fa-arrow-<?= $passRate >= 50 ? 'up' : 'down' ?>"></i> <?= round($passRate, 1) ?>% pass rate
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

<!-- Recent Exams -->
<div class="card">
  <div class="card-header">
    <h3>Recent Exams</h3>
    <a href="exams.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Course</th>
          <th>Duration</th>
          <th>Status</th>
          <th>Attempts</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentExams as $exam): ?>
        <tr>
          <td>
            <div style="font-weight:600;color:var(--slate-800)"><?= sanitize($exam['course_code']) ?></div>
            <div class="text-sm text-muted"><?= sanitize(substr($exam['exam_title'],0,35)) ?>...</div>
          </td>
          <td><?= $exam['duration_minutes'] ?> min</td>
          <td>
            <?php
            $badge = match($exam['status']) {
              'active' => 'badge-green', 'closed' => 'badge-slate', default => 'badge-gold'
            };
            ?>
            <span class="badge <?= $badge ?>"><?= ucfirst($exam['status']) ?></span>
          </td>
          <td><strong><?= $exam['attempts'] ?></strong></td>
          <td>
            <a href="exam_detail.php?id=<?= $exam['id'] ?>" class="btn btn-outline btn-sm">View</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($recentExams)): ?>
        <tr><td colspan="5" class="text-center text-muted" style="padding:32px">No exams created yet</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Recent Results -->
<div class="card">
  <div class="card-header">
    <h3>Recent Results</h3>
    <a href="results.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Course</th>
          <th>Score</th>
          <th>Grade</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentResults as $res): ?>
        <tr>
          <td>
            <div style="font-weight:600"><?= sanitize(explode(' ', $res['full_name'])[0]) ?></div>
            <div class="text-sm text-muted font-mono"><?= sanitize($res['reg_number']) ?></div>
          </td>
          <td><?= sanitize($res['course_code']) ?></td>
          <td>
            <strong style="color:var(--green-600)"><?= $res['percentage'] ?>%</strong>
          </td>
          <td>
            <span style="font-weight:700;font-size:18px;font-family:var(--font-display);color:<?= $res['grade']=='F'?'var(--red-600)':'var(--green-600)' ?>"><?= $res['grade'] ?></span>
          </td>
          <td>
            <span class="badge <?= $res['status']==='pass' ? 'badge-green' : 'badge-red' ?>">
              <?= ucfirst($res['status']) ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($recentResults)): ?>
        <tr><td colspan="5" class="text-center text-muted" style="padding:32px">No results yet</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<!-- Quick Actions -->
<div style="margin-top:24px" class="card">
  <div class="card-header"><h3>Quick Actions</h3></div>
  <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap">
    <a href="exam_create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create New Exam</a>
    <a href="questions.php" class="btn btn-gold"><i class="fas fa-question-circle"></i> Add Questions</a>
    <a href="students.php?action=add" class="btn btn-outline"><i class="fas fa-user-plus"></i> Register Student</a>
    <a href="results.php" class="btn btn-outline"><i class="fas fa-file-alt"></i> Generate Report</a>
  </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
