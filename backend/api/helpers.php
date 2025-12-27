<?php
// JWT Functions
if (!function_exists('generateToken')) {
    function generateToken($user) {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'user_id' => $user['id'],
            'role' => $user['role'],
            'institution_id' => $user['institution_id'],
            'exp' => time() + (24 * 3600)
        ]);

        $headerEnc = base64_encode($header);
        $payloadEnc = base64_encode($payload);
        $signature = hash_hmac('sha256', "$headerEnc.$payloadEnc", JWT_SECRET, true);
        $signatureEnc = base64_encode($signature);

        return "$headerEnc.$payloadEnc.$signatureEnc";
    }
}

if (!function_exists('verifyToken')) {
    function verifyToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) throw new Exception('Invalid token');

        $headerEnc = $parts[0];
        $payloadEnc = $parts[1];
        $signatureEnc = $parts[2];

        $signature = hash_hmac('sha256', "$headerEnc.$payloadEnc", JWT_SECRET, true);
        if (base64_encode($signature) !== $signatureEnc) throw new Exception('Invalid signature');

        $payload = json_decode(base64_decode($payloadEnc), true);
        if ($payload['exp'] < time()) throw new Exception('Token expired');

        return $payload;
    }
}

// Auth
if (!function_exists('verifyAuth')) {
    function verifyAuth() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) throw new Exception('No token');

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $decoded = verifyToken($token);

        $_GET['user_id'] = $decoded['user_id'];
        $_GET['role'] = $decoded['role'];
        $_GET['institution_id'] = $decoded['institution_id'];
    }
}

// Password
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

// Response
if (!function_exists('respond')) {
    function respond($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }
}

// Input
if (!function_exists('getInput')) {
    function getInput() {
        return json_decode(file_get_contents('php://input'), true);
    }
}

// Audit Log
if (!function_exists('logAudit')) {
    function logAudit($db, $user_id, $entity_type, $entity_id, $action, $old, $new) {
        $sql = "INSERT INTO audit_logs (user_id, entity_type, entity_id, action, old_values, new_values, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $db->prepare($sql)->execute([
            $user_id,
            $entity_type,
            $entity_id,
            $action,
            json_encode($old),
            json_encode($new)
        ]);
    }
}

// Check Role
if (!function_exists('checkRole')) {
    function checkRole($role, $allowed = []) {
        if (!in_array($role, $allowed)) {
            respond(['error' => 'Permission denied'], 403);
        }
    }
}
?>
