<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get product ID from URL parameter
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id === 0) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

// Query to get available sizes for the product
$sql = "SELECT size, stock_quantity 
        FROM product_sizes 
        WHERE product_id = ? AND stock_quantity > 0
        ORDER BY 
            CASE 
                WHEN size = 'S' THEN 1
                WHEN size = 'M' THEN 2
                WHEN size = 'L' THEN 3
                WHEN size = 'XL' THEN 4
                WHEN size = 'One Size' THEN 5
                ELSE 6
            END";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

$sizes = array();

while ($row = $result->fetch_assoc()) {
    $sizes[] = array(
        'size' => $row['size'],
        'stock' => $row['stock_quantity']
    );
}

echo json_encode($sizes);

$stmt->close();
$conn->close();
?>