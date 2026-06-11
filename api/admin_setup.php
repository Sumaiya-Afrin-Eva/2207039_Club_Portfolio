<?php
/**
 * admin_setup.php - Database Initialization & Admin Setup
 * 
 * This script sets up the database and creates the default admin account.
 * Access this file once in your browser: http://localhost/Club_Portfolio/api/admin_setup.php
 * After setup is complete, DELETE this file from your server for security!
 * 
 * Default Admin Credentials:
 * Email: admin@clubportfolio.com
 * Password: Admin@123
 */

require_once __DIR__ . '/db_config.php';

$conn = get_db_connection();

// Array of SQL statements to create tables
$sql_statements = [
    // Users Table
    "CREATE TABLE IF NOT EXISTS users (
        user_id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        role ENUM('admin', 'member') DEFAULT 'member',
        bio TEXT,
        profile_image VARCHAR(500),
        phone VARCHAR(20),
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_role (role),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Events Table
    "CREATE TABLE IF NOT EXISTS events (
        event_id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        category VARCHAR(100),
        status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
        event_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME,
        location VARCHAR(500) NOT NULL,
        organizer_id INT,
        price DECIMAL(10, 2) DEFAULT 0,
        capacity INT NOT NULL,
        image_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (organizer_id) REFERENCES users(user_id) ON DELETE SET NULL,
        INDEX idx_status (status),
        INDEX idx_date (event_date),
        FULLTEXT INDEX ft_search (title, description, location)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Event Agenda Table
    "CREATE TABLE IF NOT EXISTS event_agenda (
        agenda_id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT NOT NULL,
        agenda_time TIME NOT NULL,
        activity VARCHAR(500) NOT NULL,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        INDEX idx_event (event_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Event Equipment Table
    "CREATE TABLE IF NOT EXISTS event_equipment (
        equipment_id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT NOT NULL,
        equipment_name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        INDEX idx_event (event_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Registrations Table
    "CREATE TABLE IF NOT EXISTS registrations (
        registration_id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT NOT NULL,
        user_id INT NOT NULL,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('registered', 'attended', 'cancelled') DEFAULT 'registered',
        attendance_remarks TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY unique_registration (event_id, user_id),
        INDEX idx_event (event_id),
        INDEX idx_user (user_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Comments Table
    "CREATE TABLE IF NOT EXISTS comments (
        comment_id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT NOT NULL,
        user_id INT NOT NULL,
        comment_text TEXT NOT NULL,
        rating INT CHECK (rating >= 1 AND rating <= 5),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_event (event_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Reminders Table
    "CREATE TABLE IF NOT EXISTS reminders (
        reminder_id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT NOT NULL,
        user_id INT NOT NULL,
        reminder_time DATETIME NOT NULL,
        is_sent BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at TIMESTAMP NULL,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_event (event_id),
        INDEX idx_user (user_id),
        INDEX idx_reminder_time (reminder_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Audit Log Table
    "CREATE TABLE IF NOT EXISTS audit_log (
        log_id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT NOT NULL,
        action VARCHAR(100),
        table_name VARCHAR(100),
        record_id INT,
        old_values JSON,
        new_values JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_admin (admin_id),
        INDEX idx_action (action)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

// Execute each statement
$created_tables = [];
foreach ($sql_statements as $statement) {
    if ($conn->query($statement)) {
        // Extract table name for display
        if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
            $created_tables[] = $matches[1];
        }
    } else {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
        echo "Error during table creation:<br>";
        echo htmlspecialchars($conn->error);
        echo "</div>";
    }
}

// Create default admin user using prepared statement (secure)
$admin_email = 'admin@clubportfolio.com';
$admin_password = 'Admin@123'; // IMPORTANT: Change after first login!
$admin_name = 'Admin User';

// Use prepared statement for security
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$result = $stmt->get_result();

$admin_created = false;
if ($result->num_rows === 0) {
    $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);
    
    $insert_stmt = $conn->prepare("INSERT INTO users (email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, 1)");
    $insert_stmt->bind_param("ssss", $admin_email, $hashed_password, $admin_name, $role = 'admin');
    
    if ($insert_stmt->execute()) {
        $admin_created = true;
    }
    $insert_stmt->close();
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Club Portfolio</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 700px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .status-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .status-info {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            color: #1565c0;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .status-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        h3 {
            color: #333;
            margin-top: 25px;
            margin-bottom: 15px;
            font-size: 18px;
        }
        ul, ol {
            margin-left: 20px;
            line-height: 1.8;
        }
        code {
            background: #f5f5f5;
            padding: 3px 8px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        table td:first-child {
            font-weight: bold;
            width: 30%;
            color: #667eea;
        }
        .table-created {
            background: #f9f9f9;
        }
        .credentials {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-family: monospace;
        }
        .credentials p {
            margin: 8px 0;
            line-height: 1.6;
        }
        .btn-login {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
            text-align: center;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎉 Club Portfolio - Database Setup Complete</h1>
        
        <div class="status-success">
            <strong>✓ Success!</strong> All database tables have been created successfully.
        </div>

        <h3>📊 Tables Created:</h3>
        <table class="table-created">
            <tr>
                <td>1. Users</td>
                <td>Admin and member profiles</td>
            </tr>
            <tr>
                <td>2. Events</td>
                <td>Event information and details</td>
            </tr>
            <tr>
                <td>3. Event Agenda</td>
                <td>Event schedule and timeline</td>
            </tr>
            <tr>
                <td>4. Event Equipment</td>
                <td>Required equipment for events</td>
            </tr>
            <tr>
                <td>5. Registrations</td>
                <td>User event registrations</td>
            </tr>
            <tr>
                <td>6. Comments</td>
                <td>Event reviews and feedback</td>
            </tr>
            <tr>
                <td>7. Reminders</td>
                <td>Event reminders for users</td>
            </tr>
            <tr>
                <td>8. Audit Log</td>
                <td>Admin activity tracking</td>
            </tr>
        </table>

        <h3>🔐 Default Admin Account:</h3>
        <div class="credentials">
            Email: admin@clubportfolio.com<br>
            Password: Admin@123
        </div>

        <div class="status-warning">
            <strong>⚠ IMPORTANT:</strong> Change the admin password immediately after first login!
        </div>

        <h3>📝 Next Steps:</h3>
        <ol>
            <li>Click the button below to go to Admin Login</li>
            <li>Use the credentials above to log in</li>
            <li>Change the default password</li>
            <li><strong>Delete this file (admin_setup.php) for security!</strong></li>
            <li>Start creating events and managing the club</li>
        </ol>

        <a href="/Club_Portfolio/admin_login.html" class="btn-login">Go to Admin Login →</a>

        <h3>🗑️ After Setup:</h3>
        <div class="status-info">
            <strong>Security Reminder:</strong> Delete the <code>admin_setup.php</code> file from your server after setup is complete. This file should only be used for initialization.
        </div>
    </div>
</body>
</html>