<?php
// api/super-admin/asset-history.php
require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
$decoded = verifyAuth();
if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
    respond(['error' => 'Access denied. Super admin privileges required.'], 403);
}

try {
    $assetId = $_GET['asset_id'] ?? null;

    if (!$assetId) {
        respond(['error' => 'Asset ID required'], 400);
    }

    // Get asset history from audit log
    $stmt = $db->prepare("SELECT 
                         al.action_type as action,
                         al.description,
                         al.timestamp,
                         CONCAT(u.first_name, ' ', u.last_name) as user_name
                         FROM audit_log al
                         JOIN users u ON al.user_id = u.id
                         WHERE al.entity_type = 'asset' AND al.entity_id = ?
                         ORDER BY al.timestamp DESC
                         LIMIT 50");
    
    $stmt->execute([$assetId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(['history' => $history]);

} catch (Exception $e) {
    error_log('Asset history error: ' . $e->getMessage());
    respond(['error' => 'Failed to fetch asset history'], 500);
}
?>