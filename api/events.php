<?php
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Get all events
if ($action === 'get_events' && $method === 'GET') {
    $events = load_json(EVENTS_FILE);
    respond('success', 'Events retrieved', $events);
}

// Get single event
if ($action === 'get_event' && $method === 'GET') {
    $event_id = $_GET['event_id'] ?? null;
    if (!$event_id) respond('error', 'Event ID required');
    
    $events = load_json(EVENTS_FILE);
    $event = array_filter($events, fn($e) => $e['id'] === $event_id);
    
    if (empty($event)) respond('error', 'Event not found');
    respond('success', 'Event found', reset($event));
}

// Register for event
if ($action === 'register' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['event_id', 'name', 'email', 'phone', 'address', 'institute', 'academic_year', 'experience'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            respond('error', "Field '$field' is required");
        }
    }
    
    $events = load_json(EVENTS_FILE);
    $event = current(array_filter($events, fn($e) => $e['id'] === $input['event_id']));
    
    if (!$event) respond('error', 'Event not found');
    
    if ($event['registered_count'] >= $event['capacity']) {
        respond('error', 'Event is full');
    }
    
    $registrations = load_json(REGISTRATIONS_FILE);
    
    // Check if already registered
    $existing = array_filter($registrations, fn($r) => 
        $r['event_id'] === $input['event_id'] && $r['email'] === $input['email']
    );
    
    if (!empty($existing)) {
        respond('error', 'Already registered for this event');
    }
    
    $registration = [
        'id' => generate_id(),
        'event_id' => $input['event_id'],
        'name' => $input['name'],
        'email' => $input['email'],
        'phone' => $input['phone'],
        'address' => $input['address'],
        'institute' => $input['institute'],
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
    
    if (save_json(REGISTRATIONS_FILE, $registrations) && save_json(EVENTS_FILE, $events)) {
        respond('success', 'Successfully registered!', $registration);
    }
    respond('error', 'Registration failed');
}

// Add comment
if ($action === 'add_comment' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['event_id']) || empty($input['name']) || empty($input['comment'])) {
        respond('error', 'Required fields missing');
    }
    
    $comments = load_json(COMMENTS_FILE);
    
    $comment = [
        'id' => generate_id(),
        'event_id' => $input['event_id'],
        'name' => $input['name'],
        'comment' => $input['comment'],
        'created_at' => get_timestamp()
    ];
    
    $comments[] = $comment;
    
    if (save_json(COMMENTS_FILE, $comments)) {
        respond('success', 'Comment added', $comment);
    }
    respond('error', 'Failed to add comment');
}

// Get comments for event
if ($action === 'get_comments' && $method === 'GET') {
    $event_id = $_GET['event_id'] ?? null;
    if (!$event_id) respond('error', 'Event ID required');
    
    $comments = load_json(COMMENTS_FILE);
    $event_comments = array_filter($comments, fn($c) => $c['event_id'] === $event_id);
    
    respond('success', 'Comments retrieved', array_values($event_comments));
}

// Set reminder
if ($action === 'set_reminder' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['event_id']) || empty($input['email'])) {
        respond('error', 'Event ID and email required');
    }
    
    $reminders = load_json(REMINDERS_FILE);
    
    // Check if already set
    $existing = array_filter($reminders, fn($r) => 
        $r['event_id'] === $input['event_id'] && $r['email'] === $input['email']
    );
    
    if (!empty($existing)) {
        respond('error', 'Reminder already set');
    }
    
    $reminder = [
        'id' => generate_id(),
        'event_id' => $input['event_id'],
        'email' => $input['email'],
        'created_at' => get_timestamp()
    ];
    
    $reminders[] = $reminder;
    
    if (save_json(REMINDERS_FILE, $reminders)) {
        respond('success', 'Reminder set', $reminder);
    }
    respond('error', 'Failed to set reminder');
}

// Get registrations for event
if ($action === 'get_registrations' && $method === 'GET') {
    $event_id = $_GET['event_id'] ?? null;
    if (!$event_id) respond('error', 'Event ID required');
    
    $registrations = load_json(REGISTRATIONS_FILE);
    $event_regs = array_filter($registrations, fn($r) => $r['event_id'] === $event_id);
    
    respond('success', 'Registrations retrieved', array_values($event_regs));
}

respond('error', 'Invalid action');
?>
