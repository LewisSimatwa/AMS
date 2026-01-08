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
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load dependencies
$configPath = __DIR__ . '/config.php';
$helpersPath = __DIR__ . '/helpers.php';

// Debug: check if files exist
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(["error" => "config.php not found at: " . $configPath]);
    exit;
}

if (!file_exists($helpersPath)) {
    http_response_code(500);
    echo json_encode(["error" => "helpers.php not found at: " . $helpersPath]);
    exit;
}

require_once $configPath;
require_once $helpersPath;

// Verify JWT token authentication
try {
    verifyAuth(); // This sets $_GET['user_id'], $_GET['role'], $_GET['institution_id']
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["error" => "Authentication failed: " . $e->getMessage()]);
    exit;
}

$institution_id = $_GET['institution_id'];
$user_role = $_GET['role'];

// STRICT Role check - only admin, security, and auditor can access audit logs
$allowedRoles = ['admin', 'security', 'auditor'];

if (!in_array(strtolower($user_role), array_map('strtolower', $allowedRoles))) {
    http_response_code(403);
    echo json_encode([
        "error" => "Access denied. Only admin, security, and auditor roles can view audit logs.",
        "your_role" => $user_role
    ]);
    exit;
}

// Fetch audit logs
try {
    $stmt = $db->prepare("
        SELECT
            a.id,
            a.created_at AS timestamp,
            COALESCE(u.username, u.email, 'System') AS performed_by,
            a.action,
            a.entity_type,
            a.entity_id,
            a.old_values AS old_value,
            a.new_values AS new_value,
            i.name AS institution_name
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN institutions i ON a.institution_id = i.id
        WHERE a.institution_id = ?
        ORDER BY a.created_at DESC
        LIMIT 500
    ");
    
    $stmt->execute([$institution_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "logs" => $logs
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error: " . $e->getMessage()]);
    exit;
}
?>