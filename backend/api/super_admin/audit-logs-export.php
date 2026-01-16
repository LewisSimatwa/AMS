<?php
// api/super-admin/audit-logs-export.php
require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
$decoded = verifyAuth();
if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
    respond(['error' => 'Access denied. Super admin privileges required.'], 403);
}

try {
    // Get filter parameters (same as audit-logs.php)
    $institution_id = isset($_GET['institution_id']) ? $_GET['institution_id'] : null;
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    $action_type = isset($_GET['action_type']) ? $_GET['action_type'] : null;
    $entity_type = isset($_GET['entity_type']) ? $_GET['entity_type'] : null;
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;

    // Build WHERE clause
    $where = [];
    $params = [];

    if ($institution_id) {
        $where[] = "al.institution_id = :institution_id";
        $params[':institution_id'] = $institution_id;
    }

    if ($user_id) {
        $where[] = "al.user_id = :user_id";
        $params[':user_id'] = $user_id;
    }

    if ($action_type) {
        $where[] = "al.action = :action_type";
        $params[':action_type'] = $action_type;
    }

    if ($entity_type) {
        $where[] = "al.entity_type = :entity_type";
        $params[':entity_type'] = $entity_type;
    }

    if ($date_from) {
        $where[] = "al.created_at >= :date_from";
        $params[':date_from'] = $date_from . ' 00:00:00';
    }

    if ($date_to) {
        $where[] = "al.created_at <= :date_to";
        $params[':date_to'] = $date_to . ' 23:59:59';
    }

    if ($search) {
        $where[] = "(u.username ILIKE :search OR i.name ILIKE :search OR al.entity_type ILIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    // Get all logs (no limit for export)
    $sql = "SELECT 
                al.id,
                al.created_at as timestamp,
                u.username,
                i.name as institution,
                al.action,
                al.entity_type,
                al.entity_id,
                al.old_values,
                al.new_values,
                al.details
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN institutions i ON al.institution_id = i.id
            $whereClause
            ORDER BY al.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Write CSV header
    fputcsv($output, [
        'ID',
        'Timestamp',
        'User',
        'Institution',
        'Action',
        'Entity Type',
        'Entity ID',
        'Old Values',
        'New Values',
        'Details'
    ]);

    // Write data rows
    foreach ($logs as $log) {
        // Format JSON fields for CSV
        $oldValues = $log['old_values'] ? json_encode(json_decode($log['old_values']), JSON_UNESCAPED_SLASHES) : '';
        $newValues = $log['new_values'] ? json_encode(json_decode($log['new_values']), JSON_UNESCAPED_SLASHES) : '';
        $details = $log['details'] ? json_encode(json_decode($log['details']), JSON_UNESCAPED_SLASHES) : '';

        fputcsv($output, [
            $log['id'],
            $log['timestamp'],
            $log['username'] ?: 'System',
            $log['institution'] ?: 'N/A',
            $log['action'],
            $log['entity_type'],
            $log['entity_id'] ?: '',
            $oldValues,
            $newValues,
            $details
        ]);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    error_log('Export audit logs error: ' . $e->getMessage());
    respond(['error' => 'Failed to export audit logs'], 500);
}
?>