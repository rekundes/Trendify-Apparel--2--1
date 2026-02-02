<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

// Create login_attempts table if missing
$create_table = "CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    failed_attempts INT DEFAULT 1,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_locked TINYINT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    UNIQUE KEY (email)
)";
$conn->query($create_table);

// Check if account is locked
$lock_sql = "SELECT is_locked, locked_until FROM login_attempts WHERE email = ?";
$lock_stmt = $conn->prepare($lock_sql);
$lock_stmt->bind_param("s", $email);
$lock_stmt->execute();
$lock_result = $lock_stmt->get_result();

$is_locked = false;
$time_left = 0;

if ($lock_result->num_rows > 0) {
    $lock_data = $lock_result->fetch_assoc();
    if ($lock_data['is_locked'] && $lock_data['locked_until']) {
        $now = new DateTime();
        $until = new DateTime($lock_data['locked_until']);
        if ($now < $until) {
            $is_locked = true;
            $time_left = $until->getTimestamp() - $now->getTimestamp();
        } else {
            $reset = "UPDATE login_attempts SET failed_attempts = 0, is_locked = 0, locked_until = NULL WHERE email = ?";
            $rs = $conn->prepare($reset);
            $rs->bind_param("s", $email);
            $rs->execute();
            $rs->close();
        }
    }
}
$lock_stmt->close();

if ($is_locked) {
    $time_left = min($time_left, 300); // Cap at 5 minutes (300 seconds)
    $mins = floor($time_left / 60);
    $secs = $time_left % 60;
    echo json_encode(['success' => false, 'message' => 'Account locked. Try again in ' . sprintf('%d:%02d', $mins, $secs)]);
    exit;
}

$sql = "SELECT user_id, email, password_hash, first_name, last_name, is_admin FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $ins = "INSERT INTO login_attempts (email, failed_attempts) VALUES (?, 1) ON DUPLICATE KEY UPDATE failed_attempts = failed_attempts + 1, last_attempt = NOW()";
    $ins_stmt = $conn->prepare($ins);
    $ins_stmt->bind_param("s", $email);
    $ins_stmt->execute();
    $ins_stmt->close();
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

if (password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['is_admin'] = $user['is_admin'];
    
    $clr = "UPDATE login_attempts SET failed_attempts = 0, is_locked = 0, locked_until = NULL WHERE email = ?";
    $clr_stmt = $conn->prepare($clr);
    $clr_stmt->bind_param("s", $email);
    $clr_stmt->execute();
    $clr_stmt->close();
    
    $redirect = $user['is_admin'] ? 'admin_dashboard.php' : 'main.html';
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirect,
        'is_admin' => $user['is_admin'],
        'user' => [
            'user_id' => $user['user_id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name']
        ]
    ]);
} else {
    $upd = "INSERT INTO login_attempts (email, failed_attempts) VALUES (?, 1) ON DUPLICATE KEY UPDATE failed_attempts = failed_attempts + 1, last_attempt = NOW()";
    $upd_stmt = $conn->prepare($upd);
    $upd_stmt->bind_param("s", $email);
    $upd_stmt->execute();
    $upd_stmt->close();
    
    $chk = "SELECT failed_attempts FROM login_attempts WHERE email = ?";
    $chk_stmt = $conn->prepare($chk);
    $chk_stmt->bind_param("s", $email);
    $chk_stmt->execute();
    $chk_res = $chk_stmt->get_result();
    $chk_data = $chk_res->fetch_assoc();
    $attempts = $chk_data['failed_attempts'];
    $chk_stmt->close();
    
    if ($attempts >= 3) {
        $lck = "UPDATE login_attempts SET is_locked = 1, locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE email = ?";
        $lck_stmt = $conn->prepare($lck);
        $lck_stmt->bind_param("s", $email);
        $lck_stmt->execute();
        $lck_stmt->close();
        echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Account locked for 5 minutes.']);
    } else {
        $left = 3 - $attempts;
        echo json_encode(['success' => false, 'message' => "Invalid email or password. Attempts left: $left"]);
    }
}

$stmt->close();
$conn->close();
?>