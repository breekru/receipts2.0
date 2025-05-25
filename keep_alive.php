<?php
// keep_alive.php - Keep session alive endpoint
require_once 'config.php';

header('Content-Type: application/json');

if (Utils::isLoggedIn()) {
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'user_id' => Utils::getCurrentUserId()
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Session expired'
    ]);
}
?>