<?php
// api/super-admin/institutions.php
error_log("=== INSTITUTIONS.PHP CALLED ===");

require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
try {
    $decoded = verifyAuth();
    if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
        respond(['error' => 'Access denied. Super admin privileges required.'], 403);
    }
} catch (Exception $e) {
    error_log("Auth error in institutions: " . $e->getMessage());
    respond(['error' => 'Authentication failed'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {

        // ----------------------------------------
        // GET: Fetch all institutions with stats
        // ----------------------------------------
        case 'GET':
            error_log("Fetching all institutions with stats...");

            $stmt = $db->query("
                SELECT 
                    i.id,
                    i.name,
                    i.code,
                    i.address,
                    i.contact_email,
                    i.created_at,
                    i.updated_at,
                    COUNT(DISTINCT u.id) AS user_count,
                    COUNT(DISTINCT a.id) AS asset_count
                FROM institutions i
                LEFT JOIN users u ON i.id = u.institution_id
                LEFT JOIN assets a ON i.id = a.institution_id
                GROUP BY i.id, i.name, i.code, i.address, i.contact_email, i.created_at, i.updated_at
                ORDER BY i.name ASC
            ");

            $institutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($institutions) . " institutions");

            respond(['institutions' => $institutions]);
            break;

        // ----------------------------------------
        // POST: Create new institution
        // ----------------------------------------
        case 'POST':
            $input = getInput();

            if (empty($input['name'])) {
                respond(['error' => 'Institution name is required'], 400);
            }

            $stmt = $db->prepare("
                INSERT INTO institutions 
                (name, code, address, contact_email, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");

            $stmt->execute([
                $input['name'],
                $input['code'] ?? null,
                $input['address'] ?? null,
                $input['contact_email'] ?? null
            ]);

            $institutionId = $db->lastInsertId();

            // Audit log
            logAudit(
                $db,
                $decoded['user_id'],
                'institution',
                $institutionId,
                'CREATE_INSTITUTION',
                null,
                json_encode($input)
            );

            respond([
                'message' => 'Institution created successfully',
                'id' => $institutionId
            ], 201);
            break;

        // ----------------------------------------
        // PUT: Update institution
        // ----------------------------------------
        case 'PUT':
            $input = getInput();
            $institutionId = $_GET['id'] ?? null;

            if (!$institutionId) {
                respond(['error' => 'Institution ID required'], 400);
            }

            $stmt = $db->prepare("
                UPDATE institutions SET
                    name = ?,
                    code = ?,
                    address = ?,
                    contact_email = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $input['name'],
                $input['code'] ?? null,
                $input['address'] ?? null,
                $input['contact_email'] ?? null,
                $institutionId
            ]);

            // Audit log
            logAudit(
                $db,
                $decoded['user_id'],
                'institution',
                $institutionId,
                'UPDATE_INSTITUTION',
                null,
                json_encode($input)
            );

            respond(['message' => 'Institution updated successfully']);
            break;

        // ----------------------------------------
        // METHOD NOT ALLOWED
        // ----------------------------------------
        default:
            respond(['error' => 'Method not allowed'], 405);
    }

} catch (PDOException $e) {
    error_log('Database error in institutions: ' . $e->getMessage());
    respond(['error' => 'Database error: ' . $e->getMessage()], 500);

} catch (Exception $e) {
    error_log('Institutions error: ' . $e->getMessage());
    respond(['error' => 'Operation failed: ' . $e->getMessage()], 500);
}
?>