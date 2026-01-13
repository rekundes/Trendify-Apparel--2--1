<?php
require 'config.php'; // your DB connection file

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Validate required fields
$required = ['firstName','lastName','email','phoneCode','phone','address1','city','postcode','items'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Field $field is required"]);
        exit;
    }
}

$firstName = $conn->real_escape_string($data['firstName']);
$lastName  = $conn->real_escape_string($data['lastName']);
$email     = $conn->real_escape_string($data['email']);
$phoneCode = $conn->real_escape_string($data['phoneCode']);
$phone     = $conn->real_escape_string($data['phone']);
$country   = $conn->real_escape_string($data['country'] ?? 'Philippines');
$address1  = $conn->real_escape_string($data['address1']);
$city      = $conn->real_escape_string($data['city']);
$district  = $conn->real_escape_string($data['district'] ?? '');
$region    = $conn->real_escape_string($data['region'] ?? '');
$postcode  = $conn->real_escape_string($data['postcode']);

// Prices
$subtotal = floatval($data['subtotal'] ?? 0);
$delivery = floatval($data['delivery'] ?? 0);
$total    = floatval($data['total'] ?? 0);

// Insert order
$order_sql = "INSERT INTO orders 
    (first_name, last_name, email, phone_prefix, phone_number, country, address, city, district, region, postcode, subtotal, delivery_fee, total)
    VALUES ('$firstName','$lastName','$email','$phoneCode','$phone','$country','$address1','$city','$district','$region','$postcode','$subtotal','$delivery','$total')";

if ($conn->query($order_sql)) {
    $order_id = $conn->insert_id;

    // Insert items
    foreach ($data['items'] as $item) {
        $name = $conn->real_escape_string($item['name']);
        $price = floatval($item['price']);
        $qty = intval($item['qty'] ?? 1);
        $size = $conn->real_escape_string($item['size'] ?? '');
        $img = $conn->real_escape_string($item['img'] ?? '');

        $item_sql = "INSERT INTO order_items 
            (order_id, product_name, product_price, quantity, size, img_url)
            VALUES ('$order_id','$name','$price','$qty','$size','$img')";
        $conn->query($item_sql);

        // ✅ Remove item from cart if it has an ID
        if (isset($item['id'])) {
            $cart_id = intval($item['id']);
            $conn->query("DELETE FROM cart WHERE id = $cart_id");
        }
    }

    echo json_encode(['success' => true, 'message' => 'Order placed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
?>
