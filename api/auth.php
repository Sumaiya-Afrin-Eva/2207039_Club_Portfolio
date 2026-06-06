<?php
/**
 * auth.php - Authentication & Session Management
 * 
 * Handles user login, logout, session management, and admin verification
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';

// Configure session cookie settings BEFORE session_start()
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', '0');  // Allow JavaScript access if needed
ini_set('session.cookie_lifetime', '0');  // Session cookie (expires when browser closes)

// Set cache prevention headers for all auth endpoints
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
header('Content-Type: application/json');

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
        
        // Log login action to audit_log
        $conn = get_db_connection();
        $log_query = "INSERT INTO audit_log (admin_id, action, table_name, new_values)
                      VALUES (?, 'LOGIN', 'users', ?)";
        $stmt = $conn->prepare($log_query);
        if ($stmt) {
            $user_id = $user['user_id'];
            $login_data = json_encode(['email' => $user['email'], 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            $stmt->bind_param('is', $user_id, $login_data);
            $stmt->execute();
            $stmt->close();
        }
        
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
    // Get user ID before clearing session
    $user_id = $_SESSION['user_id'] ?? null;
    
    // Log logout action to audit_log
    if ($user_id) {
        $conn = get_db_connection();
        $log_query = "INSERT INTO audit_log (admin_id, action, table_name, new_values)
                      VALUES (?, 'LOGOUT', 'users', ?)";
        $stmt = $conn->prepare($log_query);
        if ($stmt) {
            $logout_data = json_encode(['ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            $stmt->bind_param('is', $user_id, $logout_data);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Set cache prevention headers
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    
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
