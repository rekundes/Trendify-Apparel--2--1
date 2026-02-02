<?php
require_once 'config.php';
$conn->query('TRUNCATE TABLE login_attempts');
echo 'Login attempts cleared. Ready for fresh testing.';
?>
