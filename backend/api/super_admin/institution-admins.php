<?php
// api/super_admin/institution-admins.php
// Note: Auth verification is already done in index.php before this file is included
error_log("=== INSTITUTION-ADMINS.PHP CALLED ===");

// No need to require files or verify auth - already done in index.php
// The $db connection is available from index.php via config.php

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['error' => 'Method not allowed'], 405);
}

try {
    // Parse query string manually since $_GET might not be populated
    $query_string = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    parse_str($query_string ?? '', $params);
    
    $institution_id = $params['institution_id'] ?? $_GET['institution_id'] ?? null;
    
    error_log("Query string: " . ($query_string ?? 'NULL'));
    error_log("Parsed params: " . print_r($params, true));
    error_log("Institution ID received: " . ($institution_id ?? 'NULL'));
    
    if (!$institution_id) {
        error_log("ERROR: Institution ID is missing from request");
        respond(['error' => 'Institution ID is required'], 400);
    }

    error_log("Fetching admins for institution ID: $institution_id");

    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.first_name,
            u.last_name,
            u.is_active,
            u.created_at,
            u.last_login
        FROM users u
        INNER JOIN user_roles ur ON u.id = ur.user_id
        WHERE ur.institution_id = :institution_id
        AND ur.role_id = 2
        ORDER BY u.first_name, u.last_name
    ");
    
    $stmt->execute(['institution_id' => $institution_id]);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Found " . count($admins) . " admins");

    respond(['admins' => $admins]);

} catch (PDOException $e) {
    error_log('Database error in institution-admins: ' . $e->getMessage());
    respond(['error' => 'Database error'], 500);
} catch (Exception $e) {
    error_log('Error in institution-admins: ' . $e->getMessage());
    respond(['error' => $e->getMessage()], 500);
}
?>
?>