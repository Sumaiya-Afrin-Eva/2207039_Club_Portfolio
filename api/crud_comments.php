<?php
/**
 * crud_comments.php - Comments & Reminders CRUD API
 * 
 * Endpoints for managing event comments and reminders:
 * - GET /crud_comments.php?action=comments&event_id=ID - Get event comments
 * - POST /crud_comments.php?action=add_comment - Add comment to event
 * - DELETE /crud_comments.php?action=delete_comment&id=ID - Delete comment
 * - POST /crud_comments.php?action=add_reminder - Add event reminder
 * - GET /crud_comments.php?action=reminders - Get user's reminders
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

// ==================== GET EVENT COMMENTS ====================
if ($action === 'comments' && $method === 'GET') {
    $event_id = $_GET['event_id'] ?? null;
    if (!$event_id) send_response('error', 'Event ID required');
    
    $comments = get_event_comments($event_id);
    send_response('success', 'Comments retrieved', $comments);
}

// ==================== ADD COMMENT ====================
if ($action === 'add_comment' && $method === 'POST') {
    require_login();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['event_id']) || empty($data['comment_text'])) {
        send_response('error', 'Event ID and comment text required');
    }
    
    $user_id = $_SESSION['user_id'];
    $event_id = $data['event_id'];
    $comment_text = $data['comment_text'];
    $rating = $data['rating'] ?? null;
    
    // Validate rating if provided
    if ($rating && ($rating < 1 || $rating > 5)) {
        send_response('error', 'Rating must be between 1 and 5');
    }
    
    // Verify user is registered for this event
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT registration_id FROM registrations WHERE event_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $event_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        send_response('error', 'You must be registered for this event to comment');
    }
    $stmt->close();
    
    $comment_id = add_comment($event_id, $user_id, $comment_text, $rating);
    
    if ($comment_id) {
        send_response('success', 'Comment added successfully', ['comment_id' => $comment_id]);
    } else {
        send_response('error', 'Failed to add comment');
    }
}

// ==================== DELETE COMMENT ====================
if ($action === 'delete_comment' && $method === 'DELETE') {
    require_login();
    
    $comment_id = $_GET['id'] ?? null;
    if (!$comment_id) send_response('error', 'Comment ID required');
    
    // Verify user owns this comment (or is admin)
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT * FROM comments WHERE comment_id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment = $result->fetch_assoc();
    $stmt->close();
    
    if (!$comment) {
        send_response('error', 'Comment not found');
    }
    
    if ($comment['user_id'] != $_SESSION['user_id'] && !is_admin_logged_in()) {
        send_response('error', 'Unauthorized');
    }
    
    if (delete_comment($comment_id)) {
        // Log audit action if admin is deleting
        if (is_admin_logged_in()) {
            log_audit_action('DELETE', 'comments', $comment_id, $comment, null);
        }
        send_response('success', 'Comment deleted successfully');
    } else {
        send_response('error', 'Failed to delete comment');
    }
}

// ==================== ADD REMINDER ====================
if ($action === 'add_reminder' && $method === 'POST') {
    require_login();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['event_id']) || empty($data['reminder_time'])) {
        send_response('error', 'Event ID and reminder time required');
    }
    
    $user_id = $_SESSION['user_id'];
    $event_id = $data['event_id'];
    $reminder_time = $data['reminder_time'];
    
    // Validate datetime format
    if (!strtotime($reminder_time)) {
        send_response('error', 'Invalid reminder time format');
    }
    
    $reminder_id = create_reminder($event_id, $user_id, $reminder_time);
    
    if ($reminder_id) {
        send_response('success', 'Reminder added successfully', ['reminder_id' => $reminder_id]);
    } else {
        send_response('error', 'Failed to add reminder');
    }
}

// ==================== GET USER REMINDERS ====================
if ($action === 'reminders' && $method === 'GET') {
    require_login();
    
    // Check if admin is requesting all reminders
    if (is_admin_logged_in()) {
        // Admin view: fetch all reminders
        $status_filter = $_GET['status'] ?? null;
        
        if ($status_filter === 'pending') {
            $reminders = db_select(
                "SELECT r.*, e.title as event_title, e.event_date, e.start_time
                 FROM reminders r
                 LEFT JOIN events e ON r.event_id = e.event_id
                 WHERE r.is_sent = 0
                 ORDER BY r.reminder_time ASC"
            );
        } elseif ($status_filter === 'sent') {
            $reminders = db_select(
                "SELECT r.*, e.title as event_title, e.event_date, e.start_time
                 FROM reminders r
                 LEFT JOIN events e ON r.event_id = e.event_id
                 WHERE r.is_sent = 1
                 ORDER BY r.reminder_time ASC"
            );
        } else {
            $reminders = db_select(
                "SELECT r.*, e.title as event_title, e.event_date, e.start_time
                 FROM reminders r
                 LEFT JOIN events e ON r.event_id = e.event_id
                 ORDER BY r.reminder_time DESC"
            );
        }
    } else {
        // User view: fetch only reminders for their email
        $user_email = $_SESSION['email'] ?? null;
        
        if ($user_email) {
            $reminders = db_select(
                "SELECT r.*, e.title as event_title, e.event_date, e.start_time
                 FROM reminders r
                 LEFT JOIN events e ON r.event_id = e.event_id
                 WHERE r.email = ?
                 ORDER BY r.reminder_time ASC",
                [$user_email]
            );
        } else {
            $reminders = [];
        }
    }
    
    send_response('success', 'Reminders retrieved', $reminders);
}

// ==================== GET SPECIFIC REMINDER ====================
if ($action === 'get_reminder' && $method === 'GET') {
    require_admin_access();
    
    $reminder_id = $_GET['reminder_id'] ?? null;
    if (!$reminder_id) send_response('error', 'Reminder ID required');
    
    $reminder = db_fetch_one(
        "SELECT r.*, e.title as event_title, e.event_date, e.start_time
         FROM reminders r
         LEFT JOIN events e ON r.event_id = e.event_id
         WHERE r.reminder_id = ?",
        [$reminder_id]
    );
    
    if ($reminder) {
        send_response('success', 'Reminder retrieved', $reminder);
    } else {
        send_response('error', 'Reminder not found');
    }
}

// ==================== DELETE REMINDER (ADMIN) ====================
if ($action === 'delete_reminder' && $method === 'DELETE') {
    require_admin_access();
    
    $data = json_decode(file_get_contents('php://input'), true);
    $reminder_id = $data['reminder_id'] ?? null;
    
    if (!$reminder_id) send_response('error', 'Reminder ID required');
    
    $conn = get_db_connection();
    
    // Get reminder data before deletion for audit log
    $stmt = $conn->prepare("SELECT * FROM reminders WHERE reminder_id = ?");
    $stmt->bind_param("i", $reminder_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reminder_data = $result->fetch_assoc();
    $stmt->close();
    
    if (!$reminder_data) {
        send_response('error', 'Reminder not found');
    }
    
    // Delete the reminder
    $stmt = $conn->prepare("DELETE FROM reminders WHERE reminder_id = ?");
    $stmt->bind_param("i", $reminder_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Log audit action
        log_audit_action('DELETE', 'reminders', $reminder_id, $reminder_data, null);
        
        send_response('success', 'Reminder deleted successfully');
    } else {
        $stmt->close();
        send_response('error', 'Failed to delete reminder');
    }
}

// ==================== GET PENDING REMINDERS (ADMIN) ====================
if ($action === 'pending' && $method === 'GET') {
    require_admin_access();
    
    $reminders = get_pending_reminders();
    send_response('success', 'Pending reminders retrieved', $reminders);
}

// ==================== MARK REMINDER AS SENT (ADMIN) ====================
if ($action === 'mark_sent' && $method === 'PUT') {
    require_admin_access();
    
    $reminder_id = $_GET['id'] ?? null;
    if (!$reminder_id) send_response('error', 'Reminder ID required');
    
    if (mark_reminder_sent($reminder_id)) {
        send_response('success', 'Reminder marked as sent');
    } else {
        send_response('error', 'Failed to mark reminder');
    }
}

send_response('error', 'Invalid action');
?>
