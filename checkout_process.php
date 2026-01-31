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

// Create orders table if it doesn't exist
$create_orders_table = "CREATE TABLE IF NOT EXISTS orders (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT DEFAULT 0,
  first_name VARCHAR(255) NOT NULL,
  last_name VARCHAR(255),
  email VARCHAR(255) NOT NULL,
  phone_prefix VARCHAR(10),
  phone_number VARCHAR(20),
  country VARCHAR(100),
  address VARCHAR(500),
  city VARCHAR(100),
  postcode VARCHAR(20),
  subtotal DECIMAL(10,2),
  delivery_fee DECIMAL(10,2),
  total DECIMAL(10,2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$conn->query($create_orders_table);

// Create order_items table if it doesn't exist
$create_items_table = "CREATE TABLE IF NOT EXISTS order_items (
  id INT PRIMARY KEY AUTO_INCREMENT,
  order_id INT NOT NULL,
  product_name VARCHAR(255),
  product_price DECIMAL(10,2),
  quantity INT DEFAULT 1,
  size VARCHAR(50),
  img_url VARCHAR(500),
  FOREIGN KEY (order_id) REFERENCES orders(id)
)";

$conn->query($create_items_table);

// Insert order
$order_sql = "INSERT INTO orders 
    (user_id, first_name, last_name, email, phone_prefix, phone_number, country, address, city, postcode, subtotal, delivery_fee, total)
    VALUES ($user_id,'$firstName','$lastName','$email','$phoneCode','$phone','$country','$address1','$city','$postcode',$subtotal,$delivery,$total)";

if ($conn->query($order_sql)) {
    $order_id = $conn->insert_id;

    // Insert items
    if (is_array($data['items']) && count($data['items']) > 0) {
        foreach ($data['items'] as $item) {
            $name = $conn->real_escape_string($item['name'] ?? 'Unknown Product');
            $price = floatval($item['price'] ?? 0);
            $qty = intval($item['qty'] ?? $item['quantity'] ?? 1);
            $size = $conn->real_escape_string($item['size'] ?? '');
            $img = $conn->real_escape_string($item['img'] ?? '');

            $item_sql = "INSERT INTO order_items 
                (order_id, product_name, product_price, quantity, size, img_url)
                VALUES ($order_id,'$name',$price,$qty,'$size','$img')";
            
            $conn->query($item_sql);

            // Remove item from cart if it has an ID
            if (isset($item['id'])) {
                $cart_id = intval($item['id']);
                $conn->query("DELETE FROM cart WHERE id = $cart_id");
            }
        }
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Order placed successfully',
        'order_id' => $order_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}
?>
