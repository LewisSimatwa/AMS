<?php
// api/super_admin/users.php
error_log("=== USERS.PHP CALLED ===");

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
    error_log("Auth error in users: " . $e->getMessage());
    respond(['error' => 'Authentication failed'], 401);
}

try {
    error_log("Fetching all users...");
    
    $stmt = $db->query("SELECT 
                        u.id,
                        u.username,
                        u.email,
                        u.first_name,
                        u.last_name,
                        u.institution_id,
                        u.is_active,
                        u.created_at,
                        u.last_login,
                        i.name as institution_name,
                        r.name as role_name
                        FROM users u
                        LEFT JOIN institutions i ON u.institution_id = i.id
                        LEFT JOIN user_roles ur ON u.id = ur.user_id
                        LEFT JOIN roles r ON ur.role_id = r.id
                        ORDER BY u.first_name ASC, u.last_name ASC");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found " . count($users) . " users");

    respond(['users' => $users]);

} catch (PDOException $e) {
    error_log('Database error in users: ' . $e->getMessage());
    respond(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log('Users error: ' . $e->getMessage());
    respond(['error' => 'Failed to fetch users: ' . $e->getMessage()], 500);
}
?>