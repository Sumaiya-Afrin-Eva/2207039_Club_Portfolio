<?php
/**
 * admin_events.php - Admin Event Management API
 * 
 * Endpoints for admin to create, update, delete, and manage events
 * Requires admin authentication
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';
require_once __DIR__ . '/auth.php';

// Check admin authentication
if (!is_admin_logged_in()) {
    respond('error', 'Unauthorized access. Admin login required.');
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

// ==================== GET ALL EVENTS ====================
if ($action === 'list' && $method === 'GET') {
    $status = $_GET['status'] ?? null;
    $events = get_all_events($status);
    respond('success', 'Events retrieved', $events);
}

// ==================== GET SINGLE EVENT ====================
if ($action === 'get' && $method === 'GET') {
    $event_id = $_GET['event_id'] ?? null;
    if (!$event_id) respond('error', 'Event ID required');
    
    $event = get_event_by_id($event_id);
    if (!$event) respond('error', 'Event not found');
    
    respond('success', 'Event retrieved', $event);
}

// ==================== CREATE EVENT ====================
if ($action === 'create' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields
    if (empty($data['title']) || empty($data['description']) || 
        empty($data['event_date']) || empty($data['location']) || empty($data['capacity'])) {
        respond('error', 'Missing required fields: title, description, event_date, location, capacity');
    }
    
    // Set organizer to current admin
    $admin = get_admin_data();
    $data['organizer_id'] = $admin['user_id'];
    
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

// ==================== UPDATE EVENT ====================
if ($action === 'update' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $event_id = $data['event_id'] ?? null;
    
    if (!$event_id) respond('error', 'Event ID required');
    
    // Check if event exists
    $event = get_event_by_id($event_id);
    if (!$event) respond('error', 'Event not found');
    
    // Update event
    if (update_event($event_id, $data)) {
        $updated_event = get_event_by_id($event_id);
        respond('success', 'Event updated successfully', $updated_event);
    } else {
        respond('error', 'Failed to update event');
    }
}

// ==================== DELETE EVENT ====================
if ($action === 'delete' && $method === 'POST') {
    $event_id = $_POST['event_id'] ?? null;
    
    if (!$event_id) respond('error', 'Event ID required');
    
    if (delete_event($event_id)) {
        respond('success', 'Event deleted successfully');
    } else {
        respond('error', 'Failed to delete event');
    }
}

// ==================== GET ALL REGISTRATIONS OR BY EVENT ====================
if ($action === 'registrations' && $method === 'GET') {
    $event_id = $_GET['event_id'] ?? null;
    
    if ($event_id) {
        // Get registrations for a specific event
        $registrations = get_event_registrations($event_id);
    } else {
        // Get all registrations from all events
        $registrations = db_select(
            "SELECT r.*, u.full_name, u.email, e.title as event_title FROM registrations r
             LEFT JOIN users u ON r.user_id = u.user_id
             LEFT JOIN events e ON r.event_id = e.event_id
             ORDER BY r.registration_date DESC",
            []
        );
    }
    
    respond('success', 'Registrations retrieved', $registrations);
}

// ==================== GET EVENT COMMENTS ====================
if ($action === 'comments' && $method === 'GET') {
    $event_id = $_GET['event_id'] ?? null;
    if (!$event_id) respond('error', 'Event ID required');
    
    $comments = get_event_comments($event_id);
    respond('success', 'Comments retrieved', $comments);
}

respond('error', 'Invalid action');

?>
