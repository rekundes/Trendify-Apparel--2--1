<?php
require "config.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user = $_SESSION['user_id'];

$sql = "SELECT * FROM cart WHERE user_id = $user ORDER BY created_at DESC";
$result = $conn->query($sql);

$cart = [];

while ($row = $result->fetch_assoc()) {
    $cart[] = [
        "id" => $row["id"],
        "name" => $row["product_name"],
        "price" => $row["price"],
        "img" => $row["image"],
        "size" => $row["size"],
        "quantity" => $row["quantity"]
    ];
}

echo json_encode($cart);
?>
