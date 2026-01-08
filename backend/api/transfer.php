<?php
// Disable HTML error display and log errors instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();
ob_end_clean();

// CORS headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load dependencies
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Verify authentication
try {
    verifyAuth();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["error" => "Authentication failed: " . $e->getMessage()]);
    exit;
}

$institution_id = $_GET['institution_id'];
$user_id = $_GET['user_id'];

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON input"]);
    exit;
}

// Validate required fields
$assetId = $input['assetId'] ?? null;
$toDepartmentId = $input['toDepartmentId'] ?? null;
$location = $input['location'] ?? '';
$remarks = $input['remarks'] ?? '';

if (!$assetId || !$toDepartmentId) {
    http_response_code(400);
    echo json_encode(["error" => "Asset and department are required"]);
    exit;
}

try {
    $db->beginTransaction();

    // Check if asset exists and is available
    $stmt = $db->prepare("
        SELECT a.id, a.asset_code, a.name, a.status, a.department_id,
               d.name AS from_department_name
        FROM assets a
        LEFT JOIN departments d ON a.department_id = d.id
        WHERE a.id = ? AND a.institution_id = ?
    ");
    $stmt->execute([$assetId, $institution_id]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$asset) {
        throw new Exception("Asset not found");
    }

    if ($asset['status'] !== 'available') {
        throw new Exception("Asset must be available for transfer. Current status: " . $asset['status']);
    }

    // Verify target department exists and belongs to institution
    $stmt = $db->prepare("
        SELECT id, name, code
        FROM departments 
        WHERE id = ? AND institution_id = ?
    ");
    $stmt->execute([$toDepartmentId, $institution_id]);
    $toDepartment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$toDepartment) {
        throw new Exception("Target department not found");
    }

    // Check if already in the same department
    if ($asset['department_id'] == $toDepartmentId) {
        throw new Exception("Asset is already in " . $toDepartment['name']);
    }

    $fromDepartmentId = $asset['department_id'];

    // Update asset department
    $stmt = $db->prepare("
        UPDATE assets 
        SET department_id = ?,
            updated_at = now()
        WHERE id = ?
    ");
    $stmt->execute([$toDepartmentId, $assetId]);

    // Create transfer transaction record
    $stmt = $db->prepare("
        INSERT INTO transactions (
            institution_id,
            asset_id,
            transaction_type,
            from_department_id,
            to_department_id,
            from_location,
            to_location,
            remarks,
            performed_by,
            performed_at
        ) VALUES (?, ?, 'transfer', ?, ?, ?, ?, ?, ?, now())
    ");
    $stmt->execute([
        $institution_id,
        $assetId,
        $fromDepartmentId,
        $toDepartmentId,
        '',
        $location,
        $remarks,
        $user_id
    ]);

    // Create audit log
    $stmt = $db->prepare("
        INSERT INTO audit_logs (
            institution_id,
            user_id,
            entity_type,
            entity_id,
            action,
            old_values,
            new_values,
            details,
            created_at
        ) VALUES (?, ?, 'assets', ?, 'TRANSFER', ?, ?, ?, now())
    ");
    $stmt->execute([
        $institution_id,
        $user_id,
        $assetId,
        json_encode([
            'department_id' => $fromDepartmentId,
            'department_name' => $asset['from_department_name']
        ]),
        json_encode([
            'department_id' => $toDepartmentId,
            'department_name' => $toDepartment['name']
        ]),
        json_encode([
            'location' => $location,
            'remarks' => $remarks
        ])
    ]);

    $db->commit();

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Asset transferred successfully",
        "asset_code" => $asset['asset_code'],
        "from_department" => $asset['from_department_name'],
        "to_department" => $toDepartment['name']
    ]);

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(400);
    echo json_encode(["error" => $e->getMessage()]);
}
?>