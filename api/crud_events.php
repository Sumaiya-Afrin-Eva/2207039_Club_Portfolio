<?php
/**
 * crud_events.php - Event CRUD API Endpoints
 * 
 * This file provides comprehensive REST API endpoints for managing events
 * using MySQL database with proper authentication and authorization.
 * 
 * Endpoints:
 * - GET /crud_events.php?action=list - Get all events
 * - GET /crud_events.php?action=get&id=ID - Get single event
 * - POST /crud_events.php?action=create - Create new event (admin only)
 * - PUT /crud_events.php?action=update&id=ID - Update event (admin only)
 * - DELETE /crud_events.php?action=delete&id=ID - Delete event (admin only)
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Helper function to send JSON response
function send_response($status, $message, $data = null) {
    http_response_code($status === 'success' ? 200 : 400);
    $response = [
        'status' => $status,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit();
}

// Helper function to require admin
function require_admin_access() {
    if (!is_admin_logged_in()) {
        http_response_code(403);
        send_response('error', 'Admin access required');
    }
}

// ==================== GET ALL EVENTS ====================
// GET /crud_events.php?action=list
if ($action === 'list' && $method === 'GET') {
    $status = $_GET['status'] ?? null;
    $events = get_all_events($status);
    send_response('success', 'Events retrieved', $events);
}

// ==================== GET SINGLE EVENT ====================
// GET /crud_events.php?action=get&id=ID
if ($action === 'get' && $method === 'GET') {
    $event_id = $_GET['id'] ?? null;
    if (!$event_id) {
        send_response('error', 'Event ID required');
    }
    
    $event = get_event_by_id($event_id);
    if (!$event) {
        send_response('error', 'Event not found');
    }
    
    send_response('success', 'Event retrieved', $event);
}

// ==================== CREATE EVENT ====================
// POST /crud_events.php?action=create
if ($action === 'create' && $method === 'POST') {
    require_admin_access();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['title', 'description', 'event_date', 'start_time', 'location', 'capacity'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            send_response('error', "Field '$field' is required");
        }
    }
    
    // Set organizer to current admin
    $data['organizer_id'] = $_SESSION['user_id'];
    
    $event_id = create_event($data);
    
    if (!$event_id) {
        send_response('error', 'Failed to create event');
    }
    
    // Add event agenda if provided
    if (!empty($data['agenda'])) {
        add_event_agenda($event_id, $data['agenda']);
    }
    
    // Add event equipment if provided
    if (!empty($data['required_equipment'])) {
        add_event_equipment($event_id, $data['required_equipment']);
    }
    
    // Log admin action
    $conn = get_db_connection();
    $log_query = "INSERT INTO audit_log (admin_id, action, table_name, record_id, new_values)
                  VALUES (?, 'CREATE', 'events', ?, ?)";
    db_execute($log_query, [$_SESSION['user_id'], $event_id, json_encode($data)]);
    
    send_response('success', 'Event created successfully', ['event_id' => $event_id]);
}

// ==================== UPDATE EVENT ====================
// PUT /crud_events.php?action=update&id=ID
if ($action === 'update' && $method === 'PUT') {
    require_admin_access();
    
    $event_id = $_GET['id'] ?? null;
    if (!$event_id) {
        send_response('error', 'Event ID required');
    }
    
    // Get existing event for audit log
    $old_event = get_event_by_id($event_id);
    if (!$old_event) {
        send_response('error', 'Event not found');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (update_event($event_id, $data)) {
        // Update agenda if provided
        if (!empty($data['agenda'])) {
            db_execute("DELETE FROM event_agenda WHERE event_id = ?", [$event_id]);
            add_event_agenda($event_id, $data['agenda']);
        }
        
        // Update equipment if provided
        if (!empty($data['required_equipment'])) {
            db_execute("DELETE FROM event_equipment WHERE event_id = ?", [$event_id]);
            add_event_equipment($event_id, $data['required_equipment']);
        }
        
        // Log admin action
        db_execute(
            "INSERT INTO audit_log (admin_id, action, table_name, record_id, old_values, new_values)
             VALUES (?, 'UPDATE', 'events', ?, ?, ?)",
            [$_SESSION['user_id'], $event_id, json_encode($old_event), json_encode($data)]
        );
        
        send_response('success', 'Event updated successfully');
    } else {
        send_response('error', 'Failed to update event');
    }
}

// ==================== DELETE EVENT ====================
// DELETE /crud_events.php?action=delete&id=ID
if ($action === 'delete' && $method === 'DELETE') {
    require_admin_access();
    
    $event_id = $_GET['id'] ?? null;
    if (!$event_id) {
        send_response('error', 'Event ID required');
    }
    
    // Get event for audit log
    $event = get_event_by_id($event_id);
    if (!$event) {
        send_response('error', 'Event not found');
    }
    
    if (delete_event($event_id)) {
        // Log admin action
        db_execute(
            "INSERT INTO audit_log (admin_id, action, table_name, record_id, old_values)
             VALUES (?, 'DELETE', 'events', ?, ?)",
            [$_SESSION['user_id'], $event_id, json_encode($event)]
        );
        
        send_response('success', 'Event deleted successfully');
    } else {
        send_response('error', 'Failed to delete event');
    }
}

// ==================== GET EVENT REGISTRATIONS ====================
// GET /crud_events.php?action=registrations&id=ID
if ($action === 'registrations' && $method === 'GET') {
    require_admin_access();
    
    $event_id = $_GET['id'] ?? null;
    if (!$event_id) {
        send_response('error', 'Event ID required');
    }
    
    $registrations = get_event_registrations($event_id);
    send_response('success', 'Registrations retrieved', $registrations);
}

send_response('error', 'Invalid action');
?>
