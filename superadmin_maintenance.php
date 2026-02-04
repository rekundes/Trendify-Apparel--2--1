<?php
require_once 'config.php';
header('Content-Type: application/json');

// Check if user is superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = isset($input['action']) ? $input['action'] : '';

if ($action === 'clear_lockouts') {
    $sql = "UPDATE login_attempts SET is_locked = 0, locked_until = NULL, failed_attempts = 0";
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'All account lockouts cleared']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error clearing lockouts']);
    }
} elseif ($action === 'truncate_logins') {
    $sql = "TRUNCATE TABLE login_attempts";
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'All login attempts cleared']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error clearing login attempts']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>
