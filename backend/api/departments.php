<?php
 //Handles listing, creating, updating, and deleting departments.
require __DIR__ . '/cors.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

error_log("=== Departments API Called ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Action: " . ($_GET['action'] ?? 'none'));

// ── Auth ──────────────────────────────────────────────────────────────────────
// Always verify the JWT ourselves so we get the role straight from the token
// (same source the frontend uses). This works whether the request comes through
// index.php or hits departments.php directly.

$authHeader = getAuthorizationHeader();
if (!$authHeader) {
    respond(['success' => false, 'error' => 'No authorization header provided'], 401);
}

if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = $matches[1];
} else {
    $token = $authHeader;
}

try {
    // verifyToken() decodes the JWT and returns its payload.
    // Your JWT payload contains: user_id, username, role, institution_id
    $tokenData = verifyToken($token);
} catch (Exception $e) {
    error_log("Token verification failed: " . $e->getMessage());
    respond(['success' => false, 'error' => 'Invalid token: ' . $e->getMessage()], 401);
}

// Pull everything we need straight from the token — no extra DB query needed
$userId        = (int)    ($tokenData['user_id']       ?? 0);
$role          = strtolower($tokenData['role']          ?? '');
$institutionId = (int)    ($tokenData['institution_id'] ?? 0);

error_log("Departments: user_id=$userId role=$role institution=$institutionId");

if (!$userId || !$institutionId) {
    respond(['success' => false, 'error' => 'Invalid token payload'], 401);
}

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

// ── LIST ──────────────────────────────────────────────────────────────────────
if ($action === 'list' && $method === 'GET') {

    $stmt = $db->prepare("
        SELECT  d.id,
                d.name,
                d.code,
                d.created_at,
                COUNT(a.id) AS asset_count
        FROM    departments d
        LEFT JOIN assets a ON a.department_id  = d.id
                          AND a.institution_id  = :inst2
        WHERE   d.institution_id = :inst1
        GROUP BY d.id, d.name, d.code, d.created_at
        ORDER BY d.name ASC
    ");
    $stmt->execute([':inst1' => $institutionId, ':inst2' => $institutionId]);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Found " . count($departments) . " departments for institution " . $institutionId);
    respond(['success' => true, 'departments' => $departments]);

// ── CREATE ────────────────────────────────────────────────────────────────────
} elseif ($action === 'create' && $method === 'POST') {

    if ($role !== 'admin') {
        error_log("Create blocked — role is '$role', not 'admin'");
        respond(['success' => false, 'error' => 'Admin access required'], 403);
    }

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = trim($body['name'] ?? '');
    $code = strtoupper(trim($body['code'] ?? '')) ?: null;

    error_log("Create dept — name: $name, code: " . ($code ?? 'null'));

    if (!$name) {
        respond(['success' => false, 'error' => 'Department name is required'], 400);
    }

    // Duplicate name check within this institution
    $dup = $db->prepare("
        SELECT id FROM departments
        WHERE  institution_id = :inst AND LOWER(name) = LOWER(:name)
        LIMIT  1
    ");
    $dup->execute([':inst' => $institutionId, ':name' => $name]);
    if ($dup->fetch()) {
        respond(['success' => false, 'error' => "A department named '{$name}' already exists"]);
    }

    $stmt = $db->prepare("
        INSERT INTO departments (institution_id, name, code)
        VALUES (:inst, :name, :code)
        RETURNING id
    ");
    $stmt->execute([':inst' => $institutionId, ':name' => $name, ':code' => $code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Department created with id: " . $row['id']);
    respond(['success' => true, 'id' => $row['id']]);

// ── UPDATE ────────────────────────────────────────────────────────────────────
} elseif ($action === 'update' && $method === 'POST') {

    if ($role !== 'admin') {
        error_log("Update blocked — role is '$role', not 'admin'");
        respond(['success' => false, 'error' => 'Admin access required'], 403);
    }

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int) ($body['id']   ?? 0);
    $name = trim($body['name']   ?? '');
    $code = strtoupper(trim($body['code'] ?? '')) ?: null;

    error_log("Update dept — id: $id, name: $name, code: " . ($code ?? 'null'));

    if (!$id || !$name) {
        respond(['success' => false, 'error' => 'Department id and name are required'], 400);
    }

    // Duplicate check — exclude the row being edited
    $dup = $db->prepare("
        SELECT id FROM departments
        WHERE  institution_id = :inst AND LOWER(name) = LOWER(:name) AND id <> :id
        LIMIT  1
    ");
    $dup->execute([':inst' => $institutionId, ':name' => $name, ':id' => $id]);
    if ($dup->fetch()) {
        respond(['success' => false, 'error' => "Another department named '{$name}' already exists"]);
    }

    $stmt = $db->prepare("
        UPDATE departments
        SET    name = :name,
               code = :code
        WHERE  id   = :id
          AND  institution_id = :inst
    ");
    $stmt->execute([':name' => $name, ':code' => $code, ':id' => $id, ':inst' => $institutionId]);

    error_log("Department $id updated");
    respond(['success' => true]);

// ── DELETE ────────────────────────────────────────────────────────────────────
} elseif ($action === 'delete' && $method === 'POST') {

    if ($role !== 'admin') {
        error_log("Delete blocked — role is '$role', not 'admin'");
        respond(['success' => false, 'error' => 'Admin access required'], 403);
    }

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int) ($body['id'] ?? 0);

    error_log("Delete dept — id: $id");

    if (!$id) {
        respond(['success' => false, 'error' => 'Department id is required'], 400);
    }

    // Safe: assets.department_id has ON DELETE SET NULL in the schema
    $stmt = $db->prepare("
        DELETE FROM departments
        WHERE id = :id AND institution_id = :inst
    ");
    $stmt->execute([':id' => $id, ':inst' => $institutionId]);

    error_log("Department $id deleted");
    respond(['success' => true]);

// ── UNKNOWN ───────────────────────────────────────────────────────────────────
} else {
    respond(['success' => false, 'error' => "Unknown action '{$action}' or wrong HTTP method ({$method})"], 400);
}
?>