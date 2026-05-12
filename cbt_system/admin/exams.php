<?php
require_once '../includes/config.php';
$currentPage = 'exams';
$pageTitle = 'Manage Exams';
$db = getDB();

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    requireAdminLogin();
    $id  = (int)$_POST['exam_id'];
    $new = sanitize($_POST['new_status']);
    if (in_array($new, ['draft','active','closed'])) {
        $db->prepare("UPDATE exams SET status=? WHERE id=?")->execute([$new, $id]);
        flashMsg('success', "Exam status updated to <strong>$new</strong>.");
    }
    redirect(BASE_URL . 'admin/exams.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    requireAdminLogin();
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM exams WHERE id=?")->execute([$id]);
    flashMsg('success', 'Exam deleted successfully.');
    redirect(BASE_URL . 'admin/exams.php');
}

$exams = $db->query("
    SELECT e.*, c.course_code, c.course_title, a.full_name AS creator,
           COUNT(DISTINCT q.id) AS question_count,
           COUNT(DISTINCT r.id) AS attempt_count
    FROM exams e
    JOIN courses c ON e.course_id = c.id
    JOIN admins a ON e.created_by = a.id
    LEFT JOIN questions q ON e.id = q.exam_id
    LEFT JOIN results r ON e.id = r.exam_id
    GROUP BY e.id
    ORDER BY e.created_at DESC
")->fetchAll();

include '../includes/admin_header.php';
$success = flashMsg('success');
?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $success ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3>All Examinations</h3>
    <a href="exam_create.php" class="btn btn-primary btn-sm">
      <i class="fas fa-plus"></i> Create Exam
    </a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Exam Title</th>
          <th>Course</th>
          <th>Duration</th>
          <th>Questions</th>
          <th>Attempts</th>
          <th>Status</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($exams as $i => $e): ?>
        <tr>
          <td class="text-muted"><?= $i+1 ?></td>
          <td>
            <div style="font-weight:600;color:var(--slate-800)"><?= sanitize($e['exam_title']) ?></div>
            <div class="text-sm text-muted">by <?= sanitize($e['creator']) ?></div>
          </td>
          <td>
            <span class="badge badge-green"><?= sanitize($e['course_code']) ?></span>
          </td>
          <td><?= $e['duration_minutes'] ?> min</td>
          <td>
            <strong><?= $e['question_count'] ?></strong>
            <span class="text-muted text-sm">/ <?= $e['total_questions'] ?> req.</span>
          </td>
          <td><?= $e['attempt_count'] ?></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="exam_id" value="<?= $e['id'] ?>">
              <input type="hidden" name="toggle_status" value="1">
              <?php
              $nextStatus = match($e['status']) {
                'draft' => 'active', 'active' => 'closed', default => 'draft'
              };
              $badge = match($e['status']) {
                'active' => 'badge-green', 'closed' => 'badge-slate', default => 'badge-gold'
              };
              ?>
              <input type="hidden" name="new_status" value="<?= $nextStatus ?>">
              <button type="submit" class="badge <?= $badge ?>" style="border:none;cursor:pointer;font-family:var(--font-body)">
                <?= ucfirst($e['status']) ?> ↻
              </button>
            </form>
          </td>
          <td class="text-sm text-muted"><?= date('M j, Y', strtotime($e['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="exam_detail.php?id=<?= $e['id'] ?>" class="btn btn-outline btn-sm" title="View">
                <i class="fas fa-eye"></i>
              </a>
              <a href="questions.php?exam_id=<?= $e['id'] ?>" class="btn btn-gold btn-sm" title="Questions">
                <i class="fas fa-question-circle"></i>
              </a>
              <a href="results.php?exam_id=<?= $e['id'] ?>" class="btn btn-outline btn-sm" title="Results">
                <i class="fas fa-chart-bar"></i>
              </a>
              <a href="exam_create.php?edit=<?= $e['id'] ?>" class="btn btn-outline btn-sm" title="Edit">
                <i class="fas fa-edit"></i>
              </a>
              <a href="?delete=<?= $e['id'] ?>" class="btn btn-danger btn-sm"
                 onclick="return confirm('Delete this exam and all its data?')" title="Delete">
                <i class="fas fa-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($exams)): ?>
        <tr>
          <td colspan="9">
            <div class="empty-state">
              <div class="icon">📋</div>
              <p>No exams created yet. <a href="exam_create.php" style="color:var(--green-600)">Create your first exam</a></p>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
