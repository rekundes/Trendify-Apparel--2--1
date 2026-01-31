<?php
header('Content-Type: application/json');
session_start();

// Return current session status
echo json_encode([
    "session_id" => session_id(),
    "session_status" => session_status(),
    "session_data" => $_SESSION,
    "cookies_sent" => headers_sent(),
    "php_session_cookie_name" => session_name(),
    "test" => "Session debugging page"
]);
?>
