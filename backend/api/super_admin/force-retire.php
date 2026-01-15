<?php
// api/super-admin/force-retire.php
require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
$decoded = verifyAuth();
if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
    respond(['error' => 'Access denied. Super admin privileges required.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Method not allowed'], 405);
}

try {
    $input = getInput();
    $assetId = $input['asset_id'] ?? null;
    $reason = $input['reason'] ?? null;

    if (!$assetId || !$reason) {
        respond(['error' => 'Asset ID and reason are required'], 400);
    }

    // Get asset details
    $stmt = $db->prepare("SELECT name, institution_id FROM assets WHERE id = ?");
    $stmt->execute([$assetId]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$asset) {
        respond(['error' => 'Asset not found'], 404);
    }

    // Update asset status to retired
    $stmt = $db->prepare("UPDATE assets 
                          SET status = 'retired', 
                              date_retired = NOW(), 
                              retirement_reason = ?,
                              updated_at = NOW() 
                          WHERE id = ?");
    $stmt->execute([$reason, $assetId]);

    // Log the force retire action
    logAudit($db, $decoded['user_id'], 'assets', $assetId, 'FORCE_RETIRE', 
            null, json_encode([
                'asset_name' => $asset['name'],
                'retirement_reason' => $reason
            ]));

    respond(['message' => 'Asset retired successfully']);

} catch (Exception $e) {
    error_log('Force retire error: ' . $e->getMessage());
    respond(['error' => 'Failed to retire asset: ' . $e->getMessage()], 500);
}
?>