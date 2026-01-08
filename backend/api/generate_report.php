<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();

require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

// Load TCPDF
require_once(__DIR__ . '/../../vendor/autoload.php');

/* HEADERS */
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* AUTH */
try {
    verifyAuth(); // This sets $_GET['user_id'], $_GET['institution_id'], etc.
} catch (Exception $e) {
    ob_end_clean();
    header("Content-Type: application/json");
    http_response_code(401);
    echo json_encode(['error' => 'Authentication failed: ' . $e->getMessage()]);
    exit;
}

$institutionId = $_GET['institution_id'];
$userId = $_GET['user_id'];

try {
    global $db;

    // Get institution details
    $stmtInst = $db->prepare("SELECT name FROM institutions WHERE id = ?");
    $stmtInst->execute([$institutionId]);
    $institution = $stmtInst->fetch(PDO::FETCH_ASSOC);

    if (!$institution) {
        throw new Exception('Institution not found');
    }

    // Get user details
    $stmtUser = $db->prepare("SELECT username, first_name, last_name FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $username = $user ? ($user['first_name'] . ' ' . $user['last_name']) : 'Unknown User';

    // Get audit logs
    $sql = "
        SELECT
            a.id,
            a.created_at AS timestamp,
            u.username AS performed_by,
            a.action,
            a.entity_type,
            a.entity_id,
            a.old_values AS old_value,
            a.new_values AS new_value
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.institution_id = ?
        ORDER BY a.created_at DESC
        LIMIT 1000
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$institutionId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get asset summary
    $stmtAssets = $db->prepare("
        SELECT 
            COUNT(*) as total_assets,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_assets,
            SUM(CASE WHEN status = 'on_loan' THEN 1 ELSE 0 END) as on_loan_assets,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
            SUM(CASE WHEN status = 'disposed' THEN 1 ELSE 0 END) as disposed_assets
        FROM assets 
        WHERE institution_id = ?
    ");
    $stmtAssets->execute([$institutionId]);
    $assetStats = $stmtAssets->fetch(PDO::FETCH_ASSOC);

    // Clear output buffer before generating PDF
    ob_end_clean();

    // Generate PDF
    generatePDFReport($institution, $logs, $assetStats, $username);

} catch (Exception $e) {
    ob_end_clean();
    header("Content-Type: application/json");
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate report: ' . $e->getMessage()]);
    exit;
}

function generatePDFReport($institution, $logs, $assetStats, $username) {
    $institutionName = htmlspecialchars($institution['name'] ?? 'Unknown Institution');
    $reportDate = date('F j, Y g:i A');

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Asset Management System');
    $pdf->SetAuthor($username);
    $pdf->SetTitle('Asset Management Report - ' . $institutionName);
    $pdf->SetSubject('Asset Report');

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    // Generate HTML
    $html = '<style>
        body { font-family: helvetica, sans-serif; }
        .header { text-align: center; border-bottom: 3px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #1e40af; font-size: 24px; margin:0 0 10px 0; }
        .header p { margin:5px 0; color:#666; font-size:11px; }
        .summary { background-color:#f3f4f6; padding:15px; border-radius:5px; margin-bottom:20px; }
        .summary h2 { margin:0 0 10px 0; color:#1e40af; font-size:16px; }
        .stat-card { background:white; padding:10px; margin-bottom:8px; border-left:4px solid #2563eb; }
        .stat-card .label { font-size:10px; color:#666; text-transform:uppercase; }
        .stat-card .value { font-size:20px; font-weight:bold; color:#1e40af; }
        table { width:100%; border-collapse:collapse; margin-top:15px; font-size:9px; }
        th { background:#2563eb; color:white; padding:8px 5px; text-align:left; font-weight:bold; }
        td { padding:6px 5px; border-bottom:1px solid #e5e7eb; }
        tr:nth-child(even) { background:#f9fafb; }
        .action-badge { background:#dbeafe; color:#1e40af; padding:3px 6px; border-radius:3px; font-size:8px; font-weight:bold; }
        .footer { margin-top:30px; padding-top:15px; border-top:2px solid #e5e7eb; text-align:center; color:#666; font-size:9px; }
    </style>';

    $html .= '<div class="header">
        <h1>📊 Asset Management Report</h1>
        <p><strong>' . $institutionName . '</strong></p>
        <p>Generated on ' . $reportDate . '</p>
        <p>Generated by: ' . htmlspecialchars($username) . '</p>
    </div>';

    $html .= '<div class="summary">
        <h2>📈 Asset Summary</h2>
        <div class="stat-card"><div class="label">Total Assets</div><div class="value">' . ($assetStats['total_assets'] ?? 0) . '</div></div>
        <div class="stat-card"><div class="label">Available</div><div class="value">' . ($assetStats['available_assets'] ?? 0) . '</div></div>
        <div class="stat-card"><div class="label">On Loan</div><div class="value">' . ($assetStats['on_loan_assets'] ?? 0) . '</div></div>
        <div class="stat-card"><div class="label">Under Maintenance</div><div class="value">' . ($assetStats['maintenance_assets'] ?? 0) . '</div></div>
        <div class="stat-card"><div class="label">Disposed</div><div class="value">' . ($assetStats['disposed_assets'] ?? 0) . '</div></div>
    </div>';

    $html .= '<h3>📋 Recent Activity Log (' . count($logs) . ' activities)</h3>
    <table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Performed By</th>
                <th>Changes</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($logs)) {
        $html .= '<tr><td colspan="5" style="text-align:center;">No activity logs found</td></tr>';
    } else {
        foreach ($logs as $log) {
            $timestamp = date('M j, Y g:i A', strtotime($log['timestamp']));
            $action = htmlspecialchars($log['action']);
            $entity = htmlspecialchars($log['entity_type'] ?? '—');
            if (!empty($log['entity_id'])) $entity .= ' #' . htmlspecialchars($log['entity_id']);
            $performedBy = htmlspecialchars($log['performed_by'] ?? 'System');

            $changes = '—';
            if (!empty($log['old_value']) && !empty($log['new_value'])) {
                $oldVal = json_decode($log['old_value'], true);
                $newVal = json_decode($log['new_value'], true);

                if ($oldVal && $newVal) {
                    $changesList = [];
                    foreach ($newVal as $key => $value) {
                        if (isset($oldVal[$key]) && $oldVal[$key] != $value) {
                            $changesList[] = htmlspecialchars($key) . ': ' . htmlspecialchars($oldVal[$key]) . ' → ' . htmlspecialchars($value);
                        }
                    }
                    if (!empty($changesList)) {
                        $changes = implode('<br>', array_slice($changesList, 0, 3));
                        if (count($changesList) > 3) $changes .= '<br>...';
                    }
                }
            }

            $html .= '<tr>
                <td>' . $timestamp . '</td>
                <td><span class="action-badge">' . $action . '</span></td>
                <td>' . $entity . '</td>
                <td>' . $performedBy . '</td>
                <td>' . $changes . '</td>
            </tr>';
        }
    }

    $html .= '</tbody></table>';
    $html .= '<div class="footer">
        <p>This is a computer-generated report from the Asset Management System</p>
        <p>&copy; ' . date('Y') . ' ' . $institutionName . ' - All rights reserved</p>
    </div>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('asset_report_' . date('Y-m-d_His') . '.pdf', 'D');
    exit;
}
?>