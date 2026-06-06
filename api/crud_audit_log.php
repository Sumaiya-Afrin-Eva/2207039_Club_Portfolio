<?php
/**
 * crud_audit_log.php - Audit Log Management API
 * 
 * Handles audit log operations:
 * - GET: Retrieve audit logs with filtering
 * - POST: Create/save audit log entries
 */

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';

// Configure session cookie settings BEFORE session_start()
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', '0');
ini_set('session.cookie_lifetime', '0');

// Start session to check auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Log all incoming requests to a file
$debug_log = fopen('/tmp/audit_api_requests.log', 'a');
fwrite($debug_log, date('Y-m-d H:i:s') . " | Action: " . ($_GET['action'] ?? 'list') . " | Session ID: " . session_id() . " | User ID: " . ($_SESSION['user_id'] ?? 'null') . " | Role: " . ($_SESSION['role'] ?? 'null') . " | Cookies: " . json_encode($_COOKIE) . "\n");
fclose($debug_log);

// Check if user is authenticated
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized. Admin access required.'
    ]);
    exit();
}

$action = $_GET['action'] ?? 'list';

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            list_audit_logs();
            break;
        case 'save':
            save_audit_log();
            break;
        case 'get':
            get_audit_log();
            break;
        case 'delete':
            delete_audit_log();
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}

/**
 * List all audit logs with pagination and filtering
 */
function list_audit_logs() {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
    $offset = ($page - 1) * $per_page;
    
    // Filter parameters - NOTE: 'action' is reserved for API action, use 'filter_action' for audit action filter
    $admin_filter = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : null;
    $action_filter = isset($_GET['filter_action']) ? $_GET['filter_action'] : null;  // Use filter_action instead of action
    $table_filter = isset($_GET['table_name']) ? $_GET['table_name'] : null;
    
    // Build query
    $query = "SELECT al.*, u.full_name as admin_name
              FROM audit_log al
              LEFT JOIN users u ON al.admin_id = u.user_id
              WHERE 1=1";
    
    $params = [];
    
    if ($admin_filter) {
        $query .= " AND al.admin_id = ?";
        $params[] = $admin_filter;
    }
    
    if ($action_filter) {
        $query .= " AND al.action = ?";
        $params[] = $action_filter;
    }
    
    if ($table_filter) {
        $query .= " AND al.table_name = ?";
        $params[] = $table_filter;
    }
    
    // Count total
    $count_query = "SELECT COUNT(*) as total FROM audit_log al WHERE 1=1";
    if ($admin_filter) $count_query .= " AND al.admin_id = ?";
    if ($action_filter) $count_query .= " AND al.action = ?";
    if ($table_filter) $count_query .= " AND al.table_name = ?";
    
    $count = db_fetch_one($count_query, $params);
    $total = $count['total'] ?? 0;
    
    // Get paginated results
    $query .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    
    $logs = db_select($query, $params);
    
    // Parse JSON fields
    foreach ($logs as &$log) {
        if ($log['old_values']) {
            $log['old_values'] = json_decode($log['old_values'], true);
        }
        if ($log['new_values']) {
            $log['new_values'] = json_decode($log['new_values'], true);
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $logs,
        'pagination' => [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page)
        ]
    ]);
}

/**
 * Save/create a new audit log entry
 */
function save_audit_log() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'POST method required'
        ]);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['action']) || !isset($data['table_name'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields: action, table_name'
        ]);
        return;
    }
    
    $admin_id = $_SESSION['user_id'];
    $action = $data['action'];
    $table_name = $data['table_name'];
    $record_id = $data['record_id'] ?? null;
    $old_values = isset($data['old_values']) ? json_encode($data['old_values']) : null;
    $new_values = isset($data['new_values']) ? json_encode($data['new_values']) : null;
    
    $query = "INSERT INTO audit_log (admin_id, action, table_name, record_id, old_values, new_values)
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $conn = get_db_connection();
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $conn->error
        ]);
        return;
    }
    
    $stmt->bind_param(
        'ississ',
        $admin_id,
        $action,
        $table_name,
        $record_id,
        $old_values,
        $new_values
    );
    
    if ($stmt->execute()) {
        $log_id = $conn->insert_id;
        $stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Audit log saved successfully',
            'log_id' => $log_id
        ]);
    } else {
        $stmt->close();
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to save audit log: ' . $stmt->error
        ]);
    }
}

/**
 * Get a single audit log entry
 */
function get_audit_log() {
    $log_id = isset($_GET['log_id']) ? (int)$_GET['log_id'] : 0;
    
    if (!$log_id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'log_id is required'
        ]);
        return;
    }
    
    $query = "SELECT al.*, u.full_name as admin_name
              FROM audit_log al
              LEFT JOIN users u ON al.admin_id = u.user_id
              WHERE al.log_id = ?";
    
    $log = db_fetch_one($query, [$log_id]);
    
    if (!$log) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Audit log not found'
        ]);
        return;
    }
    
    if ($log['old_values']) {
        $log['old_values'] = json_decode($log['old_values'], true);
    }
    if ($log['new_values']) {
        $log['new_values'] = json_decode($log['new_values'], true);
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $log
    ]);
}

/**
 * Delete an audit log entry
 */
function delete_audit_log() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'POST method required'
        ]);
        return;
    }
    
    $log_id = isset($_GET['log_id']) ? (int)$_GET['log_id'] : 0;
    
    if (!$log_id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'log_id is required'
        ]);
        return;
    }
    
    $query = "DELETE FROM audit_log WHERE log_id = ?";
    $conn = get_db_connection();
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error'
        ]);
        return;
    }
    
    $stmt->bind_param('i', $log_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode([
            'status' => 'success',
            'message' => 'Audit log deleted successfully'
        ]);
    } else {
        $stmt->close();
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete audit log'
        ]);
    }
}
