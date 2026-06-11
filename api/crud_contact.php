<?php
/**
 * crud_contact.php - Contact Submission Management API
 * 
 * Handles CRUD operations for contact form submissions:
 * - GET: Retrieve all contact submissions (admin only)
 * - GET with ID: Retrieve a specific contact submission
 * - POST: Create a new contact submission (public)
 * - PUT: Update contact submission status or notes (admin only)
 * - DELETE: Delete/archive contact submission (admin only)
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// Start session for admin check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sendJsonResponse($status, $message, $data = null, $code = 200) {
    http_response_code($code);
    $response = ['status' => $status, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit();
}

try {
    switch ($method) {
        case 'GET':
            handle_get_contact();
            break;
        case 'POST':
            handle_post_contact();
            break;
        case 'PUT':
            // Require admin for updates
            if (!is_admin_logged_in()) {
                sendJsonResponse('error', 'Admin access required', null, 403);
            }
            handle_put_contact();
            break;
        case 'DELETE':
            // Require admin for deletion
            if (!is_admin_logged_in()) {
                sendJsonResponse('error', 'Admin access required', null, 403);
            }
            handle_delete_contact();
            break;
        default:
            sendJsonResponse('error', 'Method not allowed', null, 405);
    }
} catch (Exception $e) {
    error_log("Contact API Error: " . $e->getMessage());
    sendJsonResponse('error', 'Server error: ' . $e->getMessage(), null, 500);
}

/**
 * Handle GET requests for contact submissions
 */
function handle_get_contact() {
    $contact_id = $_GET['contact_id'] ?? null;
    $status = $_GET['status'] ?? null;
    
    // Require admin for viewing
    if (!is_admin_logged_in()) {
        sendJsonResponse('error', 'Admin access required', null, 403);
    }

    if ($contact_id) {
        // Get specific contact submission
        $query = "SELECT * FROM contact_submissions WHERE contact_id = ?";
        $result = db_fetch_one($query, [(int)$contact_id]);
        
        if ($result) {
            sendJsonResponse('success', 'Contact submission retrieved', $result);
        } else {
            sendJsonResponse('error', 'Contact submission not found', null, 404);
        }
    } else {
        // Get all contact submissions
        $query = "SELECT * FROM contact_submissions";
        $params = [];
        
        if ($status) {
            $query .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $result = db_select($query, $params);
        
        sendJsonResponse('success', 'Contact submissions retrieved', $result);
    }
}

/**
 * Handle POST requests - Create new contact submission (public endpoint)
 */
function handle_post_contact() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Check if JSON parsing failed
    if ($data === null) {
        sendJsonResponse('error', 'Invalid JSON data', null, 400);
    }

    // Validate required fields
    if (empty($data['full_name']) || empty($data['email']) || empty($data['message'])) {
        sendJsonResponse('error', 'Full name, email, and message are required', null, 400);
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse('error', 'Invalid email format', null, 400);
    }

    // Sanitize inputs
    $full_name = htmlspecialchars(trim($data['full_name']));
    $email = htmlspecialchars(trim($data['email']));
    $message = htmlspecialchars(trim($data['message']));

    // Insert into database
    $query = "INSERT INTO contact_submissions (full_name, email, message, status, created_at) VALUES (?, ?, ?, 'new', NOW())";
    $params = [$full_name, $email, $message];
    
    $contact_id = db_insert($query, $params);
    
    if ($contact_id) {
        error_log("Contact submission saved - ID: $contact_id, Name: $full_name, Email: $email");
        sendJsonResponse('success', 'Contact submission received successfully', ['contact_id' => $contact_id], 201);
    } else {
        error_log("Failed to save contact submission - Name: $full_name, Email: $email");
        sendJsonResponse('error', 'Failed to submit contact form', null, 500);
    }
}

/**
 * Handle PUT requests - Update contact submission status or notes
 */
function handle_put_contact() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['contact_id'])) {
        sendJsonResponse('error', 'Contact ID is required', null, 400);
    }

    $contact_id = (int)$data['contact_id'];
    
    // Get old values for audit log
    $old_query = "SELECT * FROM contact_submissions WHERE contact_id = ?";
    $old_data = db_fetch_one($old_query, [$contact_id]);
    if (!$old_data) {
        sendJsonResponse('error', 'Contact submission not found', null, 404);
    }
    
    $updates = [];
    $params = [];

    // Build dynamic update query
    if (isset($data['status'])) {
        $valid_statuses = ['new', 'read', 'archived'];
        if (!in_array($data['status'], $valid_statuses)) {
            sendJsonResponse('error', 'Invalid status', null, 400);
        }
        $updates[] = "status = ?";
        $params[] = $data['status'];
    }

    if (isset($data['response_notes'])) {
        $updates[] = "response_notes = ?";
        $params[] = $data['response_notes'];
    }

    if (empty($updates)) {
        sendJsonResponse('error', 'No fields to update', null, 400);
    }

    $params[] = $contact_id;
    $query = "UPDATE contact_submissions SET " . implode(", ", $updates) . " WHERE contact_id = ?";

    if (db_execute($query, $params)) {
        // Log audit action
        log_audit_action('UPDATE', 'contact_submissions', $contact_id, $old_data, $data);
        sendJsonResponse('success', 'Contact submission updated successfully');
    } else {
        sendJsonResponse('error', 'Failed to update contact submission', null, 500);
    }
}

/**
 * Handle DELETE requests - Archive or delete contact submission
 */
function handle_delete_contact() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['contact_id'])) {
        sendJsonResponse('error', 'Contact ID is required', null, 400);
    }

    $contact_id = (int)$data['contact_id'];
    
    // Get old values for audit log
    $old_query = "SELECT * FROM contact_submissions WHERE contact_id = ?";
    $old_data = db_fetch_one($old_query, [$contact_id]);
    if (!$old_data) {
        sendJsonResponse('error', 'Contact submission not found', null, 404);
    }
    
    $hard_delete = $data['hard_delete'] ?? false;

    if ($hard_delete) {
        // Hard delete from database
        $query = "DELETE FROM contact_submissions WHERE contact_id = ?";
        $success = db_execute($query, [$contact_id]);
    } else {
        // Soft delete - archive only
        $query = "UPDATE contact_submissions SET status = 'archived' WHERE contact_id = ?";
        $success = db_execute($query, [$contact_id]);
    }

    if ($success) {
        // Log audit action
        log_audit_action('DELETE', 'contact_submissions', $contact_id, $old_data, null);
        sendJsonResponse('success', 'Contact submission deleted successfully');
    } else {
        sendJsonResponse('error', 'Failed to delete contact submission', null, 500);
    }
}
?>