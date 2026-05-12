<?php
require_once '../includes/config.php';
requireAdminLogin();
if ($_SESSION['admin_role'] !== 'superadmin') redirect(BASE_URL . 'admin/dashboard.php');

$db = getDB();
$currentPage = 'admins';
$pageTitle   = 'Admin Users';
$error = '';

// Add admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $fullName = sanitize($_POST['full_name']);
    $email    = sanitize($_POST['email']);
    $username = sanitize($_POST['username']);
    $role     = sanitize($_POST['role']);
    $pass     = password_hash(ADMIN_DEFAULT_PASSWORD, PASSWORD_BCRYPT);

    if (!$fullName || !$email || !$username) {
        $error = 'All fields are required.';
    } else {
        try {
            $db->prepare("INSERT INTO admins (full_name,email,username,password,role) VALUES (?,?,?,?,?)")
               ->execute([$fullName,$email,$username,$pass,$role]);
            flashMsg('success', "Admin <strong>$fullName</strong> created. Default password: " . ADMIN_DEFAULT_PASSWORD);
            redirect(BASE_URL . 'admin/admins.php');
        } catch (PDOException $e) {
            $error = 'Username or email already exists.';
        }
    }
}

// Toggle active
if (isset($_GET['toggle']) && (int)$_GET['toggle'] !== $_SESSION['admin_id']) {
    $db->prepare("UPDATE admins SET is_active = NOT is_active WHERE id=?")->execute([(int)$_GET['toggle']]);
    redirect(BASE_URL . 'admin/admins.php');
}

// Delete
if (isset($_GET['delete']) && (int)$_GET['delete'] !== $_SESSION['admin_id']) {
    $db->prepare("DELETE FROM admins WHERE id=?")->execute([(int)$_GET['delete']]);
    flashMsg('success','Admin deleted.'); redirect(BASE_URL.'admin/admins.php');
}

$admins = $db->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();
$flashSuccess = flashMsg('success');
include '../includes/admin_header.php';
?>

<?php if ($flashSuccess): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $flashSuccess ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start">

<!-- ADD FORM -->
<div class="card" style="position:sticky;top:80px">
  <div class="card-header"><h3>Add Admin User</h3></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="add_admin" value="1">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="full_name" class="form-control" placeholder="e.g. Dr. John Smith" required>
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" class="form-control" placeholder="admin@hukp.edu.ng" required>
      </div>
      <div class="form-group">
        <label>Username *</label>
        <input type="text" name="username" class="form-control" placeholder="e.g. john.smith" required>
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="role" class="form-control">
          <option value="lecturer">Lecturer</option>
          <option value="admin">Admin</option>
          <option value="superadmin">Super Admin</option>
        </select>
      </div>
      <div style="background:var(--gold-100);border:1px solid var(--gold-200);border-radius:6px;padding:10px;font-size:12px;color:var(--gold-600);margin-bottom:16px">
        <i class="fas fa-info-circle"></i> Default password: <strong><?= ADMIN_DEFAULT_PASSWORD ?></strong>
      </div>
      <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-user-plus"></i> Add Admin</button>
    </form>
  </div>
</div>

<!-- ADMIN LIST -->
<div class="card">
  <div class="card-header">
    <h3>All Admin Users</h3>
    <span class="badge badge-green"><?= count($admins) ?> users</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($admins as $i => $adm): ?>
        <tr>
          <td class="text-muted"><?= $i+1 ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div class="user-avatar" style="width:34px;height:34px;font-size:12px">
                <?= strtoupper(substr($adm['full_name'],0,1)) ?>
              </div>
              <div>
                <div style="font-weight:600"><?= sanitize($adm['full_name']) ?></div>
                <div class="text-sm text-muted"><?= sanitize($adm['email']) ?></div>
              </div>
            </div>
          </td>
          <td class="font-mono text-sm"><?= sanitize($adm['username']) ?></td>
          <td>
            <?php $rc = match($adm['role']) { 'superadmin'=>'badge-gold','admin'=>'badge-green',default=>'badge-blue' }; ?>
            <span class="badge <?= $rc ?>"><?= ucfirst($adm['role']) ?></span>
          </td>
          <td>
            <span class="badge <?= $adm['is_active']?'badge-green':'badge-red' ?>">
              <?= $adm['is_active']?'Active':'Inactive' ?>
            </span>
          </td>
          <td class="text-sm text-muted">
            <?= $adm['last_login'] ? timeAgo($adm['last_login']) : 'Never' ?>
          </td>
          <td>
            <?php if ($adm['id'] !== $_SESSION['admin_id']): ?>
            <div style="display:flex;gap:5px">
              <a href="?toggle=<?= $adm['id'] ?>" class="btn btn-outline btn-sm" title="Toggle">
                <i class="fas fa-toggle-<?= $adm['is_active']?'on':'off' ?>" style="color:<?= $adm['is_active']?'var(--green-500)':'var(--slate-400)' ?>"></i>
              </a>
              <a href="?delete=<?= $adm['id'] ?>" class="btn btn-danger btn-sm"
                 onclick="return confirm('Delete admin <?= sanitize($adm['full_name']) ?>?')">
                <i class="fas fa-trash"></i>
              </a>
            </div>
            <?php else: ?>
            <span class="badge badge-slate">You</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<?php include '../includes/admin_footer.php'; ?>
