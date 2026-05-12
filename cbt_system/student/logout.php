<?php
require_once '../includes/config.php';
if (isStudentLoggedIn()) {
    logActivity('student', $_SESSION['student_id'], 'Logout', 'Student logged out');
}
session_destroy();
redirect(BASE_URL . 'student/login.php');
