<?php
/**
 * Session test - verify if sessions work across requests
 */
header('Content-Type: application/json');

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the action from query string
$action = $_GET['action'] ?? 'check';

if ($action === 'set') {
    // Set some session data
    $_SESSION['test_key'] = 'test_value_' . time();
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Session data set',
        'session_id' => session_id(),
        'data' => $_SESSION
    ]);
} else if ($action === 'check') {
    // Check if session data exists
    echo json_encode([
        'status' => 'success',
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'test_key' => $_SESSION['test_key'] ?? null,
        'all_session_data' => $_SESSION
    ]);
} else if ($action === 'clear') {
    // Clear session
    session_destroy();
    echo json_encode([
        'status' => 'success',
        'message' => 'Session cleared'
    ]);
}
?>
