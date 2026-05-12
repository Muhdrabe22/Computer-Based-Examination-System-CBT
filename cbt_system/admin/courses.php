<?php
require_once '../includes/config.php';
requireAdminLogin();
$db = getDB();
$currentPage = 'courses';
$pageTitle   = 'Courses Management';

$departments = $db->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$error = '';

// Add course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $deptId  = (int)$_POST['department_id'];
    $code    = strtoupper(sanitize($_POST['course_code']));
    $title   = sanitize($_POST['course_title']);
    $credits = (int)$_POST['credit_units'];
    if (!$deptId || !$code || !$title) {
        $error = 'All fields are required.';
    } else {
        try {
            $db->prepare("INSERT INTO courses (department_id,course_code,course_title,credit_units) VALUES (?,?,?,?)")
               ->execute([$deptId, $code, $title, $credits]);
            flashMsg('success', "Course <strong>$code</strong> added.");
            redirect(BASE_URL . 'admin/courses.php');
        } catch (PDOException $e) {
            $error = 'Course code already exists.';
        }
    }
}

// Add department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dept'])) {
    $dName = sanitize($_POST['dept_name']);
    $dCode = strtoupper(sanitize($_POST['dept_code']));
    if (!$dName || !$dCode) {
        $error = 'Department name and code are required.';
    } else {
        try {
            $db->prepare("INSERT INTO departments (name,code) VALUES (?,?)")->execute([$dName,$dCode]);
            flashMsg('success', "Department added.");
            redirect(BASE_URL . 'admin/courses.php');
        } catch (PDOException $e) {
            $error = 'Department code already exists.';
        }
    }
}

// Delete course
if (isset($_GET['del_course'])) {
    $db->prepare("DELETE FROM courses WHERE id=?")->execute([(int)$_GET['del_course']]);
    flashMsg('success','Course deleted.'); redirect(BASE_URL.'admin/courses.php');
}

$courses = $db->query("
    SELECT c.*, d.name AS dept_name, COUNT(e.id) AS exam_count
    FROM courses c
    JOIN departments d ON c.department_id=d.id
    LEFT JOIN exams e ON c.id=e.course_id
    GROUP BY c.id
    ORDER BY c.course_code
")->fetchAll();

$flashSuccess = flashMsg('success');
include '../includes/admin_header.php';
?>

<?php if ($flashSuccess): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $flashSuccess ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start">

<!-- LEFT: Forms -->
<div style="display:flex;flex-direction:column;gap:20px;position:sticky;top:80px">

  <!-- Add Course -->
  <div class="card">
    <div class="card-header"><h3>Add Course</h3></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="add_course" value="1">
        <div class="form-group">
          <label>Department *</label>
          <select name="department_id" class="form-control" required>
            <option value="">— Select —</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>"><?= sanitize($d['code']) ?> — <?= sanitize($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Course Code *</label>
          <input type="text" name="course_code" class="form-control" placeholder="e.g. ICT405" required style="text-transform:uppercase">
        </div>
        <div class="form-group">
          <label>Course Title *</label>
          <input type="text" name="course_title" class="form-control" placeholder="e.g. Cybersecurity Fundamentals" required>
        </div>
        <div class="form-group">
          <label>Credit Units</label>
          <input type="number" name="credit_units" class="form-control" value="3" min="1" max="6">
        </div>
        <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-plus"></i> Add Course</button>
      </form>
    </div>
  </div>

  <!-- Add Department -->
  <div class="card">
    <div class="card-header"><h3>Add Department</h3></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="add_dept" value="1">
        <div class="form-group">
          <label>Department Name *</label>
          <input type="text" name="dept_name" class="form-control" placeholder="e.g. Computer Science" required>
        </div>
        <div class="form-group">
          <label>Department Code *</label>
          <input type="text" name="dept_code" class="form-control" placeholder="e.g. CSC" required style="text-transform:uppercase" maxlength="10">
        </div>
        <button type="submit" class="btn btn-gold btn-full"><i class="fas fa-building"></i> Add Department</button>
      </form>
    </div>
  </div>

</div>

<!-- RIGHT: Course list -->
<div class="card">
  <div class="card-header">
    <h3>All Courses</h3>
    <span class="badge badge-green"><?= count($courses) ?> courses</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Code</th><th>Title</th><th>Department</th><th>Credits</th><th>Exams</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($courses as $c): ?>
        <tr>
          <td><span class="badge badge-green font-mono"><?= sanitize($c['course_code']) ?></span></td>
          <td style="font-weight:500"><?= sanitize($c['course_title']) ?></td>
          <td class="text-muted text-sm"><?= sanitize($c['dept_name']) ?></td>
          <td><?= $c['credit_units'] ?> units</td>
          <td><?= $c['exam_count'] ?></td>
          <td>
            <a href="?del_course=<?= $c['id'] ?>" class="btn btn-danger btn-sm"
               onclick="return confirm('Delete course <?= sanitize($c['course_code']) ?>?')">
              <i class="fas fa-trash"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($courses)): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="icon">📚</div><p>No courses yet.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Department list -->
  <div style="padding:20px 24px;border-top:1px solid var(--slate-100)">
    <h4 style="font-family:var(--font-display);color:var(--green-700);margin-bottom:14px;font-size:16px">Departments</h4>
    <div style="display:flex;flex-wrap:wrap;gap:10px">
      <?php foreach ($departments as $d): ?>
      <div style="background:var(--green-50);border:1px solid var(--green-200);border-radius:8px;padding:10px 16px;display:flex;align-items:center;gap:10px">
        <span style="font-weight:700;color:var(--green-700);font-size:13px"><?= sanitize($d['code']) ?></span>
        <span style="color:var(--slate-600);font-size:13px"><?= sanitize($d['name']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

</div>

<?php include '../includes/admin_footer.php'; ?>
