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

if (!isset($data['order_id']) || empty($data['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

$order_id = intval($data['order_id']);

// Delete order items first
$delete_items_sql = "DELETE FROM order_items WHERE order_id = ?";
$stmt = $conn->prepare($delete_items_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$stmt->close();

// Delete the order
$delete_order_sql = "DELETE FROM orders WHERE order_id = ?";
$stmt = $conn->prepare($delete_order_sql);
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
