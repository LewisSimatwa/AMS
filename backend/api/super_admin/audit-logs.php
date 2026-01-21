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

    // CRITICAL CHANGE: Only exclude LOGIN actions, show ALL other activities
    // This will include CREATE, UPDATE, DELETE, CHECK_OUT, CHECK_IN, TRANSFER, RETIRE, etc.
    $where[] = "al.action != 'LOGIN'";

    // Institution filter
    if ($institution_id) {
        $where[] = "al.institution_id = :institution_id";
        $params[':institution_id'] = $institution_id;
    }
    // NOTE: Removed the "al.institution_id IS NOT NULL" filter
    // This allows super admin to see their own system-level actions too if needed

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
                i.code as institution_code,
                r.name as user_role
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN institutions i ON al.institution_id = i.id
            LEFT JOIN user_roles ur ON u.id = ur.user_id AND (ur.institution_id = al.institution_id OR ur.institution_id IS NULL)
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
        if (empty(trim($log['user_full_name'])) && !empty($log['username'])) {
            $log['user_full_name'] = $log['username'];
        }
        
        if (empty(trim($log['user_full_name'])) && !empty($log['user_email'])) {
            $log['user_full_name'] = $log['user_email'];
        }
        
        if (empty(trim($log['user_full_name']))) {
            $log['user_full_name'] = 'System';
        }

        // Institution name handling
        if (empty($log['institution_name'])) {
            // Check if this is a super admin action (no institution)
            if ($log['user_role'] === 'super_admin') {
                $log['institution_name'] = 'System-wide';
            } else {
                $log['institution_name'] = 'Unknown';
            }
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
    $username = $log['user_full_name'] ?: 'System';
    $institution = $log['institution_name'] ?: '';
    
    switch ($action) {
        case 'LOGIN':
            return "$username logged in" . ($institution ? " to $institution" : "");
            
        case 'LOGOUT':
            return "$username logged out";
            
        case 'CREATE':
            if ($entityType === 'users') {
                $newUser = $log['new_values']['username'] ?? 'user';
                $email = $log['new_values']['email'] ?? '';
                return "$username created user account: $newUser" . ($email ? " ($email)" : "");
            } elseif ($entityType === 'assets') {
                $assetName = $log['new_values']['name'] ?? $log['new_values']['asset_code'] ?? 'asset';
                $assetCode = $log['new_values']['asset_code'] ?? '';
                return "$username registered new asset: $assetName" . ($assetCode && $assetCode !== $assetName ? " ($assetCode)" : "");
            } elseif ($entityType === 'institutions') {
                $instName = $log['new_values']['name'] ?? 'institution';
                return "$username created institution: $instName";
            } elseif ($entityType === 'maintenance_records') {
                $assetName = $log['details']['asset_name'] ?? 'asset';
                $maintenanceType = $log['details']['maintenance_type'] ?? 'maintenance';
                return "$username created $maintenanceType record for $assetName";
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
                $assetName = $log['new_values']['name'] ?? $log['old_values']['name'] ?? '';
                return "$username updated asset: " . ($assetName ?: $assetCode);
            } elseif ($entityType === 'institutions') {
                $instName = $log['new_values']['name'] ?? $log['old_values']['name'] ?? 'institution';
                if (isset($log['new_values']['is_active']) && isset($log['old_values']['is_active'])) {
                    if ($log['new_values']['is_active'] && !$log['old_values']['is_active']) {
                        return "$username reactivated institution: $instName";
                    } elseif (!$log['new_values']['is_active'] && $log['old_values']['is_active']) {
                        return "$username deactivated institution: $instName";
                    }
                }
                return "$username updated institution: $instName";
            } elseif ($entityType === 'system_config') {
                $section = $log['details']['section'] ?? 'settings';
                return "$username updated system configuration: $section";
            }
            return "$username updated $entityType";
            
        case 'DELETE':
            if ($entityType === 'users') {
                $deletedUser = $log['old_values']['username'] ?? 'user';
                return "$username deleted user account: $deletedUser";
            } elseif ($entityType === 'assets') {
                $assetCode = $log['old_values']['asset_code'] ?? 'asset';
                $assetName = $log['old_values']['name'] ?? '';
                return "$username deleted asset: " . ($assetName ?: $assetCode);
            } elseif ($entityType === 'institutions') {
                $instName = $log['old_values']['name'] ?? 'institution';
                return "$username deleted institution: $instName";
            }
            return "$username deleted $entityType";
            
        case 'CHECK_OUT':
            $assetName = $log['new_values']['asset_name'] ?? $log['details']['asset_name'] ?? 'asset';
            $holder = $log['new_values']['holder_name'] ?? 'user';
            $location = $log['new_values']['location'] ?? $log['details']['location'] ?? '';
            return "$username checked out $assetName to $holder" . ($location ? " at $location" : "");
            
        case 'CHECK_IN':
            $assetName = $log['old_values']['asset_name'] ?? $log['details']['asset_name'] ?? 'asset';
            $condition = $log['new_values']['condition'] ?? '';
            return "$username checked in $assetName" . ($condition ? " (condition: $condition)" : "");
            
        case 'TRANSFER':
            $assetName = $log['details']['asset_name'] ?? 'asset';
            $fromDept = $log['old_values']['department_name'] ?? '';
            $toDept = $log['new_values']['department_name'] ?? '';
            $fromLoc = $log['old_values']['location'] ?? '';
            $toLoc = $log['new_values']['location'] ?? '';
            
            $desc = "$username transferred $assetName";
            if ($fromDept && $toDept) {
                $desc .= " from $fromDept to $toDept";
            } elseif ($fromLoc && $toLoc) {
                $desc .= " from $fromLoc to $toLoc";
            }
            return $desc;
            
        case 'RETIRE':
            $assetCode = $log['details']['asset_code'] ?? $log['old_values']['asset_code'] ?? 'asset';
            $assetName = $log['details']['asset_name'] ?? $log['old_values']['name'] ?? '';
            $reason = $log['details']['retirement_reason'] ?? $log['new_values']['retirement_reason'] ?? '';
            return "$username retired asset: " . ($assetName ?: $assetCode) . ($reason ? " (reason: $reason)" : "");
            
        case 'REACTIVATE':
            if ($entityType === 'institutions') {
                $instName = $log['new_values']['name'] ?? $log['old_values']['name'] ?? 'institution';
                return "$username reactivated institution: $instName";
            } elseif ($entityType === 'assets') {
                $assetCode = $log['new_values']['asset_code'] ?? 'asset';
                return "$username reactivated asset: $assetCode";
            }
            return "$username reactivated $entityType";
            
        case 'DEACTIVATE':
            if ($entityType === 'institutions') {
                $instName = $log['new_values']['name'] ?? $log['old_values']['name'] ?? 'institution';
                return "$username deactivated institution: $instName";
            }
            return "$username deactivated $entityType";
            
        case 'CSV_IMPORT':
            $imported = $log['details']['imported'] ?? 0;
            $failed = $log['details']['failed'] ?? 0;
            $total = $log['details']['total_rows'] ?? ($imported + $failed);
            $instName = $log['details']['institution_name'] ?? $institution;
            return "$username imported $imported assets via CSV" . ($failed > 0 ? " ($failed failed)" : "") . ($instName ? " for $instName" : "");
            
        case 'EXPORT_CSV':
        case 'EXPORT_AUDIT_LOGS':
        case 'EXPORT_ANALYTICS':
            $count = $log['details']['total_assets'] ?? $log['details']['total_records'] ?? 0;
            $exportType = str_replace('EXPORT_', '', strtolower($action));
            $exportType = str_replace('_', ' ', $exportType);
            return "$username exported $exportType" . ($count > 0 ? " ($count records)" : "");
            
        case 'CREATE_INSTITUTION':
            $instName = $log['details'] ? json_decode($log['details'], true)['name'] ?? 'institution' : 'institution';
            return "$username created new institution: $instName";
            
        case 'SCHEMA_UPDATE':
            $change = $log['details']['change'] ?? 'schema update';
            return "System performed: $change";
            
        default:
            return "$username performed $action on $entityType";
    }
}
?>