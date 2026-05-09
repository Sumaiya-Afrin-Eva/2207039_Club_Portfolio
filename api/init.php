<?php\n/**\n * init.php - Data Initialization Script\n * \n * This script runs on first page load to initialize the application's data.\n * It creates default events and sets up empty JSON data files for:\n * - Event registrations\n * - User comments on events\n * - Event reminders\n * \n * Features:\n * - Creates 3 default sample events (Photo Walk, Editing Workshop, Photography Competition)\n * - Initializes empty data files if they don't exist\n * - Ensures proper data structure for the event management system\n */\n\nrequire_once __DIR__ . '/config.php';\n\n// ==================== INITIALIZE DEFAULT EVENTS ====================\n// Create default events only if events file doesn't exist\nif (!file_exists(EVENTS_FILE)) {\n    // Initialize array with three sample events\n    $events = [
        [
            'id' => 'event_001',
            'title' => 'Photo Walk',
            'description' => 'Explore city life and capture street moments.',
            'category' => 'Outdoor',
            'status' => 'upcoming', // upcoming, ongoing, completed
            'date' => '2026-05-15',
            'time' => '09:00 AM',
            'end_time' => '01:00 PM',
            'location' => 'City Center Park',
            'organizer' => 'Sanjida Afrin Shikha',
            'organizer_bio' => 'Photography enthusiast with 5+ years of experience in street photography',
            'organizer_image' => 'https://i.pravatar.cc/150?img=1',
            'price' => 0.00,
            'capacity' => 20,
            'registered_count' => 5,
            'image_url' => 'https://images.unsplash.com/photo-1606608488545-d342b7a9e5a2?w=800&h=600&fit=crop',
            'required_equipment' => ['Camera (DSLR/Mirrorless/Smartphone)', 'Comfortable shoes', 'Water bottle'],
            'agenda' => [
                ['time' => '09:00 AM', 'activity' => 'Meet & Greet at Park Entrance'],
                ['time' => '09:30 AM', 'activity' => 'Composition & Framing Techniques'],
                ['time' => '10:00 AM', 'activity' => 'Free Photography Session'],
                ['time' => '12:00 PM', 'activity' => 'Photo Review & Feedback'],
                ['time' => '01:00 PM', 'activity' => 'Wrap-up']
            ],
            'highlights' => [],
            'created_at' => '2026-04-01'
        ],
        [
            'id' => 'event_002',
            'title' => 'Editing Workshop',
            'description' => 'Learn Lightroom & Photoshop basics.',
            'category' => 'Workshop',
            'status' => 'upcoming',
            'date' => '2026-05-22',
            'time' => '06:00 PM',
            'end_time' => '08:30 PM',
            'location' => 'Computer Lab, KUET',
            'organizer' => 'Sumaiya Afrin Eva',
            'organizer_bio' => 'Professional photo editor with expertise in Lightroom and Photoshop',
            'organizer_image' => 'https://i.pravatar.cc/150?img=2',
            'price' => 0.00,
            'capacity' => 30,
            'registered_count' => 12,
            'image_url' => 'https://images.unsplash.com/photo-161153273679-1382cdf1d21b?w=800&h=600&fit=crop',
            'required_equipment' => ['Laptop with Adobe Lightroom/Photoshop', 'Mouse'],
            'agenda' => [
                ['time' => '06:00 PM', 'activity' => 'Introduction to Adobe Lightroom'],
                ['time' => '06:45 PM', 'activity' => 'Basic Editing Techniques'],
                ['time' => '07:30 PM', 'activity' => 'Photoshop Essentials'],
                ['time' => '08:15 PM', 'activity' => 'Q&A Session']
            ],
            'highlights' => [],
            'created_at' => '2026-04-01'
        ],
        [
            'id' => 'event_003',
            'title' => 'Photography Competition',
            'description' => 'Showcase your talent and win prizes!',
            'category' => 'Competition',
            'status' => 'upcoming',
            'date' => '2026-06-05',
            'time' => '10:00 AM',
            'end_time' => '04:00 PM',
            'location' => 'KUET Auditorium',
            'organizer' => 'Sanjida Afrin Shikha',
            'organizer_bio' => 'Photography enthusiast with 5+ years of experience in street photography',
            'organizer_image' => 'https://i.pravatar.cc/150?img=1',
            'price' => 5.00,
            'capacity' => 50,
            'registered_count' => 28,
            'image_url' => 'https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=800&h=600&fit=crop',
            'required_equipment' => ['Camera', 'Your beautiful photos'],
            'agenda' => [
                ['time' => '10:00 AM', 'activity' => 'Registration & Setup'],
                ['time' => '10:30 AM', 'activity' => 'Competition Begins'],
                ['time' => '02:00 PM', 'activity' => 'Judging & Evaluation'],
                ['time' => '03:30 PM', 'activity' => 'Winner Announcement'],
                ['time' => '04:00 PM', 'activity' => 'Prize Distribution']
            ],
            'highlights' => [],
            'created_at' => '2026-04-05'
        ]
    ];
    
    save_json(EVENTS_FILE, $events);
}

// ==================== INITIALIZE EMPTY DATA FILES ====================
// Create empty registrations file if it doesn't exist
if (!file_exists(REGISTRATIONS_FILE)) {
    save_json(REGISTRATIONS_FILE, []);
}

// Create empty comments file if it doesn't exist
if (!file_exists(COMMENTS_FILE)) {
    save_json(COMMENTS_FILE, []);
}

// Create empty reminders file if it doesn't exist
if (!file_exists(REMINDERS_FILE)) {
    save_json(REMINDERS_FILE, []);
}

// Return success response
echo json_encode(['status' => 'success', 'message' => 'Data initialized']);
?>
