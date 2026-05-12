<?php
require_once '../includes/config.php';
requireAdminLogin();
$db = getDB();
$currentPage = 'profile';
$pageTitle   = 'My Profile';
$adminId     = $_SESSION['admin_id'];

$admin = $db->prepare("SELECT * FROM admins WHERE id=?");
$admin->execute([$adminId]);
$admin = $admin->fetch();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = sanitize($_POST['full_name']);
        $email    = sanitize($_POST['email']);
        try {
            $db->prepare("UPDATE admins SET full_name=?, email=? WHERE id=?")->execute([$fullName,$email,$adminId]);
            $_SESSION['admin_name'] = $fullName;
            $admin['full_name'] = $fullName;
            $admin['email']     = $email;
            $success = 'Profile updated successfully.';
        } catch (PDOException $e) {
            $error = 'Email already in use.';
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'];
        $new     = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        if (!password_verify($current, $admin['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $db->prepare("UPDATE admins SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_BCRYPT),$adminId]);
            $success = 'Password changed successfully.';
            logActivity('admin',$adminId,'Password Change','Admin changed password');
        }
    }
}

$initials = strtoupper(implode('', array_map(fn($w)=>$w[0], array_slice(explode(' ',$admin['full_name']),0,2))));
include '../includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:280px 1fr;gap:24px;max-width:860px;align-items:start">

  <!-- Avatar card -->
  <div class="card">
    <div class="card-body" style="text-align:center;padding:32px 24px">
      <div class="user-avatar gold" style="width:80px;height:80px;font-size:28px;margin:0 auto 16px"><?= $initials ?></div>
      <h3 style="font-family:var(--font-display);color:var(--green-800);font-size:18px;margin-bottom:4px"><?= sanitize($admin['full_name']) ?></h3>
      <span class="badge badge-gold"><?= ucfirst($admin['role']) ?></span>
      <div style="margin-top:20px;display:flex;flex-direction:column;gap:10px;text-align:left">
        <?php $fields = [
          ['fas fa-at','Username',$admin['username']],
          ['fas fa-envelope','Email',$admin['email']],
          ['fas fa-calendar','Member Since',date('M j, Y',strtotime($admin['created_at']))],
          ['fas fa-clock','Last Login',$admin['last_login']?date('M j, Y g:i A',strtotime($admin['last_login'])):'Never'],
        ]; ?>
        <?php foreach ($fields as [$icon,$label,$val]): ?>
        <div style="padding:10px 12px;background:var(--slate-50);border-radius:8px;border:1px solid var(--slate-200)">
          <div style="font-size:11px;color:var(--slate-400);letter-spacing:.06em;text-transform:uppercase"><i class="<?= $icon ?>"></i> <?= $label ?></div>
          <div style="font-size:13px;font-weight:600;color:var(--slate-700);margin-top:2px"><?= sanitize($val) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:20px">
    <!-- Update Info -->
    <div class="card">
      <div class="card-header"><h3>Update Profile</h3></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_profile">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" value="<?= sanitize($admin['full_name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control" value="<?= sanitize($admin['email']) ?>" required>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
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
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group">
              <label>New Password</label>
              <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>
            <div class="form-group">
              <label>Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" required>
            </div>
          </div>
          <button type="submit" class="btn btn-gold"><i class="fas fa-key"></i> Change Password</button>
        </form>
      </div>
    </div>
  </div>

</div>

<?php include '../includes/admin_footer.php'; ?>
