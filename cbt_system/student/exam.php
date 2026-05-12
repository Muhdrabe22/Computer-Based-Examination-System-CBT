<?php
require_once '../includes/config.php';
requireStudentLogin();
$db = getDB();

$examId    = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$studentId = $_SESSION['student_id'];
if (!$examId) redirect(BASE_URL . 'student/dashboard.php');

// Load exam
$exam = $db->prepare("SELECT e.*,c.course_code,c.course_title FROM exams e JOIN courses c ON e.course_id=c.id WHERE e.id=? AND e.status='active'");
$exam->execute([$examId]);
$exam = $exam->fetch();
if (!$exam) redirect(BASE_URL . 'student/dashboard.php');

// Load session
$session = $db->prepare("SELECT * FROM exam_sessions WHERE student_id=? AND exam_id=? AND status='in_progress'");
$session->execute([$studentId, $examId]);
$session = $session->fetch();
if (!$session) redirect(BASE_URL . "student/exam_start.php?exam_id=$examId");

$sessionId = $session['id'];

// ── Handle AJAX answer save ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = sanitize($_POST['action']);

    if ($action === 'save_answer') {
        $qid    = (int)$_POST['question_id'];
        $answer = sanitize($_POST['answer']);
        if (!in_array($answer, ['A','B','C','D',''])) { echo json_encode(['ok'=>false]); exit; }

        // Get correct answer
        $q = $db->prepare("SELECT correct_answer, marks FROM questions WHERE id=? AND exam_id=?");
        $q->execute([$qid, $examId]);
        $q = $q->fetch();

        $isCorrect    = (!empty($answer) && $answer === $q['correct_answer']) ? 1 : 0;
        $marksObtained = $isCorrect ? $q['marks'] : 0;

        $db->prepare("INSERT INTO student_answers (session_id,question_id,selected_answer,is_correct,marks_obtained)
            VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE selected_answer=?,is_correct=?,marks_obtained=?")
           ->execute([$sessionId, $qid, $answer ?: null, $isCorrect, $marksObtained,
                      $answer ?: null, $isCorrect, $marksObtained]);

        echo json_encode(['ok'=>true,'correct'=>$isCorrect]);
        exit;
    }

    if ($action === 'save_time') {
        $remaining = (int)$_POST['remaining'];
        $db->prepare("UPDATE exam_sessions SET time_remaining=? WHERE id=?")->execute([$remaining, $sessionId]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'submit') {
        submitExam($db, $session, $exam, $studentId);
        echo json_encode(['ok'=>true,'redirect'=> BASE_URL . "student/result.php?session_id=$sessionId"]);
        exit;
    }
}

// ── Auto-submit if time is up (page load check) ───────────────
$elapsed     = time() - strtotime($session['started_at']);
$totalSeconds = $exam['duration_minutes'] * 60;
$remaining   = $session['time_remaining'] ?? ($totalSeconds - $elapsed);
if ($remaining <= 0) {
    submitExam($db, $session, $exam, $studentId);
    redirect(BASE_URL . "student/result.php?session_id=$sessionId");
}

// ── Load questions in order ────────────────────────────────────
$qOrder = $_SESSION['exam_order_'.$examId] ?? [];
if (empty($qOrder)) {
    $qs = $db->prepare("SELECT sa.question_id FROM student_answers sa WHERE sa.session_id=?");
    $qs->execute([$sessionId]);
    $qOrder = array_column($qs->fetchAll(), 'question_id');
}

if (empty($qOrder)) redirect(BASE_URL . 'student/dashboard.php');

$placeholders = implode(',', array_fill(0, count($qOrder), '?'));
$questions = $db->prepare("SELECT * FROM questions WHERE id IN ($placeholders)");
$questions->execute($qOrder);
$questionsMap = [];
foreach ($questions->fetchAll() as $q) $questionsMap[$q['id']] = $q;
$questions = array_map(fn($id) => $questionsMap[$id], array_filter($qOrder, fn($id) => isset($questionsMap[$id])));

// Load existing answers
$answers = $db->prepare("SELECT question_id, selected_answer FROM student_answers WHERE session_id=?");
$answers->execute([$sessionId]);
$answersMap = [];
foreach ($answers->fetchAll() as $a) $answersMap[$a['question_id']] = $a['selected_answer'];

$totalQ   = count($questions);
$answered = count(array_filter($answersMap, fn($a) => !empty($a)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Exam in Progress — HUKP CBT</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
</head>
<body>
<div class="exam-shell">

<!-- EXAM SIDEBAR -->
<aside class="exam-sidebar">
  <div class="exam-sidebar-header">
    <h3><?= sanitize($exam['course_code']) ?></h3>
    <p><?= sanitize(substr($exam['exam_title'], 0, 40)) ?>...</p>
    <div class="exam-timer-box">
      <div class="timer-label">Time Remaining</div>
      <div id="examTimer">--:--</div>
    </div>
  </div>

  <div style="padding:12px 16px;background:var(--slate-50);border-bottom:1px solid var(--slate-200)">
    <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--slate-600);margin-bottom:6px">
      <span>Progress</span>
      <span id="answeredCount"><?= $answered ?>/<?= $totalQ ?></span>
    </div>
    <div style="height:6px;background:var(--slate-200);border-radius:3px;overflow:hidden">
      <div id="progressBar" style="height:100%;background:var(--green-500);border-radius:3px;width:<?= $totalQ>0?round(($answered/$totalQ)*100,0):0 ?>%;transition:width .4s ease"></div>
    </div>
  </div>

  <div style="padding:8px 12px;font-size:11px;color:var(--slate-500)">
    <span style="margin-right:8px">⬜ Unanswered</span>
    <span style="margin-right:8px;color:var(--green-600)">🟩 Answered</span>
    <span style="color:var(--gold-500)">🟨 Current</span>
  </div>

  <div class="question-grid" id="questionGrid">
    <?php foreach ($questions as $qi => $q): ?>
    <button class="q-btn <?= !empty($answersMap[$q['id']]) ? 'answered' : '' ?> <?= $qi===0?'current':'' ?>"
            id="qbtn_<?= $q['id'] ?>"
            onclick="goToQuestion(<?= $qi ?>)" title="Question <?= $qi+1 ?>">
      <?= $qi+1 ?>
    </button>
    <?php endforeach; ?>
  </div>
</aside>

<!-- EXAM MAIN -->
<div class="exam-main">
  <div class="exam-topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <span style="font-size:13px;font-weight:600;color:var(--slate-600)">
        Q <span id="currentQNum">1</span> of <?= $totalQ ?>
      </span>
      <div class="exam-progress-bar">
        <div class="exam-progress-fill" id="topProgressFill" style="width:<?= $totalQ>0?round((1/$totalQ)*100).'%':'0%' ?>"></div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <span style="font-size:13px;color:var(--slate-500)"><?= sanitize($_SESSION['student_name']) ?></span>
      <button onclick="confirmSubmit()" class="btn btn-primary btn-sm">
        <i class="fas fa-paper-plane"></i> Submit Exam
      </button>
    </div>
  </div>

  <div class="question-area" id="questionArea">
    <!-- Questions rendered by JS -->
  </div>
</div>
</div>

<!-- Submit Confirmation Modal -->
<div class="modal-overlay" id="submitModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Confirm Submission</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <div class="modal-body">
      <p style="margin-bottom:16px;color:var(--slate-700)">Are you sure you want to submit your exam?</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div style="background:var(--green-50);border:1px solid var(--green-200);border-radius:8px;padding:14px;text-align:center">
          <div id="modalAnswered" style="font-size:28px;font-weight:700;font-family:var(--font-display);color:var(--green-600)">0</div>
          <div style="font-size:12px;color:var(--slate-500)">Answered</div>
        </div>
        <div style="background:var(--red-100);border:1px solid #fca5a5;border-radius:8px;padding:14px;text-align:center">
          <div id="modalUnanswered" style="font-size:28px;font-weight:700;font-family:var(--font-display);color:var(--red-600)">0</div>
          <div style="font-size:12px;color:var(--slate-500)">Unanswered</div>
        </div>
      </div>
      <p style="margin-top:14px;font-size:13px;color:var(--slate-500)">
        ⚠️ Once submitted, you cannot return to this exam.
      </p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal()">Continue Exam</button>
      <button class="btn btn-primary" onclick="finalSubmit()">
        <i class="fas fa-check"></i> Submit Now
      </button>
    </div>
  </div>
</div>

<script>
// ── Question Data (from PHP) ─────────────────────────────────
const questions = <?= json_encode(array_values($questions)) ?>;
const answersMap = <?= json_encode($answersMap) ?>;
const totalSeconds = <?= max(1, $remaining) ?>;
const sessionId = <?= $sessionId ?>;
const examId = <?= $examId ?>;

let currentIndex = 0;
let timerSeconds = totalSeconds;
let answers = {...answersMap};

// ── Timer ────────────────────────────────────────────────────
const timerEl = document.getElementById('examTimer');
function pad(n) { return String(n).padStart(2,'0'); }

function updateTimer() {
  const m = Math.floor(timerSeconds / 60);
  const s = timerSeconds % 60;
  timerEl.textContent = `${pad(m)}:${pad(s)}`;

  if (timerSeconds <= 300) timerEl.classList.add('warning');
  if (timerSeconds <= 60)  { timerEl.classList.remove('warning'); timerEl.classList.add('danger'); }
  if (timerSeconds <= 0)   { clearInterval(timerInterval); autoSubmit(); return; }
  timerSeconds--;
}

const timerInterval = setInterval(updateTimer, 1000);
updateTimer();

// Save time every 30s
setInterval(() => {
  fetch('exam.php?exam_id=' + examId, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: `action=save_time&remaining=${timerSeconds}`
  });
}, 30000);

// ── Render Question ──────────────────────────────────────────
function renderQuestion(idx) {
  const q = questions[idx];
  if (!q) return;
  currentIndex = idx;

  // Update grid buttons
  document.querySelectorAll('.q-btn').forEach((btn, i) => {
    btn.classList.remove('current');
    if (answers[btn.title] || answers[questions[i]?.id]) {
      btn.classList.add('answered');
    }
  });
  document.getElementById(`qbtn_${q.id}`)?.classList.add('current');
  document.getElementById(`qbtn_${q.id}`)?.classList.remove('answered');

  const saved = answers[q.id] || '';
  const opts  = ['A','B','C','D'];
  const optTexts = [q.option_a, q.option_b, q.option_c, q.option_d];

  document.getElementById('currentQNum').textContent = idx + 1;
  document.getElementById('topProgressFill').style.width = `${((idx+1)/questions.length)*100}%`;

  document.getElementById('questionArea').innerHTML = `
    <div class="question-number">Question ${idx+1} of ${questions.length}</div>
    <p class="question-text">${escHtml(q.question_text)}</p>
    <div class="options-list">
      ${opts.map((letter, i) => `
        <div class="option-item ${saved===letter?'selected':''}"
             id="opt_${letter}"
             onclick="selectAnswer('${q.id}','${letter}')">
          <div class="option-radio"></div>
          <div class="option-letter">${letter}</div>
          <div class="option-text">${escHtml(optTexts[i])}</div>
        </div>
      `).join('')}
    </div>
    <div class="exam-nav-buttons">
      <button class="btn btn-outline" onclick="navigate(-1)" ${idx===0?'disabled':''}>
        ← Previous
      </button>
      <button class="btn btn-outline btn-sm" onclick="clearAnswer('${q.id}')">
        Clear Answer
      </button>
      ${idx < questions.length-1
        ? `<button class="btn btn-primary" onclick="navigate(1)">Next →</button>`
        : `<button class="btn btn-gold" onclick="confirmSubmit()"><i class="fas fa-paper-plane"></i> Submit Exam</button>`
      }
    </div>
  `;
}

function escHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function selectAnswer(qid, letter) {
  answers[qid] = letter;
  // Update grid
  const btn = document.getElementById(`qbtn_${qid}`);
  if (btn) { btn.classList.add('answered'); btn.classList.add('current'); }

  // Update progress
  const answeredCount = Object.values(answers).filter(a=>a).length;
  document.getElementById('answeredCount').textContent = `${answeredCount}/${questions.length}`;
  document.getElementById('progressBar').style.width = `${(answeredCount/questions.length)*100}%`;

  // Re-render options highlight
  ['A','B','C','D'].forEach(l => {
    const el = document.getElementById(`opt_${l}`);
    if (el) el.classList.toggle('selected', l===letter);
  });

  // AJAX save
  fetch('exam.php?exam_id=' + examId, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: `action=save_answer&question_id=${qid}&answer=${letter}`
  });
}

function clearAnswer(qid) {
  answers[qid] = '';
  ['A','B','C','D'].forEach(l => {
    const el = document.getElementById(`opt_${l}`);
    if (el) el.classList.remove('selected');
  });
  fetch('exam.php?exam_id=' + examId, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: `action=save_answer&question_id=${qid}&answer=`
  });
  const btn = document.getElementById(`qbtn_${qid}`);
  if (btn) btn.classList.remove('answered');
  const answeredCount = Object.values(answers).filter(a=>a).length;
  document.getElementById('answeredCount').textContent = `${answeredCount}/${questions.length}`;
  document.getElementById('progressBar').style.width = `${(answeredCount/questions.length)*100}%`;
}

function navigate(dir) {
  const next = currentIndex + dir;
  if (next >= 0 && next < questions.length) renderQuestion(next);
}

function goToQuestion(idx) { renderQuestion(idx); }

function confirmSubmit() {
  const answered = Object.values(answers).filter(a=>a).length;
  const unanswered = questions.length - answered;
  document.getElementById('modalAnswered').textContent = answered;
  document.getElementById('modalUnanswered').textContent = unanswered;
  document.getElementById('submitModal').classList.add('open');
}

function closeModal() { document.getElementById('submitModal').classList.remove('open'); }

function finalSubmit() {
  fetch('exam.php?exam_id=' + examId, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=submit'
  })
  .then(r => r.json())
  .then(data => { if (data.redirect) window.location.href = data.redirect; });
}

function autoSubmit() { finalSubmit(); }

// Prevent accidental navigation
window.addEventListener('beforeunload', e => {
  e.preventDefault();
  e.returnValue = 'Your exam is in progress. Are you sure you want to leave?';
});

// Initialize
renderQuestion(0);
</script>
</body>
</html>
<?php
// ── Auto-grading function ────────────────────────────────────
function submitExam(PDO $db, array $session, array $exam, int $studentId): void {
    $sid = $session['id'];
    $eid = $exam['id'];

    // Already submitted?
    $existing = $db->prepare("SELECT id FROM results WHERE session_id=?");
    $existing->execute([$sid]);
    if ($existing->fetch()) return;

    // Get all answers with correct info
    $answers = $db->prepare("
        SELECT sa.*, q.correct_answer, q.marks
        FROM student_answers sa
        JOIN questions q ON sa.question_id=q.id
        WHERE sa.session_id=?
    ");
    $answers->execute([$sid]);
    $answers = $answers->fetchAll();

    $totalQ   = count($answers);
    $correct  = 0;
    $wrong    = 0;
    $skipped  = 0;
    $rawScore = 0;

    foreach ($answers as $a) {
        if (empty($a['selected_answer'])) {
            $skipped++;
        } elseif ($a['selected_answer'] === $a['correct_answer']) {
            $correct++;
            $rawScore += $a['marks'];
        } else {
            $wrong++;
        }
    }

    $answered   = $correct + $wrong;
    $maxScore   = array_sum(array_column($answers, 'marks')) ?: $totalQ;
    $percentage = $maxScore > 0 ? round(($rawScore / $maxScore) * 100, 2) : 0;
    $grade      = getGrade($percentage);
    $status     = $percentage >= $exam['pass_score'] ? 'pass' : 'fail';

    $db->prepare("INSERT INTO results (session_id,student_id,exam_id,total_questions,answered,correct,wrong,skipped,raw_score,percentage,grade,status,submitted_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())")
       ->execute([$sid,$studentId,$eid,$totalQ,$answered,$correct,$wrong,$skipped,$rawScore,$percentage,$grade,$status]);

    $db->prepare("UPDATE exam_sessions SET status='submitted',submitted_at=NOW() WHERE id=?")->execute([$sid]);

    logActivity('student', $studentId, 'Exam Submit', "Submitted exam $eid — Score: $percentage% ($grade)");
}
