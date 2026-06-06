<?php
/**
 * Migration Script: Add event_participants table
 * This script adds the event_participants table to separate guest registrations from login users
 * Run this once to update the database schema
 */

require_once __DIR__ . '/db_config.php';

$conn = get_db_connection();

if (!$conn) {
    die("Database connection failed!");
}

// Check if table already exists
$tableExists = $conn->query("SHOW TABLES LIKE 'event_participants'");
if ($tableExists && $tableExists->num_rows > 0) {
    echo "✓ event_participants table already exists<br>";
    exit;
}

// Create event_participants table
$sql = "CREATE TABLE IF NOT EXISTS `event_participants` (
  `participant_id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20),
  `address` TEXT,
  `institute` VARCHAR(255),
  `academic_year` VARCHAR(50),
  `gender` VARCHAR(20),
  `experience` VARCHAR(255),
  `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_event_participant` (`event_id`, `email`),
  INDEX `idx_event` (`event_id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_registration_date` (`registration_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ Successfully created event_participants table<br>";
    echo "✓ Database migration completed<br>";
    echo "<br>";
    echo "Summary:<br>";
    echo "- event_participants table stores guest event registrations<br>";
    echo "- users table now only stores login users (with passwords)<br>";
    echo "- registrations table stores logged-in user event registrations<br>";
    echo "- System now properly separates login users from event participants<br>";
} else {
    echo "✗ Error creating table: " . $conn->error . "<br>";
}

$conn->close();
?>
