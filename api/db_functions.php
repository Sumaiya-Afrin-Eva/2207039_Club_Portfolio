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
    $query = "SELECT e.*, u.full_name as organizer_name, u.profile_image as organizer_image,
              COUNT(DISTINCT r.registration_id) as registered_count
              FROM events e
              LEFT JOIN users u ON e.organizer_id = u.user_id
              LEFT JOIN registrations r ON e.event_id = r.event_id AND r.status = 'registered'
              ";
    
    $params = [];
    
    if ($status) {
        $query .= " WHERE e.status = ?";
        $params[] = $status;
    }
    
    $query .= " GROUP BY e.event_id ORDER BY e.event_date DESC";
    
    return db_select($query, $params);
}

/**
 * Get a single event by ID with all related data
 * 
 * @param int $event_id Event ID
 * @return array|null Event data with agenda and equipment
 */
function get_event_by_id($event_id) {
    $query = "SELECT e.*, u.full_name as organizer_name, u.bio as organizer_bio,
              u.profile_image as organizer_image,
              COUNT(DISTINCT r.registration_id) as registered_count
              FROM events e
              LEFT JOIN users u ON e.organizer_id = u.user_id
              LEFT JOIN registrations r ON e.event_id = r.event_id AND r.status = 'registered'
              WHERE e.event_id = ?
              GROUP BY e.event_id";
    
    $event = db_fetch_one($query, [$event_id]);
    
    if ($event) {
        // Get agenda
        $event['agenda'] = db_select(
            "SELECT agenda_time, activity FROM event_agenda WHERE event_id = ? ORDER BY display_order",
            [$event_id]
        );
        
        // Get equipment
        $event['required_equipment'] = db_select(
            "SELECT equipment_name FROM event_equipment WHERE event_id = ?",
            [$event_id]
        );
        
        // Get comments
        $event['comments'] = db_select(
            "SELECT c.*, u.full_name, u.profile_image FROM comments c
             LEFT JOIN users u ON c.user_id = u.user_id
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
              start_time, end_time, location, organizer_id, price, capacity, image_url)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $data['title'],
        $data['description'],
        $data['category'] ?? '',
        $data['status'] ?? 'upcoming',
        $data['event_date'],
        $data['start_time'],
        $data['end_time'] ?? null,
        $data['location'],
        $data['organizer_id'],
        $data['price'] ?? 0,
        $data['capacity'],
        $data['image_url'] ?? ''
    ];
    
    return db_insert($query, $params);
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
                      'start_time', 'end_time', 'location', 'price', 'capacity', 'image_url'];
    
    $updates = [];
    $params = [];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowed_fields)) {
            $updates[] = "$key = ?";
            $params[] = $value;
        }
    }
    
    if (empty($updates)) return false;
    
    $params[] = $event_id;
    $query = "UPDATE events SET " . implode(', ', $updates) . " WHERE event_id = ?";
    
    return db_execute($query, $params);
}

/**
 * Delete an event
 * 
 * @param int $event_id Event ID
 * @return bool Success status
 */
function delete_event($event_id) {
    return db_execute("DELETE FROM events WHERE event_id = ?", [$event_id]);
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
    $query = "SELECT user_id, email, full_name, role, bio, profile_image, created_at FROM users";
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
    
    $query = "INSERT INTO users (email, password, full_name, role, bio, profile_image)
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $params = [
        $data['email'],
        hash_password($data['password']),
        $data['full_name'],
        $data['role'] ?? 'member',
        $data['bio'] ?? '',
        $data['profile_image'] ?? ''
    ];
    
    return db_insert($query, $params);
}

/**
 * Update user profile
 * 
 * @param int $user_id User ID
 * @param array $data Updated user data
 * @return bool Success status
 */
function update_user_profile($user_id, $data) {
    $allowed_fields = ['full_name', 'bio', 'profile_image'];
    
    $updates = [];
    $params = [];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowed_fields)) {
            $updates[] = "$key = ?";
            $params[] = $value;
        }
    }
    
    if (empty($updates)) return false;
    
    $params[] = $user_id;
    $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = ?";
    
    return db_execute($query, $params);
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
        "SELECT r.*, u.full_name, u.email, u.profile_image FROM registrations r
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
        "SELECT c.*, u.full_name, u.profile_image FROM comments c
         LEFT JOIN users u ON c.user_id = u.user_id
         WHERE c.event_id = ?
         ORDER BY c.created_at DESC",
        [$event_id]
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

?>
