<?php
require_once '../includes/config.php';
requireAdminLogin();
$db = getDB();
$currentPage = 'results';
$pageTitle   = 'Results & Reports';

$examFilter = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$exams = $db->query("SELECT e.id, e.exam_title, c.course_code FROM exams e JOIN courses c ON e.course_id=c.id ORDER BY e.created_at DESC")->fetchAll();

$where = $examFilter ? "WHERE r.exam_id = $examFilter" : '';
$results = $db->query("
    SELECT r.*, s.full_name, s.reg_number, s.level,
           e.exam_title, e.duration_minutes, c.course_code
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN exams e ON r.exam_id = e.id
    JOIN courses c ON e.course_id = c.id
    $where
    ORDER BY r.submitted_at DESC
")->fetchAll();

// Stats for selected exam
$stats = [];
if ($results) {
    $percentages = array_column($results, 'percentage');
    $stats = [
        'total'   => count($results),
        'avg'     => round(array_sum($percentages) / count($percentages), 1),
        'highest' => max($percentages),
        'lowest'  => min($percentages),
        'pass'    => count(array_filter($results, fn($r) => $r['status']==='pass')),
    ];
}

include '../includes/admin_header.php';
?>

<!-- Filter -->
<div class="card mb-6">
  <div class="card-body" style="padding:16px">
    <form method="GET" style="display:flex;gap:12px;align-items:center">
      <div style="flex:1">
        <select name="exam_id" class="form-control" onchange="this.form.submit()">
          <option value="">— All Exams —</option>
          <?php foreach ($exams as $ex): ?>
          <option value="<?= $ex['id'] ?>" <?= $examFilter==$ex['id']?'selected':'' ?>>
            [<?= sanitize($ex['course_code']) ?>] <?= sanitize(substr($ex['exam_title'],0,60)) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($examFilter): ?>
      <a href="results.php" class="btn btn-outline btn-sm">Clear Filter</a>
      <?php endif; ?>
      <button onclick="window.print()" class="btn btn-outline btn-sm" type="button">
        <i class="fas fa-print"></i> Print
      </button>
    </form>
  </div>
</div>

<?php if ($stats): ?>
<!-- Summary Stats -->
<div class="stats-grid" style="margin-bottom:24px">
  <div class="stat-card green">
    <div class="stat-icon green">📝</div>
    <h3><?= $stats['total'] ?></h3>
    <p>Total Attempts</p>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold">📊</div>
    <h3><?= $stats['avg'] ?>%</h3>
    <p>Average Score</p>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon blue">🏆</div>
    <h3><?= $stats['highest'] ?>%</h3>
    <p>Highest Score</p>
  </div>
  <div class="stat-card red">
    <div class="stat-icon red">✅</div>
    <h3><?= round(($stats['pass']/$stats['total'])*100, 1) ?>%</h3>
    <p>Pass Rate (<?= $stats['pass'] ?>/<?= $stats['total'] ?>)</p>
  </div>
</div>
<?php endif; ?>

<!-- Results Table -->
<div class="card">
  <div class="card-header">
    <h3>Examination Results</h3>
    <span class="badge badge-green"><?= count($results) ?> records</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Student</th>
          <th>Reg. Number</th>
          <th>Level</th>
          <th>Course</th>
          <th>Score</th>
          <th>%</th>
          <th>Grade</th>
          <th>Status</th>
          <th>Correct</th>
          <th>Wrong</th>
          <th>Date</th>
          <th>Detail</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($results as $i => $r): ?>
        <tr>
          <td class="text-muted"><?= $i+1 ?></td>
          <td style="font-weight:600"><?= sanitize($r['full_name']) ?></td>
          <td class="font-mono text-sm"><?= sanitize($r['reg_number']) ?></td>
          <td><span class="badge badge-slate"><?= $r['level'] ?></span></td>
          <td><span class="badge badge-green"><?= sanitize($r['course_code']) ?></span></td>
          <td>
            <strong><?= $r['raw_score'] ?></strong>
            <span class="text-muted text-sm">/ <?= $r['total_questions'] ?></span>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div style="width:50px;height:6px;background:var(--slate-200);border-radius:3px;overflow:hidden">
                <div style="width:<?= $r['percentage'] ?>%;height:100%;background:<?= $r['percentage']>=50?'var(--green-500)':'var(--red-500)' ?>;border-radius:3px"></div>
              </div>
              <span style="font-weight:600"><?= $r['percentage'] ?>%</span>
            </div>
          </td>
          <td>
            <span style="font-size:22px;font-weight:700;font-family:var(--font-display);
              color:<?= in_array($r['grade'],['A','B','C']) ? 'var(--green-600)' : ($r['grade']==='F' ? 'var(--red-600)' : 'var(--gold-500)') ?>">
              <?= $r['grade'] ?>
            </span>
          </td>
          <td>
            <span class="badge <?= $r['status']==='pass'?'badge-green':'badge-red' ?>">
              <?= ucfirst($r['status']) ?>
            </span>
          </td>
          <td style="color:var(--green-600);font-weight:600"><?= $r['correct'] ?></td>
          <td style="color:var(--red-600);font-weight:600"><?= $r['wrong'] ?></td>
          <td class="text-sm text-muted"><?= date('M j, Y', strtotime($r['submitted_at'])) ?></td>
          <td>
            <a href="result_detail.php?session_id=<?= $r['session_id'] ?>" class="btn btn-outline btn-sm">
              <i class="fas fa-eye"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($results)): ?>
        <tr><td colspan="13">
          <div class="empty-state"><div class="icon">📊</div><p>No results found.</p></div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
