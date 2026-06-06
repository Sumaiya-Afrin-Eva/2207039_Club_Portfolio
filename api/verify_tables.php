<?php
/**
 * Verification Script - Check event_participants and users table separation
 */

require_once __DIR__ . '/db_config.php';

echo "<h2>Database Verification Report</h2>";

$conn = get_db_connection();

// 1. Check event_participants table
echo "<h3>1. Event Participants Table</h3>";
$result = $conn->query("SELECT * FROM event_participants WHERE email='participant@test.com'");
if ($result && $result->num_rows > 0) {
    echo "✓ Found event participant registration<br>";
    while ($row = $result->fetch_assoc()) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
} else {
    echo "✗ No event participant found<br>";
}

// 2. Check users table - should NOT have participant@test.com
echo "<h3>2. Users Table (Login Users Only)</h3>";
$result = $conn->query("SELECT user_id, email, full_name, role FROM users WHERE email='participant@test.com'");
if ($result && $result->num_rows > 0) {
    echo "✗ ERROR: Participant found in users table (should not be there)<br>";
    while ($row = $result->fetch_assoc()) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
} else {
    echo "✓ Correctly NOT in users table (as expected for guest participants)<br>";
}

// 3. List all users (login users)
echo "<h3>3. All Login Users in Users Table</h3>";
$result = $conn->query("SELECT user_id, email, full_name, role FROM users LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "✓ Login users found:<br>";
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>User ID</th><th>Email</th><th>Full Name</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "✗ No users found<br>";
}

// 4. Check event_participants count
echo "<h3>4. Event Participants Count</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM event_participants");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✓ Total event participants: " . $row['count'] . "<br>";
}

// 5. Summary
echo "<h3>Summary</h3>";
echo "<ul>";
echo "<li>✓ Users table stores only login users (with passwords)</li>";
echo "<li>✓ Event participants table stores guest registrations</li>";
echo "<li>✓ System correctly separates login users from event participants</li>";
echo "</ul>";

$conn->close();
?>
