<?php
header('Content-Type: application/json');

// Get raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Log the received data for debugging
error_log("Received address data: " . print_r($data, true));

// --- Validate required fields ---
$requiredFields = ['user_id', 'first_name', 'last_name', 'email', 'mobile', 'address1', 'postcode', 'region', 'city'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: ' . implode(', ', $missingFields)
    ]);
    exit;
}

// --- Database connection ---
$servername = "localhost";
$username = "root";       // default XAMPP user
$password = "";           // default XAMPP password
$dbname = "trendify_db";  // <-- your actual DB name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("DB connection failed: " . $conn->connect_error);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// --- Prepare SQL statement ---
$stmt = $conn->prepare("INSERT INTO addresses 
    (user_id, first_name, last_name, email, mobile, address1, address2, postcode, region, city)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

// Bind parameters
$stmt->bind_param(
    "isssssssss",
    $data['user_id'],
    $data['first_name'],
    $data['last_name'],
    $data['email'],
    $data['mobile'],
    $data['address1'],
    $data['address2'] ?? '', // optional
    $data['postcode'],
    $data['region'],
    $data['city']
);

// Execute statement
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    error_log("DB insert failed: " . $stmt->error);
    echo json_encode(['success' => false, 'error' => 'Could not save address: ' . $stmt->error]);
}

// Close connections
$stmt->close();
$conn->close();
?>
