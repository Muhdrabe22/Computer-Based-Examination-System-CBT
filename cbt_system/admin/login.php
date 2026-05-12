<?php
require_once '../includes/config.php';

if (isAdminLoggedIn()) redirect(BASE_URL . 'admin/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE (username=? OR email=?) AND is_active=1");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            $db->prepare("UPDATE admins SET last_login=NOW() WHERE id=?")->execute([$admin['id']]);
            logActivity('admin', $admin['id'], 'Login', 'Admin logged in successfully');
            redirect(BASE_URL . 'admin/dashboard.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — HUKP CBT System</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-body">
<div class="auth-left">
  <div class="auth-logo">🎓</div>
  <div class="auth-brand">
    <h1>HUKP CBT System</h1>
    <p>Hassan Usman Katsina Polytechnic</p>
  </div>
  <div class="auth-divider"></div>
  <ul class="auth-features">
    <li><span class="icon">📝</span> Computer Based Examinations</li>
    <li><span class="icon">⚡</span> Automated Instant Grading</li>
    <li><span class="icon">📊</span> Comprehensive Analytics</li>
    <li><span class="icon">🔒</span> Secure & Reliable Platform</li>
    <li><span class="icon">🏫</span> ICT Department, Katsina</li>
  </ul>
</div>
<div class="auth-right">
  <div class="auth-card">
    <h2>Admin Portal</h2>
    <p class="subtitle">Sign in to manage exams, students and results</p>

    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Username or Email</label>
        <div class="input-icon-wrap">
          <i class="fas fa-user input-icon"></i>
          <input type="text" name="username" class="form-control" placeholder="Enter username or email"
            value="<?= sanitize($_POST['username'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Password</label>
        <div class="input-icon-wrap">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
          <i class="fas fa-eye" id="togglePwd" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--slate-400)"></i>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </form>

    <div style="margin-top:28px;padding:16px;background:var(--slate-50);border-radius:8px;border:1px solid var(--slate-200)">
      <p style="font-size:12px;color:var(--slate-500);margin-bottom:8px;font-weight:600;">DEFAULT CREDENTIALS</p>
      <p style="font-size:12px;color:var(--slate-600)">Username: <code style="background:var(--slate-200);padding:2px 6px;border-radius:3px">superadmin</code></p>
      <p style="font-size:12px;color:var(--slate-600);margin-top:4px">Password: <code style="background:var(--slate-200);padding:2px 6px;border-radius:3px">Admin@1234</code></p>
    </div>

    <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--slate-500)">
      Student? <a href="../student/login.php" style="color:var(--green-600);font-weight:600">Login here</a>
    </p>
  </div>
</div>
<script>
document.getElementById('togglePwd').onclick = function() {
  const p = document.getElementById('password');
  p.type = p.type === 'password' ? 'text' : 'password';
  this.classList.toggle('fa-eye');
  this.classList.toggle('fa-eye-slash');
};
</script>
</body>
</html>