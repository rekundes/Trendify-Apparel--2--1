<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$first_name = isset($input['first_name']) ? trim($input['first_name']) : '';
$last_name = isset($input['last_name']) ? trim($input['last_name']) : '';

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
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
    $conn->close();
    exit;
}
$check_stmt->close();

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$insert_sql = "INSERT INTO users (email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?)";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("ssss", $email, $password_hash, $first_name, $last_name);

if ($insert_stmt->execute()) {
    $user_id = $insert_stmt->insert_id;
    
    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    $_SESSION['first_name'] = $first_name;
    
    echo json_encode([
        'success' => true, 
        'message' => 'Registration successful',
        'user' => [
            'user_id' => $user_id,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}

$insert_stmt->close();
$conn->close();
?>