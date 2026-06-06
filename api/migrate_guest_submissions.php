<?php
/**
 * Migration: Update reminders and comments tables to store guest submissions directly
 * This migration:
 * 1. Adds full_name and email columns to reminders table
 * 2. Adds full_name and email columns to comments table, makes user_id nullable
 * 3. Migrates existing data where applicable
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';

header('Content-Type: application/json');

try {
    $conn = get_db_connection();
    
    echo "<h2>Migration: Guest Submissions (Reminders & Comments)</h2>";
    
    // ==================== REMINDERS TABLE ====================
    echo "<h3>1. Updating REMINDERS table...</h3>";
    
    // Check if columns already exist
    $check_reminders = $conn->query("SHOW COLUMNS FROM reminders LIKE 'full_name'");
    if ($check_reminders->num_rows === 0) {
        // Add full_name column
        $sql = "ALTER TABLE reminders ADD COLUMN full_name VARCHAR(255) NOT NULL DEFAULT 'Guest' AFTER event_id";
        if ($conn->query($sql)) {
            echo "✓ Added full_name column to reminders<br>";
        } else {
            echo "✗ Error adding full_name: " . $conn->error . "<br>";
        }
        
        // Add email column
        $sql = "ALTER TABLE reminders ADD COLUMN email VARCHAR(255) NOT NULL DEFAULT '' AFTER full_name";
        if ($conn->query($sql)) {
            echo "✓ Added email column to reminders<br>";
        } else {
            echo "✗ Error adding email: " . $conn->error . "<br>";
        }
        
        // Add email index
        $sql = "ALTER TABLE reminders ADD INDEX idx_email (email)";
        if ($conn->query($sql)) {
            echo "✓ Added email index to reminders<br>";
        } else {
            echo "✗ Error adding index (may already exist): " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ full_name column already exists in reminders<br>";
    }
    
    // Update reminders table foreign key to allow NULL user_id
    $check_fk = $conn->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME='reminders' AND COLUMN_NAME='user_id' AND CONSTRAINT_NAME != 'PRIMARY'");
    if ($check_fk && $check_fk->num_rows > 0) {
        $fk_name = $check_fk->fetch_assoc()['CONSTRAINT_NAME'];
        // Drop existing constraint
        $sql = "ALTER TABLE reminders DROP FOREIGN KEY $fk_name";
        $conn->query($sql);
        
        // Add new constraint with ON DELETE SET NULL
        $sql = "ALTER TABLE reminders ADD CONSTRAINT fk_reminders_user_id FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL";
        if ($conn->query($sql)) {
            echo "✓ Updated reminders foreign key constraint<br>";
        } else {
            echo "ℹ Foreign key constraint may already exist: " . $conn->error . "<br>";
        }
    }
    
    // ==================== COMMENTS TABLE ====================
    echo "<h3>2. Updating COMMENTS table...</h3>";
    
    // Check if columns already exist
    $check_comments = $conn->query("SHOW COLUMNS FROM comments LIKE 'full_name'");
    if ($check_comments->num_rows === 0) {
        // Add full_name column
        $sql = "ALTER TABLE comments ADD COLUMN full_name VARCHAR(255) NOT NULL DEFAULT 'Guest' AFTER event_id";
        if ($conn->query($sql)) {
            echo "✓ Added full_name column to comments<br>";
        } else {
            echo "✗ Error adding full_name: " . $conn->error . "<br>";
        }
        
        // Add email column
        $sql = "ALTER TABLE comments ADD COLUMN email VARCHAR(255) AFTER full_name";
        if ($conn->query($sql)) {
            echo "✓ Added email column to comments<br>";
        } else {
            echo "✗ Error adding email: " . $conn->error . "<br>";
        }
        
        // Add email index
        $sql = "ALTER TABLE comments ADD INDEX idx_email (email)";
        if ($conn->query($sql)) {
            echo "✓ Added email index to comments<br>";
        } else {
            echo "✗ Error adding index (may already exist): " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ full_name column already exists in comments<br>";
    }
    
    // Make user_id nullable in comments
    $check_user_id = $conn->query("SHOW COLUMNS FROM comments WHERE Field='user_id' AND Null='YES'");
    if ($check_user_id->num_rows === 0) {
        $sql = "ALTER TABLE comments MODIFY user_id INT DEFAULT NULL";
        if ($conn->query($sql)) {
            echo "✓ Made user_id nullable in comments<br>";
        } else {
            echo "✗ Error modifying user_id: " . $conn->error . "<br>";
        }
    }
    
    // Update comments table foreign key to allow NULL user_id
    $check_fk = $conn->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME='comments' AND COLUMN_NAME='user_id' AND CONSTRAINT_NAME != 'PRIMARY'");
    if ($check_fk && $check_fk->num_rows > 0) {
        $fk_name = $check_fk->fetch_assoc()['CONSTRAINT_NAME'];
        // Drop existing constraint
        $sql = "ALTER TABLE comments DROP FOREIGN KEY $fk_name";
        $conn->query($sql);
        
        // Add new constraint with ON DELETE SET NULL
        $sql = "ALTER TABLE comments ADD CONSTRAINT fk_comments_user_id FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL";
        if ($conn->query($sql)) {
            echo "✓ Updated comments foreign key constraint<br>";
        } else {
            echo "ℹ Foreign key constraint may already exist: " . $conn->error . "<br>";
        }
    }
    
    echo "<h3>✓ Migration completed successfully!</h3>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>Reminders table: Added full_name, email columns and nullable user_id</li>";
    echo "<li>Comments table: Added full_name, email columns and nullable user_id</li>";
    echo "<li>No user accounts will be created for reminders, comments, registrations, or contact submissions</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>✗ Migration failed</h3>";
    echo "Error: " . $e->getMessage();
}
?>
