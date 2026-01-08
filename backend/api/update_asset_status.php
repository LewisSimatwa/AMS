<?php
// Prevent any output before JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();

require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

// Clean any output that might have leaked
ob_end_clean();

/* HEADERS */
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Institution-ID");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* AUTH */
try {
    verifyAuth();
} catch (Exception $e) {
    sendJSONError('Authentication failed: ' . $e->getMessage(), 401);
}

$institutionId = $_GET['institution_id'] ?? null;
$userId = $_GET['user_id'] ?? null;

if (!$institutionId || !$userId) {
    sendJSONError('Missing authentication data', 401);
}

// Get asset ID from URL
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/api\/assets\/(\d+)\/status/', $uri, $matches);
$assetId = $matches[1] ?? null;

if (!$assetId) {
    sendJSONError('Asset ID required', 400);
}

// Get request body
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendJSONError('Invalid JSON in request body', 400);
}

$newStatus = $input['status'] ?? null;

// Validate status
$validStatuses = ['available', 'on_loan', 'maintenance', 'retired', 'lost'];
if (!in_array($newStatus, $validStatuses)) {
    sendJSONError('Invalid status. Must be one of: ' . implode(', ', $validStatuses), 400);
}

try {
    global $db;
    
    if (!$db) {
        throw new Exception('Database connection not available');
    }
    
    $db->beginTransaction();

    // Get current asset data
    $stmt = $db->prepare("
        SELECT * FROM assets 
        WHERE id = ? AND institution_id = ?
    ");
    $stmt->execute([$assetId, $institutionId]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$asset) {
        $db->rollBack();
        sendJSONError('Asset not found', 404);
    }

    $oldStatus = $asset['status'];

    // Check if status is actually changing
    if ($oldStatus === $newStatus) {
        $db->rollBack();
        sendJSONResponse([
            'success' => true,
            'message' => 'Asset already has this status',
            'asset' => [
                'id' => (int)$assetId,
                'asset_code' => $asset['asset_code'],
                'name' => $asset['name'],
                'status' => $newStatus,
                'previous_status' => $oldStatus
            ]
        ]);
    }

    // Update asset status
    $stmt = $db->prepare("
        UPDATE assets 
        SET status = ?, updated_at = NOW() 
        WHERE id = ? AND institution_id = ?
    ");
    $stmt->execute([$newStatus, $assetId, $institutionId]);

    // Log the change in audit_logs
    $stmt = $db->prepare("
        INSERT INTO audit_logs 
        (institution_id, user_id, entity_type, entity_id, action, old_values, new_values, details, created_at)
        VALUES 
        (?, ?, 'assets', ?, 'UPDATE', ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $institutionId,
        $userId,
        $assetId,
        json_encode(['status' => $oldStatus]),
        json_encode(['status' => $newStatus]),
        json_encode([
            'source' => 'status_update',
            'asset_code' => $asset['asset_code'],
            'asset_name' => $asset['name']
        ])
    ]);

    $db->commit();

    sendJSONResponse([
        'success' => true,
        'message' => 'Asset status updated successfully',
        'asset' => [
            'id' => (int)$assetId,
            'asset_code' => $asset['asset_code'],
            'name' => $asset['name'],
            'status' => $newStatus,
            'previous_status' => $oldStatus,
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Database error in update_asset_status.php: ' . $e->getMessage());
    sendJSONError('Database error occurred', 500);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error in update_asset_status.php: ' . $e->getMessage());
    sendJSONError('Failed to update asset status', 500);
}
?>