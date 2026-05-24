<?php
/**
 * crud_users.php - User Management CRUD API
 * 
 * Admin endpoints for user management:
 * - GET /crud_users.php?action=list - Get all users
 * - GET /crud_users.php?action=get&id=ID - Get single user
 * - POST /crud_users.php?action=create - Create new user
 * - PUT /crud_users.php?action=update&id=ID - Update user
 * - DELETE /crud_users.php?action=delete&id=ID - Delete user
 * - PUT /crud_users.php?action=change_role&id=ID - Change user role
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

function require_admin_access() {
    if (!is_admin_logged_in()) {
        http_response_code(403);
        send_response('error', 'Admin access required');
    }
}

// ==================== LIST ALL USERS ====================
if ($action === 'list' && $method === 'GET') {
    require_admin_access();
    $role = $_GET['role'] ?? null;
    $users = get_all_users($role);
    send_response('success', 'Users retrieved', $users);
}

// ==================== GET SINGLE USER ====================
if ($action === 'get' && $method === 'GET') {
    require_admin_access();
    $user_id = $_GET['id'] ?? null;
    if (!$user_id) send_response('error', 'User ID required');
    
    $user = get_user_by_id($user_id);
    if (!$user) send_response('error', 'User not found');
    
    unset($user['password']);
    send_response('success', 'User retrieved', $user);
}

// ==================== CREATE NEW USER ====================
if ($action === 'create' && $method === 'POST') {
    require_admin_access();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
        send_response('error', 'Email, password, and full name required');
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        send_response('error', 'Invalid email format');
    }
    
    $user_id = create_user($data);
    
    if (!$user_id) {
        send_response('error', 'Failed to create user (email may already exist)');
    }
    
    // Log admin action
    db_execute(
        "INSERT INTO audit_log (admin_id, action, table_name, record_id, new_values)
         VALUES (?, 'CREATE', 'users', ?, ?)",
        [$_SESSION['user_id'], $user_id, json_encode($data)]
    );
    
    send_response('success', 'User created successfully', ['user_id' => $user_id]);
}

// ==================== UPDATE USER ====================
if ($action === 'update' && $method === 'PUT') {
    require_admin_access();
    
    $user_id = $_GET['id'] ?? null;
    if (!$user_id) send_response('error', 'User ID required');
    
    $old_user = get_user_by_id($user_id);
    if (!$old_user) send_response('error', 'User not found');
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (update_user_profile($user_id, $data)) {
        // Log admin action
        db_execute(
            "INSERT INTO audit_log (admin_id, action, table_name, record_id, old_values, new_values)
             VALUES (?, 'UPDATE', 'users', ?, ?, ?)",
            [$_SESSION['user_id'], $user_id, json_encode($old_user), json_encode($data)]
        );
        
        send_response('success', 'User updated successfully');
    } else {
        send_response('error', 'Failed to update user');
    }
}

// ==================== CHANGE USER ROLE ====================
if ($action === 'change_role' && $method === 'PUT') {
    require_admin_access();
    
    $user_id = $_GET['id'] ?? null;
    $new_role = $_GET['role'] ?? null;
    
    if (!$user_id || !$new_role) send_response('error', 'User ID and role required');
    
    if (!in_array($new_role, ['admin', 'member'])) {
        send_response('error', 'Invalid role');
    }
    
    $old_user = get_user_by_id($user_id);
    if (!$old_user) send_response('error', 'User not found');
    
    $query = "UPDATE users SET role = ? WHERE user_id = ?";
    if (db_execute($query, [$new_role, $user_id])) {
        // Log admin action
        $old_user['role'] = $old_user['role'];
        $new_user = $old_user;
        $new_user['role'] = $new_role;
        
        db_execute(
            "INSERT INTO audit_log (admin_id, action, table_name, record_id, old_values, new_values)
             VALUES (?, 'UPDATE', 'users', ?, ?, ?)",
            [$_SESSION['user_id'], $user_id, json_encode($old_user), json_encode($new_user)]
        );
        
        send_response('success', 'User role updated successfully');
    } else {
        send_response('error', 'Failed to change user role');
    }
}

// ==================== DELETE USER ====================
if ($action === 'delete' && $method === 'DELETE') {
    require_admin_access();
    
    $user_id = $_GET['id'] ?? null;
    if (!$user_id) send_response('error', 'User ID required');
    
    $user = get_user_by_id($user_id);
    if (!$user) send_response('error', 'User not found');
    
    $query = "DELETE FROM users WHERE user_id = ?";
    if (db_execute($query, [$user_id])) {
        // Log admin action
        db_execute(
            "INSERT INTO audit_log (admin_id, action, table_name, record_id, old_values)
             VALUES (?, 'DELETE', 'users', ?, ?)",
            [$_SESSION['user_id'], $user_id, json_encode($user)]
        );
        
        send_response('success', 'User deleted successfully');
    } else {
        send_response('error', 'Failed to delete user');
    }
}

send_response('error', 'Invalid action');
?>
