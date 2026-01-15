<?php
// api/super-admin/create-admin.php
require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
$decoded = verifyAuth();
if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
    respond(['error' => 'Access denied. Super admin privileges required.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Method not allowed'], 405);
}

try {
    $input = getInput();

    // Validate required fields
    if (empty($input['first_name']) || empty($input['last_name']) || 
        empty($input['email']) || empty($input['username']) || 
        empty($input['password']) || empty($input['institution_id'])) {
        respond(['error' => 'All fields are required'], 400);
    }

    // Check if email or username already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$input['email'], $input['username']]);
    if ($stmt->fetch()) {
        respond(['error' => 'Email or username already exists'], 400);
    }

    // Hash password
    $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);

    // Create user
    $stmt = $db->prepare("INSERT INTO users 
                         (first_name, last_name, email, username, password_hash, institution_id, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([
        $input['first_name'],
        $input['last_name'],
        $input['email'],
        $input['username'],
        $passwordHash,
        $input['institution_id']
    ]);

    $userId = $db->lastInsertId();

    // Get admin role ID
    $stmt = $db->prepare("SELECT id FROM roles WHERE name = 'admin' LIMIT 1");
    $stmt->execute();
    $role = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($role) {
        // Assign admin role
        $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id, assigned_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $role['id']]);
    }

    // Log action
    logAudit($db, $decoded['user_id'], 'user', $userId, 'CREATE_ADMIN', 
            null, "Created admin user: {$input['email']} for institution ID: {$input['institution_id']}");

    respond(['message' => 'Admin created successfully', 'user_id' => $userId], 201);

} catch (Exception $e) {
    error_log('Create admin error: ' . $e->getMessage());
    respond(['error' => 'Failed to create admin: ' . $e->getMessage()], 500);
}
?>