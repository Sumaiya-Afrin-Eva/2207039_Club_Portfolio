<?php
/**
 * Debug script to test audit log API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/db_config.php';

// Test direct database query
try {
    $conn = get_db_connection();
    $query = "SELECT al.*, u.full_name as admin_name FROM audit_log al LEFT JOIN users u ON al.admin_id = u.user_id ORDER BY al.created_at DESC LIMIT 10";
    $result = $conn->query($query);
    
    if (!$result) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Query failed: ' . $conn->error,
            'query' => $query
        ]);
        exit();
    }
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    echo json_encode([
        'status' => 'success',
        'count' => count($logs),
        'data' => $logs
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>