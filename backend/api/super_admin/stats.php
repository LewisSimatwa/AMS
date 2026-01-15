<?php
// api/super-admin/stats.php
error_log("=== STATS.PHP CALLED ===");

require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
try {
    $decoded = verifyAuth();
    error_log("Auth decoded: " . json_encode($decoded));
    
    if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
        error_log("Access denied - not super admin. Role: " . ($decoded['role'] ?? 'none'));
        respond(['error' => 'Access denied. Super admin privileges required.'], 403);
    }
} catch (Exception $e) {
    error_log("Auth error: " . $e->getMessage());
    respond(['error' => 'Authentication failed'], 401);
}

try {
    error_log("Fetching institution stats...");
    
    // Get total institutions
    $stmt = $db->query("SELECT COUNT(*) as total FROM institutions");
    $institutions = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Institutions: " . json_encode($institutions));

    error_log("Fetching asset stats...");
    
    // Get total assets
    $stmt = $db->query("SELECT COUNT(*) as total,
                        SUM(CASE WHEN status = 'available' OR status = 'on_loan' THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN status = 'retired' THEN 1 ELSE 0 END) as retired
                        FROM assets");
    $assets = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Assets: " . json_encode($assets));

    error_log("Fetching user stats...");
    
    // Get total users (excluding super admin with NULL institution_id)
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE institution_id IS NOT NULL");
    $users = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Users: " . json_encode($users));

    error_log("Fetching assets per institution...");
    
    // Get assets per institution
    $stmt = $db->query("SELECT i.name, i.id,
                        COUNT(a.id) as asset_count,
                        SUM(CASE WHEN a.status IN ('available', 'on_loan') THEN 1 ELSE 0 END) as active_assets,
                        SUM(CASE WHEN a.status = 'retired' THEN 1 ELSE 0 END) as retired_assets
                        FROM institutions i
                        LEFT JOIN assets a ON i.id = a.institution_id
                        GROUP BY i.id, i.name
                        ORDER BY asset_count DESC
                        LIMIT 10");
    $assetsPerInstitution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Assets per institution: " . json_encode($assetsPerInstitution));

    $response = [
        'totalInstitutions' => (int)$institutions['total'],
        'activeInstitutions' => (int)$institutions['total'], // All are active since no is_active column
        'totalAssets' => (int)($assets['total'] ?? 0),
        'activeAssets' => (int)($assets['active'] ?? 0),
        'retiredAssets' => (int)($assets['retired'] ?? 0),
        'totalUsers' => (int)$users['total'],
        'assetsPerInstitution' => $assetsPerInstitution
    ];

    error_log("Sending response: " . json_encode($response));
    respond($response);

} catch (PDOException $e) {
    error_log('Database error in stats.php: ' . $e->getMessage());
    error_log('SQL State: ' . $e->getCode());
    respond(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log('Super admin stats error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    respond(['error' => 'Failed to fetch stats: ' . $e->getMessage()], 500);
}
?>