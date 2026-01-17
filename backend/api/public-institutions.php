<?php
// api/public-institutions.php
// Public endpoint to fetch active institutions for the login page

require __DIR__ . '/cors.php';
require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['error' => 'Method not allowed'], 405);
}

try {
    error_log("Fetching public institutions list");
    
    // Fetch only active institutions
    $stmt = $db->prepare("
        SELECT id, name, code 
        FROM institutions 
        WHERE is_active = true
        ORDER BY name ASC
    ");
    
    $stmt->execute();
    $institutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($institutions) . " active institutions");
    
    respond([
        'institutions' => $institutions
    ]);
    
} catch (PDOException $e) {
    error_log('Database error fetching public institutions: ' . $e->getMessage());
    respond(['error' => 'Failed to fetch institutions'], 500);
} catch (Exception $e) {
    error_log('Error fetching public institutions: ' . $e->getMessage());
    respond(['error' => 'Failed to fetch institutions'], 500);
}
?>