<?php
require 'config.php';
$conn->query('TRUNCATE TABLE login_attempts');
echo json_encode(['status' => 'Lockout table cleared']);
?>
