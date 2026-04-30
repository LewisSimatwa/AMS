<?php
error_log("=== ADMINS ENDPOINT CALLED ===");

require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

try {
    $decoded = verifyAuth();
    if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
        respond(['error' => 'Access denied. Super admin privileges required.'], 403);
    }
} catch (Exception $e) {
    error_log("Auth error in admins: " . $e->getMessage());
    respond(['error' => 'Authentication failed'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['error' => 'Method not allowed'], 405);
}

try {
    $institution_id = $_GET['institution_id'] ?? null;

    // If institution_id is provided, filter by that institution
    // Otherwise, get all admins across all institutions
    if ($institution_id) {
        error_log("Fetching admins for institution: " . $institution_id);
        
        $stmt = $db->prepare("
            SELECT 
                u.id, 
                u.username, 
                u.email, 
                u.first_name, 
                u.last_name, 
                u.is_active, 
                u.created_at, 
                u.last_login,
                i.name as institution_name,
                i.id as institution_id,
                r.name as role_name,
                r.id as role_id
            FROM users u
            INNER JOIN user_roles ur ON u.id = ur.user_id
            INNER JOIN roles r ON ur.role_id = r.id
            LEFT JOIN institutions i ON u.institution_id = i.id
            WHERE ur.institution_id = :institution_id 
                AND r.name IN ('admin', 'super_admin')
            ORDER BY u.first_name, u.last_name
        ");
        $stmt->execute(['institution_id' => $institution_id]);
    } else {
        error_log("Fetching all admins across all institutions");
        
        $stmt = $db->query("
            SELECT 
                u.id,
                u.username,
                u.email,
                u.first_name,
                u.last_name,
                u.is_active,
                u.created_at,
                u.last_login,
                COALESCE(i.name, 'System') as institution_name,
                i.id as institution_id,
                r.name as role_name,
                r.id as role_id
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN roles r ON ur.role_id = r.id
            LEFT JOIN institutions i ON u.institution_id = i.id
            WHERE r.name IN ('admin', 'super_admin')
            ORDER BY u.created_at DESC
        ");
    }
    
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get activity counts for each admin
    foreach ($admins as &$admin) {
        $stmtActivity = $db->prepare("
            SELECT COUNT(*) as activity_count 
            FROM audit_logs 
            WHERE user_id = ?
        ");
        $stmtActivity->execute([$admin['id']]);
        $activity = $stmtActivity->fetch(PDO::FETCH_ASSOC);
        $admin['activity_count'] = (int)$activity['activity_count'];
    }

    error_log("Found " . count($admins) . " admins");
    
    respond([
        'admins' => $admins,
        'total' => count($admins)
    ]);

} catch (PDOException $e) {
    error_log('Database error in admins: ' . $e->getMessage());
    respond(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log('Admins error: ' . $e->getMessage());
    respond(['error' => 'Operation failed: ' . $e->getMessage()], 500);
}
?>