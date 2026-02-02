<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
session_start();
require 'config.php'; // your DB connection file

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Validate required fields
$required = ['firstName','email','phone','address1','city','postcode','items'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Field $field is required"]);
        exit;
    }
}

// Escape and prepare data
$firstName = $conn->real_escape_string($data['firstName']);
$lastName  = $conn->real_escape_string($data['lastName'] ?? '');
$email     = $conn->real_escape_string($data['email']);
$phoneCode = $conn->real_escape_string($data['phoneCode'] ?? '+63');
$phone     = $conn->real_escape_string($data['phone']);
$country   = $conn->real_escape_string($data['country'] ?? 'Philippines');
$address1  = $conn->real_escape_string($data['address1']);
$city      = $conn->real_escape_string($data['city']);
$postcode  = $conn->real_escape_string($data['postcode']);

// Prices
$subtotal = floatval($data['subtotal'] ?? 0);
$delivery = floatval($data['delivery'] ?? 0);
$total    = floatval($data['total'] ?? 0);

// Get user_id from session if logged in
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Use prepared statements to prevent SQL injection
$shipping_address = "Address: $address1, City: $city, Postal: $postcode, Country: $country";

// Insert order with customer information
$order_sql = "INSERT INTO orders 
    (user_id, first_name, last_name, email, phone, city, postcode, status, total_amount, shipping_address, order_date)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'Processing', ?, ?, NOW())";

$stmt = $conn->prepare($order_sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("issssssds", $user_id, $firstName, $lastName, $email, $phone, $city, $postcode, $total, $shipping_address);

if ($stmt->execute()) {
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Insert items
    if (is_array($data['items']) && count($data['items']) > 0) {
        $item_sql = "INSERT INTO order_items 
            (order_id, product_name, price, quantity, product_size)
            VALUES (?, ?, ?, ?, ?)";
        $item_stmt = $conn->prepare($item_sql);
        
        foreach ($data['items'] as $item) {
            $name = $item['name'] ?? 'Unknown Product';
            $price = floatval($item['price'] ?? 0);
            $qty = intval($item['qty'] ?? $item['quantity'] ?? 1);
            $size = $item['size'] ?? '';
            
            $item_stmt->bind_param("isids", $order_id, $name, $price, $qty, $size);
            $item_stmt->execute();

            // Remove item from cart if it has an ID
            if (isset($item['id'])) {
                $cart_id = intval($item['id']);
                $conn->query("DELETE FROM cart WHERE id = $cart_id");
            }
        }
        $item_stmt->close();
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Order placed successfully',
        'order_id' => $order_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}
$conn->close();
?>
