<?php
// CRITICAL: Prevent ANY output before JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(0); // Disable all error reporting to prevent HTML output

// Start output buffering to capture any stray output
ob_start();

// Clean any existing output
if (ob_get_length()) ob_clean();

// Set headers FIRST
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Institution-ID");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Function to send JSON and exit cleanly
function sendJSON($data, $code = 200) {
    // Clear any buffered output
    if (ob_get_length()) ob_clean();
    
    http_response_code($code);
    echo json_encode($data);
    
    // Flush and end
    ob_end_flush();
    exit;
}

// Load dependencies
$configPath = __DIR__ . '/config.php';
$helpersPath = __DIR__ . '/helpers.php';

if (!file_exists($configPath)) {
    sendJSON(["error" => "config.php not found"], 500);
}

if (!file_exists($helpersPath)) {
    sendJSON(["error" => "helpers.php not found"], 500);
}

// Capture output from includes
ob_start();
require_once $configPath;
require_once $helpersPath;
ob_end_clean(); // Discard any output from includes

// Verify authentication
try {
    verifyAuth();
} catch (Exception $e) {
    sendJSON(["error" => "Authentication failed: " . $e->getMessage()], 401);
}

$institution_id = $_GET['institution_id'] ?? null;
$user_role = $_GET['role'] ?? null;

if (!$institution_id) {
    sendJSON(["error" => "institution_id is required"], 400);
}

// Role check
$allowedRoles = ['admin', 'security', 'auditor'];

if (!in_array(strtolower($user_role), array_map('strtolower', $allowedRoles))) {
    sendJSON([
        "error" => "Access denied. Only admin, security, and auditor roles can view audit logs.",
        "your_role" => $user_role
    ], 403);
}

// Fetch audit logs
try {
    $stmt = $db->prepare("
        SELECT
            a.id,
            a.created_at,
            COALESCE(u.username, u.email, 'System') AS username,
            u.email AS user_email,
            a.action,
            a.entity_type,
            a.entity_id,
            a.old_values,
            a.new_values,
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

    sendJSON([
        "success" => true,
        "logs" => $logs
    ], 200);

} catch (PDOException $e) {
    sendJSON(["error" => "Database error: " . $e->getMessage()], 500);
} catch (Exception $e) {
    sendJSON(["error" => "Error: " . $e->getMessage()], 500);
}
?>