<?php
// Include CORS handler FIRST - before anything else
require __DIR__ . '/cors.php';

// Now enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Log that we reached this file
error_log("auth.php: Request received - Method: " . $_SERVER['REQUEST_METHOD']);

require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

function login() {
    global $db;
    
    try {
        $input = getInput();
        
        error_log("auth.php login: Input received - " . json_encode($input));

        if (empty($input['email']) || empty($input['password'])) {
            error_log("auth.php login: Missing required fields");
            respond(['error' => 'Missing email or password'], 400);
        }

        // Check if this is a super admin login (no institution required)
        $isSuperAdminAttempt = !isset($input['institution_id']) || $input['institution_id'] === null || $input['institution_id'] === '';

        if ($isSuperAdminAttempt) {
            // Super admin login - institution_id should be NULL
            $sql = "SELECT u.id, u.email, u.username, u.first_name, u.last_name, u.password_hash, 
                           r.name AS role, u.institution_id
                    FROM users u
                    LEFT JOIN user_roles ur ON u.id = ur.user_id
                    LEFT JOIN roles r ON ur.role_id = r.id
                    WHERE u.email = ? AND u.institution_id IS NULL
                    LIMIT 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$input['email']]);
        } else {
            // Regular user login - requires institution_id
            $sql = "SELECT u.id, u.email, u.username, u.first_name, u.last_name, u.password_hash, 
                           r.name AS role, u.institution_id
                    FROM users u
                    LEFT JOIN user_roles ur ON u.id = ur.user_id
                    LEFT JOIN roles r ON ur.role_id = r.id
                    WHERE u.email = ? AND u.institution_id = ?
                    LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->execute([$input['email'], (int)$input['institution_id']]);
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            error_log("auth.php login: User not found - " . $input['email'] . " with institution: " . ($isSuperAdminAttempt ? 'NULL' : $input['institution_id']));
            respond(['error' => 'Invalid credentials'], 401);
        }

        if (!checkPassword($input['password'], $user['password_hash'])) {
            error_log("auth.php login: Invalid password for - " . $input['email']);
            respond(['error' => 'Invalid credentials'], 401);
        }

        // Normalize role name
        if (strtolower($user['role']) === 'super_admin' || strtolower($user['role']) === 'superadmin') {
            $user['role'] = 'super_admin';
        }

        // Log login in audit table
        logAudit($db, $user['id'], 'auth', $user['id'], 'LOGIN', null, null);

        // Generate JWT token with role information
        $token = generateToken($user);

        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        // Remove sensitive data from response
        unset($user['password_hash']);

        error_log("auth.php login: Login successful for - " . $input['email'] . " with role: " . $user['role']);

        respond([
            'token' => $token,
            'user' => $user
        ]);
    } catch (Exception $e) {
        error_log('auth.php login error: ' . $e->getMessage());
        respond(['error' => 'Login failed: ' . $e->getMessage()], 500);
    }
}

function logout() {
    try {
        // Verify authentication
        $decoded = verifyAuth();
        
        $user_id = $decoded['user_id'];
        
        // Log logout
        global $db;
        logAudit($db, $user_id, 'auth', $user_id, 'LOGOUT', null, null);
        
        respond(['message' => 'Logged out successfully']);
    } catch (Exception $e) {
        error_log('auth.php logout error: ' . $e->getMessage());
        respond(['error' => 'Logout failed: ' . $e->getMessage()], 500);
    }
}

function verifySuperAdmin() {
    try {
        $decoded = verifyAuth();
        
        // Check if user has super_admin role
        if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
            error_log('Access denied: User does not have super_admin role');
            respond(['error' => 'Access denied. Super admin privileges required.'], 403);
        }
        
        return $decoded;
    } catch (Exception $e) {
        error_log('Super admin verification error: ' . $e->getMessage());
        respond(['error' => 'Authentication failed'], 401);
    }
}

// Route based on action
$input = getInput();
$action = $input['action'] ?? 'login';

error_log("auth.php: Action requested - " . $action);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Method not allowed'], 405);
}

switch ($action) {
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    default:
        respond(['error' => 'Invalid action'], 400);
}
?>