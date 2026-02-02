<?php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || empty($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$user_id = intval($data['user_id']);

// Prevent deleting self
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
    exit;
}

// Delete user's orders and order items first
$delete_items_sql = "DELETE FROM order_items WHERE order_id IN (SELECT order_id FROM orders WHERE user_id = ?)";
$stmt = $conn->prepare($delete_items_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// Delete user's orders
$delete_orders_sql = "DELETE FROM orders WHERE user_id = ?";
$stmt = $conn->prepare($delete_orders_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// Delete the user
$delete_user_sql = "DELETE FROM users WHERE user_id = ?";
$stmt = $conn->prepare($delete_user_sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
