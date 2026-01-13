<?php
session_start();
include "config.php";

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id']);
$qty = intval($data['quantity']);

if ($id <= 0 || $qty < 1) {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
    exit;
}

$stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
$stmt->bind_param("ii", $qty, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}

$stmt->close();
$conn->close();
?>
