<?php
/**
 * test_connection.php - Database Connection & Login Test
 * 
 * This script tests the database connection and admin login functionality.
 * Access: http://localhost/Club_Portfolio/api/test_connection.php
 * 
 * Delete this file after testing!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<h1>Club Portfolio - Database & Login Test</h1>';
echo '<hr>';

// Test 1: Database Connection
echo '<h2>Test 1: Database Connection</h2>';
try {
    $conn = new mysqli('localhost', 'root', '', 'club_portfolio');
    
    if ($conn->connect_error) {
        echo '<p style="color: red;">❌ Connection Failed: ' . $conn->connect_error . '</p>';
    } else {
        echo '<p style="color: green;">✅ Connection Successful</p>';
        echo '<p>Server: ' . $conn->server_info . '</p>';
        
        // Test 2: Check if users table exists
        echo '<h2>Test 2: Database Structure</h2>';
        $result = $conn->query("SHOW TABLES FROM club_portfolio");
        $tables = [];
        
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        
        if (count($tables) === 0) {
            echo '<p style="color: red;">❌ No tables found! Run admin_setup.php first.</p>';
        } else {
            echo '<p style="color: green;">✅ Found ' . count($tables) . ' tables</p>';
            echo '<ul>';
            foreach ($tables as $table) {
                echo '<li>' . htmlspecialchars($table) . '</li>';
            }
            echo '</ul>';
        }
        
        // Test 3: Check admin user
        echo '<h2>Test 3: Admin User</h2>';
        $admin_check = $conn->query("SELECT * FROM users WHERE email = 'admin@clubportfolio.com'");
        
        if ($admin_check->num_rows === 0) {
            echo '<p style="color: orange;">⚠️ Admin user not found</p>';
        } else {
            $admin = $admin_check->fetch_assoc();
            echo '<p style="color: green;">✅ Admin user found</p>';
            echo '<ul>';
            echo '<li>Email: ' . htmlspecialchars($admin['email']) . '</li>';
            echo '<li>Full Name: ' . htmlspecialchars($admin['full_name']) . '</li>';
            echo '<li>Role: ' . htmlspecialchars($admin['role']) . '</li>';
            echo '<li>Status: ' . ($admin['is_active'] ? 'Active' : 'Inactive') . '</li>';
            echo '<li>Password Hash: ' . substr($admin['password'], 0, 20) . '...</li>';
            echo '</ul>';
            
            // Test 4: Password verification
            echo '<h2>Test 4: Password Verification</h2>';
            $test_password = 'Admin@123';
            
            if (password_verify($test_password, $admin['password'])) {
                echo '<p style="color: green;">✅ Password verification successful!</p>';
                echo '<p>The password "Admin@123" is correct.</p>';
            } else {
                echo '<p style="color: red;">❌ Password verification failed</p>';
                echo '<p>Tested password: ' . htmlspecialchars($test_password) . '</p>';
                
                // Try to create a new hash and test
                echo '<h3>Creating test hash...</h3>';
                $test_hash = password_hash($test_password, PASSWORD_BCRYPT);
                echo '<p>Test hash: ' . $test_hash . '</p>';
                
                if (password_verify($test_password, $test_hash)) {
                    echo '<p style="color: green;">✅ Hash verification works in principle</p>';
                } else {
                    echo '<p style="color: red;">❌ Hash verification failed</p>';
                }
            }
        }
        
        // Test 5: Simulate login
        echo '<h2>Test 5: Simulated Login</h2>';
        
        $login_email = 'admin@clubportfolio.com';
        $login_password = 'Admin@123';
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $login_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo '<p style="color: red;">❌ User not found</p>';
        } else {
            $user = $result->fetch_assoc();
            
            if (password_verify($login_password, $user['password'])) {
                echo '<p style="color: green;">✅ Login would succeed</p>';
                echo '<ul>';
                echo '<li>User ID: ' . $user['user_id'] . '</li>';
                echo '<li>Email: ' . $user['email'] . '</li>';
                echo '<li>Name: ' . $user['full_name'] . '</li>';
                echo '<li>Role: ' . $user['role'] . '</li>';
                echo '</ul>';
            } else {
                echo '<p style="color: red;">❌ Login would fail - password incorrect</p>';
            }
        }
        $stmt->close();
        
        $conn->close();
    }
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Exception: ' . $e->getMessage() . '</p>';
}

echo '<hr>';
echo '<p><strong>Next Steps:</strong></p>';
echo '<ol>';
echo '<li>If all tests passed, your database setup is complete and login should work</li>';
echo '<li>Go to: <a href="/Club_Portfolio/admin_login.html" target="_blank">Admin Login</a></li>';
echo '<li>Use credentials: admin@clubportfolio.com / Admin@123</li>';
echo '<li>If tests failed, check the issues above and run admin_setup.php if needed</li>';
echo '<li>Delete this test file (test_connection.php) after verification</li>';
echo '</ol>';
?>
