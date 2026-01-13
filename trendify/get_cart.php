<?php
header('Content-Type: application/json');
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first', 'cart' => []]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Query cart items
$sql = "SELECT c.cart_id, c.product_id, c.size, c.quantity, 
               p.product_name, p.price, p.image_url
        FROM cart c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.user_id = ?
        ORDER BY c.added_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = array();
$total = 0;

while ($row = $result->fetch_assoc()) {
    $item_total = $row['price'] * $row['quantity'];
    $total += $item_total;
    
    $cart_items[] = array(
        'cart_id' => $row['cart_id'],
        'product_id' => $row['product_id'],
        'product_name' => $row['product_name'],
        'size' => $row['size'],
        'quantity' => $row['quantity'],
        'price' => $row['price'],
        'price_formatted' => '₱' . number_format($row['price'], 2),
        'item_total' => $item_total,
        'item_total_formatted' => '₱' . number_format($item_total, 2),
        'image_url' => $row['image_url']
    );
}

echo json_encode([
    'success' => true,
    'cart' => $cart_items,
    'total' => $total,
    'total_formatted' => '₱' . number_format($total, 2),
    'item_count' => count($cart_items)
]);

$stmt->close();
$conn->close();
?>