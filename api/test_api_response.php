<?php
/**
 * test_api_response.php - Test API endpoint response
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';

// Simulate the API endpoint
header('Content-Type: application/json');

function format_event($event) {
    if (!$event) return null;
    
    return [
        'id' => $event['event_id'] ?? null,
        'title' => $event['title'] ?? null,
        'description' => $event['description'] ?? null,
        'category' => $event['category'] ?? null,
        'status' => $event['status'] ?? 'upcoming',
        'date' => $event['event_date'] ?? null,
        'time' => $event['start_time'] ?? null,
        'end_time' => $event['end_time'] ?? null,
        'location' => $event['location'] ?? null,
        'organizer_name' => $event['organizer_name'] ?? null,
        'organizer_image' => $event['organizer_image'] ?? null,
        'price' => $event['price'] ?? 0,
        'capacity' => $event['capacity'] ?? 0,
        'registered_count' => $event['registered_count'] ?? 0,
        'event_id' => $event['event_id'] ?? null
    ];
}

function format_events($events) {
    return array_map('format_event', $events ?? []);
}

$events = get_all_events();
$response = [
    'status' => 'success',
    'message' => 'Events retrieved',
    'data' => format_events($events)
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
