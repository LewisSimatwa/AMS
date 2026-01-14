<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

ob_start();
ob_end_clean();

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

try {
    verifyAuth();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["error" => "Authentication failed: " . $e->getMessage()]);
    exit;
}

$institution_id = $_GET['institution_id'];

try {
    global $db;
    
    $stmt = $db->prepare("
        SELECT 
            id,
            name,
            building,
            floor,
            room,
            description
        FROM locations
        WHERE institution_id = ? AND is_active = true
        ORDER BY name ASC
    ");
    
    $stmt->execute([$institution_id]);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "locations" => $locations
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>