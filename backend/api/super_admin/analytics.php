<?php
require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
$decoded = verifyAuth();
if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
    respond(['error' => 'Access denied. Super admin privileges required.'], 403);
}

try {
    // Get total asset counts
    $stmt = $db->query("SELECT 
                        COUNT(*) as total_assets,
                        COUNT(CASE WHEN status = 'available' THEN 1 END) as available_assets,
                        COUNT(CASE WHEN status = 'on_loan' THEN 1 END) as on_loan_assets,
                        COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance_assets,
                        COUNT(CASE WHEN status = 'retired' THEN 1 END) as retired_assets
                        FROM assets");
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate active assets (available + on_loan + maintenance)
    $activeAssets = (int)$totals['available_assets'] + (int)$totals['on_loan_assets'] + (int)$totals['maintenance_assets'];

    // Get asset growth over time (last 12 months)
    $stmt = $db->query("SELECT 
                        TO_CHAR(created_at, 'Mon YYYY') as month,
                        DATE_TRUNC('month', created_at) as month_start,
                        COUNT(*) as total,
                        COUNT(CASE WHEN status IN ('available', 'on_loan', 'maintenance') THEN 1 END) as active,
                        COUNT(CASE WHEN status = 'retired' THEN 1 END) as retired
                        FROM assets
                        WHERE created_at >= NOW() - INTERVAL '12 months'
                        GROUP BY TO_CHAR(created_at, 'Mon YYYY'), DATE_TRUNC('month', created_at)
                        ORDER BY DATE_TRUNC('month', created_at)");
    $assetGrowth = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get status distribution stats
    $stmt = $db->query("SELECT 
                        COUNT(CASE WHEN status = 'available' THEN 1 END) as available,
                        COUNT(CASE WHEN status = 'on_loan' THEN 1 END) as on_loan,
                        COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance,
                        COUNT(CASE WHEN status = 'retired' THEN 1 END) as retired
                        FROM assets");
    $statusStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get institution comparison
    $stmt = $db->query("SELECT 
                        i.id,
                        i.name,
                        i.code,
                        COUNT(a.id) as total_assets,
                        COUNT(CASE WHEN a.status IN ('available', 'on_loan', 'maintenance') THEN 1 END) as active_assets,
                        COUNT(CASE WHEN a.status = 'retired' THEN 1 END) as retired_assets,
                        COUNT(CASE WHEN a.status = 'available' THEN 1 END) as available_assets,
                        COUNT(CASE WHEN a.status = 'on_loan' THEN 1 END) as on_loan_assets,
                        COUNT(CASE WHEN a.status = 'maintenance' THEN 1 END) as maintenance_assets
                        FROM institutions i
                        LEFT JOIN assets a ON i.id = a.institution_id
                        GROUP BY i.id, i.name, i.code
                        ORDER BY total_assets DESC");
    $institutionComparison = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get admin activity ranking
    $stmt = $db->query("SELECT 
                        u.id as user_id,
                        u.username,
                        u.email,
                        COALESCE(i.name, 'System') as institution_name,
                        COUNT(al.id) as action_count,
                        MAX(al.created_at) as last_action
                        FROM users u
                        LEFT JOIN institutions i ON u.institution_id = i.id
                        LEFT JOIN audit_logs al ON u.id = al.user_id
                        JOIN user_roles ur ON u.id = ur.user_id
                        JOIN roles r ON ur.role_id = r.id
                        WHERE r.name IN ('admin', 'super_admin')
                        GROUP BY u.id, u.username, u.email, i.name
                        HAVING COUNT(al.id) > 0
                        ORDER BY action_count DESC
                        LIMIT 20");
    $adminActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get asset types distribution
    $stmt = $db->query("SELECT 
                        COALESCE(at.name, 'Uncategorized') as asset_type,
                        COUNT(a.id) as count
                        FROM assets a
                        LEFT JOIN asset_types at ON a.asset_type_id = at.id
                        WHERE a.status != 'retired'
                        GROUP BY at.name
                        ORDER BY count DESC
                        LIMIT 10");
    $assetTypeDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent activities (last 10)
    $stmt = $db->query("SELECT 
                        al.id,
                        al.action,
                        al.entity_type,
                        al.created_at,
                        u.username,
                        i.name as institution_name
                        FROM audit_logs al
                        LEFT JOIN users u ON al.user_id = u.id
                        LEFT JOIN institutions i ON al.institution_id = i.id
                        ORDER BY al.created_at DESC
                        LIMIT 10");
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total users count
    $stmt = $db->query("SELECT COUNT(*) as total_users FROM users WHERE is_active = true");
    $userStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get total institutions count
    $stmt = $db->query("SELECT COUNT(*) as total_institutions FROM institutions");
    $institutionStats = $stmt->fetch(PDO::FETCH_ASSOC);

    respond([
        'totalAssets' => (int)$totals['total_assets'],
        'activeAssets' => $activeAssets,
        'retiredAssets' => (int)$totals['retired_assets'],
        'totalUsers' => (int)$userStats['total_users'],
        'totalInstitutions' => (int)$institutionStats['total_institutions'],
        'assetGrowth' => $assetGrowth,
        'statusStats' => $statusStats,
        'institutionComparison' => $institutionComparison,
        'adminActivity' => $adminActivity,
        'assetTypeDistribution' => $assetTypeDistribution,
        'recentActivities' => $recentActivities
    ]);

} catch (Exception $e) {
    error_log('Analytics error: ' . $e->getMessage());
    respond(['error' => 'Failed to fetch analytics data: ' . $e->getMessage()], 500);
}
?>