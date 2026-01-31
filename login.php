<?php
// IMPORTANT: session_start() MUST be called before ANY output
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
require_once 'config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

// Query user by email
$sql = "SELECT user_id, email, password_hash, first_name, last_name FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

// Verify password
if (password_verify($password, $user['password_hash'])) {
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'user_id' => $user['user_id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
}

$stmt->close();
$conn->close();
?>