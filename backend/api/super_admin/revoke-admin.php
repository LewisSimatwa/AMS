<?php
error_log("=== INSTITUTION ADMINS CALLED ===");

require __DIR__ . '/../../cors.php';
require __DIR__ . '/../../config.php';
require __DIR__ . '/../../helpers.php';

try {
    $decoded = verifyAuth();
    if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
        respond(['error' => 'Access denied'], 403);
    }
} catch (Exception $e) {
    respond(['error' => 'Authentication failed'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['error' => 'Method not allowed'], 405);
}

try {
    $institution_id = $_GET['institution_id'] ?? null;
    if (!$institution_id) {
        respond(['error' => 'Institution ID is required'], 400);
    }

    error_log("Fetching admins for institution: " . $institution_id);

    $stmt = $db->prepare("
        SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.is_active, u.created_at, u.last_login
        FROM users u
        INNER JOIN user_roles ur ON u.id = ur.user_id
        WHERE ur.institution_id = :institution_id AND ur.role_id = 2
        ORDER BY u.first_name, u.last_name
    ");
    $stmt->execute(['institution_id' => $institution_id]);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Found " . count($admins) . " admins");
    respond(['admins' => $admins]);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    respond(['error' => 'Database error'], 500);
}
?>