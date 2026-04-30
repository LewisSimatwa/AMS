<?php
error_log("=== RECENT-ACTIONS.PHP CALLED ===");

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
    error_log("Auth error in recent-actions: " . $e->getMessage());
    respond(['error' => 'Authentication failed'], 401);
}

try {
    error_log("Fetching recent actions...");
    
    // Fetch recent high-risk actions from audit_logs table
    $stmt = $db->query("SELECT 
                        al.action as action_type,
                        COALESCE(al.new_values::text, al.details::text, '') as description,
                        al.created_at as timestamp,
                        u.first_name,
                        u.last_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_name,
                        i.name as institution_name
                        FROM audit_logs al
                        LEFT JOIN users u ON al.user_id = u.id
                        LEFT JOIN institutions i ON al.institution_id = i.id
                        WHERE al.action IN ('RETIRE', 'DELETE', 'FORCE_RETIRE', 'CREATE', 'UPDATE')
                        ORDER BY al.created_at DESC
                        LIMIT 10");
    
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found " . count($actions) . " recent actions");

    respond(['actions' => $actions]);

} catch (PDOException $e) {
    error_log('Database error in recent-actions: ' . $e->getMessage());
    respond(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log('Recent actions error: ' . $e->getMessage());
    respond(['error' => 'Failed to fetch recent actions: ' . $e->getMessage()], 500);
}
?>