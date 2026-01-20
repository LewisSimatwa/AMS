<?php
// Disable HTML error display and log errors instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering to catch any stray output
ob_start();

// Clear any output and send headers
ob_end_clean();

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load dependencies
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Verify JWT token authentication
try {
    verifyAuth();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["error" => "Authentication failed: " . $e->getMessage()]);
    exit;
}

$user_id = $_GET['user_id'];
$institution_id = $_GET['institution_id'];

try {
    // Get user's primary role
    $stmt = $db->prepare("
        SELECT r.name as role, u.id as user_id
        FROM users u
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE u.id = ? AND u.institution_id = ?
        LIMIT 1
    ");
    
    $stmt->execute([$user_id, $institution_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        http_response_code(200);
        echo json_encode([
            'role' => $result['role'],
            'user_id' => (int)$result['user_id']
        ]);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "User role not found"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    exit;
}