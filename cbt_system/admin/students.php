<?php
require_once '../includes/config.php';
requireAdminLogin();
$db = getDB();
$currentPage = 'students';
$pageTitle   = 'Student Management';

$departments = $db->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$error = '';

// Add student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $regNum  = sanitize($_POST['reg_number']);
    $name    = sanitize($_POST['full_name']);
    $email   = sanitize($_POST['email']);
    $deptId  = (int)$_POST['department_id'];
    $level   = sanitize($_POST['level']);
    $gender  = sanitize($_POST['gender']);
    $phone   = sanitize($_POST['phone']);
    $pass    = password_hash(STUDENT_DEFAULT_PASSWORD, PASSWORD_BCRYPT);

    if (!$regNum || !$name || !$email || !$deptId) {
        $error = 'Please fill all required fields.';
    } else {
        try {
            $db->prepare("INSERT INTO students (reg_number,full_name,email,password,department_id,level,gender,phone) VALUES (?,?,?,?,?,?,?,?)")
               ->execute([$regNum,$name,$email,$pass,$deptId,$level,$gender,$phone]);
            flashMsg('success', "Student <strong>$name</strong> registered. Default password: " . STUDENT_DEFAULT_PASSWORD);
            redirect(BASE_URL . 'admin/students.php');
        } catch (PDOException $e) {
            $error = 'Registration number or email already exists.';
        }
    }
}

// Toggle active
if (isset($_GET['toggle'])) {
    $sid = (int)$_GET['toggle'];
    $db->prepare("UPDATE students SET is_active = NOT is_active WHERE id=?")->execute([$sid]);
    redirect(BASE_URL . 'admin/students.php');
}

// Delete
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM students WHERE id=?")->execute([(int)$_GET['delete']]);
    flashMsg('success', 'Student deleted.');
    redirect(BASE_URL . 'admin/students.php');
}

$search    = sanitize($_GET['search'] ?? '');
$deptFilter = (int)($_GET['dept'] ?? 0);
$whereClause = 'WHERE 1=1';
$params = [];
if ($search) { $whereClause .= " AND (s.full_name LIKE ? OR s.reg_number LIKE ? OR s.email LIKE ?)"; $params = array_fill(0, 3, "%$search%"); }
if ($deptFilter) { $whereClause .= " AND s.department_id=?"; $params[] = $deptFilter; }

$stmt = $db->prepare("SELECT s.*, d.name AS dept_name, COUNT(r.id) AS exam_count
    FROM students s
    JOIN departments d ON s.department_id=d.id
    LEFT JOIN results r ON s.id=r.student_id
    $whereClause
    GROUP BY s.id
    ORDER BY s.created_at DESC");
$stmt->execute($params);
$students = $stmt->fetchAll();

$flashSuccess = flashMsg('success');
include '../includes/admin_header.php';
?>

<?php if ($flashSuccess): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $flashSuccess ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start">

<!-- REGISTER FORM -->
<div class="card" style="position:sticky;top:80px">
  <div class="card-header"><h3>Register Student</h3></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="add_student" value="1">
      <div class="form-group">
        <label>Reg. Number *</label>
        <input type="text" name="reg_number" class="form-control" placeholder="e.g. ICT/HND1/003/2024" required>
      </div>
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="full_name" class="form-control" placeholder="Student full name" required>
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" class="form-control" placeholder="student@hukp.edu.ng" required>
      </div>
      <div class="form-group">
        <label>Department *</label>
        <select name="department_id" class="form-control" required>
          <option value="">— Select Department —</option>
          <?php foreach ($departments as $d): ?>
          <option value="<?= $d['id'] ?>"><?= sanitize($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label>Level</label>
          <select name="level" class="form-control">
            <option>ND1</option><option>ND2</option><option>HND1</option><option>HND2</option>
          </select>
        </div>
        <div class="form-group">
          <label>Gender</label>
          <select name="gender" class="form-control">
            <option>Male</option><option>Female</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control" placeholder="08012345678">
      </div>
      <div style="background:var(--gold-100);border:1px solid var(--gold-200);border-radius:6px;padding:10px;font-size:12px;color:var(--gold-600);margin-bottom:16px">
        <i class="fas fa-info-circle"></i> Default password: <strong><?= STUDENT_DEFAULT_PASSWORD ?></strong>
      </div>
      <button type="submit" class="btn btn-primary btn-full">
        <i class="fas fa-user-plus"></i> Register Student
      </button>
    </form>
  </div>
</div>

<!-- STUDENT LIST -->
<div class="card">
  <div class="card-header">
    <h3>All Students</h3>
    <span class="badge badge-green"><?= count($students) ?> records</span>
  </div>

  <!-- Search bar -->
  <div style="padding:16px;border-bottom:1px solid var(--slate-100)">
    <form method="GET" style="display:flex;gap:10px">
      <div class="input-icon-wrap" style="flex:1">
        <i class="fas fa-search input-icon"></i>
        <input type="text" name="search" class="form-control" placeholder="Search by name, reg number or email..."
          value="<?= sanitize($search) ?>">
      </div>
      <select name="dept" class="form-control" style="width:180px">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>" <?= $deptFilter==$d['id']?'selected':'' ?>><?= sanitize($d['code']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
      <?php if ($search||$deptFilter): ?><a href="students.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Reg. Number</th>
          <th>Dept.</th>
          <th>Level</th>
          <th>Exams</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $i => $st): ?>
        <tr>
          <td class="text-muted"><?= $i+1 ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div class="user-avatar" style="width:32px;height:32px;font-size:12px">
                <?= strtoupper(substr($st['full_name'],0,1)) ?>
              </div>
              <div>
                <div style="font-weight:600"><?= sanitize($st['full_name']) ?></div>
                <div class="text-sm text-muted"><?= sanitize($st['email']) ?></div>
              </div>
            </div>
          </td>
          <td class="font-mono text-sm"><?= sanitize($st['reg_number']) ?></td>
          <td><span class="badge badge-green"><?= sanitize($st['dept_name']) ?></span></td>
          <td><span class="badge badge-slate"><?= $st['level'] ?></span></td>
          <td><?= $st['exam_count'] ?></td>
          <td>
            <span class="badge <?= $st['is_active'] ? 'badge-green' : 'badge-red' ?>">
              <?= $st['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:5px">
              <a href="?toggle=<?= $st['id'] ?>" class="btn btn-outline btn-sm" title="Toggle status">
                <i class="fas fa-toggle-<?= $st['is_active'] ? 'on' : 'off' ?>" style="color:<?= $st['is_active'] ? 'var(--green-500)' : 'var(--slate-400)' ?>"></i>
              </a>
              <a href="results.php?student_id=<?= $st['id'] ?>" class="btn btn-outline btn-sm" title="View results">
                <i class="fas fa-chart-bar"></i>
              </a>
              <a href="?delete=<?= $st['id'] ?>" class="btn btn-danger btn-sm"
                 onclick="return confirm('Delete student <?= sanitize($st['full_name']) ?>?')">
                <i class="fas fa-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($students)): ?>
        <tr><td colspan="8">
          <div class="empty-state"><div class="icon">👨‍🎓</div><p>No students found.</p></div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<?php include '../includes/admin_footer.php'; ?>
