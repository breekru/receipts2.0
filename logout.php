<?php
// logout.php - User Logout
require_once 'config.php';

// Perform logout
$userManager->logout();

// Redirect to login page with success message
Utils::redirect('login.php', 'You have been logged out successfully.', 'success');
?>