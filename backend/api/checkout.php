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
    header("Access-Control-Allow-Origin: *"); // Fallback for development
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
$toUserId = $input['toUserId'] ?? null;
$locationId = $input['locationId'] ?? null;
$remarks = $input['remarks'] ?? '';

if (!$assetId || !$toUserId) {
    http_response_code(400);
    echo json_encode(["error" => "Asset and user are required"]);
    exit;
}

try {
    $db->beginTransaction();

    // Check if asset exists and is available
    $stmt = $db->prepare("
        SELECT id, asset_code, name, status, department_id
        FROM assets 
        WHERE id = ? AND institution_id = ?
    ");
    $stmt->execute([$assetId, $institution_id]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$asset) {
        throw new Exception("Asset not found");
    }

    if ($asset['status'] !== 'available') {
        throw new Exception("Asset is not available for checkout. Current status: " . $asset['status']);
    }

    // Verify user exists and belongs to institution
    $stmt = $db->prepare("
        SELECT id, first_name, last_name
        FROM users 
        WHERE id = ? AND institution_id = ? AND is_active = true
    ");
    $stmt->execute([$toUserId, $institution_id]);
    $toUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$toUser) {
        throw new Exception("User not found or inactive");
    }

    // Verify location if provided
    $locationName = null;
    if ($locationId) {
        $stmt = $db->prepare("
            SELECT name, building, floor, room
            FROM locations 
            WHERE id = ? AND institution_id = ?
        ");
        $stmt->execute([$locationId, $institution_id]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($location) {
            $locationName = $location['name'];
            if ($location['building']) {
                $locationName .= ' - ' . $location['building'];
            }
            if ($location['floor']) {
                $locationName .= ', Floor ' . $location['floor'];
            }
            if ($location['room']) {
                $locationName .= ', Room ' . $location['room'];
            }
        }
    }

    // Update asset status, current holder, and location
    $stmt = $db->prepare("
        UPDATE assets 
        SET status = 'on_loan',
            current_holder_id = ?,
            location_id = ?,
            updated_at = now()
        WHERE id = ?
    ");
    $stmt->execute([$toUserId, $locationId, $assetId]);

    // Create transaction record
    $stmt = $db->prepare("
        INSERT INTO transactions (
            institution_id,
            asset_id,
            transaction_type,
            to_user_id,
            from_department_id,
            to_department_id,
            to_location,
            remarks,
            performed_by,
            performed_at
        ) VALUES (?, ?, 'check_out', ?, ?, ?, ?, ?, ?, now())
    ");
    $stmt->execute([
        $institution_id,
        $assetId,
        $toUserId,
        $asset['department_id'],
        $asset['department_id'],
        $locationName,
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
            new_values,
            details,
            created_at
        ) VALUES (?, ?, 'assets', ?, 'CHECK_OUT', ?, ?, now())
    ");
    $stmt->execute([
        $institution_id,
        $user_id,
        $assetId,
        json_encode([
            'status' => 'on_loan',
            'current_holder_id' => $toUserId,
            'holder_name' => $toUser['first_name'] . ' ' . $toUser['last_name'],
            'location_id' => $locationId
        ]),
        json_encode([
            'location' => $locationName,
            'remarks' => $remarks
        ])
    ]);

    $db->commit();

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Asset checked out successfully",
        "asset_code" => $asset['asset_code'],
        "holder" => $toUser['first_name'] . ' ' . $toUser['last_name'],
        "location" => $locationName
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