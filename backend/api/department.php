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

// Fetch departments for the institution
try {
    $stmt = $db->prepare("
        SELECT 
            id, 
            name, 
            code
        FROM departments
        WHERE institution_id = ?
        ORDER BY name
    ");
    
    $stmt->execute([$institution_id]);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode($departments);

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