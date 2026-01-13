<?php
require_once 'config.php';

if ($conn->connect_error) {
    echo "❌ Connection failed: " . $conn->connect_error;
} else {
    echo "✅ Database connected successfully!<br>";
    echo "Database name: " . DB_NAME . "<br>";
    
    // Test query
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Products in database: " . $row['count'];
    }
}
?>
```