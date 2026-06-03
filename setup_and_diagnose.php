<?php
/**
 * setup_and_diagnose.php - Complete setup and diagnostic tool
 * Visit this page to automatically setup and diagnose the registration system
 */

require_once __DIR__ . '/api/db_config.php';
require_once __DIR__ . '/api/db_functions.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Club Portfolio - Setup & Diagnostics</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial; background: #f5f7fa; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; }
        h2 { color: #667eea; margin: 20px 0 10px 0; font-size: 18px; }
        .test-item { padding: 12px; margin: 10px 0; border-radius: 4px; border-left: 4px solid #ddd; }
        .success { background: #d4edda; border-left-color: #28a745; color: #155724; }
        .error { background: #f8d7da; border-left-color: #dc3545; color: #721c24; }
        .warning { background: #fff3cd; border-left-color: #ffc107; color: #856404; }
        .info { background: #cfe2ff; border-left-color: #0d6efd; color: #084298; }
        button { background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin: 5px; }
        button:hover { background: #5568d3; }
        .details { background: #f9fafb; padding: 12px; border-radius: 4px; margin-top: 10px; font-family: monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all; max-height: 200px; overflow-y: auto; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Club Portfolio - Setup & Diagnostics</h1>
        
        <div class="card">
            <h2>System Diagnostics</h2>
            
            <?php
            // Test 1: PHP Version
            $php_version = phpversion();
            echo '<div class="test-item success">✓ PHP Version: ' . $php_version . '</div>';
            
            // Test 2: Database Connection
            try {
                $conn = get_db_connection();
                if ($conn) {
                    echo '<div class="test-item success">✓ Database Connection: OK</div>';
                } else {
                    echo '<div class="test-item error">✗ Database Connection: FAILED</div>';
                }
            } catch (Exception $e) {
                echo '<div class="test-item error">✗ Database Connection Failed: ' . $e->getMessage() . '</div>';
            }
            
            // Test 3: Database Tables
            $required_tables = ['users', 'events', 'registrations', 'comments'];
            $existing_tables = [];
            $missing_tables = [];
            
            foreach ($required_tables as $table) {
                $query = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'club_portfolio' AND TABLE_NAME = '$table'";
                $result = $conn->query($query);
                if ($result && $result->num_rows > 0) {
                    $existing_tables[] = $table;
                } else {
                    $missing_tables[] = $table;
                }
            }
            
            if (empty($missing_tables)) {
                echo '<div class="test-item success">✓ All required tables exist: ' . implode(', ', $existing_tables) . '</div>';
            } else {
                echo '<div class="test-item error">✗ Missing tables: ' . implode(', ', $missing_tables) . '</div>';
            }
            
            // Test 4: Events Count
            $events_result = $conn->query("SELECT COUNT(*) as count FROM events");
            $events_count = $events_result->fetch_assoc()['count'];
            
            if ($events_count > 0) {
                echo '<div class="test-item success">✓ Events in database: ' . $events_count . '</div>';
            } else {
                echo '<div class="test-item warning">⚠ No events found in database</div>';
            }
            
            // Test 5: Users Count
            $users_result = $conn->query("SELECT COUNT(*) as count FROM users");
            $users_count = $users_result->fetch_assoc()['count'];
            echo '<div class="test-item info">ℹ Users in database: ' . $users_count . '</div>';
            
            // Test 6: Registrations Count
            $regs_result = $conn->query("SELECT COUNT(*) as count FROM registrations");
            $regs_count = $regs_result->fetch_assoc()['count'];
            echo '<div class="test-item info">ℹ Registrations in database: ' . $regs_count . '</div>';
            
            // Test 7: Logs Directory
            $logs_dir = __DIR__ . '/logs';
            if (!file_exists($logs_dir)) {
                @mkdir($logs_dir, 0777, true);
            }
            if (is_writable($logs_dir)) {
                echo '<div class="test-item success">✓ Logs directory: Writable</div>';
            } else {
                echo '<div class="test-item warning">⚠ Logs directory: Not writable</div>';
            }
            ?>
        </div>
        
        <div class="card">
            <h2>Quick Actions</h2>
            <button onclick="setupSampleEvents()">📦 Setup Sample Events</button>
            <button onclick="createTestUser()">👤 Create Test User</button>
            <button onclick="testRegistration()">📝 Test Registration</button>
            <button onclick="location.reload()">🔄 Refresh</button>
            
            <div id="action-result"></div>
        </div>
        
        <div class="card">
            <h2>Sample Events</h2>
            <?php
            if ($events_count > 0) {
                $events = db_select("SELECT event_id, title, capacity FROM events LIMIT 5", []);
                echo '<table style="width:100%; border-collapse: collapse;">';
                echo '<tr style="background: #f0f0f0;"><th style="padding:8px; text-align:left;">ID</th><th style="padding:8px; text-align:left;">Title</th><th style="padding:8px; text-align:left;">Capacity</th></tr>';
                foreach ($events as $event) {
                    echo '<tr style="border-bottom: 1px solid #ddd;"><td style="padding:8px;">' . $event['event_id'] . '</td><td style="padding:8px;">' . $event['title'] . '</td><td style="padding:8px;">' . $event['capacity'] . '</td></tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="test-item warning">No events found. Click "Setup Sample Events" to create them.</div>';
            }
            ?>
        </div>
        
        <div class="card">
            <h2>Testing Pages</h2>
            <p>Visit these pages to test the registration system:</p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li><a href="registration_test.html" target="_blank">registration_test.html</a> - Direct API testing</li>
                <li><a href="home.html" target="_blank">home.html</a> - Main application</li>
            </ul>
        </div>
    </div>

    <script>
        function showResult(message, isSuccess = true) {
            const resultDiv = document.getElementById('action-result');
            resultDiv.innerHTML = '<div class="test-item ' + (isSuccess ? 'success' : 'error') + '" style="margin-top: 15px;">' + message + '</div>';
        }

        async function setupSampleEvents() {
            try {
                const response = await fetch('api/setup_sample_events.php');
                const data = await response.json();
                const msg = data.message || JSON.stringify(data);
                showResult('✓ ' + msg, data.status === 'success' || data.status === 'info');
            } catch (error) {
                showResult('✗ Error: ' + error.message, false);
            }
        }

        async function createTestUser() {
            try {
                const response = await fetch('api/events.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        event_id: 1,
                        name: 'Test User ' + Date.now(),
                        email: 'test' + Date.now() + '@example.com',
                        phone: '01712345678',
                        address: 'Test Address',
                        institute: 'Test Institute',
                        gender: 'Male',
                        academic_year: '2nd Year',
                        experience: 'Beginner'
                    })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    showResult('✓ Test registration successful! Registration ID: ' + data.data.registration_id, true);
                } else {
                    showResult('✗ ' + data.message, false);
                }
            } catch (error) {
                showResult('✗ Error: ' + error.message, false);
            }
        }

        function testRegistration() {
            window.open('registration_test.html', '_blank');
        }

        // Auto-check on load
        window.onload = function() {
            console.log('Setup & Diagnostics page loaded');
        };
    </script>
</body>
</html>
