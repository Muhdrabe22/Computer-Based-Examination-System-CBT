<?php
// admin/logout.php
require_once '../includes/config.php';
if (isAdminLoggedIn()) {
    logActivity('admin', $_SESSION['admin_id'], 'Logout', 'Admin logged out');
}
session_destroy();
redirect(BASE_URL . 'admin/login.php');
