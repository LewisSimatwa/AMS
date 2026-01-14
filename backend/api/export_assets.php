<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();

require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

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

    // Fetch all assets for the institution with related data
    $sql = "
        SELECT 
            a.id,
            a.asset_code,
            a.name,
            a.serial_number,
            a.description,
            a.status,
            a.condition,
            a.acquisition_date,
            a.acquisition_cost,
            at.name as asset_type,
            d.name as department_name,
            d.code as department_code,
            l.name as location_name,
            l.building,
            l.floor,
            l.room,
            CASE 
                WHEN a.current_holder_id IS NOT NULL 
                THEN CONCAT(u.first_name, ' ', u.last_name)
                ELSE NULL 
            END as assigned_to,
            u.username as assigned_username,
            a.created_at,
            a.updated_at
        FROM assets a
        LEFT JOIN asset_types at ON a.asset_type_id = at.id
        LEFT JOIN departments d ON a.department_id = d.id
        LEFT JOIN locations l ON a.location_id = l.id
        LEFT JOIN users u ON a.current_holder_id = u.id
        WHERE a.institution_id = ?
        ORDER BY a.asset_code ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$institutionId]);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clear output buffer before generating CSV
    ob_end_clean();

    // Generate CSV
    generateCSVExport($assets, $institution['name']);

} catch (Exception $e) {
    ob_end_clean();
    header("Content-Type: application/json");
    http_response_code(500);
    echo json_encode(['error' => 'Failed to export assets: ' . $e->getMessage()]);
    exit;
}

function generateCSVExport($assets, $institutionName) {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="assets_export_' . date('Y-m-d_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write CSV header
    fputcsv($output, [
        'Asset Code',
        'Name',
        'Asset Type',
        'Serial Number',
        'Description',
        'Status',
        'Condition',
        'Department',
        'Department Code',
        'Location',
        'Building',
        'Floor',
        'Room',
        'Assigned To',
        'Username',
        'Acquisition Date',
        'Acquisition Cost',
        'Created At',
        'Updated At'
    ]);
    
    // Write data rows
    foreach ($assets as $asset) {
        fputcsv($output, [
            $asset['asset_code'] ?? '',
            $asset['name'] ?? '',
            $asset['asset_type'] ?? '',
            $asset['serial_number'] ?? '',
            $asset['description'] ?? '',
            $asset['status'] ?? '',
            $asset['condition'] ?? '',
            $asset['department_name'] ?? '',
            $asset['department_code'] ?? '',
            $asset['location_name'] ?? '',
            $asset['building'] ?? '',
            $asset['floor'] ?? '',
            $asset['room'] ?? '',
            $asset['assigned_to'] ?? '',
            $asset['assigned_username'] ?? '',
            $asset['acquisition_date'] ?? '',
            $asset['acquisition_cost'] ?? '',
            $asset['created_at'] ?? '',
            $asset['updated_at'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}
?>