<?php
/**
 * Test script for event creation
 */
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';
require_once __DIR__ . '/auth.php';

// Simulate session with logged-in admin
$_SESSION['user_id'] = 1;  // Assuming admin ID is 1
$_SESSION['role'] = 'admin';

header('Content-Type: application/json');

try {
    $data = [
        'title' => 'Test Event from Script',
        'description' => 'Testing event creation via script',
        'category' => 'Test',
        'status' => 'upcoming',
        'event_date' => '2026-09-01',
        'start_time' => '14:00',
        'end_time' => null,
        'location' => 'Script Test Hall',
        'organizer_id' => 1,
        'price' => 0,
        'capacity' => 50,
        'image_url' => '',
        'organizer_bio' => '',
        'organizer_image' => ''
    ];
    
    $event_id = create_event($data);
    
    if ($event_id) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Event created successfully',
            'event_id' => $event_id
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create event'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>