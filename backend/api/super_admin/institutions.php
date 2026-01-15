<?php
// api/super-admin/institutions.php
require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
$decoded = verifyAuth();
if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
    respond(['error' => 'Access denied. Super admin privileges required.'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all institutions with stats
            $stmt = $db->query("SELECT i.*,
                                COUNT(DISTINCT u.id) as user_count,
                                COUNT(DISTINCT a.id) as asset_count
                                FROM institutions i
                                LEFT JOIN users u ON i.id = u.institution_id
                                LEFT JOIN assets a ON i.id = a.institution_id
                                GROUP BY i.id
                                ORDER BY i.name ASC");
            $institutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            respond(['institutions' => $institutions]);
            break;

        case 'POST':
            // Create new institution
            $input = getInput();
            
            $stmt = $db->prepare("INSERT INTO institutions 
                                 (name, code, address, contact_email, created_at, updated_at) 
                                 VALUES (?, ?, ?, ?, NOW(), NOW())");
            
            $stmt->execute([
                $input['name'],
                $input['code'] ?? null,
                $input['address'] ?? null,
                $input['contact_email'] ?? null
            ]);

            $institutionId = $db->lastInsertId();

            // Log action
            logAudit($db, $decoded['user_id'], 'institution', $institutionId, 'CREATE_INSTITUTION', 
                    null, json_encode(['name' => $input['name']]));

            respond(['message' => 'Institution created successfully', 'id' => $institutionId], 201);
            break;

        case 'PUT':
            // Update institution
            $input = getInput();
            $institutionId = $_GET['id'] ?? null;

            if (!$institutionId) {
                respond(['error' => 'Institution ID required'], 400);
            }

            $stmt = $db->prepare("UPDATE institutions SET 
                                 name = ?, 
                                 code = ?, 
                                 address = ?, 
                                 contact_email = ?, 
                                 updated_at = NOW()
                                 WHERE id = ?");
            
            $stmt->execute([
                $input['name'],
                $input['code'] ?? null,
                $input['address'] ?? null,
                $input['contact_email'] ?? null,
                $institutionId
            ]);

            // Log action
            logAudit($db, $decoded['user_id'], 'institution', $institutionId, 'UPDATE_INSTITUTION', 
                    null, json_encode(['name' => $input['name']]));

            respond(['message' => 'Institution updated successfully']);
            break;

        default:
            respond(['error' => 'Method not allowed'], 405);
    }

} catch (Exception $e) {
    error_log('Institution management error: ' . $e->getMessage());
    respond(['error' => 'Operation failed: ' . $e->getMessage()], 500);
}
?>