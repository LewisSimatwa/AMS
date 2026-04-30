<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();

require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
try {
    $decoded = verifyAuth();
    if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
        throw new Exception('Access denied. Super admin privileges required.');
    }
} catch (Exception $e) {
    ob_end_clean();
    header("Content-Type: application/json");
    http_response_code(403);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

try {
    // Get analytics data
    $stmt = $db->query("SELECT 
                        COUNT(*) as total_assets,
                        COUNT(CASE WHEN status = 'available' THEN 1 END) as available_assets,
                        COUNT(CASE WHEN status = 'on_loan' THEN 1 END) as on_loan_assets,
                        COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance_assets,
                        COUNT(CASE WHEN status = 'retired' THEN 1 END) as retired_assets
                        FROM assets");
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $activeAssets = (int)$totals['available_assets'] + (int)$totals['on_loan_assets'] + (int)$totals['maintenance_assets'];

    // Get institution comparison
    $stmt = $db->query("SELECT 
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
    $institutions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get admin activity
    $stmt = $db->query("SELECT 
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
    $assetTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get status distribution
    $stmt = $db->query("SELECT 
                        COUNT(CASE WHEN status = 'available' THEN 1 END) as available,
                        COUNT(CASE WHEN status = 'on_loan' THEN 1 END) as on_loan,
                        COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance,
                        COUNT(CASE WHEN status = 'retired' THEN 1 END) as retired
                        FROM assets");
    $statusStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get user and institution counts
    $stmt = $db->query("SELECT COUNT(*) as total_users FROM users WHERE is_active = true");
    $userStats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->query("SELECT COUNT(*) as total_institutions FROM institutions");
    $institutionStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Log the export
    $stmtLog = $db->prepare("INSERT INTO audit_logs (user_id, action, entity_type, details, created_at) 
                             VALUES (?, ?, ?, ?, NOW())");
    $stmtLog->execute([
        $decoded['user_id'],
        'EXPORT_ANALYTICS',
        'analytics',
        json_encode(['total_assets' => $totals['total_assets']])
    ]);

    // Generate CSV export
    ob_end_clean();

    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="analytics_report_' . date('Y-m-d_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Section 1: System Overview
    fputcsv($output, ['MIAMS Analytics Report']);
    fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    fputcsv($output, ['SYSTEM OVERVIEW']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Assets', $totals['total_assets']]);
    fputcsv($output, ['Active Assets', $activeAssets]);
    fputcsv($output, ['Available Assets', $totals['available_assets']]);
    fputcsv($output, ['On Loan Assets', $totals['on_loan_assets']]);
    fputcsv($output, ['Maintenance Assets', $totals['maintenance_assets']]);
    fputcsv($output, ['Retired Assets', $totals['retired_assets']]);
    fputcsv($output, ['Total Users', $userStats['total_users']]);
    fputcsv($output, ['Total Institutions', $institutionStats['total_institutions']]);
    fputcsv($output, []);

    // Section 2: Assets by Institution
    fputcsv($output, ['ASSETS BY INSTITUTION']);
    fputcsv($output, ['Institution', 'Code', 'Total', 'Active', 'Available', 'On Loan', 'Maintenance', 'Retired']);
    foreach ($institutions as $inst) {
        fputcsv($output, [
            $inst['name'],
            $inst['code'] ?? 'N/A',
            $inst['total_assets'],
            $inst['active_assets'],
            $inst['available_assets'],
            $inst['on_loan_assets'],
            $inst['maintenance_assets'],
            $inst['retired_assets']
        ]);
    }
    fputcsv($output, []);

    // Section 3: Asset Types Distribution
    fputcsv($output, ['ASSET TYPES DISTRIBUTION']);
    fputcsv($output, ['Asset Type', 'Count']);
    foreach ($assetTypes as $type) {
        fputcsv($output, [$type['asset_type'], $type['count']]);
    }
    fputcsv($output, []);

    // Section 4: Status Distribution
    fputcsv($output, ['STATUS DISTRIBUTION']);
    fputcsv($output, ['Status', 'Count']);
    fputcsv($output, ['Available', $statusStats['available']]);
    fputcsv($output, ['On Loan', $statusStats['on_loan']]);
    fputcsv($output, ['Maintenance', $statusStats['maintenance']]);
    fputcsv($output, ['Retired', $statusStats['retired']]);
    fputcsv($output, []);

    // Section 5: Top Admin Activity
    fputcsv($output, ['TOP ADMIN ACTIVITY']);
    fputcsv($output, ['Username', 'Email', 'Institution', 'Total Actions', 'Last Activity']);
    foreach ($adminActivity as $admin) {
        fputcsv($output, [
            $admin['username'],
            $admin['email'],
            $admin['institution_name'],
            $admin['action_count'],
            $admin['last_action'] ? date('Y-m-d H:i:s', strtotime($admin['last_action'])) : 'N/A'
        ]);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    ob_end_clean();
    error_log('Export analytics error: ' . $e->getMessage());
    header("Content-Type: application/json");
    http_response_code(500);
    echo json_encode(['error' => 'Failed to export analytics report: ' . $e->getMessage()]);
    exit;
}
?>