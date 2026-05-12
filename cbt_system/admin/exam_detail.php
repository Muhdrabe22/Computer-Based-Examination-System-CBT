<?php
require_once '../includes/config.php';
requireAdminLogin();
$db = getDB();
$currentPage = 'exams';

$examId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$examId) redirect(BASE_URL . 'admin/exams.php');

$exam = $db->prepare("SELECT e.*,c.course_code,c.course_title,a.full_name AS creator FROM exams e JOIN courses c ON e.course_id=c.id JOIN admins a ON e.created_by=a.id WHERE e.id=?");
$exam->execute([$examId]);
$exam = $exam->fetch();
if (!$exam) redirect(BASE_URL . 'admin/exams.php');

$pageTitle    = 'Exam Detail';
$pageSubtitle = sanitize($exam['course_code']) . ' — ' . sanitize($exam['exam_title']);

$questionCount = $db->prepare("SELECT COUNT(*) FROM questions WHERE exam_id=?");
$questionCount->execute([$examId]);
$questionCount = $questionCount->fetchColumn();

$results = $db->query("
    SELECT r.*, s.full_name, s.reg_number, s.level
    FROM results r
    JOIN students s ON r.student_id=s.id
    WHERE r.exam_id=$examId
    ORDER BY r.percentage DESC
")->fetchAll();

$totalAttempts = count($results);
$passed        = count(array_filter($results, fn($r) => $r['status']==='pass'));
$avgScore      = $totalAttempts ? round(array_sum(array_column($results,'percentage'))/$totalAttempts,1) : 0;
$highest       = $totalAttempts ? max(array_column($results,'percentage')) : 0;
$lowest        = $totalAttempts ? min(array_column($results,'percentage')) : 0;

// Grade distribution
$gradeDist = array_fill_keys(['A','B','C','D','E','F'], 0);
foreach ($results as $r) { if (isset($gradeDist[$r['grade']])) $gradeDist[$r['grade']]++; }

include '../includes/admin_header.php';
?>

<div style="display:flex;gap:10px;margin-bottom:20px">
  <a href="exams.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> All Exams</a>
  <a href="questions.php?exam_id=<?= $examId ?>" class="btn btn-gold btn-sm"><i class="fas fa-question-circle"></i> Manage Questions</a>
  <a href="exam_create.php?edit=<?= $examId ?>" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i> Edit Exam</a>
  <button onclick="window.print()" class="btn btn-outline btn-sm"><i class="fas fa-print"></i> Print</button>
</div>

<!-- Exam Info -->
<div class="card mb-6">
  <div style="background:linear-gradient(135deg,var(--green-800),var(--green-900));padding:28px;border-radius:var(--radius-lg) var(--radius-lg) 0 0">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;flex-wrap:wrap">
      <div>
        <span class="badge badge-green" style="margin-bottom:10px"><?= sanitize($exam['course_code']) ?></span>
        <h2 style="font-family:var(--font-display);color:white;font-size:20px;margin-bottom:6px"><?= sanitize($exam['exam_title']) ?></h2>
        <p style="color:var(--green-200);font-size:13px"><?= sanitize($exam['course_title']) ?></p>
      </div>
      <?php $badgeClass = match($exam['status']) { 'active'=>'badge-green','closed'=>'badge-slate',default=>'badge-gold' }; ?>
      <span class="badge <?= $badgeClass ?>" style="font-size:13px;padding:6px 14px"><?= ucfirst($exam['status']) ?></span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-top:22px">
      <?php $info = [
        ['⏱️','Duration',$exam['duration_minutes'].' min'],
        ['❓','Questions',"$questionCount / {$exam['total_questions']} req."],
        ['🎯','Pass Mark',$exam['pass_score'].'%'],
        ['📝','Attempts',$totalAttempts],
        ['👤','Created by',$exam['creator']],
      ]; ?>
      <?php foreach ($info as [$icon,$label,$val]): ?>
      <div style="background:rgba(255,255,255,.08);padding:12px 14px;border-radius:8px">
        <div style="font-size:11px;color:var(--green-300);text-transform:uppercase;letter-spacing:.06em"><?= $icon ?> <?= $label ?></div>
        <div style="font-size:14px;font-weight:600;color:white;margin-top:4px"><?= sanitize($val) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($exam['instructions']): ?>
  <div style="padding:16px 24px;background:var(--slate-50);border-top:1px solid var(--slate-200)">
    <p style="font-size:12px;font-weight:700;color:var(--slate-400);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">Instructions</p>
    <p style="font-size:13px;color:var(--slate-600);white-space:pre-line"><?= sanitize($exam['instructions']) ?></p>
  </div>
  <?php endif; ?>
</div>

<?php if ($totalAttempts): ?>
<!-- Stats -->
<div class="stats-grid mb-6">
  <div class="stat-card green">
    <div class="stat-icon green">📝</div>
    <h3><?= $totalAttempts ?></h3>
    <p>Total Attempts</p>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold">📊</div>
    <h3><?= $avgScore ?>%</h3>
    <p>Average Score</p>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon blue">🏆</div>
    <h3><?= $highest ?>%</h3>
    <p>Highest Score</p>
  </div>
  <div class="stat-card <?= ($passed/$totalAttempts)>=0.5?'green':'red' ?>">
    <div class="stat-icon <?= ($passed/$totalAttempts)>=0.5?'green':'red' ?>">✅</div>
    <h3><?= round(($passed/$totalAttempts)*100) ?>%</h3>
    <p>Pass Rate (<?= $passed ?>/<?= $totalAttempts ?>)</p>
  </div>
</div>

<!-- Grade Distribution -->
<div class="card mb-6">
  <div class="card-header"><h3>Grade Distribution</h3></div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px">
      <?php
      $gradeColors = ['A'=>'var(--green-500)','B'=>'var(--green-400)','C'=>'var(--gold-400)','D'=>'var(--gold-500)','E'=>'var(--slate-400)','F'=>'var(--red-500)'];
      foreach ($gradeDist as $grade => $count):
        $pct = $totalAttempts ? round(($count/$totalAttempts)*100) : 0;
      ?>
      <div style="text-align:center;padding:16px;border:1px solid var(--slate-200);border-radius:var(--radius);background:var(--white)">
        <div style="font-size:32px;font-weight:700;font-family:var(--font-display);color:<?= $gradeColors[$grade] ?>;margin-bottom:4px"><?= $grade ?></div>
        <div style="font-size:20px;font-weight:700;color:var(--slate-700)"><?= $count ?></div>
        <div style="font-size:12px;color:var(--slate-400)"><?= $pct ?>%</div>
        <div style="margin-top:8px;height:4px;background:var(--slate-200);border-radius:2px;overflow:hidden">
          <div style="width:<?= $pct ?>%;height:100%;background:<?= $gradeColors[$grade] ?>;border-radius:2px"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Results Table -->
<div class="card">
  <div class="card-header">
    <h3>Student Results</h3>
    <span class="badge badge-green"><?= $totalAttempts ?> attempts</span>
  </div>
  <?php if (empty($results)): ?>
  <div class="empty-state"><div class="icon">📊</div><p>No students have taken this exam yet.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Rank</th><th>Student</th><th>Reg. Number</th><th>Level</th><th>Score</th><th>%</th><th>Grade</th><th>Status</th><th>Correct</th><th>Date</th><th>Detail</th></tr>
      </thead>
      <tbody>
        <?php foreach ($results as $i => $r): ?>
        <tr>
          <td>
            <?php if ($i===0): ?><span style="font-size:20px">🥇</span>
            <?php elseif ($i===1): ?><span style="font-size:20px">🥈</span>
            <?php elseif ($i===2): ?><span style="font-size:20px">🥉</span>
            <?php else: ?><span class="text-muted">#<?= $i+1 ?></span>
            <?php endif; ?>
          </td>
          <td style="font-weight:600"><?= sanitize($r['full_name']) ?></td>
          <td class="font-mono text-sm"><?= sanitize($r['reg_number']) ?></td>
          <td><span class="badge badge-slate"><?= $r['level'] ?></span></td>
          <td><strong><?= $r['raw_score'] ?></strong><span class="text-muted text-sm">/<?= $r['total_questions'] ?></span></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div style="width:50px;height:6px;background:var(--slate-200);border-radius:3px;overflow:hidden">
                <div style="width:<?= $r['percentage'] ?>%;height:100%;background:<?= $r['percentage']>=$exam['pass_score']?'var(--green-500)':'var(--red-500)' ?>;border-radius:3px"></div>
              </div>
              <strong><?= $r['percentage'] ?>%</strong>
            </div>
          </td>
          <td><span style="font-size:22px;font-weight:700;font-family:var(--font-display);color:<?= $r['grade']==='F'?'var(--red-600)':'var(--green-600)' ?>"><?= $r['grade'] ?></span></td>
          <td><span class="badge <?= $r['status']==='pass'?'badge-green':'badge-red' ?>"><?= ucfirst($r['status']) ?></span></td>
          <td style="color:var(--green-600);font-weight:600"><?= $r['correct'] ?></td>
          <td class="text-sm text-muted"><?= date('M j, Y', strtotime($r['submitted_at'])) ?></td>
          <td><a href="result_detail.php?session_id=<?= $r['session_id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include '../includes/admin_footer.php'; ?>
