<?php
/**
 * Debug script to check session and auth
 */
header('Content-Type: application/json');

// Print session info
$output = [
    'session_status' => session_status(),
    'PHP_SESSION_NONE' => PHP_SESSION_NONE,
    'PHP_SESSION_ACTIVE' => PHP_SESSION_ACTIVE,
    'PHP_SESSION_DISABLED' => PHP_SESSION_DISABLED,
];

// Start session to check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$output['session_started'] = true;
$output['session_id'] = session_id();
$output['_SESSION'] = $_SESSION;
$output['cookies'] = $_COOKIE;
$output['headers'] = getallheaders() ?: $_SERVER;

// Also check the direct API check
require_once __DIR__ . '/db_config.php';
try {
    $conn = get_db_connection();
    if ($conn) {
        $output['db_connected'] = true;
    } else {
        $output['db_error'] = 'Failed to connect';
    }
} catch (Exception $e) {
    $output['db_error'] = $e->getMessage();
}

echo json_encode($output, JSON_PRETTY_PRINT);
?>