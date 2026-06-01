<?php
/**
 * Fix email column to allow NULL values
 * This allows guest users created from comments to have NULL email addresses
 */

require_once __DIR__ . '/db_config.php';

$conn = get_db_connection();

// Alter the email column to allow NULL
$sql = "ALTER TABLE users MODIFY COLUMN email VARCHAR(255) NULL";

if ($conn->query($sql)) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Email column updated to allow NULL values'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update email column: ' . $conn->error
    ]);
}

$conn->close();
?>
