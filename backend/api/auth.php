<?php
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

        $email = trim($input['email']);
        $password = $input['password'];

        // Extract domain from email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("auth.php login: Invalid email format");
            respond(['error' => 'Invalid email address format'], 400);
        }

        $emailParts = explode('@', $email);
        $domain = strtolower(trim($emailParts[1]));

        error_log("auth.php login: Extracted domain: " . $domain);

        // Check if this is a super admin login (no domain lookup needed)
        $isSuperAdminAttempt = strpos($email, 'super@') !== false;

        $institution_name = null; // Initialize institution_name

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
            $stmt->execute([$email]);
        } else {
            // Regular user login - lookup institution by domain
            // First, get institution_id from domain
            $stmt = $db->prepare("SELECT id, is_active, name FROM institutions WHERE LOWER(domain) = LOWER(?)");
            $stmt->execute([$domain]);
            $institution = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$institution) {
                error_log("auth.php login: No institution found for domain: " . $domain);
                respond(['error' => 'No institution found for email domain: ' . $domain . '. Please contact your administrator.'], 404);
            }

            if (!$institution['is_active']) {
                error_log("auth.php login: Institution is deactivated - " . $institution['name']);
                respond(['error' => 'This institution has been deactivated. Please contact system administrator.'], 403);
            }

            $institution_id = $institution['id'];
            $institution_name = $institution['name']; // Store institution name
            error_log("auth.php login: Found institution_id: " . $institution_id . " for domain: " . $domain);

            // Now lookup user with this email and institution
            $sql = "SELECT u.id, u.email, u.username, u.first_name, u.last_name, u.password_hash, 
                           r.name AS role, u.institution_id
                    FROM users u
                    LEFT JOIN user_roles ur ON u.id = ur.user_id
                    LEFT JOIN roles r ON ur.role_id = r.id
                    WHERE u.email = ? AND u.institution_id = ?
                    LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->execute([$email, $institution_id]);
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            error_log("auth.php login: User not found - " . $email . " with domain: " . $domain);
            respond(['error' => 'Invalid credentials'], 401);
        }

        if (!checkPassword($password, $user['password_hash'])) {
            error_log("auth.php login: Invalid password for - " . $email);
            respond(['error' => 'Invalid credentials'], 401);
        }

        // Normalize role name
        if (strtolower($user['role']) === 'super_admin' || strtolower($user['role']) === 'superadmin') {
            $user['role'] = 'super_admin';
        }

        // Generate JWT token with role information
        $token = generateToken($user);

        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        // Remove sensitive data from response
        unset($user['password_hash']);

        error_log("auth.php login: Login successful for - " . $email . " with role: " . $user['role']);

        respond([
            'token' => $token,
            'user' => $user,
            'institution_name' => $institution_name  // Add institution_name to response
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
        $institution_id = $decoded['institution_id'] ?? null;
        
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