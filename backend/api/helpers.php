<?php
// Secret key for JWT
//if (!defined('JWT_SECRET')) define('JWT_SECRET', 'replace_this_with_your_secret');

// -------------------
// Header Helper Function
// -------------------
if (!function_exists('getAllHeadersCaseInsensitive')) {
    function getAllHeadersCaseInsensitive() {
        $headers = [];
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $key => $value) {
                if (substr($key, 0, 5) === 'HTTP_') {
                    $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                    $headers[$header] = $value;
                }
            }
        }
        
        return array_change_key_case($headers, CASE_LOWER);
    }
}

if (!function_exists('getAuthorizationHeader')) {
    function getAuthorizationHeader() {
        $headers = getAllHeadersCaseInsensitive();
        
        if (isset($headers['authorization'])) {
            return $headers['authorization'];
        }
        
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        
        if (function_exists('apache_request_headers')) {
            $apacheHeaders = apache_request_headers();
            if (isset($apacheHeaders['Authorization'])) {
                return $apacheHeaders['Authorization'];
            }
            if (isset($apacheHeaders['authorization'])) {
                return $apacheHeaders['authorization'];
            }
        }
        
        return null;
    }
}

// -------------------
// JWT Functions
// -------------------
if (!function_exists('generateToken')) {
    function generateToken($user) {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'user_id' => $user['id'],
            'username' => $user['username'] ?? 'unknown',  // Add username to token
            'role' => $user['role'],
            'institution_id' => $user['institution_id'],
            'iat' => time(),
            'exp' => time() + (24 * 3600),
            'nonce' => bin2hex(random_bytes(8))
        ]);

        $headerEnc = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $payloadEnc = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', "$headerEnc.$payloadEnc", JWT_SECRET, true);
        $signatureEnc = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return "$headerEnc.$payloadEnc.$signatureEnc";
    }
}

if (!function_exists('verifyToken')) {
    function verifyToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) throw new Exception('Invalid token format');

        list($headerEnc, $payloadEnc, $signatureEnc) = $parts;

        $signatureCheck = hash_hmac('sha256', "$headerEnc.$payloadEnc", JWT_SECRET, true);
        $signatureCheckEnc = rtrim(strtr(base64_encode($signatureCheck), '+/', '-_'), '=');

        if (!hash_equals($signatureCheckEnc, $signatureEnc)) {
            throw new Exception('Invalid token signature');
        }

        $payload = json_decode(base64_decode(strtr($payloadEnc, '-_', '+/')), true);

        if ($payload['exp'] < time()) {
            throw new Exception('Token expired');
        }

        return $payload;
    }
}

// -------------------
// Auth Middleware
// -------------------
if (!function_exists('verifyAuth')) {
    function verifyAuth() {
        $authHeader = getAuthorizationHeader();
        
        error_log("=== Auth Verification ===");
        error_log("Auth header found: " . ($authHeader ? 'YES' : 'NO'));
        
        if (!$authHeader) {
            error_log("No authorization header found");
            respond(['error' => 'No authorization header provided'], 401);
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }
        
        try {
            $decoded = verifyToken($token);
            
            error_log("Token verified successfully for user_id: " . $decoded['user_id']);
            
            // Check if user has a role assigned
            if (empty($decoded['role']) || $decoded['role'] === null || $decoded['role'] === '') {
                error_log("Access denied: User has no role assigned (user_id: " . $decoded['user_id'] . ")");
                respond(['error' => 'Access denied: No role assigned to your account. Please contact your administrator.'], 403);
            }
            
            // Set $_GET for backward compatibility
            $_GET['user_id'] = $decoded['user_id'];
            $_GET['role'] = $decoded['role'];
            $_GET['institution_id'] = $decoded['institution_id'];
            
            // Return properly formatted currentUser array
            $currentUser = [
                'id' => $decoded['user_id'],
                'username' => $decoded['username'] ?? 'unknown',
                'institution_id' => $decoded['institution_id'],
                'role' => $decoded['role']
            ];
            
            error_log("Returning currentUser: " . json_encode($currentUser));
            
            return $currentUser;
            
        } catch (Exception $e) {
            error_log("Token verification failed: " . $e->getMessage());
            respond(['error' => 'Invalid token: ' . $e->getMessage()], 401);
        }
    }
}

// -------------------
// Password Helpers
// -------------------
if (!function_exists('hashPassword')) {
    function hashPassword($pass) {
        return password_hash($pass, PASSWORD_BCRYPT, ['cost' => 10]);
    }
}

if (!function_exists('checkPassword')) {
    function checkPassword($pass, $hash) {
        return password_verify($pass, $hash);
    }
}

// -------------------
// Response Helpers
// -------------------
if (!function_exists('respond')) {
    function respond($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// -------------------
// Input Helpers
// -------------------
if (!function_exists('getInput')) {
    function getInput() {
        $input = file_get_contents('php://input');
        $decoded = json_decode($input, true);
        
        error_log('Raw input: ' . substr($input, 0, 200));
        error_log('Decoded input: ' . json_encode($decoded));
        
        return $decoded ?? [];
    }
}

// -------------------
// Audit Logs - FIXED VERSION
// -------------------
if (!function_exists('logAudit')) {
    /**
     * Log audit activity with institution support
     * 
     * @param PDO $db - Database connection
     * @param int $user_id - User performing the action
     * @param string $entity_type - Type of entity (users, assets, etc.)
     * @param int|null $entity_id - ID of the entity
     * @param string $action - Action performed (CREATE, UPDATE, DELETE, LOGIN, etc.)
     * @param mixed $old - Old values (array or null)
     * @param mixed $new - New values (array or null)
     * @param int|null $institution_id - Institution ID (will auto-fetch if null)
     */
    function logAudit($db, $user_id, $entity_type, $entity_id, $action, $old = null, $new = null, $institution_id = null) {
        try {
            // If institution_id not provided, get it from user
            if ($institution_id === null && $user_id !== null) {
                $stmt = $db->prepare("SELECT institution_id FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $institution_id = $result ? $result['institution_id'] : null;
            }
            
            $sql = "INSERT INTO audit_logs (
                        user_id, 
                        institution_id,
                        entity_type, 
                        entity_id, 
                        action, 
                        old_values, 
                        new_values, 
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $user_id,
                $institution_id,
                $entity_type,
                $entity_id,
                $action,
                $old ? json_encode($old) : null,
                $new ? json_encode($new) : null
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('Audit log error: ' . $e->getMessage());
            return false;
        }
    }
}

// -------------------
// Simplified Audit Helpers
// -------------------
if (!function_exists('logLogin')) {
    function logLogin($db, $user_id, $institution_id = null) {
        return logAudit($db, $user_id, 'auth', null, 'LOGIN', null, null, $institution_id);
    }
}

if (!function_exists('logLogout')) {
    function logLogout($db, $user_id, $institution_id = null) {
        return logAudit($db, $user_id, 'auth', null, 'LOGOUT', null, null, $institution_id);
    }
}

if (!function_exists('logUserCreate')) {
    function logUserCreate($db, $admin_id, $new_user_id, $username, $email, $institution_id = null) {
        return logAudit(
            $db, 
            $admin_id, 
            'users', 
            $new_user_id, 
            'CREATE', 
            null, 
            ['username' => $username, 'email' => $email],
            $institution_id
        );
    }
}

if (!function_exists('logUserStatusChange')) {
    function logUserStatusChange($db, $admin_id, $user_id, $username, $old_status, $new_status, $institution_id = null) {
        return logAudit(
            $db,
            $admin_id,
            'users',
            $user_id,
            'UPDATE',
            ['username' => $username, 'is_active' => $old_status],
            ['username' => $username, 'is_active' => $new_status],
            $institution_id
        );
    }
}

if (!function_exists('logAssetCheckout')) {
    function logAssetCheckout($db, $user_id, $asset_id, $asset_name, $holder_name, $institution_id = null) {
        return logAudit(
            $db,
            $user_id,
            'assets',
            $asset_id,
            'CHECK_OUT',
            null,
            ['asset_name' => $asset_name, 'holder_name' => $holder_name],
            $institution_id
        );
    }
}

if (!function_exists('logAssetCheckin')) {
    function logAssetCheckin($db, $user_id, $asset_id, $asset_name, $institution_id = null) {
        return logAudit(
            $db,
            $user_id,
            'assets',
            $asset_id,
            'CHECK_IN',
            ['asset_name' => $asset_name],
            null,
            $institution_id
        );
    }
}

// -------------------
// Role Check
// -------------------
if (!function_exists('checkRole')) {
    function checkRole($role, $allowed = []) {
        if (!in_array($role, $allowed)) {
            respond(['error' => 'Permission denied'], 403);
        }
    }
}
?>