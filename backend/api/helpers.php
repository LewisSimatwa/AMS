<?php
// NO WHITESPACE BEFORE THIS LINE!

// Secret key for JWT
if (!defined('JWT_SECRET')) define('JWT_SECRET', 'replace_this_with_your_secret');

// -------------------
// JWT Functions
// -------------------
if (!function_exists('generateToken')) {
    function generateToken($user) {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'user_id' => $user['id'],
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
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            respond(['error' => 'No authorization header provided'], 401);
        }

        $authHeader = $headers['Authorization'];
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            respond(['error' => 'Invalid authorization header format'], 401);
        }

        $token = $matches[1];
        
        try {
            $decoded = verifyToken($token);
            
            $_GET['user_id'] = $decoded['user_id'];
            $_GET['role'] = $decoded['role'];
            $_GET['institution_id'] = $decoded['institution_id'];
            
            return $decoded;
        } catch (Exception $e) {
            respond(['error' => $e->getMessage()], 401);
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
        
        // Log for debugging
        error_log('Raw input: ' . $input);
        error_log('Decoded input: ' . json_encode($decoded));
        
        return $decoded ?? [];
    }
}

// -------------------
// Audit Logs
// -------------------
if (!function_exists('logAudit')) {
    function logAudit($db, $user_id, $entity_type, $entity_id, $action, $old, $new) {
        try {
            $sql = "INSERT INTO audit_logs (user_id, entity_type, entity_id, action, old_values, new_values, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $user_id,
                $entity_type,
                $entity_id,
                $action,
                json_encode($old),
                json_encode($new)
            ]);
        } catch (Exception $e) {
            error_log('Audit log error: ' . $e->getMessage());
        }
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