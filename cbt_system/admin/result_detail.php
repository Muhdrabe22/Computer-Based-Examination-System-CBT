<?php
require_once '../includes/config.php';
requireAdminLogin();
$db = getDB();
$currentPage = 'results';
$pageTitle   = 'Result Detail';

$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if (!$sessionId) redirect(BASE_URL . 'admin/results.php');

$result = $db->prepare("
    SELECT r.*, s.full_name, s.reg_number, s.level, s.email, s.phone,
           d.name AS dept_name,
           e.exam_title, e.duration_minutes, e.pass_score, e.total_questions AS exam_total,
           c.course_code, c.course_title,
           es.started_at, es.submitted_at AS session_end, es.ip_address
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN departments d ON s.department_id = d.id
    JOIN exam_sessions es ON r.session_id = es.id
    JOIN exams e ON r.exam_id = e.id
    JOIN courses c ON e.course_id = c.id
    WHERE r.session_id = ?
");
$result->execute([$sessionId]);
$result = $result->fetch();
if (!$result) redirect(BASE_URL . 'admin/results.php');

// Answers with question details
$answers = $db->prepare("
    SELECT sa.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d,
           q.correct_answer, q.marks, q.explanation
    FROM student_answers sa
    JOIN questions q ON sa.question_id = q.id
    WHERE sa.session_id = ?
    ORDER BY sa.id
");
$answers->execute([$sessionId]);
$answers = $answers->fetchAll();

$pageSubtitle = sanitize($result['full_name']) . ' — ' . sanitize($result['course_code']);
include '../includes/admin_header.php';

$duration = round((strtotime($result['session_end']) - strtotime($result['started_at'])) / 60, 1);
$isPassed = $result['status'] === 'pass';
?>

<div style="max-width:900px">

  <!-- Action buttons -->
  <div style="display:flex;gap:10px;margin-bottom:20px">
    <a href="results.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Results</a>
    <button onclick="window.print()" class="btn btn-outline btn-sm"><i class="fas fa-print"></i> Print Report</button>
  </div>

  <!-- Result Hero Card -->
  <div class="card mb-6" style="overflow:visible">
    <div style="background:linear-gradient(135deg,var(--green-800),var(--green-900));padding:32px;border-radius:var(--radius-lg) var(--radius-lg) 0 0;display:flex;gap:28px;align-items:center">
      <!-- Score Circle -->
      <div style="width:120px;height:120px;border-radius:50%;border:5px solid rgba(255,255,255,.2);display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(255,255,255,.06);flex-shrink:0">
        <div style="font-size:32px;font-weight:700;font-family:var(--font-display);color:white;line-height:1"><?= $result['percentage'] ?>%</div>
        <div style="font-size:22px;font-weight:700;color:var(--gold-300)"><?= $result['grade'] ?></div>
      </div>
      <!-- Info -->
      <div style="flex:1">
        <h2 style="font-family:var(--font-display);color:white;font-size:20px;margin-bottom:6px"><?= sanitize($result['full_name']) ?></h2>
        <p style="color:var(--green-200);font-size:13px;margin-bottom:4px"><?= sanitize($result['reg_number']) ?> • <?= sanitize($result['dept_name']) ?> • Level <?= $result['level'] ?></p>
        <p style="color:var(--green-300);font-size:13px"><?= sanitize($result['exam_title']) ?></p>
        <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap">
          <span class="badge <?= $isPassed?'badge-green':'badge-red' ?>" style="font-size:12px"><?= $isPassed?'✓ PASS':'✗ FAIL' ?></span>
          <span class="badge badge-gold"><?= getGradeLabel($result['grade']) ?></span>
          <span class="badge badge-slate"><?= sanitize($result['course_code']) ?></span>
        </div>
      </div>
      <!-- Stats -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;flex-shrink:0">
        <?php $stats = [
          ['Correct',  $result['correct'],  'var(--green-300)'],
          ['Wrong',    $result['wrong'],    '#ff8080'],
          ['Skipped',  $result['skipped'],  'var(--slate-300)'],
          ['Duration', $duration.' min',    'var(--gold-300)'],
        ]; ?>
        <?php foreach ($stats as [$label,$val,$color]): ?>
        <div style="background:rgba(255,255,255,.08);padding:12px 16px;border-radius:8px;text-align:center">
          <div style="font-size:22px;font-weight:700;font-family:var(--font-display);color:<?= $color ?>"><?= $val ?></div>
          <div style="font-size:11px;color:var(--green-300);text-transform:uppercase;letter-spacing:.06em;margin-top:2px"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Meta row -->
    <div style="padding:16px 24px;background:var(--slate-50);border-top:1px solid var(--slate-200);display:flex;gap:28px;flex-wrap:wrap">
      <?php $meta = [
        ['📅','Started',       date('M j, Y g:i A', strtotime($result['started_at']))],
        ['📤','Submitted',      date('M j, Y g:i A', strtotime($result['session_end']))],
        ['🌐','IP Address',     $result['ip_address']],
        ['📧','Email',          $result['email']],
        ['📞','Phone',          $result['phone'] ?: '—'],
        ['🎯','Pass Mark',      $result['pass_score'] . '%'],
      ]; ?>
      <?php foreach ($meta as [$icon,$label,$val]): ?>
      <div>
        <div style="font-size:11px;color:var(--slate-400);text-transform:uppercase;letter-spacing:.06em"><?= $icon ?> <?= $label ?></div>
        <div style="font-size:13px;font-weight:600;color:var(--slate-700)"><?= sanitize($val) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Score breakdown bar -->
  <div class="card mb-6">
    <div class="card-body">
      <div style="display:flex;justify-content:space-between;margin-bottom:10px">
        <span style="font-size:14px;font-weight:600;color:var(--slate-700)">Score: <?= $result['raw_score'] ?> / <?= $result['total_questions'] ?> marks</span>
        <span style="font-size:14px;color:var(--slate-500)">Pass threshold: <?= $result['pass_score'] ?>%</span>
      </div>
      <div style="height:20px;background:var(--slate-200);border-radius:10px;overflow:hidden;position:relative">
        <div style="height:100%;width:<?= $result['percentage'] ?>%;background:linear-gradient(90deg,<?= $isPassed?'var(--green-400),var(--green-500)':'var(--red-400),var(--red-500)' ?>);border-radius:10px"></div>
        <div style="position:absolute;top:0;left:<?= $result['pass_score'] ?>%;height:100%;width:3px;background:var(--gold-400)"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--slate-400);margin-top:6px">
        <span>0%</span>
        <span style="color:var(--gold-600);font-weight:600">Pass: <?= $result['pass_score'] ?>%</span>
        <span>100%</span>
      </div>
    </div>
  </div>

  <!-- Answer Review -->
  <div class="card">
    <div class="card-header">
      <h3>Answer-by-Answer Review</h3>
      <div style="display:flex;gap:8px">
        <span class="badge badge-green">✅ <?= $result['correct'] ?> Correct</span>
        <span class="badge badge-red">❌ <?= $result['wrong'] ?> Wrong</span>
        <span class="badge badge-slate">⏭ <?= $result['skipped'] ?> Skipped</span>
      </div>
    </div>
    <div style="padding:16px;display:flex;flex-direction:column;gap:12px">
      <?php foreach ($answers as $i => $a): ?>
      <?php
      $my       = $a['selected_answer'];
      $correct  = $a['correct_answer'];
      $isRight  = !empty($my) && $my === $correct;
      $isSkip   = empty($my);
      $bg       = $isRight ? 'var(--green-50)' : ($isSkip ? 'var(--slate-50)' : 'var(--red-100)');
      $border   = $isRight ? 'var(--green-300)' : ($isSkip ? 'var(--slate-200)' : '#fca5a5');
      $icon     = $isRight ? '✅' : ($isSkip ? '⏭️' : '❌');
      ?>
      <div style="border:1px solid <?= $border ?>;background:<?= $bg ?>;border-radius:var(--radius);padding:16px">
        <div style="display:flex;gap:10px;margin-bottom:10px">
          <span style="font-size:16px"><?= $icon ?></span>
          <div>
            <span style="font-size:11px;font-weight:700;color:var(--slate-400);text-transform:uppercase;letter-spacing:.06em">Q<?= $i+1 ?></span>
            <p style="font-size:14px;color:var(--slate-800);margin-top:2px;line-height:1.5"><?= sanitize($a['question_text']) ?></p>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-left:26px">
          <?php foreach (['A'=>$a['option_a'],'B'=>$a['option_b'],'C'=>$a['option_c'],'D'=>$a['option_d']] as $l=>$t): ?>
          <?php
          $isMine   = $l === $my;
          $isCorr   = $l === $correct;
          $ob = $isCorr ? 'var(--green-100)' : ($isMine && !$isCorr ? 'var(--red-100)' : 'var(--white)');
          $obb = $isCorr ? 'var(--green-300)' : ($isMine && !$isCorr ? '#fca5a5' : 'var(--slate-200)');
          ?>
          <div style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:6px;font-size:13px;background:<?= $ob ?>;border:1px solid <?= $obb ?>">
            <span style="font-weight:700;min-width:16px;color:<?= $isCorr?'var(--green-700)':($isMine&&!$isCorr?'var(--red-600)':'var(--slate-500)') ?>"><?= $l ?></span>
            <span style="flex:1"><?= sanitize($t) ?></span>
            <?php if ($isCorr):  ?><i class="fas fa-check-circle" style="color:var(--green-600);flex-shrink:0;font-size:12px"></i><?php endif; ?>
            <?php if ($isMine && !$isCorr): ?><i class="fas fa-times-circle" style="color:var(--red-500);flex-shrink:0;font-size:12px"></i><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if ($isSkip): ?>
        <p style="margin-top:8px;margin-left:26px;font-size:12px;color:var(--slate-400);font-style:italic">Student did not attempt this question.</p>
        <?php endif; ?>
        <?php if ($a['explanation']): ?>
        <div style="margin-top:8px;margin-left:26px;padding:7px 10px;background:rgba(0,0,0,.04);border-radius:6px;font-size:12px;color:var(--slate-600);font-style:italic">
          💡 <?= sanitize($a['explanation']) ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<?php include '../includes/admin_footer.php'; ?>
