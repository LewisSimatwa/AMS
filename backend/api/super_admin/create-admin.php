<?php
error_log("=== CREATE-ADMIN.PHP CALLED ===");

// No need to require files or verify auth - already done in index.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Method not allowed'], 405);
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    error_log("Create admin data: " . print_r($data, true));
    
    // Validate required fields
    $required = ['institution_id', 'first_name', 'last_name', 'email', 'username', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            respond(['error' => "Missing required field: $field"], 400);
        }
    }
    
    $institution_id = $data['institution_id'];
    $first_name = $data['first_name'];
    $last_name = $data['last_name'];
    $email = $data['email'];
    $username = $data['username'];
    $password = $data['password'];
    
    // Validate password length
    if (strlen($password) < 6) {
        respond(['error' => 'Password must be at least 6 characters'], 400);
    }
    
    // Verify institution exists
    $stmt = $db->prepare("SELECT id, name FROM institutions WHERE id = :id");
    $stmt->execute(['id' => $institution_id]);
    $institution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$institution) {
        respond(['error' => 'Institution not found'], 404);
    }
    
    error_log("Creating admin for institution: " . $institution['name']);
    
    $db->beginTransaction();
    
    try {
        // Check if username already exists in this institution
        $stmt = $db->prepare("SELECT id FROM users WHERE institution_id = :institution_id AND username = :username");
        $stmt->execute([
            'institution_id' => $institution_id,
            'username' => $username
        ]);
        
        if ($stmt->fetch()) {
            $db->rollBack();
            respond(['error' => 'Username already exists in this institution'], 400);
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Create user
        $stmt = $db->prepare("
            INSERT INTO users (institution_id, username, email, password_hash, first_name, last_name, is_active, created_at, updated_at)
            VALUES (:institution_id, :username, :email, :password_hash, :first_name, :last_name, true, NOW(), NOW())
            RETURNING id
        ");
        
        $stmt->execute([
            'institution_id' => $institution_id,
            'username' => $username,
            'email' => $email,
            'password_hash' => $password_hash,
            'first_name' => $first_name,
            'last_name' => $last_name
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $result['id'];
        
        error_log("Created user with ID: $user_id");
        
        // Assign admin role (role_id = 2) with institution_id
        $stmt = $db->prepare("
            INSERT INTO user_roles (user_id, role_id, institution_id, assigned_at)
            VALUES (:user_id, 2, :institution_id, NOW())
        ");
        
        $stmt->execute([
            'user_id' => $user_id,
            'institution_id' => $institution_id
        ]);
        
        error_log("Assigned admin role to user $user_id for institution $institution_id");
        
        $db->commit();
        
        respond([
            'message' => 'Admin created successfully',
            'admin' => [
                'id' => $user_id,
                'username' => $username,
                'email' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'institution_id' => $institution_id
            ]
        ]);
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Database error creating admin: ' . $e->getMessage());
        
        // Check for unique constraint violations
        if (strpos($e->getMessage(), 'unique') !== false || strpos($e->getMessage(), 'duplicate') !== false) {
            respond(['error' => 'Username or email already exists'], 400);
        }
        
        throw $e;
    }
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Database error: ' . $e->getMessage());
    respond(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error creating admin: ' . $e->getMessage());
    respond(['error' => $e->getMessage()], 500);
}
?>