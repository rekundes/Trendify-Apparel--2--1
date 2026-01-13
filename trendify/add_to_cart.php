<?php
header('Content-Type: application/json');
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$product_id = isset($input['product_id']) ? intval($input['product_id']) : 0;
$size = isset($input['size']) ? trim($input['size']) : '';
$quantity = isset($input['quantity']) ? intval($input['quantity']) : 1;
$user_id = $_SESSION['user_id'];

// Validate input
if ($product_id === 0 || empty($size) || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid product data']);
    exit;
}

// Check if product exists and has stock
$check_sql = "SELECT ps.stock_quantity, p.product_name, p.price 
              FROM product_sizes ps
              JOIN products p ON ps.product_id = p.product_id
              WHERE ps.product_id = ? AND ps.size = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $product_id, $size);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product or size not found']);
    $check_stmt->close();
    $conn->close();
    exit;
}

$product_data = $check_result->fetch_assoc();

if ($product_data['stock_quantity'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
    $check_stmt->close();
    $conn->close();
    exit;
}
$check_stmt->close();

// Check if item already in cart
$cart_check_sql = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND size = ?";
$cart_check_stmt = $conn->prepare($cart_check_sql);
$cart_check_stmt->bind_param("iis", $user_id, $product_id, $size);
$cart_check_stmt->execute();
$cart_result = $cart_check_stmt->get_result();

if ($cart_result->num_rows > 0) {
    // Update existing cart item
    $cart_item = $cart_result->fetch_assoc();
    $new_quantity = $cart_item['quantity'] + $quantity;
    
    $update_sql = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $new_quantity, $cart_item['cart_id']);
    $update_stmt->execute();
    $update_stmt->close();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cart updated successfully',
        'product_name' => $product_data['product_name']
    ]);
} else {
    // Insert new cart item
    $insert_sql = "INSERT INTO cart (user_id, product_id, size, quantity) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iisi", $user_id, $product_id, $size, $quantity);
    
    if ($insert_stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Added to cart successfully',
            'product_name' => $product_data['product_name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
    }
    $insert_stmt->close();
}

$cart_check_stmt->close();
$conn->close();
?>