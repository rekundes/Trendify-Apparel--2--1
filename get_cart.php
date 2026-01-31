<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
session_start();
require "config.php";

// If not logged in, return empty cart (guests can see localStorage items)
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => true,
        "items" => [],
        "item_count" => 0
    ]);
    exit;
}

$user = $_SESSION['user_id'];

$sql = "SELECT * FROM cart WHERE user_id = $user ORDER BY added_at DESC";
$result = $conn->query($sql);

$cart = [];
$item_count = 0;

while ($row = $result->fetch_assoc()) {
    $item_count += $row['quantity'];
    $cart[] = [
        "id" => $row["id"],
        "name" => $row["product_name"],
        "price" => "₱" . number_format($row["price"], 0),
        "img" => $row["image"],
        "size" => $row["size"],
        "quantity" => $row["quantity"]
    ];
}

echo json_encode([
    "success" => true,
    "items" => $cart,
    "item_count" => $item_count
]);
