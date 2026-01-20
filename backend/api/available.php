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

// Fetch assets for institution
try {
    $stmt = $db->prepare("
        SELECT a.id, a.asset_code, a.name, a.status,
               at.name AS asset_type_name,
               d.name AS department_name
        FROM assets a
        LEFT JOIN asset_types at ON a.asset_type_id = at.id
        LEFT JOIN departments d ON a.department_id = d.id
        WHERE a.institution_id = ?
        ORDER BY a.name
    ");
    
    $stmt->execute([$institution_id]);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode($assets);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error: " . $e->getMessage()]);
    exit;
}