<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
header('Cache-Control: no-cache, no-store, must-revalidate');
session_start();

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        "logged_in" => true,
        "user" => [
            "user_id" => $_SESSION['user_id'],
            "first_name" => $_SESSION['first_name'] ?? '',
            "email" => $_SESSION['email']
        ]
    ]);
} else {
    echo json_encode([
        "logged_in" => false
    ]);
}
?>
