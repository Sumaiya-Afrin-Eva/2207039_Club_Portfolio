<?php
/**
 * config.php - API Configuration and Helper Functions
 * 
 * This file configures the Photography Club API backend. It defines:
 * - Directory paths for JSON data storage
 * - File paths for events, registrations, comments, and reminders
 * - HTTP headers for JSON responses and CORS support
 * - Utility functions for JSON file operations
 * - Helper functions for data generation and response formatting
 * 
 * The application uses JSON files instead of a traditional database for simplicity.
 * All data files are stored in the /data directory.
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==================== FILE PATHS & CONSTANTS ====================
// Define base directory for data storage
define('DATA_DIR', __DIR__ . '/../data/');

// Define file paths for different data types
define('EVENTS_FILE', DATA_DIR . 'events.json');              // Stores all events
define('REGISTRATIONS_FILE', DATA_DIR . 'registrations.json'); // Stores user registrations
define('COMMENTS_FILE', DATA_DIR . 'comments.json');          // Stores event comments
define('REMINDERS_FILE', DATA_DIR . 'reminders.json');        // Stores event reminders

// Ensure data directory exists
if (!file_exists(DATA_DIR)) {
    @mkdir(DATA_DIR, 0777, true);
}

// Verify data directory is writable
if (!is_writable(DATA_DIR)) {
    @chmod(DATA_DIR, 0777);
}

// ==================== HTTP HEADERS CONFIGURATION ====================
// Set response content type to JSON
header('Content-Type: application/json');

// CORS (Cross-Origin Resource Sharing) headers for cross-domain requests
header('Access-Control-Allow-Origin: *');                         // Allow requests from any origin
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); // Allowed HTTP methods
header('Access-Control-Allow-Headers: Content-Type');            // Allowed request headers

// Handle preflight CORS requests (OPTIONS method)
if (($_SERVER['REQUEST_METHOD'] ?? null) === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==================== HELPER FUNCTIONS ====================

/**
 * Load JSON data from file
 * 
 * @param string $file Path to the JSON file
 * @return array Decoded JSON array, or empty array if file doesn't exist
 */
function load_json($file) {
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true) ?: [];
    }
    return [];
}

/**
 * Save data to JSON file
 * 
 * @param string $file Path to the JSON file
 * @param array $data Data array to save
 * @return bool True if save successful, false otherwise
 */
function save_json($file, $data) {
    $dir = dirname($file);
    // Create directory if it doesn't exist
    if (!file_exists($dir)) {
        @mkdir($dir, 0777, true);
    }
    // Ensure directory is writable
    if (!is_writable($dir)) {
        @chmod($dir, 0777);
    }
    // Save JSON with pretty formatting for readability
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Get current timestamp in MySQL format
 * 
 * @return string Formatted timestamp (YYYY-MM-DD HH:MM:SS)
 */
function get_timestamp() {
    return date('Y-m-d H:i:s');
}

/**
 * Generate unique ID for database records
 * Combines current timestamp with random number for uniqueness
 * 
 * @return string Unique identifier (format: id_[timestamp]_[random])
 */
function generate_id() {
    return 'id_' . time() . '_' . rand(1000, 9999);
}

/**
 * Send standardized JSON response to client
 * 
 * @param string $status Response status ('success' or 'error')
 * @param string $message Human-readable message about the response
 * @param mixed $data Optional data to include in response (null by default)
 * @return void Outputs JSON and exits
 */
function respond($status, $message, $data = null) {
    // Build response array
    $response = [
        'status' => $status,
        'message' => $message
    ];
    
    // Include data if provided
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    // Set HTTP response code (200 for success, 400 for error)
    http_response_code($status === 'success' ? 200 : 400);
    
    // Output JSON response and exit
    echo json_encode($response);
    exit();
}
?>