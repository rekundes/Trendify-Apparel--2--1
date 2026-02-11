<?php
require_once 'config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$out = ['success' => true, 'reviews' => []];

$stmt = $conn->prepare("SELECT product_name FROM product_reviews WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $out['reviews'][] = $r['product_name'];
    }
    $stmt->close();
}

echo json_encode($out);
?>
