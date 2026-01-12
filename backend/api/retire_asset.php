<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

ob_start();

require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

ob_end_clean();

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Institution-ID");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_log('=== RETIRE ASSET REQUEST START ===');
error_log('Method: ' . $_SERVER['REQUEST_METHOD']);

try {
    verifyAuth();
} catch (Exception $e) {
    error_log('Auth error: ' . $e->getMessage());
    respond(['error' => 'Authentication failed: ' . $e->getMessage()], 401);
}

$institutionId = $_SERVER['HTTP_X_INSTITUTION_ID'] ?? null;
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

// Decode JWT to get user info
$token = str_replace('Bearer ', '', $authHeader);
$payload = null;

try {
    $parts = explode('.', $token);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode($parts[1]), true);
    }
} catch (Exception $e) {
    error_log('Token decode error: ' . $e->getMessage());
}

$userId = $payload['user_id'] ?? null;
$userRole = $payload['role'] ?? null;

error_log("User: $userId, Role: $userRole, Institution: $institutionId");

// Check if user is admin
if ($userRole !== 'admin') {
    error_log("Permission denied - User role '$userRole' is not admin");
    respond(['error' => 'Only administrators can retire assets'], 403);
}

// Get input data
$input = getInput();
$assetId = $input['asset_id'] ?? null;
$retirementReason = $input['retirement_reason'] ?? null;

error_log("Asset ID to retire: " . ($assetId ?? 'null'));
error_log("Retirement reason length: " . strlen($retirementReason ?? ''));

// Validate input
if (!$assetId) {
    respond(['error' => 'Asset ID required'], 400);
}

if (!$retirementReason || trim($retirementReason) === '') {
    respond(['error' => 'Retirement reason is required'], 400);
}

if (strlen(trim($retirementReason)) < 10) {
    respond(['error' => 'Retirement reason must be at least 10 characters'], 400);
}

try {
    global $db;
    
    if (!$db) {
        throw new Exception('Database connection not available');
    }
    
    // Check if already in a transaction
    if ($db->inTransaction()) {
        error_log("WARNING: Already in transaction, rolling back...");
        $db->rollBack();
    }
    
    error_log("Starting new transaction for asset retirement: $assetId");
    $db->beginTransaction();

    // Get asset
    $stmt = $db->prepare("SELECT * FROM assets WHERE id = ? AND institution_id = ?");
    $stmt->execute([$assetId, $institutionId]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$asset) {
        $db->rollBack();
        error_log("Asset not found - ID: $assetId, Institution: $institutionId");
        respond(['error' => 'Asset not found or access denied'], 404);
    }

    error_log("Found asset: {$asset['asset_code']} - {$asset['name']} (status: {$asset['status']})");

    // Check if already retired
    if ($asset['status'] === 'retired') {
        $db->rollBack();
        error_log("Asset already retired");
        respond(['error' => 'Asset is already retired'], 400);
    }

    // Check if currently on loan
    if ($asset['status'] === 'on_loan') {
        $db->rollBack();
        error_log("Cannot retire - asset is currently on loan");
        respond(['error' => 'Cannot retire asset that is currently on loan. Please check it in first.'], 400);
    }

    // Store old values for audit
    $oldValues = [
        'status' => $asset['status'],
        'date_retired' => $asset['date_retired'] ?? null,
        'retirement_reason' => $asset['retirement_reason'] ?? null
    ];

    // Update asset - set status to retired, add retirement date and reason
    error_log("Updating asset to retired status...");
    $updateSql = "UPDATE assets 
                  SET status = 'retired',
                      date_retired = NOW(),
                      retirement_reason = ?,
                      updated_at = NOW()
                  WHERE id = ? AND institution_id = ?";
    
    $updateStmt = $db->prepare($updateSql);
    $updateResult = $updateStmt->execute([
        trim($retirementReason),
        $assetId,
        $institutionId
    ]);
    
    $rowsUpdated = $updateStmt->rowCount();
    
    error_log("Update executed: " . ($updateResult ? 'true' : 'false'));
    error_log("Rows affected: $rowsUpdated");

    if ($rowsUpdated === 0) {
        $db->rollBack();
        error_log("ERROR: No rows updated!");
        respond(['error' => 'Failed to retire asset - no rows affected'], 500);
    }

    // Verify the update
    $verifyStmt = $db->prepare("SELECT status, date_retired, retirement_reason FROM assets WHERE id = ?");
    $verifyStmt->execute([$assetId]);
    $verifyData = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Verification - Status: " . $verifyData['status']);
    error_log("Verification - Date retired: " . $verifyData['date_retired']);
    
    if ($verifyData['status'] !== 'retired') {
        $db->rollBack();
        error_log("ERROR: Asset status not updated to retired!");
        respond(['error' => 'Asset retirement failed - status not updated'], 500);
    }

    // New values for audit
    $newValues = [
        'status' => 'retired',
        'date_retired' => $verifyData['date_retired'],
        'retirement_reason' => trim($retirementReason)
    ];

    // Create audit log
    try {
        $auditSql = "INSERT INTO audit_logs 
                     (institution_id, user_id, entity_type, entity_id, action, old_values, new_values, details, created_at)
                     VALUES (?, ?, 'assets', ?, 'RETIRE', ?, ?, ?, NOW())";
        $auditStmt = $db->prepare($auditSql);
        $auditStmt->execute([
            $institutionId,
            $userId,
            $assetId,
            json_encode($oldValues),
            json_encode($newValues),
            json_encode([
                'asset_code' => $asset['asset_code'],
                'asset_name' => $asset['name'],
                'retired_by' => $userId,
                'retirement_reason' => trim($retirementReason)
            ])
        ]);
        error_log("Audit log created successfully");
    } catch (Exception $e) {
        error_log("Warning: Audit log failed - " . $e->getMessage());
        // Don't fail the retirement if audit log fails
    }

    // Commit the transaction
    error_log("Committing transaction...");
    $commitResult = $db->commit();
    error_log("Commit result: " . ($commitResult ? 'SUCCESS' : 'FAILED'));
    
    // Final verification after commit
    $finalVerifyStmt = $db->prepare("SELECT status, date_retired, retirement_reason FROM assets WHERE id = ?");
    $finalVerifyStmt->execute([$assetId]);
    $finalData = $finalVerifyStmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Final verification - Status: " . $finalData['status']);
    
    if ($finalData['status'] !== 'retired') {
        error_log("CRITICAL ERROR: Transaction committed but asset not retired!");
        respond(['error' => 'Retirement failed - asset status not updated after commit'], 500);
    }

    error_log("=== RETIREMENT SUCCESSFUL ===");

    respond([
        'success' => true,
        'message' => 'Asset retired successfully',
        'retired_asset' => [
            'id' => (int)$assetId,
            'asset_code' => $asset['asset_code'],
            'name' => $asset['name'],
            'status' => 'retired',
            'date_retired' => $finalData['date_retired'],
            'retirement_reason' => $finalData['retirement_reason']
        ]
    ], 200);

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        error_log("Rolling back transaction due to error...");
        $db->rollBack();
    }
    error_log('DATABASE ERROR: ' . $e->getMessage());
    error_log('SQL State: ' . $e->getCode());
    error_log('Stack trace: ' . $e->getTraceAsString());
    respond([
        'error' => 'Database error occurred',
        'details' => $e->getMessage(),
        'code' => $e->getCode()
    ], 500);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        error_log("Rolling back transaction due to error...");
        $db->rollBack();
    }
    error_log('ERROR: ' . $e->getMessage());
    respond(['error' => 'Failed to retire asset: ' . $e->getMessage()], 500);
}