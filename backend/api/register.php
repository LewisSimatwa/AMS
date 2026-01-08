<?php
// Include CORS handler FIRST
require __DIR__ . '/cors.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

try {
    $input = getInput();
    
    error_log('Registration attempt with data: ' . json_encode($input));

    // Validate required fields
    if (empty($input['fullName'])) {
        respond(['error' => 'Full name is required'], 400);
    }
    
    if (empty($input['email'])) {
        respond(['error' => 'Email is required'], 400);
    }
    
    if (empty($input['password'])) {
        respond(['error' => 'Password is required'], 400);
    }
    
    if (empty($input['institution_id'])) {
        respond(['error' => 'Institution is required'], 400);
    }

    // Extract first and last name
    $names = explode(' ', trim($input['fullName']), 2);
    $first_name = $names[0];
    $last_name = $names[1] ?? '';

    // Generate username
    $username = strtolower(str_replace(' ', '', $input['fullName']));

    error_log("Generated username: $username");

    // Hash password
    $password_hash = password_hash($input['password'], PASSWORD_BCRYPT);

    // Check if username already exists for this institution
    $stmt = $db->prepare("SELECT id FROM users WHERE institution_id = :inst AND username = :username");
    $stmt->execute([
        ':inst' => $input['institution_id'],
        ':username' => $username
    ]);
    
    if ($stmt->rowCount() > 0) {
        error_log("Username already exists: $username");
        respond(['error' => 'Username already exists. Please use a different name.'], 400);
    }

    // Check if email already exists for this institution
    $stmt = $db->prepare("SELECT id FROM users WHERE institution_id = :inst AND email = :email");
    $stmt->execute([
        ':inst' => $input['institution_id'],
        ':email' => $input['email']
    ]);
    
    if ($stmt->rowCount() > 0) {
        error_log("Email already exists: " . $input['email']);
        respond(['error' => 'Email already exists'], 400);
    }

    error_log("Attempting to insert user into database");

    // Insert user
    $stmt = $db->prepare("
        INSERT INTO users (institution_id, username, email, password_hash, first_name, last_name)
        VALUES (:inst, :username, :email, :password_hash, :first_name, :last_name)
        RETURNING id, username, email, first_name, last_name
    ");
    
    $stmt->execute([
        ':inst' => $input['institution_id'],
        ':username' => $username,
        ':email' => $input['email'],
        ':password_hash' => $password_hash,
        ':first_name' => $first_name,
        ':last_name' => $last_name
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("User created successfully: " . json_encode($user));

    respond([
        'message' => 'User created successfully',
        'user' => $user
    ], 201);

} catch (PDOException $e) {
    error_log("Database error in registration: " . $e->getMessage());
    respond(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log("General error in registration: " . $e->getMessage());
    respond(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>