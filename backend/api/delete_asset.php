<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

ob_start();

require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

ob_end_clean();

$allowedOrigins = ['http://localhost:5173', 'http://localhost:5174'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Institution-ID");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_log('=== DELETE ASSET REQUEST START ===');
error_log('Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('URI: ' . $_SERVER['REQUEST_URI']);

try {
    verifyAuth();
} catch (Exception $e) {
    error_log('Auth error: ' . $e->getMessage());
    respond(['error' => 'Authentication failed: ' . $e->getMessage()], 401);
}

$institutionId = $_GET['institution_id'];
$userId = $_GET['user_id'];
$userRole = $_GET['role'];

error_log("User: $userId, Role: $userRole, Institution: $institutionId");

// Check if user is admin
if ($userRole !== ROLE_ADMIN) {
    error_log("Permission denied - User role '$userRole' is not admin");
    respond(['error' => 'Only administrators can delete assets'], 403);
}

// Get asset ID
$assetId = $_GET['id'] ?? null;

if (!$assetId) {
    $input = getInput();
    $assetId = $input['asset_id'] ?? null;
}

error_log("Asset ID to delete: " . ($assetId ?? 'null'));

if (!$assetId) {
    respond(['error' => 'Asset ID required'], 400);
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
    
    error_log("Starting new transaction for asset deletion: $assetId");
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

    // Check if on loan
    if ($asset['status'] === 'on_loan') {
        $db->rollBack();
        error_log("Cannot delete - asset is on loan");
        respond(['error' => 'Cannot delete asset that is currently on loan'], 400);
    }

    // Store for audit
    $assetData = [
        'id' => $asset['id'],
        'asset_code' => $asset['asset_code'],
        'name' => $asset['name'],
        'category' => $asset['category'],
        'status' => $asset['status']
    ];

    // Delete related records
    error_log("Deleting related records...");
    
    $relatedTables = [
        'asset_tags',
        'asset_documents',
        'maintenance_records',
        'transactions',
        'predictive_features',
        'asset_risk_scores',
        'checkout_requests'
    ];

    foreach ($relatedTables as $table) {
        try {
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?");
            $checkStmt->execute([$table]);
            $tableExists = $checkStmt->fetchColumn() > 0;
            
            if ($tableExists) {
                $stmt = $db->prepare("DELETE FROM $table WHERE asset_id = ?");
                $stmt->execute([$assetId]);
                $count = $stmt->rowCount();
                error_log("Deleted $count rows from $table");
            } else {
                error_log("Table $table does not exist, skipping...");
            }
        } catch (PDOException $e) {
            error_log("Error with table $table: " . $e->getMessage());
            // Continue with other tables
        }
    }

    // Delete the asset - THIS IS THE CRITICAL PART
    error_log("Attempting to delete asset from assets table...");
    error_log("DELETE FROM assets WHERE id = $assetId AND institution_id = $institutionId");
    
    $deleteStmt = $db->prepare("DELETE FROM assets WHERE id = ? AND institution_id = ?");
    $deleteResult = $deleteStmt->execute([$assetId, $institutionId]);
    $rowsDeleted = $deleteStmt->rowCount();
    
    error_log("Delete executed: " . ($deleteResult ? 'true' : 'false'));
    error_log("Rows affected: $rowsDeleted");

    if ($rowsDeleted === 0) {
        $db->rollBack();
        error_log("ERROR: No rows deleted from assets table!");
        respond(['error' => 'Failed to delete asset - no rows affected'], 500);
    }

    // Verify deletion
    $verifyStmt = $db->prepare("SELECT COUNT(*) FROM assets WHERE id = ?");
    $verifyStmt->execute([$assetId]);
    $stillExists = $verifyStmt->fetchColumn();
    
    error_log("Verification: Asset still exists in DB? " . ($stillExists > 0 ? 'YES' : 'NO'));
    
    if ($stillExists > 0) {
        $db->rollBack();
        error_log("ERROR: Asset still exists after DELETE!");
        respond(['error' => 'Asset deletion failed - asset still exists'], 500);
    }

    // Create audit log
    try {
        $auditSql = "INSERT INTO audit_logs (institution_id, user_id, entity_type, entity_id, action, old_values, new_values, created_at)
                     VALUES (?, ?, 'assets', ?, 'DELETE', ?, NULL, NOW())";
        $auditStmt = $db->prepare($auditSql);
        $auditStmt->execute([
            $institutionId,
            $userId,
            $assetId,
            json_encode($assetData)
        ]);
        error_log("Audit log created successfully");
    } catch (Exception $e) {
        error_log("Warning: Audit log failed - " . $e->getMessage());
        // Don't fail the deletion if audit log fails
    }

    // Commit the transaction
    error_log("Committing transaction...");
    $commitResult = $db->commit();
    error_log("Commit result: " . ($commitResult ? 'SUCCESS' : 'FAILED'));
    
    // Final verification after commit
    $finalVerifyStmt = $db->prepare("SELECT COUNT(*) FROM assets WHERE id = ?");
    $finalVerifyStmt->execute([$assetId]);
    $finalCheck = $finalVerifyStmt->fetchColumn();
    
    error_log("Final verification: Asset exists? " . ($finalCheck > 0 ? 'YES (ERROR!)' : 'NO (SUCCESS!)'));
    
    if ($finalCheck > 0) {
        error_log("CRITICAL ERROR: Transaction committed but asset still exists!");
        respond(['error' => 'Deletion failed - asset still exists after commit'], 500);
    }

    error_log("=== DELETION SUCCESSFUL ===");

    respond([
        'success' => true,
        'message' => 'Asset deleted successfully',
        'deleted_asset' => [
            'id' => (int)$assetId,
            'asset_code' => $asset['asset_code'],
            'name' => $asset['name']
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
    respond(['error' => 'Failed to delete asset: ' . $e->getMessage()], 500);
}