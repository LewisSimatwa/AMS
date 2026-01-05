<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

/* HEADERS */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* AUTH */
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    respond(['error' => 'Missing Authorization header'], 401);
}

$token = str_replace('Bearer ', '', $headers['Authorization']);

try {
    $user = verifyToken($token);
} catch (Exception $e) {
    respond(['error' => $e->getMessage()], 401);
}

/* ROLE CHECK */
$allowedRoles = ['admin', 'auditor', 'ict'];

if (!in_array($user['role'], $allowedRoles)) {
    respond(['error' => 'Access denied'], 403);
}

/* FETCH LOGS */
try {
    global $db;

    $sql = "
        SELECT
            a.id,
            a.created_at AS timestamp,
            u.email AS performed_by,
            a.action,
            a.entity_type,
            a.entity_id,
            a.old_values AS old_value,
            a.new_values AS new_value,
            i.name AS institution_name
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN institutions i ON a.institution_id = i.id
        WHERE a.institution_id = ?
        ORDER BY a.created_at DESC
        LIMIT 500
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$user['institution_id']]);

    respond(['logs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    respond(['error' => 'Failed to fetch audit logs'], 500);
}
