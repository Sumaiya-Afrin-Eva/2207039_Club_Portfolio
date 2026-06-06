<?php
/**
 * Test script for admin_events.php event creation
 */
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';
require_once __DIR__ . '/auth.php';

// Simulate admin session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['email'] = 'admin@test.com';
$_SESSION['full_name'] = 'Admin User';

header('Content-Type: application/json');

$action = 'create';
$method = 'POST';

// Copy of CREATE EVENT logic from admin_events.php
if ($action === 'create' && $method === 'POST') {
    // Simulate the JSON payload
    $data = [
        'title' => 'Admin Dashboard Test Event',
        'description' => 'Testing via admin_events.php logic',
        'event_date' => '2026-09-15',
        'start_time' => '15:00',
        'location' => 'Admin Test Location',
        'capacity' => 75,
        'category' => '',
        'status' => 'upcoming',
        'end_time' => null,
        'price' => 0,
        'image_url' => '',
        'organizer_bio' => '',
        'organizer_image' => '',
        'agenda' => [],
        'required_equipment' => []
    ];
    
    // Validate required fields
    if (empty($data['title']) || empty($data['description']) || 
        empty($data['event_date']) || empty($data['location']) || empty($data['capacity'])) {
        respond('error', 'Missing required fields: title, description, event_date, location, capacity');
    }
    
    // Set organizer to current admin
    $data['organizer_id'] = $_SESSION['user_id'];
    
    $event_id = create_event($data);
    
    if ($event_id) {
        // Add agenda items if provided
        if (!empty($data['agenda'])) {
            add_event_agenda($event_id, $data['agenda']);
        }
        
        // Add equipment requirements if provided
        if (!empty($data['required_equipment'])) {
            add_event_equipment($event_id, $data['required_equipment']);
        }
        
        $event = get_event_by_id($event_id);
        respond('success', 'Event created successfully', $event);
    } else {
        respond('error', 'Failed to create event');
    }
}

?>
