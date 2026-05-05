<?php
// Database configuration (using JSON files for demo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DATA_DIR', __DIR__ . '/../data/');
define('EVENTS_FILE', DATA_DIR . 'events.json');
define('REGISTRATIONS_FILE', DATA_DIR . 'registrations.json');
define('COMMENTS_FILE', DATA_DIR . 'comments.json');
define('REMINDERS_FILE', DATA_DIR . 'reminders.json');

// Ensure data directory exists
if (!file_exists(DATA_DIR)) {
    @mkdir(DATA_DIR, 0777, true);
}

// Verify data directory is writable
if (!is_writable(DATA_DIR)) {
    @chmod(DATA_DIR, 0777);
}

// Headers for JSON responses
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? null) === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Helper functions
function load_json($file) {
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true) ?: [];
    }
    return [];
}

function save_json($file, $data) {
    $dir = dirname($file);
    if (!file_exists($dir)) {
        @mkdir($dir, 0777, true);
    }
    if (!is_writable($dir)) {
        @chmod($dir, 0777);
    }
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

function get_timestamp() {
    return date('Y-m-d H:i:s');
}

function generate_id() {
    return 'id_' . time() . '_' . rand(1000, 9999);
}

function respond($status, $message, $data = null) {
    $response = [
        'status' => $status,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    http_response_code($status === 'success' ? 200 : 400);
    echo json_encode($response);
    exit();
}
?>
