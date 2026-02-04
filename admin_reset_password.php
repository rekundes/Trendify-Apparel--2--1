<?php
require_once 'config.php';
header('Content-Type: application/json');

// Only admins can reset user passwords
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user id']);
    exit;
}

// Prevent non-superadmin from resetting a superadmin's password
$check = $conn->prepare("SELECT user_id, email, role FROM users WHERE user_id = ?");
$check->bind_param("i", $user_id);
$check->execute();
$res = $check->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}
$user = $res->fetch_assoc();
$check->close();

if (isset($user['role']) && $user['role'] === 'superadmin' && ($_SESSION['role'] ?? '') !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Cannot reset a superadmin password']);
    exit;
}

// Generate a secure temporary password (10 chars)
try {
    $bytes = random_bytes(6);
    $temp_password = substr(bin2hex($bytes), 0, 10);
} catch (Exception $e) {
    $temp_password = substr(md5(uniqid('', true)), 0, 10);
}

$password_hash = password_hash($temp_password, PASSWORD_DEFAULT);

$update = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
$update->bind_param("si", $password_hash, $user_id);
if ($update->execute()) {
    echo json_encode(['success' => true, 'temp_password' => $temp_password, 'email' => $user['email']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
$update->close();
$conn->close();
?>