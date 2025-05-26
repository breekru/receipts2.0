<?php
// logout.php - Simple logout
require_once 'config.php';

// Clear session
session_unset();
session_destroy();

// Start new session for flash message
session_start();

redirect('login.php', 'You have been logged out successfully.', 'success');
?>