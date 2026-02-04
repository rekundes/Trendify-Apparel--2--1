<?php
require_once 'config.php';
header('Content-Type: application/json');

// Check if user is superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$first_name = isset($input['first_name']) ? trim($input['first_name']) : '';
$last_name = isset($input['last_name']) ? trim($input['last_name']) : '';

// Validate input
if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit;
}

// Check if email already exists
$check_sql = "SELECT user_id FROM users WHERE email = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    $check_stmt->close();
    exit;
}
$check_stmt->close();

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert new admin
$insert_sql = "INSERT INTO users (email, password_hash, first_name, last_name, is_admin, role) 
               VALUES (?, ?, ?, ?, 1, 'admin')";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("ssss", $email, $password_hash, $first_name, $last_name);

if ($insert_stmt->execute()) {
    $user_id = $insert_stmt->insert_id;
    echo json_encode([
        'success' => true,
        'message' => 'Admin account created successfully',
        'user' => [
            'user_id' => $user_id,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => 'admin'
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error creating admin account']);
}

$insert_stmt->close();
$conn->close();
?>
