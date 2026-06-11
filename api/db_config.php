<?php
/**
 * db_config.php - MySQL Database Configuration & Connection Handler
 * 
 * This file handles MySQL database connections and provides utility functions
 * for secure database operations using prepared statements.
 * 
 * Database Credentials (Update these with your MySQL settings)
 */

// Enable error reporting but don't display errors (log them instead to avoid breaking JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// ==================== DATABASE CONFIGURATION ====================
// MySQL connection credentials - CHANGE THESE AS NEEDED
define('DB_HOST', 'localhost');              // MySQL server host
define('DB_USER', 'root');                   // MySQL username (default XAMPP)
define('DB_PASS', '');                       // MySQL password (empty for XAMPP default)
define('DB_NAME', 'club_portfolio');         // Database name

// ==================== DATABASE CONNECTION ====================
/**
 * Create and return a MySQLi connection
 * 
 * @return mysqli|bool Connection object or false on failure
 */
function get_db_connection() {
    static $connection = null;
    
    if ($connection === null) {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check connection
        if ($connection->connect_error) {
            log_error("Database Connection Error: " . $connection->connect_error);
            http_response_code(500);
            die(json_encode([
                'status' => 'error',
                'message' => 'Database connection failed. Please try again later.'
            ]));
        }
        
        // Set charset to UTF-8
        $connection->set_charset("utf8mb4");
    }
    
    return $connection;
}

// ==================== DATABASE QUERY FUNCTIONS ====================

/**
 * Execute a SELECT query and return results
 * 
 * @param string $query SQL query with ? placeholders
 * @param array $params Parameters to bind
 * @return array Result rows as associative arrays
 */
function db_select($query, $params = []) {
    $conn = get_db_connection();
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        log_error("Query Prepare Error: " . $conn->error);
        return [];
    }
    
    // Bind parameters with proper type detection
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        log_error("Query Execution Error: " . $stmt->error);
        $stmt->close();
        return [];
    }
    
    $result = $stmt->get_result();
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    $stmt->close();
    return $rows;
}

/**
 * Execute an INSERT query
 * 
 * @param string $query SQL query with ? placeholders
 * @param array $params Parameters to bind
 * @return int|bool Last insert ID on success, false on failure
 */
function db_insert($query, $params = []) {
    $conn = get_db_connection();
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        log_error("Insert Query Prepare Error: " . $conn->error);
        return false;
    }
    
    // Bind parameters with proper type detection
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        log_error("Insert Execution Error: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $insert_id = $stmt->insert_id;
    $stmt->close();
    
    return $insert_id;
}

/**
 * Execute an UPDATE or DELETE query
 * 
 * @param string $query SQL query with ? placeholders
 * @param array $params Parameters to bind
 * @return bool True on success, false on failure
 */
function db_execute($query, $params = []) {
    $conn = get_db_connection();
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        log_error("Execute Query Prepare Error: " . $conn->error);
        return false;
    }
    
    // Bind parameters with proper type detection
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        log_error("Execute Query Error: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $stmt->close();
    return true;
}

/**
 * Execute a query and return a single row
 * 
 * @param string $query SQL query with ? placeholders
 * @param array $params Parameters to bind
 * @return array|null Single row as associative array or null if not found
 */
function db_fetch_one($query, $params = []) {
    $results = db_select($query, $params);
    return !empty($results) ? $results[0] : null;
}

/**
 * Get total rows affected by last query
 * 
 * @return int Number of affected rows
 */
function db_affected_rows() {
    $conn = get_db_connection();
    return $conn->affected_rows;
}

// ==================== HELPER FUNCTIONS ====================

/**
 * Hash a password for secure storage
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify a password against its hash
 * 
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Log errors to a file for debugging
 * 
 * @param string $message Error message
 */
function log_error($message) {
    $log_file = __DIR__ . '/../logs/error.log';
    $dir = dirname($log_file);
    
    if (!file_exists($dir)) {
        @mkdir($dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, $log_file);
}

/**
 * Send JSON response
 * 
 * @param string $status 'success' or 'error'
 * @param string $message Response message
 * @param mixed $data Additional data
 */
function respond($status, $message, $data = null) {
    header('Content-Type: application/json');
    // Always return 200 - let JSON body indicate success/error
    http_response_code(200);
    
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

?>