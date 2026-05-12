<?php
// ============================================================
// HUKP CBT SYSTEM — Password Reset Utility
// Place this file in: cbt_system/reset_password.php
// Visit: http://localhost/cbt_system/reset_password.php
// DELETE this file after use for security!
// ============================================================

require_once 'includes/config.php';

$message = '';
$messageType = '';
$accounts = [];

// Fetch all admins and students for display
try {
    $db = getDB();
    $admins   = $db->query("SELECT id, full_name, username, email, role, is_active FROM admins ORDER BY role, username")->fetchAll();
    $students = $db->query("SELECT id, full_name, reg_number, email, is_active FROM students ORDER BY full_name LIMIT 20")->fetchAll();
} catch (Exception $e) {
    $admins = [];
    $students = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action']      ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirm     = $_POST['confirm']      ?? '';

    if (empty($newPassword) || strlen($newPassword) < 6) {
        $message     = 'Password must be at least 6 characters.';
        $messageType = 'error';
    } elseif ($newPassword !== $confirm) {
        $message     = 'Passwords do not match.';
        $messageType = 'error';
    } else {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $db   = getDB();

        try {
            if ($action === 'reset_single_admin') {
                $adminId = (int)($_POST['admin_id'] ?? 0);
                $stmt = $db->prepare("UPDATE admins SET password=? WHERE id=?");
                $stmt->execute([$hash, $adminId]);
                $admin = $db->prepare("SELECT username FROM admins WHERE id=?");
                $admin->execute([$adminId]);
                $row = $admin->fetch();
                $message     = "✅ Password updated for admin: <strong>{$row['username']}</strong>. New password: <code>{$newPassword}</code>";
                $messageType = 'success';

            } elseif ($action === 'reset_all_admins') {
                $db->prepare("UPDATE admins SET password=?")->execute([$hash]);
                $count = $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
                $message     = "✅ Password reset for ALL <strong>{$count} admin accounts</strong>. New password: <code>{$newPassword}</code>";
                $messageType = 'success';

            } elseif ($action === 'reset_single_student') {
                $studentId = (int)($_POST['student_id'] ?? 0);
                $stmt = $db->prepare("UPDATE students SET password=? WHERE id=?");
                $stmt->execute([$hash, $studentId]);
                $student = $db->prepare("SELECT reg_number, full_name FROM students WHERE id=?");
                $student->execute([$studentId]);
                $row = $student->fetch();
                $message     = "✅ Password updated for student: <strong>{$row['full_name']} ({$row['reg_number']})</strong>. New password: <code>{$newPassword}</code>";
                $messageType = 'success';

            } elseif ($action === 'reset_all_students') {
                $db->prepare("UPDATE students SET password=?")->execute([$hash]);
                $count = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
                $message     = "✅ Password reset for ALL <strong>{$count} student accounts</strong>. New password: <code>{$newPassword}</code>";
                $messageType = 'success';

            } elseif ($action === 'fix_superadmin') {
                // Quick fix — resets only superadmin to Admin@1234
                $fixHash = password_hash('Admin@1234', PASSWORD_BCRYPT);
                $db->prepare("UPDATE admins SET password=?, is_active=1 WHERE username='superadmin'")->execute([$fixHash]);
                $message     = "✅ Superadmin password reset to <code>Admin@1234</code> and account activated.";
                $messageType = 'success';
            }

            // Refresh admin list
            $admins   = $db->query("SELECT id, full_name, username, email, role, is_active FROM admins ORDER BY role, username")->fetchAll();
            $students = $db->query("SELECT id, full_name, reg_number, email, is_active FROM students ORDER BY full_name LIMIT 20")->fetchAll();

        } catch (Exception $e) {
            $message     = '❌ Database error: ' . htmlspecialchars($e->getMessage());
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Password Reset — HUKP CBT</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: system-ui, sans-serif; background: #f1f5f9; color: #1e293b; min-height: 100vh; padding: 24px 16px; }
  .container { max-width: 900px; margin: 0 auto; }

  .header { background: linear-gradient(135deg, #1e40af, #7c3aed); color: #fff; padding: 28px 32px; border-radius: 16px; margin-bottom: 24px; }
  .header h1 { font-size: 22px; font-weight: 700; }
  .header p  { font-size: 14px; opacity: .8; margin-top: 6px; }

  .warning { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px; color: #92400e; }
  .warning strong { display: block; margin-bottom: 4px; font-size: 14px; }

  .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
  .alert.success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
  .alert.error   { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }

  .card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; overflow: hidden; }
  .card-header { background: #f8fafc; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 10px; }
  .card-header h2 { font-size: 15px; font-weight: 600; }
  .badge { font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 600; }
  .badge-blue { background: #dbeafe; color: #1d4ed8; }
  .badge-green { background: #dcfce7; color: #166534; }
  .card-body { padding: 20px; }

  .quick-fix { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; }
  .quick-fix p { font-size: 13px; color: #1e40af; }
  .quick-fix strong { display: block; font-size: 14px; margin-bottom: 2px; }

  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; }
  @media(max-width:600px) { .form-row { grid-template-columns: 1fr; } }
  label { display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .5px; }
  input[type=password], select { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; transition: border .2s; }
  input[type=password]:focus, select:focus { border-color: #6366f1; box-shadow: 0 0 0 3px #e0e7ff; }

  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: opacity .15s; }
  .btn:hover { opacity: .88; }
  .btn-primary { background: #4f46e5; color: #fff; }
  .btn-danger  { background: #dc2626; color: #fff; }
  .btn-success { background: #16a34a; color: #fff; }
  .btn-sm { padding: 6px 12px; font-size: 12px; }

  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { background: #f8fafc; padding: 10px 12px; text-align: left; font-weight: 600; color: #64748b; border-bottom: 1px solid #e2e8f0; }
  td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
  tr:last-child td { border-bottom: none; }
  .status { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 4px; }
  .status.active   { background: #22c55e; }
  .status.inactive { background: #ef4444; }

  .section-title { font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 14px; }
  hr { border: none; border-top: 1px solid #e2e8f0; margin: 20px 0; }
  code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
</style>
</head>
<body>
<div class="container">

  <div class="header">
    <h1>🔐 HUKP CBT — Password Reset Utility</h1>
    <p>Hassan Usman Katsina Polytechnic · ICT Department</p>
  </div>

  <div class="warning">
    <strong>⚠️ Security Notice</strong>
    Delete this file (<code>reset_password.php</code>) immediately after fixing your passwords. Anyone who can access this URL can reset all accounts.
  </div>

  <?php if ($message): ?>
  <div class="alert <?= $messageType ?>">
    <?= $message ?>
  </div>
  <?php endif; ?>

  <!-- QUICK FIX -->
  <div class="card">
    <div class="card-header">
      <h2>⚡ One-Click Fix</h2>
      <span class="badge badge-blue">Recommended</span>
    </div>
    <div class="card-body">
      <div class="quick-fix">
        <div>
          <strong>Reset superadmin to default password</strong>
          <p>Sets username: <code>superadmin</code> · password: <code>Admin@1234</code> · activates account</p>
        </div>
        <form method="POST">
          <input type="hidden" name="action" value="fix_superadmin">
          <input type="hidden" name="new_password" value="Admin@1234">
          <input type="hidden" name="confirm" value="Admin@1234">
          <button type="submit" class="btn btn-success">Fix Superadmin Now</button>
        </form>
      </div>
    </div>
  </div>

  <!-- RESET SINGLE ADMIN -->
  <div class="card">
    <div class="card-header">
      <h2>👤 Reset Single Admin Password</h2>
      <span class="badge badge-blue">Admin</span>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="reset_single_admin">
        <div class="form-row">
          <div>
            <label>Select Admin Account</label>
            <select name="admin_id" required>
              <option value="">— Choose admin —</option>
              <?php foreach ($admins as $a): ?>
              <option value="<?= $a['id'] ?>">
                <?= htmlspecialchars($a['username']) ?> (<?= $a['role'] ?>)
                <?= $a['is_active'] ? '' : ' [INACTIVE]' ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div></div>
          <div>
            <label>New Password</label>
            <input type="password" name="new_password" placeholder="Min 6 characters" required>
          </div>
          <div>
            <label>Confirm Password</label>
            <input type="password" name="confirm" placeholder="Repeat password" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Update Admin Password</button>
      </form>

      <hr>
      <p class="section-title">All Admin Accounts</p>
      <table>
        <thead><tr><th>Username</th><th>Full Name</th><th>Role</th><th>Email</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($admins as $a): ?>
          <tr>
            <td><strong><?= htmlspecialchars($a['username']) ?></strong></td>
            <td><?= htmlspecialchars($a['full_name']) ?></td>
            <td><span class="badge badge-blue"><?= $a['role'] ?></span></td>
            <td><?= htmlspecialchars($a['email']) ?></td>
            <td><span class="status <?= $a['is_active'] ? 'active' : 'inactive' ?>"></span><?= $a['is_active'] ? 'Active' : 'Inactive' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($admins)): ?>
          <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px">No admin accounts found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- RESET ALL ADMINS -->
  <div class="card">
    <div class="card-header">
      <h2>🔄 Reset ALL Admins to Same Password</h2>
      <span class="badge" style="background:#fee2e2;color:#dc2626">Bulk</span>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="reset_all_admins">
        <div class="form-row">
          <div>
            <label>New Password for All Admins</label>
            <input type="password" name="new_password" placeholder="Min 6 characters" required>
          </div>
          <div>
            <label>Confirm Password</label>
            <input type="password" name="confirm" placeholder="Repeat password" required>
          </div>
        </div>
        <button type="submit" class="btn btn-danger" onclick="return confirm('Reset ALL admin passwords?')">Reset All Admin Passwords</button>
      </form>
    </div>
  </div>

  <!-- RESET SINGLE STUDENT -->
  <?php if (!empty($students)): ?>
  <div class="card">
    <div class="card-header">
      <h2>🎓 Reset Single Student Password</h2>
      <span class="badge badge-green">Student</span>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="reset_single_student">
        <div class="form-row">
          <div>
            <label>Select Student Account</label>
            <select name="student_id" required>
              <option value="">— Choose student —</option>
              <?php foreach ($students as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= $s['reg_number'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div></div>
          <div>
            <label>New Password</label>
            <input type="password" name="new_password" placeholder="Min 6 characters" required>
          </div>
          <div>
            <label>Confirm Password</label>
            <input type="password" name="confirm" placeholder="Repeat password" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Update Student Password</button>
      </form>
    </div>
  </div>

  <!-- RESET ALL STUDENTS -->
  <div class="card">
    <div class="card-header">
      <h2>🔄 Reset ALL Students to Same Password</h2>
      <span class="badge" style="background:#fee2e2;color:#dc2626">Bulk</span>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="reset_all_students">
        <div class="form-row">
          <div>
            <label>New Password for All Students</label>
            <input type="password" name="new_password" placeholder="Min 6 characters" required>
          </div>
          <div>
            <label>Confirm Password</label>
            <input type="password" name="confirm" placeholder="Repeat password" required>
          </div>
        </div>
        <button type="submit" class="btn btn-danger" onclick="return confirm('Reset ALL student passwords?')">Reset All Student Passwords</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div style="text-align:center;padding:16px;font-size:12px;color:#94a3b8">
    HUKP CBT System · Password Reset Utility · Delete after use
  </div>

</div>
</body>
</html>
