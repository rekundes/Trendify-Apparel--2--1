<?php
session_start();
include "config.php"; // your db connection

header('Content-Type: application/json');

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id']);

if ($id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid ID"]);
    exit;
}

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("DELETE FROM cart WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}

$stmt->close();
$conn->close();
?>
