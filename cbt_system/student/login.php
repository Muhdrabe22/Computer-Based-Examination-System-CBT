<?php
require_once '../includes/config.php';
if (isStudentLoggedIn()) redirect(BASE_URL . 'student/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regNum  = sanitize($_POST['reg_number'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($regNum) || empty($password)) {
        $error = 'Please enter your registration number and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM students WHERE (reg_number=? OR email=?) AND is_active=1");
        $stmt->execute([$regNum, $regNum]);
        $student = $stmt->fetch();

        if ($student && password_verify($password, $student['password'])) {
            $_SESSION['student_id']   = $student['id'];
            $_SESSION['student_name'] = $student['full_name'];
            $_SESSION['student_reg']  = $student['reg_number'];
            $_SESSION['student_dept'] = $student['department_id'];
            $db->prepare("UPDATE students SET last_login=NOW() WHERE id=?")->execute([$student['id']]);
            logActivity('student', $student['id'], 'Login', 'Student logged in');
            redirect(BASE_URL . 'student/dashboard.php');
        } else {
            $error = 'Invalid registration number or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Login — HUKP CBT System</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-body">
<div class="auth-left">
  <div class="auth-logo">📚</div>
  <div class="auth-brand">
    <h1>Student CBT Portal</h1>
    <p>Hassan Usman Katsina Polytechnic<br>ICT Department</p>
  </div>
  <div class="auth-divider"></div>
  <ul class="auth-features">
    <li><span class="icon">⏱️</span> Timed Computer Based Tests</li>
    <li><span class="icon">📊</span> Instant Automated Results</li>
    <li><span class="icon">🔍</span> Detailed Score Breakdown</li>
    <li><span class="icon">📱</span> Works on All Devices</li>
    <li><span class="icon">🏫</span> HUKP — Katsina, Nigeria</li>
  </ul>
</div>
<div class="auth-right">
  <div class="auth-card">
    <h2>Student Login</h2>
    <p class="subtitle">Enter your Email Adress to access your exams</p>

    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div>
    <?php endif; ?>

    <?php $success = flashMsg('success'); if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Email Adress</label>
        <div class="input-icon-wrap">
          <i class="fas fa-id-card input-icon"></i>
          <input type="text" name="reg_number" class="form-control"
            placeholder="Umma@gmail.com"
            value="<?= sanitize($_POST['reg_number'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Password</label>
        <div class="input-icon-wrap">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" name="password" id="pwd" class="form-control"
            placeholder="Enter your password" required>
          <i class="fas fa-eye" id="togglePwd"
            style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--slate-400)"></i>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px">
        <i class="fas fa-sign-in-alt"></i> Sign In to Exam Portal
      </button>
    </form>

    <div style="margin-top:28px;padding:16px;background:var(--green-50);border-radius:8px;border:1px solid var(--green-200)">
      <p style="font-size:12px;color:var(--green-700);margin-bottom:6px"><i class="fas fa-info-circle"></i> <strong>First time login?</strong></p>
      <p style="font-size:12px;color:var(--green-700)">Use your registration number and default password: <code style="background:var(--green-100);padding:2px 6px;border-radius:3px">Student@123</code></p>
    </div>

    <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--slate-500)">
      Admin? <a href="../admin/login.php" style="color:var(--green-600);font-weight:600">Admin login</a>
    </p>
  </div>
</div>
<script>
document.getElementById('togglePwd').onclick = function() {
  const p = document.getElementById('pwd');
  p.type = p.type === 'password' ? 'text' : 'password';
  this.classList.toggle('fa-eye'); this.classList.toggle('fa-eye-slash');
};
</script>
</body>
</html>
