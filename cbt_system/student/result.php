<?php
require_once '../includes/config.php';
requireStudentLogin();
$db = getDB();

$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$studentId = $_SESSION['student_id'];

if (!$sessionId) redirect(BASE_URL . 'student/dashboard.php');

// Load result (must belong to this student)
$result = $db->prepare("
    SELECT r.*, s.full_name, s.reg_number, s.level, d.name AS dept_name,
           e.exam_title, e.duration_minutes, e.pass_score,
           c.course_code, c.course_title
    FROM results r
    JOIN students s ON r.student_id=s.id
    JOIN exam_sessions es ON r.session_id=es.id
    JOIN exams e ON r.exam_id=e.id
    JOIN courses c ON e.course_id=c.id
    JOIN departments d ON s.department_id=d.id
    WHERE r.session_id=? AND r.student_id=?
");
$result->execute([$sessionId, $studentId]);
$result = $result->fetch();
if (!$result) redirect(BASE_URL . 'student/dashboard.php');

// Load answered questions with review
$reviewItems = $db->prepare("
    SELECT sa.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d,
           q.correct_answer, q.explanation
    FROM student_answers sa
    JOIN questions q ON sa.question_id=q.id
    WHERE sa.session_id=?
    ORDER BY sa.id
");
$reviewItems->execute([$sessionId]);
$reviewItems = $reviewItems->fetchAll();

$gradeLabel = getGradeLabel($result['grade']);
$isPassed   = $result['status'] === 'pass';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Exam Result — HUKP CBT</title>
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
    <a href="dashboard.php" class="nav-item"><span class="nav-icon">🏠</span> Dashboard</a>
    <a href="my_results.php" class="nav-item active"><span class="nav-icon">📊</span> My Results</a>
    <a href="logout.php" class="nav-item" style="color:#f87171"><span class="nav-icon">🚪</span> Logout</a>
  </nav>
</aside>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">
      <h1>Exam Result</h1>
      <p><?= sanitize($result['course_code']) ?> — <?= sanitize($result['exam_title']) ?></p>
    </div>
    <div class="topbar-actions">
      <button onclick="window.print()" class="btn btn-outline btn-sm"><i class="fas fa-print"></i> Print</button>
      <a href="dashboard.php" class="btn btn-outline btn-sm"><i class="fas fa-home"></i> Dashboard</a>
    </div>
  </div>

  <div class="page-content">

    <!-- Result Hero -->
    <div class="result-hero">
      <div class="score-circle" style="border-color:<?= $isPassed?'rgba(77,184,127,.4)':'rgba(248,113,113,.4)' ?>">
        <div class="score-pct"><?= $result['percentage'] ?>%</div>
        <div class="score-grade" style="color:<?= $isPassed?'var(--gold-300)':'#ff8080' ?>"><?= $result['grade'] ?></div>
      </div>

      <h2 style="font-family:var(--font-display);font-size:28px;margin-bottom:6px">
        <?= $isPassed ? '🎉 Congratulations!' : '😔 Better Luck Next Time' ?>
      </h2>
      <p style="color:var(--green-200);margin-bottom:4px"><?= sanitize($result['full_name']) ?> — <?= sanitize($result['reg_number']) ?></p>
      <p style="color:var(--green-200);font-size:14px"><?= sanitize($result['dept_name']) ?> — <?= $result['level'] ?></p>

      <div style="margin-top:20px;display:inline-block;padding:10px 24px;border-radius:30px;
        background:<?= $isPassed?'rgba(77,184,127,.2)':'rgba(248,113,113,.2)' ?>;
        border:1px solid <?= $isPassed?'rgba(77,184,127,.4)':'rgba(248,113,113,.4)' ?>">
        <span style="font-size:16px;font-weight:700;color:<?= $isPassed?'var(--green-200)':'#ff8080' ?>">
          <?= $result['grade'] ?> — <?= $gradeLabel ?> — <?= ucfirst($result['status']) ?>
        </span>
      </div>
    </div>

    <!-- Breakdown -->
    <div class="result-breakdown">
      <?php $items = [
        ['value'=>$result['total_questions'], 'label'=>'Total Questions', 'color'=>'var(--slate-600)'],
        ['value'=>$result['answered'],         'label'=>'Attempted',       'color'=>'var(--blue-600)'],
        ['value'=>$result['correct'],          'label'=>'Correct',         'color'=>'var(--green-600)'],
        ['value'=>$result['wrong'],            'label'=>'Wrong',           'color'=>'var(--red-600)'],
        ['value'=>$result['skipped'],          'label'=>'Skipped',         'color'=>'var(--slate-500)'],
        ['value'=>$result['raw_score'],        'label'=>'Raw Score',       'color'=>'var(--gold-500)'],
      ]; ?>
      <?php foreach ($items as $item): ?>
      <div class="breakdown-item">
        <div class="value" style="color:<?= $item['color'] ?>"><?= $item['value'] ?></div>
        <div class="label"><?= $item['label'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Score Bar -->
    <div class="card mb-6">
      <div class="card-body">
        <div style="display:flex;justify-content:space-between;margin-bottom:8px">
          <span style="font-size:14px;font-weight:600;color:var(--slate-700)">Score Performance</span>
          <span style="font-size:14px;color:var(--slate-500)">Pass Mark: <?= $result['pass_score'] ?>%</span>
        </div>
        <div style="height:18px;background:var(--slate-200);border-radius:9px;overflow:hidden;position:relative">
          <div style="height:100%;width:<?= $result['percentage'] ?>%;background:linear-gradient(90deg,<?= $isPassed?'var(--green-400),var(--green-500)':'var(--red-500),var(--red-600)' ?>);border-radius:9px;transition:width 1s ease"></div>
          <div style="position:absolute;top:0;left:<?= $result['pass_score'] ?>%;height:100%;width:2px;background:var(--gold-400)"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:12px;color:var(--slate-400)">
          <span>0%</span>
          <span style="color:var(--gold-600)">Pass: <?= $result['pass_score'] ?>%</span>
          <span>100%</span>
        </div>
      </div>
    </div>

    <!-- Review Questions -->
    <div class="card">
      <div class="card-header">
        <h3>Answer Review</h3>
        <div style="display:flex;gap:8px">
          <span class="badge badge-green">✅ Correct: <?= $result['correct'] ?></span>
          <span class="badge badge-red">❌ Wrong: <?= $result['wrong'] ?></span>
        </div>
      </div>
      <div style="padding:16px;display:flex;flex-direction:column;gap:14px">
        <?php foreach ($reviewItems as $ri => $item): ?>
        <?php
        $myAnswer   = $item['selected_answer'];
        $isCorrect  = !empty($myAnswer) && $myAnswer === $item['correct_answer'];
        $isSkipped  = empty($myAnswer);
        $borderColor = $isCorrect ? 'var(--green-300)' : ($isSkipped ? 'var(--slate-200)' : 'var(--red-300)');
        $bgColor     = $isCorrect ? 'var(--green-50)' : ($isSkipped ? 'var(--slate-50)' : 'var(--red-100)');
        $icon        = $isCorrect ? '✅' : ($isSkipped ? '⏭️' : '❌');
        ?>
        <div style="border:1px solid <?= $borderColor ?>;background:<?= $bgColor ?>;border-radius:var(--radius);padding:18px">
          <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:12px">
            <span style="font-size:18px;flex-shrink:0;margin-top:2px"><?= $icon ?></span>
            <div style="flex:1">
              <p style="font-size:14px;font-weight:600;color:var(--slate-700);margin-bottom:4px">Q<?= $ri+1 ?>.</p>
              <p style="font-size:15px;color:var(--slate-800);line-height:1.55"><?= sanitize($item['question_text']) ?></p>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-left:30px">
            <?php foreach (['A'=>$item['option_a'],'B'=>$item['option_b'],'C'=>$item['option_c'],'D'=>$item['option_d']] as $letter=>$text): ?>
            <?php
            $isMyAns   = $letter === $myAnswer;
            $isCorrectOpt = $letter === $item['correct_answer'];
            $optBg  = $isCorrectOpt ? 'var(--green-100)' : ($isMyAns && !$isCorrectOpt ? 'var(--red-100)' : 'var(--white)');
            $optBorder = $isCorrectOpt ? 'var(--green-300)' : ($isMyAns && !$isCorrectOpt ? '#fca5a5' : 'var(--slate-200)');
            ?>
            <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:6px;font-size:13px;background:<?= $optBg ?>;border:1px solid <?= $optBorder ?>">
              <span style="font-weight:700;color:<?= $isCorrectOpt?'var(--green-700)':($isMyAns&&!$isCorrectOpt?'var(--red-600)':'var(--slate-500)') ?>"><?= $letter ?></span>
              <span style="flex:1"><?= sanitize($text) ?></span>
              <?php if ($isCorrectOpt): ?><i class="fas fa-check-circle" style="color:var(--green-600);flex-shrink:0"></i><?php endif; ?>
              <?php if ($isMyAns && !$isCorrectOpt): ?><i class="fas fa-times-circle" style="color:var(--red-500);flex-shrink:0"></i><?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>

          <?php if ($isSkipped): ?>
          <p style="margin-top:10px;margin-left:30px;font-size:12px;color:var(--slate-500)">⏭️ You did not answer this question.</p>
          <?php endif; ?>

          <?php if ($item['explanation']): ?>
          <div style="margin-top:10px;margin-left:30px;padding:8px 12px;background:rgba(0,0,0,.04);border-radius:6px;font-size:12px;color:var(--slate-600);font-style:italic">
            💡 <?= sanitize($item['explanation']) ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="text-align:center;margin-top:28px">
      <a href="dashboard.php" class="btn btn-primary btn-lg">← Back to Dashboard</a>
    </div>

  </div>
</div>
</div>
</body>
</html>
