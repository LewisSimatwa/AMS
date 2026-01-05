<?php
require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

function login() {
    global $db;
    $input = getInput();

    if (empty($input['email']) || empty($input['password']) || empty($input['institution_id'])) {
    respond(['error' => 'Missing fields'], 400);
    }

    // Correct query: join user_roles and roles to get role name
     $sql = "SELECT u.id, u.email, u.password_hash, r.name AS role, u.institution_id
        FROM users u
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE u.email = ? AND u.institution_id = ?";


    $stmt = $db->prepare($sql);
    $stmt->execute([$input['email'], $input['institution_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !checkPassword($input['password'], $user['password_hash'])) {
        respond(['error' => 'Invalid credentials'], 401);
    }

    // Log login in audit table
    logAudit($db, $user['id'], 'auth', $user['id'], 'LOGIN', null, null);

    // Generate JWT token
    $token = generateToken($user);

    respond([
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'institution_id' => $user['institution_id']
        ]
    ]);
}

function logout() {
    respond(['message' => 'Logged out']);
}
