<?php
// api/super-admin/analytics-export.php
require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
$decoded = verifyAuth();
if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
    respond(['error' => 'Access denied. Super admin privileges required.'], 403);
}

try {
    // Get analytics data
    $stmt = $db->query("SELECT 
                        COUNT(*) as total_assets,
                        COUNT(CASE WHEN status = 'available' THEN 1 END) as active_assets,
                        COUNT(CASE WHEN status = 'retired' THEN 1 END) as retired_assets
                        FROM assets");
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get institution comparison
    $stmt = $db->query("SELECT 
                        i.name,
                        COUNT(a.id) as total_assets,
                        COUNT(CASE WHEN a.status IN ('available', 'on_loan', 'maintenance') THEN 1 END) as active_assets,
                        COUNT(CASE WHEN a.status = 'retired' THEN 1 END) as retired_assets
                        FROM institutions i
                        LEFT JOIN assets a ON i.id = a.institution_id
                        GROUP BY i.id, i.name
                        ORDER BY total_assets DESC");
    $institutions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get admin activity
    $stmt = $db->query("SELECT 
                        u.username,
                        u.email,
                        i.name as institution_name,
                        COUNT(al.id) as action_count,
                        MAX(al.created_at) as last_action
                        FROM users u
                        JOIN institutions i ON u.institution_id = i.id
                        LEFT JOIN audit_logs al ON u.id = al.user_id
                        JOIN user_roles ur ON u.id = ur.user_id
                        JOIN roles r ON ur.role_id = r.id
                        WHERE r.name IN ('admin', 'super_admin')
                        GROUP BY u.id, u.username, u.email, i.name
                        HAVING COUNT(al.id) > 0
                        ORDER BY action_count DESC
                        LIMIT 20");
    $adminActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate HTML report
    $date = date('F d, Y');
    $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>MIAMS Analytics Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
        h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
        .header { text-align: center; margin-bottom: 30px; }
        .summary { display: flex; justify-content: space-around; margin: 20px 0; }
        .summary-box { 
            padding: 20px; 
            background: #f5f7fa; 
            border-radius: 8px; 
            text-align: center;
            flex: 1;
            margin: 0 10px;
        }
        .summary-box h3 { margin: 0; color: #667eea; font-size: 32px; }
        .summary-box p { margin: 5px 0 0 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #667eea; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: #f9fafb; }
        .footer { margin-top: 40px; text-align: center; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>MIAMS Analytics Report</h1>
        <p>Generated on $date</p>
    </div>

    <h2>System Overview</h2>
    <div class='summary'>
        <div class='summary-box'>
            <h3>{$totals['total_assets']}</h3>
            <p>Total Assets</p>
        </div>
        <div class='summary-box'>
            <h3>{$totals['active_assets']}</h3>
            <p>Active Assets</p>
        </div>
        <div class='summary-box'>
            <h3>{$totals['retired_assets']}</h3>
            <p>Retired Assets</p>
        </div>
    </div>

    <h2>Assets by Institution</h2>
    <table>
        <thead>
            <tr>
                <th>Institution</th>
                <th>Total Assets</th>
                <th>Active</th>
                <th>Retired</th>
            </tr>
        </thead>
        <tbody>";

    foreach ($institutions as $inst) {
        $html .= "<tr>
                    <td>{$inst['name']}</td>
                    <td>{$inst['total_assets']}</td>
                    <td>{$inst['active_assets']}</td>
                    <td>{$inst['retired_assets']}</td>
                  </tr>";
    }

    $html .= "</tbody>
    </table>

    <h2>Top Admin Activity</h2>
    <table>
        <thead>
            <tr>
                <th>Admin</th>
                <th>Email</th>
                <th>Institution</th>
                <th>Actions</th>
                <th>Last Activity</th>
            </tr>
        </thead>
        <tbody>";

    foreach ($adminActivity as $admin) {
        $lastAction = date('M d, Y', strtotime($admin['last_action']));
        $html .= "<tr>
                    <td>{$admin['username']}</td>
                    <td>{$admin['email']}</td>
                    <td>{$admin['institution_name']}</td>
                    <td>{$admin['action_count']}</td>
                    <td>$lastAction</td>
                  </tr>";
    }

    $html .= "</tbody>
    </table>

    <div class='footer'>
        <p>MIAMS - Multi-Institution Asset Management System</p>
        <p>This report is system-generated and contains confidential information</p>
    </div>
</body>
</html>";

    // For basic PDF generation, we'll use DomPDF or similar library
    // If you have composer, install: composer require dompdf/dompdf
    // For now, we'll return HTML that can be converted to PDF client-side
    // or you can implement a proper PDF library

    // Simple approach: Return HTML with PDF headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="analytics_report_' . date('Y-m-d') . '.pdf"');
    
    // Note: For production, use a proper PDF library like DomPDF or TCPDF
    // This is a simplified version that returns HTML
    echo $html;

} catch (Exception $e) {
    error_log('Export analytics error: ' . $e->getMessage());
    respond(['error' => 'Failed to export analytics report'], 500);
}
?>