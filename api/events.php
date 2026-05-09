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

require_once __DIR__ . '/config.php';

// Get request action and HTTP method
$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ==================== EVENT RETRIEVAL ENDPOINTS ====================

/**
 * GET /events.php?action=get_events
 * Retrieves all events from the database
 * Returns: Array of all event objects
 */
if ($action === 'get_events' && $method === 'GET') {
    $events = load_json(EVENTS_FILE);
    respond('success', 'Events retrieved', $events);
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
    
    $events = load_json(EVENTS_FILE);
    $event = array_filter($events, fn($e) => $e['id'] === $event_id);
    
    if (empty($event)) respond('error', 'Event not found');
    respond('success', 'Event found', reset($event));
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
    
    // Validate required fields
    $required = ['event_id', 'name', 'email', 'phone', 'address', 'institute', 'gender', 'academic_year', 'experience'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            respond('error', "Field '$field' is required");
        }
    }
    
    // Load events and find the target event
    $events = load_json(EVENTS_FILE);
    $event = current(array_filter($events, fn($e) => $e['id'] === $input['event_id']));
    
    if (!$event) respond('error', 'Event not found');
    
    // Check if event has available capacity
    if ($event['registered_count'] >= $event['capacity']) {
        respond('error', 'Event is full');
    }
    
    $registrations = load_json(REGISTRATIONS_FILE);
    
    // Check if user is already registered for this event
    $existing = array_filter($registrations, fn($r) => 
        $r['event_id'] === $input['event_id'] && $r['email'] === $input['email']
    );
    
    if (!empty($existing)) {
        respond('error', 'Already registered for this event');
    }
    
    // Create new registration record
    $registration = [
        'id' => generate_id(),
        'event_id' => $input['event_id'],
        'name' => $input['name'],
        'email' => $input['email'],
        'phone' => $input['phone'],
        'address' => $input['address'],
        'institute' => $input['institute'],
        'gender' => $input['gender'],
        'academic_year' => $input['academic_year'],
        'experience' => $input['experience'],
        'registered_at' => get_timestamp()
    ];
    
    $registrations[] = $registration;
    
    // Update event registered count
    foreach ($events as &$e) {
        if ($e['id'] === $input['event_id']) {
            $e['registered_count']++;
        }
    }
    
    // Save updated data
    if (save_json(REGISTRATIONS_FILE, $registrations) && save_json(EVENTS_FILE, $events)) {
        respond('success', 'Successfully registered!', $registration);
    }
    respond('error', 'Registration failed');
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
    
    // Validate required fields
    if (empty($input['event_id']) || empty($input['name']) || empty($input['comment'])) {
        respond('error', 'Required fields missing');
    }
    
    $comments = load_json(COMMENTS_FILE);
    
    // Create new comment record
    $comment = [
        'id' => generate_id(),
        'event_id' => $input['event_id'],
        'name' => $input['name'],
        'comment' => $input['comment'],
        'created_at' => get_timestamp()
    ];
    
    $comments[] = $comment;
    
    // Save updated comments
    if (save_json(COMMENTS_FILE, $comments)) {
        respond('success', 'Comment added', $comment);
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
    
    $comments = load_json(COMMENTS_FILE);
    // Filter comments for the specific event
    $event_comments = array_filter($comments, fn($c) => $c['event_id'] === $event_id);
    
    respond('success', 'Comments retrieved', array_values($event_comments));
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
    
    $reminders = load_json(REMINDERS_FILE);
    
    // Check if reminder already exists for this event and email
    $existing = array_filter($reminders, fn($r) => 
        $r['event_id'] === $input['event_id'] && $r['email'] === $input['email']
    );
    
    if (!empty($existing)) {
        respond('error', 'Reminder already set');
    }
    
    // Create new reminder record
    $reminder = [
        'id' => generate_id(),
        'event_id' => $input['event_id'],
        'email' => $input['email'],
        'created_at' => get_timestamp()
    ];
    
    $reminders[] = $reminder;
    
    // Save updated reminders
    if (save_json(REMINDERS_FILE, $reminders)) {
        respond('success', 'Reminder set', $reminder);
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
    
    $registrations = load_json(REGISTRATIONS_FILE);
    // Filter registrations for the specific event
    $event_regs = array_filter($registrations, fn($r) => $r['event_id'] === $event_id);
    
    respond('success', 'Registrations retrieved', array_values($event_regs));
}

// ==================== ERROR HANDLING ====================
// If action is not recognized, return error
respond('error', 'Invalid action');
?>
