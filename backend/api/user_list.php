<?php
/**
 * MIAMS User List API
 * Provides a simple list of active users for checkout purposes
 * Accessible by all authenticated users
 */
// NO WHITESPACE BEFORE THIS LINE!

// Include CORS handler FIRST
require __DIR__ . '/cors.php';

// Disable display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Include required files
require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

/**
 * Verify JWT token for any authenticated user
 */
function verifyAuthenticatedUser($db) {
    // Get authorization header using helper function
    $authHeader = getAuthorizationHeader();
    
    error_log("=== User List - Token Verification ===");
    
    if (!$authHeader) {
        error_log("No authorization header found");
        respond(['success' => false, 'error' => 'No authorization header provided'], 401);
    }
    
    // Extract token
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    } else {
        $token = $authHeader;
    }
    
    try {
        // Use the verifyToken function from helpers.php
        $tokenData = verifyToken($token);
        error_log("Token verified, user_id: " . $tokenData['user_id']);
    } catch (Exception $e) {
        error_log("Token verification failed: " . $e->getMessage());
        respond(['success' => false, 'error' => 'Invalid token: ' . $e->getMessage()], 401);
    }

    // Get user information
    $stmt = $db->prepare("
        SELECT u.*, u.institution_id
        FROM users u
        WHERE u.id = :user_id AND u.is_active = true
        LIMIT 1
    ");
    $stmt->execute(['user_id' => $tokenData['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        error_log("User not found or inactive");
        respond(['success' => false, 'error' => 'User not found or inactive'], 404);
    }

    error_log("User verified: " . $user['username']);
    return $user;
}

try {
    error_log("=== User List API Called ===");
    error_log("Method: " . $_SERVER['REQUEST_METHOD']);
    
    // Verify user is authenticated
    $currentUser = verifyAuthenticatedUser($db);
    error_log("Authenticated user: " . $currentUser['id'] . " from institution: " . $currentUser['institution_id']);
    
    // List active users from the same institution
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.first_name,
            u.last_name,
            u.is_active
        FROM users u
        WHERE u.institution_id = :institution_id 
        AND u.is_active = true
        ORDER BY u.first_name, u.last_name
    ");
    
    $stmt->execute(['institution_id' => $currentUser['institution_id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($users) . " active users for institution " . $currentUser['institution_id']);
    
    respond([
        'success' => true,
        'users' => $users
    ]);

} catch (PDOException $e) {
    error_log("Database error in user_list.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    respond([
        'success' => false, 
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    error_log("General error in user_list.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    respond([
        'success' => false, 
        'message' => 'Server error occurred',
        'error' => $e->getMessage()
    ], 500);
}
?>