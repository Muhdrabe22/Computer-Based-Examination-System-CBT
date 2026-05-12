<?php
require_once '../includes/config.php';
requireStudentLogin();
$db = getDB();

$examId    = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$studentId = $_SESSION['student_id'];

if (!$examId) redirect(BASE_URL . 'student/dashboard.php');

$exam = $db->prepare("SELECT e.*,c.course_code,c.course_title FROM exams e JOIN courses c ON e.course_id=c.id WHERE e.id=? AND e.status='active'");
$exam->execute([$examId]);
$exam = $exam->fetch();
if (!$exam) { flashMsg('error','Exam not available.'); redirect(BASE_URL.'student/dashboard.php'); }

// Check if already taken
$taken = $db->prepare("SELECT id FROM results WHERE student_id=? AND exam_id=?");
$taken->execute([$studentId, $examId]);
if ($taken->fetch()) { flashMsg('error','You have already taken this exam.'); redirect(BASE_URL.'student/dashboard.php'); }

// Check active session
$session = $db->prepare("SELECT * FROM exam_sessions WHERE student_id=? AND exam_id=? AND status='in_progress'");
$session->execute([$studentId, $examId]);
$session = $session->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($session) {
        redirect(BASE_URL . "student/exam.php?exam_id=$examId");
    }
    // Create session
    $db->prepare("INSERT INTO exam_sessions (student_id,exam_id,started_at,ip_address) VALUES (?,?,NOW(),?)")
       ->execute([$studentId, $examId, $_SERVER['REMOTE_ADDR']??'']);
    $sid = $db->lastInsertId();

    // Load questions (shuffled)
    $qs = $db->prepare("SELECT id FROM questions WHERE exam_id=?");
    $qs->execute([$examId]);
    $qids = array_column($qs->fetchAll(), 'id');
    if ($exam['shuffle_questions']) shuffle($qids);

    // Pre-create answer rows (null)
    foreach ($qids as $qid) {
        $db->prepare("INSERT IGNORE INTO student_answers (session_id, question_id) VALUES (?,?)")->execute([$sid, $qid]);
    }
    // Store question order in session
    $_SESSION['exam_order_'.$examId] = $qids;

    logActivity('student', $studentId, 'Exam Start', "Started exam ID $examId");
    redirect(BASE_URL . "student/exam.php?exam_id=$examId");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Exam Instructions — HUKP CBT</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body style="background:var(--slate-100);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px">

<div style="max-width:680px;width:100%">
  <!-- Header -->
  <div style="text-align:center;margin-bottom:28px">
    <div style="width:80px;height:80px;background:linear-gradient(135deg,var(--gold-300),var(--gold-500));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto 16px;box-shadow:0 8px 24px rgba(212,160,42,.3)">📝</div>
    <h1 style="font-family:var(--font-display);color:var(--green-800);font-size:24px;margin-bottom:4px"><?= sanitize($exam['exam_title']) ?></h1>
    <span class="badge badge-green"><?= sanitize($exam['course_code']) ?> — <?= sanitize($exam['course_title']) ?></span>
  </div>

  <!-- Exam Info -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px">
    <?php $info = [['⏱️','Duration',$exam['duration_minutes'].' minutes'],['❓','Questions',$exam['total_questions'].' questions'],['🎯','Pass Mark',$exam['pass_score'].'%']]; ?>
    <?php foreach ($info as [$icon,$label,$val]): ?>
    <div style="background:white;border:1px solid var(--slate-200);border-radius:var(--radius);padding:18px;text-align:center">
      <div style="font-size:28px;margin-bottom:8px"><?= $icon ?></div>
      <div style="font-size:20px;font-weight:700;color:var(--slate-800);font-family:var(--font-display)"><?= $val ?></div>
      <div style="font-size:12px;color:var(--slate-500);margin-top:2px"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Instructions -->
  <div class="card mb-6">
    <div class="card-header"><h3>📋 Exam Instructions</h3></div>
    <div class="card-body">
      <?php if ($exam['instructions']): ?>
      <div style="white-space:pre-line;font-size:14px;color:var(--slate-700);line-height:1.8">
        <?= sanitize($exam['instructions']) ?>
      </div>
      <?php else: ?>
      <ol style="font-size:14px;color:var(--slate-700);line-height:2;padding-left:20px">
        <li>Read each question carefully before selecting your answer.</li>
        <li>Each question carries equal marks. There is no negative marking.</li>
        <li>Do <strong>not</strong> refresh or close the browser during the exam.</li>
        <li>Your answers are saved automatically as you select them.</li>
        <li>Submit your exam before the countdown timer reaches zero.</li>
        <li>Once submitted, you cannot re-enter the examination.</li>
      </ol>
      <?php endif; ?>

      <div style="margin-top:20px;padding:14px;background:var(--gold-100);border:1px solid var(--gold-200);border-radius:8px">
        <p style="font-size:13px;color:var(--gold-600)">
          <i class="fas fa-exclamation-triangle"></i>
          <strong>Warning:</strong> Academic dishonesty is a serious offence. Your IP address and activity are being logged.
        </p>
      </div>
    </div>
  </div>

  <!-- Start Button -->
  <form method="POST">
    <div style="display:flex;flex-direction:column;align-items:center;gap:14px">
      <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:14px;color:var(--slate-700)">
        <input type="checkbox" id="agree" required style="margin-top:3px;flex-shrink:0">
        I have read and understood the exam instructions. I agree to abide by the examination rules.
      </label>
      <button type="submit" class="btn btn-primary btn-lg" style="min-width:260px" id="startBtn">
        <i class="fas fa-play-circle"></i>
        <?= $session ? 'Continue Exam' : 'Start Exam Now' ?>
      </button>
      <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
    </div>
  </form>
</div>

<script>
document.getElementById('agree').addEventListener('change', function() {
  document.getElementById('startBtn').disabled = !this.checked;
});
document.getElementById('startBtn').disabled = true;
</script>
</body>
</html>
