<?php
// api/super_admin/audit-logs.php
error_log("=== AUDIT-LOGS.PHP CALLED ===");

require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
try {
    $decoded = verifyAuth();
    
    if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
        respond(['error' => 'Access denied. Super admin privileges required.'], 403);
    }
} catch (Exception $e) {
    error_log("Auth error in audit-logs: " . $e->getMessage());
    respond(['error' => 'Authentication failed'], 401);
}

try {
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 50;
    $offset = ($page - 1) * $limit;

    // Get optional filters from query parameters
    $filters = [];
    $params = [];
    
    if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
        $filters[] = "al.user_id = :user_id";
        $params[':user_id'] = intval($_GET['user_id']);
    }
    
    if (isset($_GET['institution_id']) && $_GET['institution_id'] !== '') {
        $filters[] = "al.institution_id = :institution_id";
        $params[':institution_id'] = intval($_GET['institution_id']);
    }
    
    if (isset($_GET['action_type']) && $_GET['action_type'] !== '') {
        $filters[] = "UPPER(al.action) = UPPER(:action_type)";
        $params[':action_type'] = $_GET['action_type'];
    }
    
    if (isset($_GET['entity_type']) && $_GET['entity_type'] !== '') {
        $filters[] = "LOWER(al.entity_type) = LOWER(:entity_type)";
        $params[':entity_type'] = $_GET['entity_type'];
    }
    
    if (isset($_GET['date_from']) && $_GET['date_from'] !== '') {
        $filters[] = "al.created_at >= :date_from";
        $params[':date_from'] = $_GET['date_from'] . ' 00:00:00';
    }
    
    if (isset($_GET['date_to']) && $_GET['date_to'] !== '') {
        $filters[] = "al.created_at <= :date_to";
        $params[':date_to'] = $_GET['date_to'] . ' 23:59:59';
    }

    // Build WHERE clause
    $whereClause = '';
    if (!empty($filters)) {
        $whereClause = 'WHERE ' . implode(' AND ', $filters);
    }

    error_log("Fetching audit logs with filters: " . json_encode($filters));
    error_log("Parameters: " . json_encode($params));

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total 
                 FROM audit_logs al 
                 $whereClause";
    
    $countStmt = $db->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Fetch audit logs with pagination
    $sql = "SELECT 
                al.id,
                al.user_id,
                al.institution_id,
                al.entity_type,
                al.entity_id,
                al.action,
                al.old_values,
                al.new_values,
                al.details,
                al.created_at,
                CONCAT(u.first_name, ' ', u.last_name) as user_name,
                u.username,
                u.email as user_email,
                i.name as institution_name,
                i.code as institution_code
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN institutions i ON al.institution_id = i.id
            $whereClause
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    
    // Bind filter parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind pagination parameters
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process logs to parse JSON fields
    foreach ($logs as &$log) {
        // Parse JSON fields if they exist
        if ($log['old_values']) {
            $log['old_values'] = json_decode($log['old_values'], true);
        }
        if ($log['new_values']) {
            $log['new_values'] = json_decode($log['new_values'], true);
        }
        if ($log['details']) {
            $log['details'] = json_decode($log['details'], true);
        }

        // Create a combined details object for frontend
        $combinedDetails = [];
        if ($log['old_values']) {
            $combinedDetails['old_values'] = $log['old_values'];
        }
        if ($log['new_values']) {
            $combinedDetails['new_values'] = $log['new_values'];
        }
        if ($log['details']) {
            $combinedDetails = array_merge($combinedDetails, $log['details']);
        }
        
        $log['details'] = $combinedDetails;
    }

    error_log("Found " . count($logs) . " audit logs (page $page of $totalPages)");

    respond([
        'logs' => $logs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit
        ],
        'total_pages' => $totalPages,
        'total_records' => $totalRecords
    ]);

} catch (PDOException $e) {
    error_log('Database error in audit-logs: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    respond(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log('Audit logs error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    respond(['error' => 'Failed to fetch audit logs: ' . $e->getMessage()], 500);
}
?>