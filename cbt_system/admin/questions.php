<?php
require_once '../includes/config.php';
requireAdminLogin();
$db = getDB();
$currentPage = 'questions';
$pageTitle   = 'Question Bank';

$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$exams  = $db->query("SELECT e.id, e.exam_title, c.course_code FROM exams e JOIN courses c ON e.course_id=c.id ORDER BY e.created_at DESC")->fetchAll();
$currentExam = $examId ? $db->prepare("SELECT e.*,c.course_code FROM exams e JOIN courses c ON e.course_id=c.id WHERE e.id=?") : null;
if ($examId && $currentExam) { $currentExam->execute([$examId]); $currentExam = $currentExam->fetch(); }

$error = $success = '';

// Add question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $eId     = (int)$_POST['exam_id'];
    $qText   = trim($_POST['question_text']);
    $optA    = trim($_POST['option_a']);
    $optB    = trim($_POST['option_b']);
    $optC    = trim($_POST['option_c']);
    $optD    = trim($_POST['option_d']);
    $correct = sanitize($_POST['correct_answer']);
    $marks   = (int)$_POST['marks'];
    $expl    = trim($_POST['explanation']);

    if (!$eId || !$qText || !$optA || !$optB || !$optC || !$optD || !$correct) {
        $error = 'All fields except explanation are required.';
    } else {
        $db->prepare("INSERT INTO questions (exam_id,question_text,option_a,option_b,option_c,option_d,correct_answer,marks,explanation) VALUES (?,?,?,?,?,?,?,?,?)")
           ->execute([$eId,$qText,$optA,$optB,$optC,$optD,$correct,$marks,$expl]);
        $success = 'Question added successfully.';
        $examId  = $eId;
    }
}

// Delete question
if (isset($_GET['delete_q'])) {
    $db->prepare("DELETE FROM questions WHERE id=?")->execute([(int)$_GET['delete_q']]);
    flashMsg('success', 'Question deleted.');
    redirect(BASE_URL . "admin/questions.php?exam_id=$examId");
}

$questions = $examId ? $db->prepare("SELECT * FROM questions WHERE exam_id=? ORDER BY id") : null;
if ($examId && $questions) { $questions->execute([$examId]); $questions = $questions->fetchAll(); }
else $questions = [];

$flashSuccess = flashMsg('success');
include '../includes/admin_header.php';
?>

<?php if ($flashSuccess): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $flashSuccess ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:360px 1fr;gap:24px;align-items:start">

<!-- ADD QUESTION FORM -->
<div class="card" style="position:sticky;top:80px">
  <div class="card-header"><h3>Add Question</h3></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="add_question" value="1">

      <div class="form-group">
        <label>Select Exam *</label>
        <select name="exam_id" class="form-control" required onchange="this.form.submit()">
          <option value="">— Choose Exam —</option>
          <?php foreach ($exams as $ex): ?>
          <option value="<?= $ex['id'] ?>" <?= $examId==$ex['id']?'selected':'' ?>>
            [<?= sanitize($ex['course_code']) ?>] <?= sanitize(substr($ex['exam_title'],0,40)) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if ($examId): ?>
      <div class="form-group">
        <label>Question Text *</label>
        <textarea name="question_text" class="form-control" rows="3" placeholder="Enter the question..." required></textarea>
      </div>

      <?php foreach (['a'=>'A','b'=>'B','c'=>'C','d'=>'D'] as $k=>$v): ?>
      <div class="form-group">
        <label>Option <?= $v ?> *</label>
        <input type="text" name="option_<?= $k ?>" class="form-control" placeholder="Enter option <?= $v ?>" required>
      </div>
      <?php endforeach; ?>

      <div class="form-group">
        <label>Correct Answer *</label>
        <select name="correct_answer" class="form-control" required>
          <option value="">— Select —</option>
          <option value="A">A</option>
          <option value="B">B</option>
          <option value="C">C</option>
          <option value="D">D</option>
        </select>
      </div>

      <div class="form-group">
        <label>Marks</label>
        <input type="number" name="marks" class="form-control" value="1" min="1" max="10">
      </div>

      <div class="form-group">
        <label>Explanation (optional)</label>
        <textarea name="explanation" class="form-control" rows="2" placeholder="Why is this the correct answer?"></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-full">
        <i class="fas fa-plus"></i> Add Question
      </button>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- QUESTION LIST -->
<div>
  <div class="card">
    <div class="card-header">
      <h3>
        Questions
        <?php if ($currentExam): ?>
        — <span style="font-size:14px;color:var(--slate-500)"><?= sanitize($currentExam['course_code']) ?></span>
        <?php endif; ?>
      </h3>
      <span class="badge badge-green"><?= count($questions) ?> questions</span>
    </div>

    <?php if (empty($questions)): ?>
    <div class="empty-state">
      <div class="icon">❓</div>
      <p><?= $examId ? 'No questions yet. Add your first question using the form.' : 'Select an exam to view its questions.' ?></p>
    </div>
    <?php else: ?>
    <div style="padding:16px;display:flex;flex-direction:column;gap:12px">
      <?php foreach ($questions as $qi => $q): ?>
      <div style="border:1px solid var(--slate-200);border-radius:var(--radius);padding:18px;background:var(--white)">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
          <div style="flex:1">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
              <span style="background:var(--green-700);color:white;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0"><?= $qi+1 ?></span>
              <p style="font-size:15px;color:var(--slate-800);line-height:1.5"><?= sanitize($q['question_text']) ?></p>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-left:34px">
              <?php foreach (['A'=>$q['option_a'],'B'=>$q['option_b'],'C'=>$q['option_c'],'D'=>$q['option_d']] as $letter=>$text): ?>
              <div style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:6px;font-size:13px;
                <?= $letter===$q['correct_answer'] ? 'background:var(--green-100);border:1px solid var(--green-300)' : 'background:var(--slate-50);border:1px solid var(--slate-200)' ?>">
                <span style="font-weight:700;color:<?= $letter===$q['correct_answer'] ? 'var(--green-700)' : 'var(--slate-500)' ?>"><?= $letter ?></span>
                <?= sanitize($text) ?>
                <?php if ($letter===$q['correct_answer']): ?>
                <i class="fas fa-check-circle" style="color:var(--green-600);margin-left:auto"></i>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php if ($q['explanation']): ?>
            <div style="margin-top:10px;margin-left:34px;font-size:12px;color:var(--slate-500);font-style:italic">
              💡 <?= sanitize($q['explanation']) ?>
            </div>
            <?php endif; ?>
          </div>
          <div style="display:flex;gap:6px;flex-shrink:0">
            <span class="badge badge-gold"><?= $q['marks'] ?> mk</span>
            <a href="?exam_id=<?= $examId ?>&delete_q=<?= $q['id'] ?>"
               class="btn btn-danger btn-sm"
               onclick="return confirm('Delete this question?')">
              <i class="fas fa-trash"></i>
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

</div>

<?php include '../includes/admin_footer.php'; ?>
