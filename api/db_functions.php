<?php
/**
 * db_functions.php - Database Business Logic Functions
 * 
 * Contains reusable functions for managing events, users, registrations, and comments
 * These functions handle data validation and database operations
 */

require_once __DIR__ . '/db_config.php';

// ==================== EVENT MANAGEMENT ====================

/**
 * Get all events (with optional filtering)
 * 
 * @param string $status Filter by status (optional)
 * @return array Array of events
 */
function get_all_events($status = null) {
    $query = "SELECT e.*, u.full_name as admin_organizer_name
              FROM events e
              LEFT JOIN users u ON e.organizer_id = u.user_id
              ";
    
    $params = [];
    
    if ($status) {
        $query .= " WHERE e.status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY e.event_date DESC";
    
    $events = db_select($query, $params);
    
    // Fetch registration counts and other details for each event
    foreach ($events as &$event) {
        // Count all registrations
        $registered = db_fetch_one(
            "SELECT COUNT(*) as count FROM registrations WHERE event_id = ?",
            [$event['event_id']]
        );
        $event['registered_count'] = $registered['count'] ?? 0;
        
        // Get agenda
        $event['agenda'] = db_select(
            "SELECT agenda_time as time, activity FROM event_agenda WHERE event_id = ? ORDER BY display_order",
            [$event['event_id']]
        );
        
        // Get equipment
        $equipment = db_select(
            "SELECT equipment_name FROM event_equipment WHERE event_id = ?",
            [$event['event_id']]
        );
        $event['required_equipment'] = array_map(fn($e) => $e['equipment_name'], $equipment);
    }
    
    return $events;
}

/**
 * Get a single event by ID with all related data
 * 
 * @param int $event_id Event ID
 * @return array|null Event data with agenda and equipment
 */
function get_event_by_id($event_id) {
    $query = "SELECT e.*, u.full_name as admin_organizer_name
              FROM events e
              LEFT JOIN users u ON e.organizer_id = u.user_id
              WHERE e.event_id = ?";
    
    $event = db_fetch_one($query, [$event_id]);
    
    if ($event) {
        // Get registration count from consolidated registrations table
        $registered = db_fetch_one(
            "SELECT COUNT(*) as count FROM registrations WHERE event_id = ?",
            [$event_id]
        );
        $event['registered_count'] = $registered['count'] ?? 0;
        
        // Get agenda - format with 'time' and 'activity' keys
        $agenda = db_select(
            "SELECT agenda_time as time, activity FROM event_agenda WHERE event_id = ? ORDER BY display_order",
            [$event_id]
        );
        $event['agenda'] = $agenda;
        
        // Get equipment - return as simple array of strings
        $equipment = db_select(
            "SELECT equipment_name FROM event_equipment WHERE event_id = ?",
            [$event_id]
        );
        $event['required_equipment'] = array_map(fn($e) => $e['equipment_name'], $equipment);
        
        // Get comments - now comments store full_name directly
        $event['comments'] = db_select(
            "SELECT c.* FROM comments c
             WHERE c.event_id = ?
             ORDER BY c.created_at DESC",
            [$event_id]
        );
    }
    
    return $event;
}

/**
 * Create a new event
 * 
 * @param array $data Event data
 * @return int|bool Event ID on success, false on failure
 */
function create_event($data) {
    $query = "INSERT INTO events (title, description, category, status, event_date, 
              start_time, end_time, location, organizer_id, price, capacity, image_url, organizer_name, organizer_bio, organizer_image)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $data['title'],
        $data['description'],
        $data['category'] ?? '',
        $data['status'] ?? 'upcoming',
        $data['event_date'],
        $data['start_time'],
        empty($data['end_time']) ? null : $data['end_time'],
        $data['location'],
        $data['organizer_id'],
        $data['price'] ?? 0,
        $data['capacity'],
        $data['image_url'] ?? '',
        $data['organizer_name'] ?? '',
        $data['organizer_bio'] ?? '',
        $data['organizer_image'] ?? ''
    ];
    
    $event_id = db_insert($query, $params);
    
    // Log the create action in audit_log
    if ($event_id) {
        $new_values = [
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'] ?? '',
            'status' => $data['status'] ?? 'upcoming',
            'event_date' => $data['event_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
            'location' => $data['location'],
            'price' => $data['price'] ?? 0,
            'capacity' => $data['capacity']
        ];
        log_audit_action('CREATE', 'events', $event_id, null, $new_values);
    }
    
    return $event_id;
}

/**
 * Update an event
 * 
 * @param int $event_id Event ID
 * @param array $data Updated event data
 * @return bool Success status
 */
function update_event($event_id, $data) {
    $allowed_fields = ['title', 'description', 'category', 'status', 'event_date', 
                      'start_time', 'end_time', 'location', 'price', 'capacity', 'image_url', 
                      'organizer_name', 'organizer_bio', 'organizer_image'];
    
    // Get old values before update
    $old_event = get_event_by_id($event_id);
    $old_values = $old_event ? array_intersect_key($old_event, array_flip($allowed_fields)) : null;
    
    $updates = [];
    $params = [];
    $new_values = [];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowed_fields)) {
            // Convert empty strings to null for optional time/text fields
            if (empty($value) && $key === 'end_time') {
                $value = null;
            }
            $updates[] = "$key = ?";
            $params[] = $value;
            $new_values[$key] = $value;
        }
    }
    
    if (empty($updates)) return false;
    
    $params[] = $event_id;
    $query = "UPDATE events SET " . implode(', ', $updates) . " WHERE event_id = ?";
    
    $result = db_execute($query, $params);
    
    // Log the update action in audit_log
    if ($result) {
        log_audit_action('UPDATE', 'events', $event_id, $old_values, $new_values);
    }
    
    return $result;
}

/**
 * Delete an event
 * 
 * @param int $event_id Event ID
 * @return bool Success status
 */
function delete_event($event_id) {
    // Get event data before deletion for audit log
    $old_event = get_event_by_id($event_id);
    
    $result = db_execute("DELETE FROM events WHERE event_id = ?", [$event_id]);
    
    // Log the delete action in audit_log
    if ($result && $old_event) {
        $old_values = [
            'title' => $old_event['title'],
            'description' => $old_event['description'],
            'category' => $old_event['category'],
            'status' => $old_event['status'],
            'event_date' => $old_event['event_date'],
            'start_time' => $old_event['start_time'],
            'end_time' => $old_event['end_time'],
            'location' => $old_event['location'],
            'price' => $old_event['price'],
            'capacity' => $old_event['capacity']
        ];
        log_audit_action('DELETE', 'events', $event_id, $old_values, null);
    }
    
    return $result;
}

/**
 * Add event agenda item
 * 
 * @param int $event_id Event ID
 * @param array $agenda_items Array of agenda items
 * @return bool Success status
 */
function add_event_agenda($event_id, $agenda_items) {
    foreach ($agenda_items as $index => $item) {
        $query = "INSERT INTO event_agenda (event_id, agenda_time, activity, display_order)
                 VALUES (?, ?, ?, ?)";
        
        if (!db_insert($query, [$event_id, $item['time'], $item['activity'], $index])) {
            return false;
        }
    }
    return true;
}

/**
 * Add event equipment requirements
 * 
 * @param int $event_id Event ID
 * @param array $equipment Array of equipment names
 * @return bool Success status
 */
function add_event_equipment($event_id, $equipment) {
    foreach ($equipment as $item) {
        $query = "INSERT INTO event_equipment (event_id, equipment_name) VALUES (?, ?)";
        if (!db_insert($query, [$event_id, $item])) {
            return false;
        }
    }
    return true;
}

// ==================== USER MANAGEMENT ====================

/**
 * Get user by email
 * 
 * @param string $email User email
 * @return array|null User data
 */
function get_user_by_email($email) {
    return db_fetch_one("SELECT * FROM users WHERE email = ?", [$email]);
}

/**
 * Get user by ID
 * 
 * @param int $user_id User ID
 * @return array|null User data
 */
function get_user_by_id($user_id) {
    return db_fetch_one("SELECT * FROM users WHERE user_id = ?", [$user_id]);
}

/**
 * Get all users
 * 
 * @param string $role Filter by role (optional)
 * @return array Array of users
 */
function get_all_users($role = null) {
    $query = "SELECT user_id, email, full_name, role, phone, created_at FROM users";
    $params = [];
    
    if ($role) {
        $query .= " WHERE role = ?";
        $params[] = $role;
    }
    
    $query .= " ORDER BY created_at DESC";
    return db_select($query, $params);
}

/**
 * Create a new user
 * 
 * @param array $data User data
 * @return int|bool User ID on success, false on failure
 */
function create_user($data) {
    // Check if email already exists
    if (get_user_by_email($data['email'])) {
        return false; // Email already exists
    }
    
    $query = "INSERT INTO users (email, password, full_name, role, phone)
              VALUES (?, ?, ?, ?, ?)";
    
    $params = [
        $data['email'],
        hash_password($data['password']),
        $data['full_name'],
        $data['role'] ?? 'member',
        $data['phone'] ?? ''
    ];
    
    $user_id = db_insert($query, $params);
    
    // Log the create action in audit_log
    if ($user_id) {
        $new_values = [
            'email' => $data['email'],
            'full_name' => $data['full_name'],
            'role' => $data['role'] ?? 'member',
            'phone' => $data['phone'] ?? ''
        ];
        log_audit_action('CREATE', 'users', $user_id, null, $new_values);
    }
    
    return $user_id;
}

/**
 * Update user profile (admin can update email, role, full_name, phone)
 * 
 * @param int $user_id User ID
 * @param array $data Updated user data
 * @return bool Success status
 */
function update_user_profile($user_id, $data) {
    $allowed_fields = ['full_name', 'phone', 'email', 'role'];
    
    // Get old values before update
    $old_user = get_user_by_id($user_id);
    $old_values = $old_user ? array_intersect_key($old_user, array_flip($allowed_fields)) : null;
    
    $updates = [];
    $params = [];
    $new_values = [];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowed_fields)) {
            $updates[] = "$key = ?";
            $params[] = $value;
            $new_values[$key] = $value;
        }
    }
    
    if (empty($updates)) return false;
    
    $params[] = $user_id;
    $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = ?";
    
    $result = db_execute($query, $params);
    
    // Log the update action in audit_log
    if ($result) {
        log_audit_action('UPDATE', 'users', $user_id, $old_values, $new_values);
    }
    
    return $result;
}

/**
 * Delete a user
 * 
 * @param int $user_id User ID
 * @return bool Success status
 */
function delete_user($user_id) {
    // Get user data before deletion for audit log
    $old_user = get_user_by_id($user_id);
    
    $result = db_execute("DELETE FROM users WHERE user_id = ?", [$user_id]);
    
    // Log the delete action in audit_log
    if ($result && $old_user) {
        $old_values = [
            'email' => $old_user['email'],
            'full_name' => $old_user['full_name'],
            'role' => $old_user['role'],
            'phone' => $old_user['phone']
        ];
        log_audit_action('DELETE', 'users', $user_id, $old_values, null);
    }
    
    return $result;
}

/**
 * Verify user login
 * 
 * @param string $email User email
 * @param string $password User password
 * @return array|bool User data on success, false on failure
 */
function verify_login($email, $password) {
    $user = get_user_by_email($email);
    
    if ($user && verify_password($password, $user['password'])) {
        return $user;
    }
    
    return false;
}

// ==================== REGISTRATION MANAGEMENT ====================

/**
 * Register user for an event
 * 
 * @param int $event_id Event ID
 * @param int $user_id User ID
 * @return int|bool Registration ID on success, false on failure
 */
function register_user_for_event($event_id, $user_id) {
    // Check if user already registered
    $existing = db_fetch_one(
        "SELECT registration_id FROM registrations WHERE event_id = ? AND user_id = ?",
        [$event_id, $user_id]
    );
    
    if ($existing) {
        return false; // Already registered
    }
    
    // Check event capacity
    $event = get_event_by_id($event_id);
    if ($event['registered_count'] >= $event['capacity']) {
        return false; // Event is full
    }
    
    $query = "INSERT INTO registrations (event_id, user_id, status) VALUES (?, ?, 'registered')";
    return db_insert($query, [$event_id, $user_id]);
}

/**
 * Get event registrations
 * 
 * @param int $event_id Event ID
 * @return array Array of registrations
 */
function get_event_registrations($event_id) {
    return db_select(
        "SELECT r.*, u.full_name, u.email FROM registrations r
         LEFT JOIN users u ON r.user_id = u.user_id
         WHERE r.event_id = ?
         ORDER BY r.registration_date DESC",
        [$event_id]
    );
}

/**
 * Get user event registrations
 * 
 * @param int $user_id User ID
 * @return array Array of registered events
 */
function get_user_registrations($user_id) {
    return db_select(
        "SELECT e.*, r.registration_id, r.status as registration_status, r.registration_date
         FROM registrations r
         LEFT JOIN events e ON r.event_id = e.event_id
         WHERE r.user_id = ?
         ORDER BY e.event_date DESC",
        [$user_id]
    );
}

/**
 * Cancel registration
 * 
 * @param int $registration_id Registration ID
 * @return bool Success status
 */
function cancel_registration($registration_id) {
    return db_execute(
        "UPDATE registrations SET status = 'cancelled' WHERE registration_id = ?",
        [$registration_id]
    );
}

// ==================== COMMENTS MANAGEMENT ====================

/**
 * Add a comment to an event
 * 
 * @param int $event_id Event ID
 * @param int $user_id User ID
 * @param string $comment_text Comment text
 * @param int $rating Rating (1-5)
 * @return int|bool Comment ID on success, false on failure
 */
function add_comment($event_id, $user_id, $comment_text, $rating = null) {
    $query = "INSERT INTO comments (event_id, user_id, comment_text, rating) VALUES (?, ?, ?, ?)";
    return db_insert($query, [$event_id, $user_id, $comment_text, $rating]);
}

/**
 * Get event comments
 * 
 * @param int $event_id Event ID
 * @return array Array of comments
 */
function get_event_comments($event_id) {
    return db_select(
        "SELECT c.*, e.title as event_title FROM comments c
         LEFT JOIN events e ON c.event_id = e.event_id
         WHERE c.event_id = ?
         ORDER BY c.created_at DESC",
        [$event_id]
    );
}

/**
 * Get all comments from all events with event details
 * 
 * @return array Array of comments with event information
 */
function get_all_comments() {
    return db_select(
        "SELECT c.*, e.title as event_title FROM comments c
         LEFT JOIN events e ON c.event_id = e.event_id
         ORDER BY c.created_at DESC"
    );
}

/**
 * Delete a comment
 * 
 * @param int $comment_id Comment ID
 * @return bool Success status
 */
function delete_comment($comment_id) {
    return db_execute("DELETE FROM comments WHERE comment_id = ?", [$comment_id]);
}

// ==================== REMINDER MANAGEMENT ====================

/**
 * Create an event reminder for user
 * 
 * @param int $event_id Event ID
 * @param int $user_id User ID
 * @param string $reminder_time Reminder time (YYYY-MM-DD HH:MM:SS)
 * @return int|bool Reminder ID on success, false on failure
 */
function create_reminder($event_id, $user_id, $reminder_time) {
    $query = "INSERT INTO reminders (event_id, user_id, reminder_time) VALUES (?, ?, ?)";
    return db_insert($query, [$event_id, $user_id, $reminder_time]);
}

/**
 * Get pending reminders to send
 * 
 * @return array Array of pending reminders
 */
function get_pending_reminders() {
    return db_select(
        "SELECT r.*, e.title as event_title, e.event_date, e.start_time, 
                u.email, u.full_name
         FROM reminders r
         LEFT JOIN events e ON r.event_id = e.event_id
         LEFT JOIN users u ON r.user_id = u.user_id
         WHERE r.is_sent = FALSE AND r.reminder_time <= NOW()
         ORDER BY r.reminder_time ASC"
    );
}

/**
 * Mark reminder as sent
 * 
 * @param int $reminder_id Reminder ID
 * @return bool Success status
 */
function mark_reminder_sent($reminder_id) {
    return db_execute("UPDATE reminders SET is_sent = TRUE WHERE reminder_id = ?", [$reminder_id]);
}

// ==================== AUDIT LOG MANAGEMENT ====================

/**
 * Log an admin action to the audit_log table
 * 
 * @param string $action Action type (CREATE, UPDATE, DELETE, etc.)
 * @param string $table_name Name of the table affected
 * @param int $record_id ID of the affected record
 * @param array|null $old_values Previous values (for updates)
 * @param array|null $new_values New values
 * @return int|bool Log ID on success, false on failure
 */
function log_audit_action($action, $table_name, $record_id = null, $old_values = null, $new_values = null) {
    // Only log if user is logged in
    // Session is already started by auth.php in API context
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        $admin_id = $_SESSION['user_id'];
        $old_json = $old_values ? json_encode($old_values) : null;
        $new_json = $new_values ? json_encode($new_values) : null;
        
        $query = "INSERT INTO audit_log (admin_id, action, table_name, record_id, old_values, new_values)
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        return db_insert($query, [$admin_id, $action, $table_name, $record_id, $old_json, $new_json]);
    } catch (Exception $e) {
        // Log audit action failures silently to prevent breaking the main operation
        error_log("Audit logging error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all audit logs with optional filtering
 * 
 * @param string|null $action Filter by action type
 * @param string|null $table_name Filter by table name
 * @param int|null $admin_id Filter by admin user ID
 * @param int $limit Limit results
 * @return array Array of audit logs
 */
function get_audit_logs($action = null, $table_name = null, $admin_id = null, $limit = 100) {
    $query = "SELECT al.*, u.full_name as admin_name
              FROM audit_log al
              LEFT JOIN users u ON al.admin_id = u.user_id
              WHERE 1=1";
    
    $params = [];
    
    if ($action) {
        $query .= " AND al.action = ?";
        $params[] = $action;
    }
    
    if ($table_name) {
        $query .= " AND al.table_name = ?";
        $params[] = $table_name;
    }
    
    if ($admin_id) {
        $query .= " AND al.admin_id = ?";
        $params[] = $admin_id;
    }
    
    $query .= " ORDER BY al.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    return db_select($query, $params);
}

/**
 * Get recent audit logs for dashboard display
 * 
 * @param int $limit Number of recent logs to retrieve
 * @return array Array of recent audit logs
 */
function get_recent_audit_logs($limit = 20) {
    return db_select(
        "SELECT al.*, u.full_name as admin_name
         FROM audit_log al
         LEFT JOIN users u ON al.admin_id = u.user_id
         ORDER BY al.created_at DESC
         LIMIT ?",
        [$limit]
    );
}

/**
 * Get audit log statistics for dashboard
 * 
 * @return array Statistics including total logs, actions breakdown, etc.
 */
function get_audit_statistics() {
    $stats = [];
    
    // Total audit logs
    $total = db_fetch_one("SELECT COUNT(*) as count FROM audit_log");
    $stats['total_logs'] = $total['count'] ?? 0;
    
    // Logs by action
    $by_action = db_select(
        "SELECT action, COUNT(*) as count FROM audit_log GROUP BY action ORDER BY count DESC"
    );
    $stats['by_action'] = $by_action;
    
    // Logs by table
    $by_table = db_select(
        "SELECT table_name, COUNT(*) as count FROM audit_log GROUP BY table_name ORDER BY count DESC"
    );
    $stats['by_table'] = $by_table;
    
    // Logs by admin (top 5)
    $by_admin = db_select(
        "SELECT al.admin_id, u.full_name, COUNT(*) as count 
         FROM audit_log al
         LEFT JOIN users u ON al.admin_id = u.user_id
         GROUP BY al.admin_id, u.full_name
         ORDER BY count DESC
         LIMIT 5"
    );
    $stats['by_admin'] = $by_admin;
    
    // Today's logs
    $today = db_fetch_one(
        "SELECT COUNT(*) as count FROM audit_log WHERE DATE(created_at) = CURDATE()"
    );
    $stats['today_logs'] = $today['count'] ?? 0;
    
    return $stats;
}

?>
