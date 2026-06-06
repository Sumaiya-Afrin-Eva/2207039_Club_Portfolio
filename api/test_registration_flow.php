<?php
/**
 * test_registration_flow.php - Complete registration flow test
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';

header('Content-Type: application/json');

$results = [];

// Test 1: Database connection
try {
    $conn = get_db_connection();
    $results['database_connection'] = 'OK';
} catch (Exception $e) {
    $results['database_connection'] = 'FAILED: ' . $e->getMessage();
}

// Test 2: Check tables exist
$tables = ['users', 'events', 'registrations'];
$results['tables'] = [];
foreach ($tables as $table) {
    $query = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'club_portfolio' AND TABLE_NAME = '$table'";
    $result = $conn->query($query);
    $results['tables'][$table] = $result->num_rows > 0 ? 'EXISTS' : 'MISSING';
}

// Test 3: Check events
$events = db_select("SELECT event_id, title, capacity FROM events LIMIT 1", []);
$results['events_found'] = count($events);
if (count($events) > 0) {
    $results['sample_event'] = $events[0];
}

// Test 4: Check users table
$users = db_select("SELECT user_id, email, role FROM users LIMIT 3", []);
$results['users_count'] = count($users);
$results['sample_users'] = $users;

// Test 5: Try a registration flow
if (count($events) > 0) {
    $test_event_id = (int)$events[0]['event_id'];
    $test_email = 'test_' . time() . '@example.com';
    
    // Test creating user
    $user_query = "INSERT INTO users (email, full_name, phone, role, is_active) VALUES (?, ?, ?, 'member', 1)";
    $test_user_id = db_insert($user_query, [
        $test_email,
        'Test Registration User',
        '01712345678'
    ]);
    
    $results['test_user_creation'] = [
        'success' => $test_user_id ? true : false,
        'user_id' => $test_user_id
    ];
    
    if ($test_user_id) {
        // Test creating registration
        $reg_query = "INSERT INTO registrations (event_id, user_id, status) VALUES (?, ?, 'registered')";
        $test_reg_id = db_insert($reg_query, [
            $test_event_id,
            $test_user_id
        ]);
        
        $results['test_registration_creation'] = [
            'success' => $test_reg_id ? true : false,
            'registration_id' => $test_reg_id
        ];
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
?>
