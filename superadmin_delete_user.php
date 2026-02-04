<?php
require_once 'config.php';
header('Content-Type: application/json');

// Check if user is superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Prevent deleting self
if ($user_id === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
    exit;
}

// Delete the user and all associated data
$conn->begin_transaction();

try {
    // Delete orders and order items
    $get_orders = "SELECT order_id FROM orders WHERE user_id = ?";
    $stmt = $conn->prepare($get_orders);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($order = $result->fetch_assoc()) {
        $delete_items = "DELETE FROM order_items WHERE order_id = ?";
        $del_stmt = $conn->prepare($delete_items);
        $del_stmt->bind_param("i", $order['order_id']);
        $del_stmt->execute();
        $del_stmt->close();
    }
    $stmt->close();
    
    // Delete orders
    $delete_orders = "DELETE FROM orders WHERE user_id = ?";
    $stmt = $conn->prepare($delete_orders);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete user
    $delete_user = "DELETE FROM users WHERE user_id = ? AND role = 'customer'";
    $stmt = $conn->prepare($delete_user);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'User not found or not a customer']);
    }
    $stmt->close();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error deleting user']);
}

$conn->close();
?>
