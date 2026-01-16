<?php
error_log("=== RESET ADMIN PASSWORD CALLED ===");

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Method not allowed'], 405);
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['user_id'] ?? null;
    $new_password = $data['new_password'] ?? null;

    if (!$user_id || !$new_password) {
        respond(['error' => 'User ID and password required'], 400);
    }
    if (strlen($new_password) < 6) {
        respond(['error' => 'Password must be at least 6 characters'], 400);
    }

    $db->beginTransaction();

    $stmt = $db->prepare("
        SELECT u.username, ur.institution_id
        FROM users u
        INNER JOIN user_roles ur ON u.id = ur.user_id
        WHERE u.id = :user_id AND ur.role_id = 2
    ");
    $stmt->execute(['user_id' => $user_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) {
        $db->rollBack();
        respond(['error' => 'User not found'], 404);
    }

    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :user_id");
    $stmt->execute(['password_hash' => $password_hash, 'user_id' => $user_id]);

    $stmt = $db->prepare("
        INSERT INTO audit_logs (institution_id, user_id, entity_type, entity_id, action, details)
        VALUES (:institution_id, :super_admin_id, 'users', :user_id, 'PASSWORD_RESET', :details)
    ");
    $stmt->execute([
        'institution_id' => $user_info['institution_id'],
        'super_admin_id' => $decoded['user_id'],
        'user_id' => $user_id,
        'details' => json_encode(['reset_by' => 'super_admin'])
    ]);

    $db->commit();
    respond(['message' => 'Password reset successfully']);

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Database error: ' . $e->getMessage());
    respond(['error' => 'Database error'], 500);
}
?>