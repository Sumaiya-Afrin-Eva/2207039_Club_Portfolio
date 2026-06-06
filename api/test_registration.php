<?php
/**
 * test_registration.php - Debug registration endpoint
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';

header('Content-Type: application/json');

// Test 1: Check database connection
$conn = get_db_connection();
if (!$conn) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Test 2: Check if events table has data
$events = db_select("SELECT event_id, title, capacity FROM events LIMIT 3", []);
$test_result = [
    'database_connected' => true,
    'events_exist' => count($events) > 0,
    'sample_events' => $events
];

if (count($events) > 0) {
    $test_event = $events[0];
    $event_id = $test_event['event_id'];
    
    // Test 3: Try registration with test data
    $test_data = [
        'event_id' => $event_id,
        'name' => 'Test User',
        'email' => 'test_' . time() . '@example.com',
        'phone' => '01712345678',
        'address' => 'Test Address',
        'institute' => 'Test Institute',
        'gender' => 'Male',
        'academic_year' => '2nd',
        'experience' => 'Beginner'
    ];
    
    // Check event exists
    $event = get_event_by_id($event_id);
    $test_result['event_found'] = $event ? true : false;
    
    if ($event) {
        $test_result['event_details'] = [
            'event_id' => $event['event_id'],
            'title' => $event['title'],
            'capacity' => $event['capacity'],
            'registered_count' => $event['registered_count']
        ];
        
        // Try to create user
        $user_query = "INSERT INTO users (email, full_name, phone, role, is_active) VALUES (?, ?, ?, 'member', 1)";
        $user_id = db_insert($user_query, [
            $test_data['email'],
            $test_data['name'],
            $test_data['phone']
        ]);
        
        $test_result['user_created'] = $user_id ? true : false;
        if ($user_id) {
            $test_result['user_id'] = $user_id;
            
            // Try to create registration
            $reg_query = "INSERT INTO registrations (event_id, user_id, status) VALUES (?, ?, 'registered')";
            $registration_id = db_insert($reg_query, [
                $event_id,
                $user_id
            ]);
            
            $test_result['registration_created'] = $registration_id ? true : false;
            if ($registration_id) {
                $test_result['registration_id'] = $registration_id;
                $test_result['status'] = 'success';
            } else {
                $test_result['status'] = 'registration_failed';
            }
        } else {
            $test_result['status'] = 'user_creation_failed';
        }
    }
}

echo json_encode($test_result, JSON_PRETTY_PRINT);
?>
