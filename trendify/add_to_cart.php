<?php
require "config.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$name  = $conn->real_escape_string($data['name']);
$price = floatval(str_replace(["₱",","], "", $data['price']));
$img   = $conn->real_escape_string($data['img']);
$size  = $conn->real_escape_string($data['size']);
$qty   = intval($data['qty']);
$user  = $_SESSION['user_id'];

/* Check if same product+size already exists */
$check = $conn->prepare("
  SELECT id, quantity 
  FROM cart 
  WHERE user_id=? AND product_name=? AND size=?
");
$check->bind_param("iss", $user, $name, $size);
$check->execute();
$result = $check->get_result();

if ($row = $result->fetch_assoc()) {
    // Update quantity
    $newQty = $row['quantity'] + $qty;
    $update = $conn->prepare("UPDATE cart SET quantity=? WHERE id=?");
    $update->bind_param("ii", $newQty, $row['id']);
    $update->execute();
} else {
    // Insert new
    $insert = $conn->prepare("
      INSERT INTO cart (user_id, product_name, price, image, size, quantity)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param("isdssi", $user, $name, $price, $img, $size, $qty);
    $insert->execute();
}

echo json_encode(["success" => true]);
?>
