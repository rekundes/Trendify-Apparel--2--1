<?php
require_once 'config.php';

// Check if the is_admin column exists, if not add it
$check_column = "SHOW COLUMNS FROM users LIKE 'is_admin'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    // Add is_admin column if it doesn't exist
    $alter_sql = "ALTER TABLE users ADD COLUMN is_admin TINYINT DEFAULT 0";
    if ($conn->query($alter_sql) === TRUE) {
        echo "Column 'is_admin' added successfully.<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
}

// Check if admin exists
$admin_email = 'admin@trendify.com';
$check_admin = "SELECT user_id FROM users WHERE email = ?";
$stmt = $conn->prepare($check_admin);
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Admin account already exists.<br>";
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Create admin account
$admin_password = 'admin123'; // Change this to a secure password
$password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
$first_name = 'Admin';
$last_name = 'User';
$is_admin = 1;

$insert_sql = "INSERT INTO users (email, password_hash, first_name, last_name, is_admin) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_sql);
$stmt->bind_param("ssssi", $admin_email, $password_hash, $first_name, $last_name, $is_admin);

if ($stmt->execute()) {
    echo "Admin account created successfully!<br>";
    echo "Email: " . $admin_email . "<br>";
    echo "Password: " . $admin_password . "<br>";
    echo "<br><strong>Please change the password after first login!</strong>";
} else {
    echo "Error creating admin account: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
