<?php
/**
 * crud_registrations.php - Registration Management CRUD API
 * 
 * Endpoints for managing event registrations:
 * - GET /crud_registrations.php?action=list&event_id=ID - Get event registrations
 * - POST /crud_registrations.php?action=register - Register user for event
 * - PUT /crud_registrations.php?action=update&id=ID - Update registration
 * - DELETE /crud_registrations.php?action=cancel&id=ID - Cancel registration
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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

function require_login() {
    if (!is_logged_in()) {
        http_response_code(403);
        send_response('error', 'Login required');
    }
}

function require_admin_access() {
    if (!is_admin_logged_in()) {
        http_response_code(403);
        send_response('error', 'Admin access required');
    }
}

// ==================== GET EVENT REGISTRATIONS ====================
if ($action === 'list' && $method === 'GET') {
    require_admin_access();
    
    $event_id = $_GET['event_id'] ?? null;
    if (!$event_id) send_response('error', 'Event ID required');
    
    $registrations = get_event_registrations($event_id);
    send_response('success', 'Registrations retrieved', $registrations);
}

// ==================== GET USER REGISTRATIONS ====================
if ($action === 'user_registrations' && $method === 'GET') {
    require_login();
    
    $user_id = $_SESSION['user_id'];
    $registrations = get_user_registrations($user_id);
    send_response('success', 'User registrations retrieved', $registrations);
}

// ==================== REGISTER USER FOR EVENT ====================
if ($action === 'register' && $method === 'POST') {
    require_login();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['event_id'])) {
        send_response('error', 'Event ID required');
    }
    
    $event_id = $data['event_id'];
    $user_id = $_SESSION['user_id'];
    
    $registration_id = register_user_for_event($event_id, $user_id);
    
    if ($registration_id === false) {
        send_response('error', 'Unable to register (already registered or event full)');
    }
    
    send_response('success', 'Registered successfully', ['registration_id' => $registration_id]);
}

// ==================== CANCEL REGISTRATION ====================
if ($action === 'cancel' && $method === 'DELETE') {
    require_login();
    
    $registration_id = $_GET['id'] ?? null;
    if (!$registration_id) send_response('error', 'Registration ID required');
    
    // Verify user owns this registration
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT user_id FROM registrations WHERE registration_id = ?");
    $stmt->bind_param("i", $registration_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reg = $result->fetch_assoc();
    $stmt->close();
    
    if (!$reg || $reg['user_id'] != $_SESSION['user_id']) {
        send_response('error', 'Unauthorized');
    }
    
    if (cancel_registration($registration_id)) {
        send_response('success', 'Registration cancelled successfully');
    } else {
        send_response('error', 'Failed to cancel registration');
    }
}

// ==================== ADMIN: UPDATE REGISTRATION STATUS ====================
if ($action === 'update' && $method === 'PUT') {
    require_admin_access();
    
    $registration_id = $_GET['id'] ?? null;
    if (!$registration_id) send_response('error', 'Registration ID required');
    
    $data = json_decode(file_get_contents('php://input'), true);
    $new_status = $data['status'] ?? null;
    
    if (!in_array($new_status, ['registered', 'attended', 'cancelled'])) {
        send_response('error', 'Invalid status');
    }
    
    $query = "UPDATE registrations SET status = ? WHERE registration_id = ?";
    if (db_execute($query, [$new_status, $registration_id])) {
        // Log admin action
        db_execute(
            "INSERT INTO audit_log (admin_id, action, table_name, record_id, new_values)
             VALUES (?, 'UPDATE', 'registrations', ?, ?)",
            [$_SESSION['user_id'], $registration_id, json_encode(['status' => $new_status])]
        );
        
        send_response('success', 'Registration updated successfully');
    } else {
        send_response('error', 'Failed to update registration');
    }
}

send_response('error', 'Invalid action');
?>
