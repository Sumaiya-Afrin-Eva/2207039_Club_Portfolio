<?php
/**
 * events.php - Event Management API Endpoints
 * 
 * This file handles all event-related API requests including:
 * - Retrieving events (all or individual)
 * - Managing event registrations
 * - Adding and retrieving event comments
 * - Setting event reminders
 * 
 * All endpoints respond with JSON format containing status, message, and data.
 * Supported actions via GET/POST parameters are processed below.
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Global error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    log_error("PHP Error: [$errno] $errstr in $errfile:$errline");
    respond('error', 'Server error: ' . $errstr);
});

// Get request action and HTTP method
$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Helper function to format event data for frontend
function format_event_for_frontend($event) {
    return [
        'id' => $event['event_id'],  // home-events.js expects 'id'
        'event_id' => $event['event_id'],
        'title' => $event['title'],
        'description' => $event['description'],
        'category' => $event['category'] ?? '',
        'status' => $event['status'],
        'date' => $event['event_date'],  // home-events.js expects 'date'
        'event_date' => $event['event_date'],
        'time' => $event['start_time'],  // home-events.js expects 'time'
        'start_time' => $event['start_time'],
        'end_time' => $event['end_time'],
        'location' => $event['location'],
        'organizer' => $event['organizer_name'] ?? 'Unknown Organizer',
        'organizer_bio' => $event['organizer_bio'] ?? '',
        'organizer_image' => $event['organizer_image'] ?? 'https://i.pravatar.cc/150?img=1',
        'price' => (float)($event['price'] ?? 0),
        'capacity' => (int)($event['capacity'] ?? 0),
        'registered_count' => (int)($event['registered_count'] ?? 0),
        'image_url' => $event['image_url'] ?? 'https://images.unsplash.com/photo-1606608488545-d342b7a9e5a2?w=800&h=600&fit=crop',
        'agenda' => $event['agenda'] ?? [],
        'required_equipment' => $event['required_equipment'] ?? [],
        'comments' => $event['comments'] ?? []
    ];
}

// ==================== EVENT RETRIEVAL ENDPOINTS ====================

/**
 * GET /events.php?action=get_events
 * Retrieves all events from the database
 * Returns: Array of all event objects
 */
if ($action === 'get_events' && $method === 'GET') {
    $events = get_all_events();
    $formatted_events = array_map('format_event_for_frontend', $events);
    respond('success', 'Events retrieved', $formatted_events);
}


/**
 * GET /events.php?action=get_event&event_id=EVENT_ID
 * Retrieves a specific event by its ID
 * Parameters: event_id (required) - The unique event identifier
 * Returns: Single event object
 */
if ($action === 'get_event' && $method === 'GET') {
    $event_id = $_GET['event_id'] ?? null;
    if (!$event_id) respond('error', 'Event ID required');
    
    $event = get_event_by_id($event_id);
    if (!$event) respond('error', 'Event not found');
    
    respond('success', 'Event found', format_event_for_frontend($event));
}


// ==================== EVENT REGISTRATION ENDPOINTS ====================

/**
 * POST /events.php?action=register
 * Registers a user for an event
 * Required POST parameters:
 *   - event_id: ID of the event to register for
 *   - name: Participant name
 *   - email: Participant email
 *   - phone: Participant phone number
 *   - address: Participant address
 *   - institute: Educational/Professional institute
 *   - academic_year: Current academic year
 *   - experience: Photography experience level
 * Returns: Registration object with confirmation details
 * Validates: Event exists, has capacity, user not already registered
 */
if ($action === 'register' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if JSON parsing failed
    if ($input === null) {
        log_error("Registration: JSON parsing failed - " . json_last_error_msg());
        respond('error', 'Invalid JSON in request body');
    }
    
    log_error("Registration attempt with data: " . json_encode($input));
    
    // Validate required fields
    $required = ['event_id', 'name', 'email', 'phone', 'address', 'institute', 'gender', 'academic_year', 'experience'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            log_error("Registration: Missing field - $field");
            respond('error', "Field '$field' is required");
        }
    }
    
    // Ensure event_id is an integer
    $event_id = (int)$input['event_id'];
    log_error("Registration: Processing event_id=$event_id");
    
    // Check if event exists and has capacity
    $event = get_event_by_id($event_id);
    if (!$event) {
        log_error("Registration: Event not found - event_id=$event_id");
        respond('error', 'Event not found');
    }
    
    log_error("Registration: Event found - " . $event['title'] . " (registered: {$event['registered_count']}/{$event['capacity']})");
    
    if ($event['registered_count'] >= $event['capacity']) {
        log_error("Registration: Event full - event_id=$event_id");
        respond('error', 'Event is full');
    }
    
    // Create user if doesn't exist, or get existing user
    $user = db_fetch_one(
        "SELECT user_id FROM users WHERE email = ?",
        [$input['email']]
    );
    
    $user_id = null;
    if ($user) {
        $user_id = (int)$user['user_id'];
        log_error("Registration: Using existing user - user_id=$user_id");
        
        // Check if user already registered for this event
        $existing = db_fetch_one(
            "SELECT registration_id FROM registrations WHERE event_id = ? AND user_id = ?",
            [$event_id, $user_id]
        );
        
        if ($existing) {
            log_error("Registration: Already registered - email=" . $input['email']);
            respond('error', 'Already registered for this event');
        }
    }
    
    if (!$user) {
        // Create guest user - cast to proper types
        log_error("Registration: Creating new user - email=" . $input['email']);
        $user_query = "INSERT INTO users (email, full_name, phone, password, role, is_active) VALUES (?, ?, ?, '', 'member', 1)";
        $user_id = db_insert($user_query, [
            $input['email'],
            $input['name'],
            $input['phone']
        ]);
        
        if (!$user_id) {
            log_error("Registration: Failed to create user - email=" . $input['email']);
            respond('error', 'Failed to create user account');
        }
        log_error("Registration: User created with id=$user_id");
    }
    
    // Create registration - ensure types are correct
    log_error("Registration: Creating registration for user_id=$user_id, event_id=$event_id");
    $reg_query = "INSERT INTO registrations (event_id, user_id, status) VALUES (?, ?, 'registered')";
    $registration_id = db_insert($reg_query, [
        $event_id,
        $user_id
    ]);
    
    if ($registration_id) {
        log_error("Registration: Success - registration_id=$registration_id");
        respond('success', 'Successfully registered!', [
            'registration_id' => $registration_id,
            'event_id' => $event_id,
            'email' => $input['email'],
            'name' => $input['name']
        ]);
    } else {
        log_error("Registration: Failed to create registration record");
        respond('error', 'Failed to create registration record');
    }
}


// ==================== COMMENT ENDPOINTS ====================

/**
 * POST /events.php?action=add_comment
 * Adds a comment to an event
 * Required POST parameters:
 *   - event_id: ID of the event to comment on
 *   - name: Commenter's name
 *   - comment: The comment text
 * Returns: Comment object with submission details
 */
if ($action === 'add_comment' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if JSON parsing failed
    if ($input === null) {
        log_error("Comment: JSON parsing failed - " . json_last_error_msg());
        respond('error', 'Invalid JSON in request body');
    }
    
    log_error("Comment attempt with data: " . json_encode($input));
    
    // Validate required fields
    if (empty($input['event_id']) || empty($input['name']) || empty($input['comment'])) {
        log_error("Comment: Missing required fields");
        respond('error', 'Required fields missing');
    }
    
    // Ensure event_id is an integer
    $event_id = (int)$input['event_id'];
    
    // Check if event exists
    $event = get_event_by_id($event_id);
    if (!$event) {
        log_error("Comment: Event not found - event_id=$event_id");
        respond('error', 'Event not found');
    }
    
    log_error("Comment: Processing for event - " . $event['title']);
    
    // Get or create user
    $user = db_fetch_one(
        "SELECT user_id FROM users WHERE full_name = ?",
        [$input['name']]
    );
    
    if (!$user) {
        // Create guest user - include password field
        log_error("Comment: Creating guest user - name=" . $input['name']);
        $user_query = "INSERT INTO users (email, full_name, password, role, is_active) VALUES (NULL, ?, '', 'member', 1)";
        $user_id = db_insert($user_query, [$input['name']]);
        
        if (!$user_id) {
            log_error("Comment: Failed to create user - name=" . $input['name']);
            respond('error', 'Failed to create user account');
        }
        log_error("Comment: User created with id=$user_id");
    } else {
        $user_id = (int)$user['user_id'];
        log_error("Comment: Using existing user - user_id=$user_id");
    }
    
    // Create comment
    log_error("Comment: Creating comment for user_id=$user_id, event_id=$event_id");
    $comment_query = "INSERT INTO comments (event_id, user_id, comment_text) VALUES (?, ?, ?)";
    $comment_id = db_insert($comment_query, [$event_id, $user_id, $input['comment']]);
    
    if ($comment_id) {
        log_error("Comment: Success - comment_id=$comment_id");
        respond('success', 'Comment added', [
            'comment_id' => $comment_id,
            'event_id' => $event_id,
            'name' => $input['name'],
            'comment' => $input['comment']
        ]);
    } else {
        log_error("Comment: Failed to create comment record");
    }
    respond('error', 'Failed to add comment');
}


/**
 * GET /events.php?action=get_comments&event_id=EVENT_ID
 * Retrieves all comments for a specific event
 * Parameters: event_id (required) - The event ID to get comments for
 * Returns: Array of comment objects for the event
 */
if ($action === 'get_comments' && $method === 'GET') {
    $event_id = $_GET['event_id'] ?? null;
    if (!$event_id) respond('error', 'Event ID required');
    
    $comments = db_select(
        "SELECT c.*, u.full_name as name FROM comments c
         LEFT JOIN users u ON c.user_id = u.user_id
         WHERE c.event_id = ?
         ORDER BY c.created_at DESC",
        [$event_id]
    );
    
    // Format for frontend
    $formatted_comments = array_map(function($c) {
        return [
            'id' => $c['comment_id'],
            'comment_id' => $c['comment_id'],
            'event_id' => $c['event_id'],
            'name' => $c['name'] ?? 'Anonymous',
            'comment' => $c['comment_text'],
            'created_at' => $c['created_at']
        ];
    }, $comments);
    
    respond('success', 'Comments retrieved', $formatted_comments);
}


// ==================== REMINDER ENDPOINTS ====================

/**
 * POST /events.php?action=set_reminder
 * Creates a reminder for an event
 * Required POST parameters:
 *   - event_id: ID of the event to set reminder for
 *   - email: Email to receive reminder at
 * Returns: Reminder object with confirmation details
 * Validates: User hasn't already set a reminder for this event
 */
if ($action === 'set_reminder' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required parameters
    if (empty($input['event_id']) || empty($input['email'])) {
        respond('error', 'Event ID and email required');
    }
    
    // Check if event exists
    $event = get_event_by_id($input['event_id']);
    if (!$event) respond('error', 'Event not found');
    
    // Get or create user
    $user = db_fetch_one(
        "SELECT user_id FROM users WHERE email = ?",
        [$input['email']]
    );
    
    if (!$user) {
        $user_query = "INSERT INTO users (email, role, is_active) VALUES (?, 'member', 1)";
        $user_id = db_insert($user_query, [$input['email']]);
    } else {
        $user_id = $user['user_id'];
    }
    
    // Check if reminder already exists
    $existing = db_fetch_one(
        "SELECT reminder_id FROM reminders WHERE event_id = ? AND user_id = ?",
        [$input['event_id'], $user_id]
    );
    
    if ($existing) {
        respond('error', 'Reminder already set for this event');
    }
    
    // Set reminder for 1 day before the event
    $event_date = strtotime($event['event_date']);
    $reminder_time = date('Y-m-d H:i:s', $event_date - (24 * 3600));
    
    $reminder_query = "INSERT INTO reminders (event_id, user_id, reminder_time) VALUES (?, ?, ?)";
    $reminder_id = db_insert($reminder_query, [$input['event_id'], $user_id, $reminder_time]);
    
    if ($reminder_id) {
        respond('success', 'Reminder set successfully', [
            'reminder_id' => $reminder_id,
            'event_id' => $input['event_id'],
            'email' => $input['email']
        ]);
    }
    respond('error', 'Failed to set reminder');
}


/**
 * GET /events.php?action=get_registrations&event_id=EVENT_ID
 * Retrieves all registrations for a specific event
 * Parameters: event_id (required) - The event ID to get registrations for
 * Returns: Array of registration objects for the event
 */
if ($action === 'get_registrations' && $method === 'GET') {
    $event_id = $_GET['event_id'] ?? null;
    if (!$event_id) respond('error', 'Event ID required');
    
    $regs = db_select(
        "SELECT r.*, u.full_name, u.email FROM registrations r
         LEFT JOIN users u ON r.user_id = u.user_id
         WHERE r.event_id = ?
         ORDER BY r.registration_date DESC",
        [$event_id]
    );
    
    // Format for frontend
    $formatted_regs = array_map(function($r) {
        return [
            'registration_id' => $r['registration_id'],
            'event_id' => $r['event_id'],
            'name' => $r['full_name'] ?? 'Guest',
            'email' => $r['email'] ?? 'N/A',
            'status' => $r['status'],
            'registered_at' => $r['registration_date']
        ];
    }, $regs);
    
    respond('success', 'Registrations retrieved', $formatted_regs);
}

// ==================== ERROR HANDLING ====================
// If action is not recognized, return error
respond('error', 'Invalid action');
?>