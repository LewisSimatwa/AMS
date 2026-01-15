<?php
// api/super-admin/assets.php
require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
$decoded = verifyAuth();
if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
    respond(['error' => 'Access denied. Super admin privileges required.'], 403);
}

try {
    // Get all assets with institution and type info
    $stmt = $db->query("SELECT 
                        a.*,
                        i.name as institution_name,
                        at.name as type_name,
                        d.name as department_name
                        FROM assets a
                        JOIN institutions i ON a.institution_id = i.id
                        LEFT JOIN asset_types at ON a.asset_type_id = at.id
                        LEFT JOIN departments d ON a.department_id = d.id
                        ORDER BY a.created_at DESC");
    
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(['assets' => $assets]);

} catch (Exception $e) {
    error_log('Global assets error: ' . $e->getMessage());
    respond(['error' => 'Failed to fetch assets'], 500);
}
?>