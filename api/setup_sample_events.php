<?php
/**
 * setup_sample_events.php - Add Sample Events to Database
 * 
 * This script adds sample events to the database for testing.
 * Run this once to populate the database with initial events.
 */

require_once __DIR__ . '/db_config.php';

header('Content-Type: application/json');

try {
    $conn = get_db_connection();
    
    // Check if events already exist
    $result = $conn->query("SELECT COUNT(*) as total FROM events");
    $row = $result->fetch_assoc();
    
    if ($row['total'] > 0) {
        echo json_encode([
            'status' => 'info',
            'message' => 'Events already exist in database',
            'count' => $row['total']
        ], JSON_PRETTY_PRINT);
        exit();
    }
    
    // Get or create admin user
    $admin_result = $conn->query("SELECT user_id FROM users WHERE role = 'admin' LIMIT 1");
    if ($admin_result && $admin_result->num_rows > 0) {
        $admin = $admin_result->fetch_assoc();
        $organizer_id = $admin['user_id'];
    } else {
        // Create default admin if not exists
        $email = 'admin@clubportfolio.com';
        $password_hash = '$2y$10$GYsT5/hSy7mZUMSGrKdVOemzw4p4H.GGOvN9fQwwwN5RRv9u5M7zm';
        
        $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, role, is_active) VALUES (?, ?, ?, 'admin', 1)");
        $stmt->bind_param("sss", $email, $password_hash, $full_name = 'Admin User');
        $stmt->execute();
        $organizer_id = $stmt->insert_id;
        $stmt->close();
    }
    
    // Sample events data
    $today = date('Y-m-d');
    $events = [
        [
            'title' => 'Portrait Photography Workshop',
            'description' => 'Learn professional portrait photography techniques including lighting, posing, and editing.',
            'category' => 'Workshop',
            'status' => 'upcoming',
            'event_date' => date('Y-m-d', strtotime('+7 days')),
            'start_time' => '10:00:00',
            'end_time' => '14:00:00',
            'location' => 'Photography Studio, Building A',
            'organizer_id' => $organizer_id,
            'price' => 50.00,
            'capacity' => 25
        ],
        [
            'title' => 'Outdoor Landscape Photography',
            'description' => 'Explore nature photography at a beautiful outdoor location. Bring your own camera and lenses.',
            'category' => 'Outdoor',
            'status' => 'upcoming',
            'event_date' => date('Y-m-d', strtotime('+14 days')),
            'start_time' => '06:00:00',
            'end_time' => '12:00:00',
            'location' => 'National Park South Entrance',
            'organizer_id' => $organizer_id,
            'price' => 30.00,
            'capacity' => 30
        ],
        [
            'title' => 'Photography Competition 2026',
            'description' => 'Annual photography competition. Submit your best work and compete for prizes worth $1000.',
            'category' => 'Competition',
            'status' => 'upcoming',
            'event_date' => date('Y-m-d', strtotime('+21 days')),
            'start_time' => '14:00:00',
            'end_time' => '18:00:00',
            'location' => 'Community Center Grand Hall',
            'organizer_id' => $organizer_id,
            'price' => 25.00,
            'capacity' => 100
        ],
        [
            'title' => 'Photo Editing Masterclass',
            'description' => 'Advanced editing techniques using Adobe Lightroom and Photoshop. For intermediate and advanced photographers.',
            'category' => 'Workshop',
            'status' => 'upcoming',
            'event_date' => date('Y-m-d', strtotime('+28 days')),
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
            'location' => 'Computer Lab, Building C',
            'organizer_id' => $organizer_id,
            'price' => 40.00,
            'capacity' => 20
        ],
        [
            'title' => 'Spring Photography Meetup',
            'description' => 'Casual meetup for photographers to share work, exchange tips, and network with fellow enthusiasts.',
            'category' => 'Outdoor',
            'status' => 'upcoming',
            'event_date' => date('Y-m-d', strtotime('+5 days')),
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'location' => 'Central Park Pavilion',
            'organizer_id' => $organizer_id,
            'price' => 0.00,
            'capacity' => 50
        ]
    ];
    
    $inserted = 0;
    foreach ($events as $event) {
        $stmt = $conn->prepare(
            "INSERT INTO events (title, description, category, status, event_date, start_time, end_time, location, organizer_id, price, capacity) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->bind_param(
            "sssssssssdd",
            $event['title'],
            $event['description'],
            $event['category'],
            $event['status'],
            $event['event_date'],
            $event['start_time'],
            $event['end_time'],
            $event['location'],
            $event['organizer_id'],
            $event['price'],
            $event['capacity']
        );
        
        if ($stmt->execute()) {
            $inserted++;
        }
        $stmt->close();
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Sample events added to database',
        'inserted' => $inserted,
        'total' => count($events)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to add sample events',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
