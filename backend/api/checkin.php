<?php
// Disable HTML error display and log errors instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();
ob_end_clean();

// CORS headers
$allowedOrigins = ['http://localhost:5173', 'http://localhost:5174'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: *");
}
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
$condition = $input['condition'] ?? 'good';
$remarks = $input['remarks'] ?? '';

if (!$assetId) {
    http_response_code(400);
    echo json_encode(["error" => "Asset ID is required"]);
    exit;
}

// Validate condition
$validConditions = ['good', 'fair', 'poor', 'damaged'];
if (!in_array($condition, $validConditions)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid condition value"]);
    exit;
}

try {
    $db->beginTransaction();

    // Check if asset exists and is checked out
    $stmt = $db->prepare("
        SELECT id, asset_code, name, status, current_holder_id, department_id, condition AS old_condition
        FROM assets 
        WHERE id = ? AND institution_id = ?
    ");
    $stmt->execute([$assetId, $institution_id]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$asset) {
        throw new Exception("Asset not found");
    }

    if ($asset['status'] !== 'on_loan' && $asset['status'] !== 'checked_out') {
        throw new Exception("Asset is not checked out. Current status: " . $asset['status']);
    }

    if (!$asset['current_holder_id']) {
        throw new Exception("No current holder found for this asset");
    }

    $fromUserId = $asset['current_holder_id'];

    // Determine new status based on condition
    $newStatus = 'available';
    if ($condition === 'poor' || $condition === 'damaged') {
        $newStatus = 'maintenance';
    }

    // Update asset status
    $stmt = $db->prepare("
        UPDATE assets 
        SET status = ?,
            current_holder_id = NULL,
            condition = ?,
            updated_at = now()
        WHERE id = ?
    ");
    $stmt->execute([$newStatus, $condition, $assetId]);

    // Create check-in transaction record
    $stmt = $db->prepare("
        INSERT INTO transactions (
            institution_id,
            asset_id,
            transaction_type,
            from_user_id,
            from_department_id,
            to_department_id,
            remarks,
            performed_by,
            performed_at
        ) VALUES (?, ?, 'check_in', ?, ?, ?, ?, ?, now())
    ");
    $stmt->execute([
        $institution_id,
        $assetId,
        $fromUserId,
        $asset['department_id'],
        $asset['department_id'],
        $remarks,
        $user_id
    ]);

    // If condition is poor or damaged, create maintenance record
    if ($condition === 'poor' || $condition === 'damaged') {
        $stmt = $db->prepare("
            INSERT INTO maintenance_records (
                institution_id,
                asset_id,
                reported_by,
                maintenance_type,
                description,
                status,
                start_date,
                created_at
            ) VALUES (?, ?, ?, 'corrective', ?, 'open', now(), now())
        ");
        $stmt->execute([
            $institution_id,
            $assetId,
            $user_id,
            "Asset returned in " . $condition . " condition. " . $remarks
        ]);
    }

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
        ) VALUES (?, ?, 'assets', ?, 'CHECK_IN', ?, ?, ?, now())
    ");
    $stmt->execute([
        $institution_id,
        $user_id,
        $assetId,
        json_encode([
            'status' => $asset['status'],
            'current_holder_id' => $fromUserId,
            'condition' => $asset['old_condition']
        ]),
        json_encode([
            'status' => $newStatus,
            'current_holder_id' => null,
            'condition' => $condition
        ]),
        json_encode([
            'remarks' => $remarks
        ])
    ]);

    $db->commit();

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Asset checked in successfully",
        "asset_code" => $asset['asset_code'],
        "new_status" => $newStatus,
        "condition" => $condition
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