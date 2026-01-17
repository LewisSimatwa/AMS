<?php
// api/super_admin/audit-logs.php
require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
$decoded = verifyAuth();
if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
    respond(['error' => 'Access denied. Super admin privileges required.'], 403);
}

try {
    // Get filter parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $limit;

    $institution_id = isset($_GET['institution_id']) ? $_GET['institution_id'] : null;
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    $action_type = isset($_GET['action_type']) ? $_GET['action_type'] : null;
    $entity_type = isset($_GET['entity_type']) ? $_GET['entity_type'] : null;
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;

    // Clean up empty strings
    $institution_id = ($institution_id === '' || $institution_id === 'all') ? null : $institution_id;
    $user_id = ($user_id === '') ? null : $user_id;
    $action_type = ($action_type === '') ? null : $action_type;
    $entity_type = ($entity_type === '') ? null : $entity_type;
    $date_from = ($date_from === '') ? null : $date_from;
    $date_to = ($date_to === '') ? null : $date_to;
    $search = ($search === '') ? null : $search;

    // Build WHERE clause
    $where = [];
    $params = [];

    // IMPORTANT: Filter out LOGIN/LOGOUT actions to show only meaningful activities
    $where[] = "al.action NOT IN ('LOGIN', 'LOGOUT')";

    // For super admin: if institution_id is provided, filter by it
    // Otherwise, show ALL institutions' logs (exclude NULL institution_id which are super admin's own logs)
    if ($institution_id) {
        $where[] = "al.institution_id = :institution_id";
        $params[':institution_id'] = $institution_id;
    } else {
        // Show only logs that belong to institutions (exclude super admin's own logs with NULL institution_id)
        $where[] = "al.institution_id IS NOT NULL";
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
        $where[] = "(u.username LIKE :search OR u.email LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR i.name LIKE :search OR al.entity_type LIKE :search OR al.action LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    // Get total count
    $countSql = "SELECT COUNT(*) as total 
                 FROM audit_logs al
                 LEFT JOIN users u ON al.user_id = u.id
                 LEFT JOIN institutions i ON al.institution_id = i.id
                 $whereClause";
    
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get logs with enhanced information
    $sql = "SELECT 
                al.id,
                al.action,
                al.entity_type,
                al.entity_id,
                al.old_values,
                al.new_values,
                al.details,
                al.created_at,
                al.user_id,
                al.institution_id,
                u.username,
                u.email as user_email,
                u.first_name,
                u.last_name,
                TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) as user_full_name,
                i.name as institution_name,
                r.name as user_role
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN institutions i ON al.institution_id = i.id
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            $whereClause
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse JSON fields and enhance log descriptions
    foreach ($logs as &$log) {
        // Parse JSON fields
        if ($log['old_values']) {
            $log['old_values'] = json_decode($log['old_values'], true);
        }
        if ($log['new_values']) {
            $log['new_values'] = json_decode($log['new_values'], true);
        }
        if ($log['details']) {
            $log['details'] = json_decode($log['details'], true);
        }

        // Fix display values
        // If user_full_name is empty, use username
        if (empty(trim($log['user_full_name'])) && !empty($log['username'])) {
            $log['user_full_name'] = $log['username'];
        }
        
        // If still empty, use email or "Unknown User"
        if (empty(trim($log['user_full_name'])) && !empty($log['user_email'])) {
            $log['user_full_name'] = $log['user_email'];
        }
        
        if (empty(trim($log['user_full_name']))) {
            $log['user_full_name'] = 'Unknown User';
        }

        // Fix institution display
        // Leave institution_name as NULL/empty for super admins
        // Only show institution name for regular institution users
        if (empty($log['institution_name'])) {
            $log['institution_name'] = null; // Keep it null/blank for super admins
        }

        // Generate human-readable description
        $log['description'] = generateLogDescription($log);
    }

    respond([
        'logs' => $logs,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => ceil($total / $limit)
    ]);

} catch (Exception $e) {
    error_log('Audit logs error: ' . $e->getMessage());
    respond(['error' => 'Failed to fetch audit logs: ' . $e->getMessage()], 500);
}

// Helper function to generate human-readable descriptions
function generateLogDescription($log) {
    $action = $log['action'];
    $entityType = $log['entity_type'];
    
    // Use the properly formatted user_full_name
    $username = $log['user_full_name'];
    
    // If somehow still empty, fallback
    if (empty(trim($username))) {
        $username = $log['username'] ?: $log['user_email'] ?: 'Unknown User';
    }
    
    switch ($action) {
        case 'LOGIN':
            return "$username logged in";
            
        case 'LOGOUT':
            return "$username logged out";
            
        case 'CREATE':
            if ($entityType === 'users') {
                $newUser = $log['new_values']['username'] ?? 'user';
                return "$username created user account: $newUser";
            } elseif ($entityType === 'assets') {
                $assetName = $log['new_values']['name'] ?? $log['new_values']['asset_code'] ?? 'asset';
                return "$username registered new asset: $assetName";
            } elseif ($entityType === 'institutions') {
                $instName = $log['new_values']['name'] ?? 'institution';
                return "$username created institution: $instName";
            }
            return "$username created $entityType";
            
        case 'UPDATE':
            if ($entityType === 'users') {
                $targetUser = $log['new_values']['username'] ?? $log['old_values']['username'] ?? 'user';
                if (isset($log['new_values']['is_active']) && isset($log['old_values']['is_active'])) {
                    if ($log['new_values']['is_active'] && !$log['old_values']['is_active']) {
                        return "$username activated user account: $targetUser";
                    } elseif (!$log['new_values']['is_active'] && $log['old_values']['is_active']) {
                        return "$username deactivated user account: $targetUser";
                    }
                }
                return "$username updated user: $targetUser";
            } elseif ($entityType === 'assets') {
                $assetCode = $log['new_values']['asset_code'] ?? $log['old_values']['asset_code'] ?? 'asset';
                return "$username updated asset: $assetCode";
            } elseif ($entityType === 'institutions') {
                $instName = $log['new_values']['name'] ?? $log['old_values']['name'] ?? 'institution';
                return "$username updated institution: $instName";
            }
            return "$username updated $entityType";
            
        case 'DELETE':
            if ($entityType === 'users') {
                $deletedUser = $log['old_values']['username'] ?? 'user';
                return "$username deleted user account: $deletedUser";
            } elseif ($entityType === 'assets') {
                $assetCode = $log['old_values']['asset_code'] ?? 'asset';
                return "$username deleted asset: $assetCode";
            }
            return "$username deleted $entityType";
            
        case 'CHECK_OUT':
            $assetName = $log['new_values']['asset_name'] ?? 'asset';
            $holder = $log['new_values']['holder_name'] ?? 'user';
            return "$username checked out $assetName to $holder";
            
        case 'CHECK_IN':
            $assetName = $log['old_values']['asset_name'] ?? 'asset';
            return "$username checked in $assetName";
            
        case 'TRANSFER':
            $assetName = $log['details']['asset_name'] ?? 'asset';
            $fromDept = $log['old_values']['department_name'] ?? 'unknown';
            $toDept = $log['new_values']['department_name'] ?? 'unknown';
            return "$username transferred $assetName from $fromDept to $toDept";
            
        case 'RETIRE':
            $assetCode = $log['details']['asset_code'] ?? 'asset';
            return "$username retired asset: $assetCode";
            
        case 'REACTIVATE':
            if ($entityType === 'institutions') {
                $instName = $log['new_values']['name'] ?? $log['old_values']['name'] ?? 'institution';
                return "$username reactivated institution: $instName";
            }
            return "$username reactivated $entityType";
            
        case 'DEACTIVATE':
            if ($entityType === 'institutions') {
                $instName = $log['new_values']['name'] ?? $log['old_values']['name'] ?? 'institution';
                return "$username deactivated institution: $instName";
            }
            return "$username deactivated $entityType";
            
        case 'CSV_IMPORT':
            $count = $log['details']['imported'] ?? $log['details']['total_count'] ?? 0;
            return "$username imported $count items via CSV";
            
        case 'EXPORT_CSV':
        case 'EXPORT_AUDIT_LOGS':
        case 'EXPORT_ANALYTICS':
            $count = $log['details']['total_assets'] ?? $log['details']['total_records'] ?? 0;
            $exportType = str_replace('EXPORT_', '', $action);
            return "$username exported $exportType" . ($count > 0 ? " ($count records)" : "");
            
        default:
            return "$username performed $action on $entityType";
    }
}
?>