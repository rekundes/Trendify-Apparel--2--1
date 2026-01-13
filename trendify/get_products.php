<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get category from URL parameter
$category = isset($_GET['category']) ? $_GET['category'] : 'shirts';

// Sanitize input
$category = $conn->real_escape_string($category);

// Query to get products by category
$sql = "SELECT p.product_id, p.product_name, p.price, p.image_url, 
               p.secondary_image_url, p.is_new, p.stock_quantity
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        WHERE c.category_name = ?
        ORDER BY p.product_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();

$products = array();

while ($row = $result->fetch_assoc()) {
    $products[] = array(
        'id' => $row['product_id'],
        'name' => $row['product_name'],
        'price' => '₱' . number_format($row['price'], 0),
        'img' => $row['image_url'],
        'secondaryImg' => $row['secondary_image_url'],
        'isNew' => (bool)$row['is_new'],
        'stock' => $row['stock_quantity']
    );
}

echo json_encode($products);

$stmt->close();
$conn->close();
?>