<?php
/**
 * auth.php - Authentication & Session Management
 * 
 * Handles user login, logout, session management, and admin verification
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== LOGIN ====================
/**
 * User login handler
 * 
 * Usage: POST /api/auth.php?action=login
 * Parameters: email, password
 */
if (isset($_GET['action']) && $_GET['action'] === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['email']) || empty($data['password'])) {
        respond('error', 'Email and password required');
    }
    
    $user = verify_login($data['email'], $data['password']);
    
    if ($user) {
        // Store user in session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        // Remove password from response
        unset($user['password']);
        
        respond('success', 'Login successful', $user);
    } else {
        respond('error', 'Invalid email or password');
    }
}

// ==================== LOGOUT ====================
/**
 * User logout handler
 * 
 * Usage: GET /api/auth.php?action=logout
 */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    respond('success', 'Logged out successfully');
}

// ==================== GET CURRENT USER ====================
/**
 * Get currently logged in user
 * 
 * Usage: GET /api/auth.php?action=current_user
 */
if (isset($_GET['action']) && $_GET['action'] === 'current_user') {
    if (is_logged_in()) {
        $user = get_user_by_id($_SESSION['user_id']);
        unset($user['password']);
        respond('success', 'User data', $user);
    } else {
        respond('error', 'Not logged in');
    }
}

// ==================== REGISTER ====================
/**
 * User registration handler
 * 
 * Usage: POST /api/auth.php?action=register
 * Parameters: email, password, full_name
 */
if (isset($_GET['action']) && $_GET['action'] === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
        respond('error', 'Email, password, and full name required');
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        respond('error', 'Invalid email format');
    }
    
    if (strlen($data['password']) < 6) {
        respond('error', 'Password must be at least 6 characters');
    }
    
    $user_id = create_user($data);
    
    if ($user_id) {
        $user = get_user_by_id($user_id);
        
        // Auto-login after registration
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        unset($user['password']);
        respond('success', 'Registration successful', $user);
    } else {
        respond('error', 'Registration failed. Email may already exist.');
    }
}

// ==================== HELPER FUNCTIONS ====================

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if logged in user is admin
 * 
 * @return bool True if admin
 */
function is_admin_logged_in() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get admin data from session
 * 
 * @return array Admin user data
 */
function get_admin_data() {
    if (!is_admin_logged_in()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role']
    ];
}

/**
 * Require admin access
 * Terminates script if user is not admin
 */
function require_admin() {
    if (!is_admin_logged_in()) {
        http_response_code(403);
        respond('error', 'Admin access required');
    }
}

?>
