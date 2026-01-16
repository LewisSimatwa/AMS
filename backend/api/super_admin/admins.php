<?php
// api/super_admin/admins.php
require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
$decoded = verifyAuth();
if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
    respond(['error' => 'Access denied. Super admin privileges required.'], 403);
}

try {
    // Get all admin users with their roles and institutions
    $stmt = $db->query("SELECT 
                        u.id,
                        u.username,
                        u.email,
                        u.first_name,
                        u.last_name,
                        u.is_active,
                        i.name as institution_name,
                        i.id as institution_id,
                        r.name as role_name
                        FROM users u
                        JOIN institutions i ON u.institution_id = i.id
                        JOIN user_roles ur ON u.id = ur.user_id
                        JOIN roles r ON ur.role_id = r.id
                        WHERE r.name IN ('admin', 'super_admin', 'security', 'ict', 'auditor', 'manager')
                        ORDER BY i.name, u.username");
    
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(['admins' => $admins]);

} catch (Exception $e) {
    error_log('Admins fetch error: ' . $e->getMessage());
    respond(['error' => 'Failed to fetch admins'], 500);
}
?>