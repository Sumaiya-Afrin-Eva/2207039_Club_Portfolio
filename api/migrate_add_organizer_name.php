<?php
/**
 * Migration Script: Add organizer_name column to events table
 * 
 * This script adds the organizer_name field to store the name of the event organizer.
 * Run this script once to update the database schema.
 */

require_once __DIR__ . '/db_config.php';

try {
    $conn = get_db_connection();
    
    // Check if column already exists
    $result = $conn->query("SHOW COLUMNS FROM events LIKE 'organizer_name'");
    
    if ($result && $result->num_rows > 0) {
        echo json_encode([
            'status' => 'info',
            'message' => 'organizer_name column already exists'
        ]);
        exit();
    }
    
    // Add organizer_name column after organizer_bio
    $sql = "ALTER TABLE events ADD COLUMN organizer_name VARCHAR(255) AFTER organizer_bio";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode([
            'status' => 'success',
            'message' => 'organizer_name column added successfully to events table'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to add organizer_name column: ' . $conn->error
        ]);
    }
    
    $conn->close();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Migration error: ' . $e->getMessage()
    ]);
}
?>
