<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require 'config.php';

$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Delete order items first (FK constraint)
    $deleteItems = "DELETE FROM order_items WHERE order_id IN (SELECT order_id FROM orders WHERE user_id = ?)";
    $stmt = $conn->prepare($deleteItems);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Delete orders
    $deleteOrders = "DELETE FROM orders WHERE user_id = ?";
    $stmt = $conn->prepare($deleteOrders);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Delete user
    $deleteUser = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($deleteUser);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    // Destroy session
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
