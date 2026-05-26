<?php
/**
 * admin_users.php - Admin User Management API
 * 
 * Endpoints for admin to manage users and view registrations
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

// ==================== GET ALL USERS ====================
if ($action === 'list' && $method === 'GET') {
    $role = $_GET['role'] ?? null;
    $users = get_all_users($role);
    respond('success', 'Users retrieved', $users);
}

// ==================== GET SINGLE USER ====================
if ($action === 'get' && $method === 'GET') {
    $user_id = $_GET['user_id'] ?? null;
    if (!$user_id) respond('error', 'User ID required');
    
    $user = get_user_by_id($user_id);
    if (!$user) respond('error', 'User not found');
    
    // Don't return password hash
    unset($user['password']);
    
    respond('success', 'User retrieved', $user);
}

// ==================== CREATE USER (ADMIN ONLY) ====================
if ($action === 'create' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields
    if (empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
        respond('error', 'Missing required fields: email, password, full_name');
    }
    
    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        respond('error', 'Invalid email format');
    }
    
    $user_id = create_user($data);
    
    if ($user_id) {
        $user = get_user_by_id($user_id);
        unset($user['password']);
        respond('success', 'User created successfully', $user);
    } else {
        respond('error', 'Failed to create user. Email may already exist.');
    }
}

// ==================== UPDATE USER ====================
if ($action === 'update' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = $data['user_id'] ?? null;
    
    if (!$user_id) respond('error', 'User ID required');
    
    if (update_user_profile($user_id, $data)) {
        $user = get_user_by_id($user_id);
        unset($user['password']);
        respond('success', 'User updated successfully', $user);
    } else {
        respond('error', 'Failed to update user');
    }
}

// ==================== GET USER EVENT REGISTRATIONS ====================
if ($action === 'registrations' && $method === 'GET') {
    $user_id = $_GET['user_id'] ?? null;
    if (!$user_id) respond('error', 'User ID required');
    
    $registrations = get_user_registrations($user_id);
    respond('success', 'User registrations retrieved', $registrations);
}

respond('error', 'Invalid action');

?>
