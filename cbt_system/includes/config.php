<?php
// ============================================================
// HUKP CBT SYSTEM - Database Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hukp_cbt');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'HUKP CBT System');
define('SITE_TAGLINE', 'ICT Department - Hassan Usman Katsina Polytechnic');
define('BASE_URL', 'http://localhost/cbt_system/');
define('ADMIN_DEFAULT_PASSWORD', 'Admin@1234');
define('STUDENT_DEFAULT_PASSWORD', 'Student@123');

// Create PDO connection
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:40px;background:#fee2e2;border:1px solid #ef4444;border-radius:8px;max-width:600px;margin:100px auto;">
                <h2 style="color:#dc2626;margin:0 0 10px">Database Connection Failed</h2>
                <p style="color:#7f1d1d">Could not connect to MySQL. Please ensure:</p>
                <ul style="color:#7f1d1d">
                    <li>XAMPP/WAMP is running with MySQL active</li>
                    <li>You have imported <strong>hukp_cbt.sql</strong> into phpMyAdmin</li>
                    <li>DB credentials in <code>includes/config.php</code> are correct</li>
                </ul>
                <code style="display:block;background:#fca5a5;padding:10px;border-radius:4px;margin-top:10px;font-size:13px">' . htmlspecialchars($e->getMessage()) . '</code>
            </div>');
        }
    }
    return $pdo;
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Utility helpers ────────────────────────────────────────

function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never {
    header("Location: $url");
    exit;
}

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function isStudentLoggedIn(): bool {
    return isset($_SESSION['student_id']) && !empty($_SESSION['student_id']);
}

function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        redirect(BASE_URL . 'admin/login.php');
    }
}

function requireStudentLogin(): void {
    if (!isStudentLoggedIn()) {
        redirect(BASE_URL . 'student/login.php');
    }
}

function logActivity(string $userType, int $userId, string $action, string $details = ''): void {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $db->prepare("INSERT INTO activity_log (user_type, user_id, action, details, ip_address) VALUES (?,?,?,?,?)");
    $stmt->execute([$userType, $userId, $action, $details, $ip]);
}

function getGrade(float $percentage): string {
    if ($percentage >= 70) return 'A';
    if ($percentage >= 60) return 'B';
    if ($percentage >= 50) return 'C';
    if ($percentage >= 45) return 'D';
    if ($percentage >= 40) return 'E';
    return 'F';
}

function getGradeLabel(string $grade): string {
    return match($grade) {
        'A' => 'Distinction',
        'B' => 'Credit',
        'C' => 'Merit',
        'D' => 'Pass',
        'E' => 'Pass',
        'F' => 'Fail',
        default => 'Fail'
    };
}

function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hr ago';
    return date('M j, Y', $time);
}

function flashMsg(string $key, string $msg = ''): string {
    if ($msg !== '') {
        $_SESSION['flash'][$key] = $msg;
        return '';
    }
    $out = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $out;
}
