<?php
error_log("=== DEACTIVATE INSTITUTION CALLED ===");

require __DIR__ . '/../../cors.php';
require __DIR__ . '/../../config.php';
require __DIR__ . '/../../helpers.php';

try {
    $decoded = verifyAuth();
    if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
        respond(['error' => 'Access denied'], 403);
    }
} catch (Exception $e) {
    respond(['error' => 'Authentication failed'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    respond(['error' => 'Method not allowed'], 405);
}

try {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        respond(['error' => 'Institution ID is required'], 400);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $is_active = $data['is_active'] ?? false;

    error_log("Updating institution: ID=$id, is_active=" . ($is_active ? 'true' : 'false'));

    $db->beginTransaction();

    $stmt = $db->prepare("SELECT id, name, code, is_active FROM institutions WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $institution = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$institution) {
        $db->rollBack();
        respond(['error' => 'Institution not found'], 404);
    }

    $stmt = $db->prepare("UPDATE institutions SET is_active = :is_active, updated_at = NOW() WHERE id = :id");
    $stmt->execute(['is_active' => $is_active ? 1 : 0, 'id' => $id]);

    if (!$is_active) {
        $stmt = $db->prepare("UPDATE users SET is_active = false WHERE institution_id = :institution_id");
        $stmt->execute(['institution_id' => $id]);
    }

    // Fixed: Use separate parameter names for institution_id and entity_id
    $stmt = $db->prepare("
        INSERT INTO audit_logs (institution_id, user_id, entity_type, entity_id, action, old_values, new_values, details)
        VALUES (:institution_id, :super_admin_id, 'institutions', :entity_id, :action, :old_values, :new_values, :details)
    ");
    $stmt->execute([
        'institution_id' => $id,
        'entity_id' => $id,  // Added separate binding for entity_id
        'super_admin_id' => $decoded['user_id'],
        'action' => $is_active ? 'REACTIVATE' : 'DEACTIVATE',
        'old_values' => json_encode(['is_active' => $institution['is_active']]),
        'new_values' => json_encode(['is_active' => $is_active]),
        'details' => json_encode(['name' => $institution['name']])
    ]);

    $db->commit();

    respond([
        'message' => $is_active ? 'Institution reactivated' : 'Institution deactivated',
        'institution' => ['id' => $id, 'name' => $institution['name'], 'is_active' => $is_active]
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Database error: ' . $e->getMessage());
    respond(['error' => 'Database error'], 500);
}
?>