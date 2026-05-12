<?php
require_once '../includes/config.php';
requireStudentLogin();
$db = getDB();
$studentId = $_SESSION['student_id'];
$student   = $db->prepare("SELECT s.*, d.name AS dept_name FROM students s JOIN departments d ON s.department_id=d.id WHERE s.id=?");
$student->execute([$studentId]);
$student = $student->fetch();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);
        try {
            $db->prepare("UPDATE students SET phone=?, email=? WHERE id=?")->execute([$phone, $email, $studentId]);
            $success = 'Profile updated successfully.';
            $student['phone'] = $phone;
            $student['email'] = $email;
        } catch (PDOException $e) {
            $error = 'Email already in use by another account.';
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'];
        $new     = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if (!password_verify($current, $student['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $db->prepare("UPDATE students SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_BCRYPT), $studentId]);
            $success = 'Password changed successfully.';
            logActivity('student', $studentId, 'Password Change', 'Student changed their password');
        }
    }
}

$initials = strtoupper(implode('', array_map(fn($w)=>$w[0], array_slice(explode(' ', $student['full_name']),0,2))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — HUKP CBT</title>
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
    <span class="nav-section-label">Navigation</span>
    <a href="dashboard.php" class="nav-item"><span class="nav-icon">🏠</span> Dashboard</a>
    <a href="my_results.php" class="nav-item"><span class="nav-icon">📊</span> My Results</a>
    <a href="profile.php" class="nav-item active"><span class="nav-icon">⚙️</span> My Profile</a>
    <a href="logout.php" class="nav-item" style="color:#f87171"><span class="nav-icon">🚪</span> Logout</a>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar gold"><?= $initials ?></div>
      <div class="user-info">
        <h4><?= sanitize(explode(' ', $student['full_name'])[0]) ?></h4>
        <span><?= sanitize($student['reg_number']) ?></span>
      </div>
    </div>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">
      <h1>My Profile</h1>
      <p>Manage your account information</p>
    </div>
    <div class="topbar-actions">
      <span class="topbar-time" id="clock">--:--:--</span>
    </div>
  </div>

  <div class="page-content">
    <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:860px">

      <!-- Profile Info -->
      <div class="card">
        <div class="card-header"><h3>Personal Information</h3></div>
        <div class="card-body">
          <!-- Avatar -->
          <div style="text-align:center;margin-bottom:24px">
            <div class="user-avatar gold" style="width:80px;height:80px;font-size:28px;margin:0 auto 12px"><?= $initials ?></div>
            <h3 style="font-family:var(--font-display);color:var(--green-800)"><?= sanitize($student['full_name']) ?></h3>
            <p class="text-muted text-sm"><?= sanitize($student['reg_number']) ?></p>
          </div>

          <div style="display:flex;flex-direction:column;gap:14px">
            <?php $fields = [
              ['label'=>'Full Name',   'value'=>$student['full_name'], 'icon'=>'fas fa-user'],
              ['label'=>'Reg. Number', 'value'=>$student['reg_number'], 'icon'=>'fas fa-id-card'],
              ['label'=>'Department',  'value'=>$student['dept_name'], 'icon'=>'fas fa-building'],
              ['label'=>'Level',       'value'=>$student['level'], 'icon'=>'fas fa-graduation-cap'],
              ['label'=>'Gender',      'value'=>$student['gender'], 'icon'=>'fas fa-venus-mars'],
              ['label'=>'Member Since','value'=>date('F j, Y', strtotime($student['created_at'])), 'icon'=>'fas fa-calendar'],
            ]; ?>
            <?php foreach ($fields as $f): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--slate-50);border-radius:8px;border:1px solid var(--slate-200)">
              <i class="<?= $f['icon'] ?>" style="color:var(--green-500);width:18px;text-align:center"></i>
              <div>
                <div style="font-size:11px;color:var(--slate-400);text-transform:uppercase;letter-spacing:.06em"><?= $f['label'] ?></div>
                <div style="font-size:14px;font-weight:600;color:var(--slate-700)"><?= sanitize($f['value']) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:24px">
        <!-- Edit Editable Fields -->
        <div class="card">
          <div class="card-header"><h3>Update Contact</h3></div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="action" value="update_profile">
              <div class="form-group">
                <label>Email Address</label>
                <div class="input-icon-wrap">
                  <i class="fas fa-envelope input-icon"></i>
                  <input type="email" name="email" class="form-control" value="<?= sanitize($student['email']) ?>" required>
                </div>
              </div>
              <div class="form-group">
                <label>Phone Number</label>
                <div class="input-icon-wrap">
                  <i class="fas fa-phone input-icon"></i>
                  <input type="text" name="phone" class="form-control" value="<?= sanitize($student['phone'] ?? '') ?>" placeholder="08012345678">
                </div>
              </div>
              <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-save"></i> Save Changes
              </button>
            </form>
          </div>
        </div>

        <!-- Change Password -->
        <div class="card">
          <div class="card-header"><h3>Change Password</h3></div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="action" value="change_password">
              <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
              </div>
              <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
              </div>
              <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-gold btn-full">
                <i class="fas fa-key"></i> Change Password
              </button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</div>
<script>
function updateClock(){document.getElementById('clock').textContent=new Date().toLocaleTimeString('en-NG',{hour12:false});}
updateClock();setInterval(updateClock,1000);
</script>
</body>
</html>
