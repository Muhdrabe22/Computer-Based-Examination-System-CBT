<?php
require_once '../includes/config.php';
requireAdminLogin();
$db = getDB();
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$exam   = $editId ? $db->prepare("SELECT * FROM exams WHERE id=?") : null;
if ($editId) { $exam->execute([$editId]); $exam = $exam->fetch(); }

$currentPage  = 'exams';
$pageTitle    = $editId ? 'Edit Exam' : 'Create New Exam';

$courses = $db->query("SELECT c.*, d.name AS dept_name FROM courses c JOIN departments d ON c.department_id=d.id ORDER BY c.course_code")->fetchAll();
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId     = (int)$_POST['course_id'];
    $examTitle    = sanitize($_POST['exam_title']);
    $instructions = sanitize($_POST['instructions']);
    $duration     = (int)$_POST['duration_minutes'];
    $totalQ       = (int)$_POST['total_questions'];
    $passScore    = (int)$_POST['pass_score'];
    $status       = sanitize($_POST['status']);
    $shuffleQ     = isset($_POST['shuffle_questions']) ? 1 : 0;
    $shuffleO     = isset($_POST['shuffle_options']) ? 1 : 0;

    if (!$courseId || !$examTitle || !$duration) {
        $error = 'Please fill in all required fields.';
    } else {
        if ($editId) {
            $db->prepare("UPDATE exams SET course_id=?,exam_title=?,instructions=?,duration_minutes=?,total_questions=?,pass_score=?,status=?,shuffle_questions=?,shuffle_options=? WHERE id=?")
               ->execute([$courseId,$examTitle,$instructions,$duration,$totalQ,$passScore,$status,$shuffleQ,$shuffleO,$editId]);
            flashMsg('success', 'Exam updated successfully.');
        } else {
            $db->prepare("INSERT INTO exams (course_id,exam_title,instructions,duration_minutes,total_questions,pass_score,status,shuffle_questions,shuffle_options,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)")
               ->execute([$courseId,$examTitle,$instructions,$duration,$totalQ,$passScore,$status,$shuffleQ,$shuffleO,$_SESSION['admin_id']]);
            $newId = $db->lastInsertId();
            flashMsg('success', 'Exam created! Now add questions.');
            redirect(BASE_URL . "admin/questions.php?exam_id=$newId");
        }
        redirect(BASE_URL . 'admin/exams.php');
    }
}

include '../includes/admin_header.php';
?>

<div style="max-width:760px">
  <?php if ($error): ?>
  <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">
      <h3><?= $editId ? 'Edit Examination' : 'Create New Examination' ?></h3>
      <a href="exams.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
      <form method="POST">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
          <div class="form-group" style="grid-column:1/-1">
            <label>Exam Title *</label>
            <input type="text" name="exam_title" class="form-control"
              placeholder="e.g. ICT101 - First Semester CBT Examination 2024/2025"
              value="<?= sanitize($exam['exam_title'] ?? $_POST['exam_title'] ?? '') ?>" required>
          </div>

          <div class="form-group">
            <label>Course *</label>
            <select name="course_id" class="form-control" required>
              <option value="">— Select Course —</option>
              <?php foreach ($courses as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($exam['course_id']??0)==$c['id']?'selected':'' ?>>
                <?= sanitize($c['course_code']) ?> — <?= sanitize($c['course_title']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
              <?php foreach (['draft','active','closed'] as $s): ?>
              <option value="<?= $s ?>" <?= ($exam['status']??'draft')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Duration (minutes) *</label>
            <input type="number" name="duration_minutes" class="form-control" min="5" max="300"
              value="<?= $exam['duration_minutes'] ?? 60 ?>" required>
          </div>

          <div class="form-group">
            <label>Total Questions</label>
            <input type="number" name="total_questions" class="form-control" min="1" max="200"
              value="<?= $exam['total_questions'] ?? 40 ?>">
          </div>

          <div class="form-group">
            <label>Pass Score (%)</label>
            <input type="number" name="pass_score" class="form-control" min="1" max="100"
              value="<?= $exam['pass_score'] ?? 40 ?>">
          </div>

          <div class="form-group" style="grid-column:1/-1">
            <label>Instructions (shown to students before exam)</label>
            <textarea name="instructions" class="form-control" rows="5"
              placeholder="Enter exam instructions..."><?= sanitize($exam['instructions'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
              <input type="checkbox" name="shuffle_questions" <?= ($exam['shuffle_questions']??1)?'checked':'' ?>>
              Shuffle Question Order
            </label>
          </div>

          <div class="form-group">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
              <input type="checkbox" name="shuffle_options" <?= ($exam['shuffle_options']??1)?'checked':'' ?>>
              Shuffle Answer Options
            </label>
          </div>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px">
          <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> <?= $editId ? 'Update Exam' : 'Create Exam & Add Questions' ?>
          </button>
          <a href="exams.php" class="btn btn-outline btn-lg">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
