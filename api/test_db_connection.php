<?php
/**
 * test_db_connection.php - Database Connection Test
 * 
 * This file tests the MySQL database connection and displays connection status.
 * Run this to verify the database is properly configured.
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';

header('Content-Type: application/json');

try {
    // Test database connection
    $conn = get_db_connection();
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Get database name
    $db_name = DB_NAME;
    
    // Test query - count events
    $events = get_all_events();
    $event_count = count($events ?? []);
    
    // Test query - count users
    $users = db_select("SELECT COUNT(*) as total FROM users");
    $user_count = $users[0]['total'] ?? 0;
    
    // Test query - count registrations
    $regs = db_select("SELECT COUNT(*) as total FROM registrations");
    $reg_count = $regs[0]['total'] ?? 0;
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection successful',
        'details' => [
            'host' => DB_HOST,
            'database' => $db_name,
            'events' => $event_count,
            'users' => $user_count,
            'registrations' => $reg_count
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection test failed',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
