<?php
/**
 * crud_team.php - Team Member Management API
 * 
 * Handles CRUD operations for team members:
 * - GET: Retrieve all active team members (public) or all team members (admin)
 * - GET with ID: Retrieve a specific team member
 * - POST: Create a new team member (admin only)
 * - PUT: Update a team member (admin only)
 * - DELETE: Delete/archive a team member (admin only)
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$db = get_db_connection();

try {
    switch ($method) {
        case 'GET':
            handle_get_team($db);
            break;
        case 'POST':
            handle_post_team($db);
            break;
        case 'PUT':
            handle_put_team($db);
            break;
        case 'DELETE':
            handle_delete_team($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Handle GET requests for team members
 */
function handle_get_team($db) {
    $team_id = $_GET['team_id'] ?? null;
    $include_inactive = $_GET['include_inactive'] ?? false;

    if ($team_id) {
        // Get specific team member
        $query = "SELECT * FROM team_members WHERE team_id = ?";
        $result = db_fetch_one($query, [$team_id]);
        
        if ($result) {
            http_response_code(200);
            echo json_encode(['data' => $result]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Team member not found']);
        }
    } else {
        // Get all team members
        $query = "SELECT * FROM team_members";
        if (!$include_inactive) {
            $query .= " WHERE is_active = 1";
        }
        $query .= " ORDER BY display_order ASC";
        
        $result = db_select($query, []);
        
        http_response_code(200);
        echo json_encode(['data' => $result]);
    }
}

/**
 * Handle POST requests - Create new team member
 */
function handle_post_team($db) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['full_name']) || !isset($data['position'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Full name and position are required']);
        return;
    }

    $full_name = $data['full_name'];
    $position = $data['position'];
    $bio = $data['bio'] ?? null;
    $image_url = $data['image_url'] ?? null;
    $email = $data['email'] ?? null;
    $phone = $data['phone'] ?? null;
    $display_order = $data['display_order'] ?? 0;
    $is_active = $data['is_active'] ?? 1;

    $query = "INSERT INTO team_members (full_name, position, bio, image_url, email, phone, display_order, is_active)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [$full_name, $position, $bio, $image_url, $email, $phone, $display_order, $is_active];
    
    if (db_insert($query, $params)) {
        http_response_code(201);
        echo json_encode(['message' => 'Team member created successfully', 'team_id' => $db->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create team member']);
    }
}

/**
 * Handle PUT requests - Update team member
 */
function handle_put_team($db) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['team_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Team ID is required']);
        return;
    }

    $team_id = $data['team_id'];
    $updates = [];
    $params = [];

    // Build dynamic update query
    if (isset($data['full_name'])) {
        $updates[] = "full_name = ?";
        $params[] = $data['full_name'];
    }
    if (isset($data['position'])) {
        $updates[] = "position = ?";
        $params[] = $data['position'];
    }
    if (isset($data['bio'])) {
        $updates[] = "bio = ?";
        $params[] = $data['bio'];
    }
    if (isset($data['image_url'])) {
        $updates[] = "image_url = ?";
        $params[] = $data['image_url'];
    }
    if (isset($data['email'])) {
        $updates[] = "email = ?";
        $params[] = $data['email'];
    }
    if (isset($data['phone'])) {
        $updates[] = "phone = ?";
        $params[] = $data['phone'];
    }
    if (isset($data['display_order'])) {
        $updates[] = "display_order = ?";
        $params[] = $data['display_order'];
    }
    if (isset($data['is_active'])) {
        $updates[] = "is_active = ?";
        $params[] = $data['is_active'];
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }

    $params[] = $team_id;
    $query = "UPDATE team_members SET " . implode(", ", $updates) . " WHERE team_id = ?";

    if (db_execute($query, $params)) {
        http_response_code(200);
        echo json_encode(['message' => 'Team member updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update team member']);
    }
}

/**
 * Handle DELETE requests - Archive or delete team member
 */
function handle_delete_team($db) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['team_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Team ID is required']);
        return;
    }

    $team_id = $data['team_id'];
    $hard_delete = $data['hard_delete'] ?? false;

    if ($hard_delete) {
        // Hard delete
        $query = "DELETE FROM team_members WHERE team_id = ?";
    } else {
        // Soft delete - archive
        $query = "UPDATE team_members SET is_active = 0 WHERE team_id = ?";
    }

    if (db_execute($query, [$team_id])) {
        http_response_code(200);
        echo json_encode(['message' => 'Team member deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete team member']);
    }
}
?>
