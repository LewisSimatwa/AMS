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
    if (empty($input['firstName'])) {
        respond(['error' => 'First name is required'], 400);
    }
    
    if (empty($input['lastName'])) {
        respond(['error' => 'Last name is required'], 400);
    }
    
    if (empty($input['email'])) {
        respond(['error' => 'Email is required'], 400);
    }
    
    if (empty($input['password'])) {
        respond(['error' => 'Password is required'], 400);
    }

    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        respond(['error' => 'Invalid email address format'], 400);
    }

    // Extract domain from email
    $emailParts = explode('@', $input['email']);
    if (count($emailParts) !== 2) {
        respond(['error' => 'Invalid email address format'], 400);
    }
    
    $domain = strtolower(trim($emailParts[1]));
    error_log("Extracted domain from email: $domain");

    // Lookup institution by domain
    $stmt = $db->prepare("SELECT id, name, is_active FROM institutions WHERE LOWER(domain) = LOWER(?)");
    $stmt->execute([$domain]);
    $institution = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$institution) {
        error_log("No institution found for domain: $domain");
        respond(['error' => 'No institution found for email domain: ' . $domain . '. Please use your institutional email address or contact your administrator.'], 404);
    }

    if (!$institution['is_active']) {
        error_log("Institution is deactivated: " . $institution['name']);
        respond(['error' => 'This institution has been deactivated. Please contact system administrator.'], 403);
    }

    $institution_id = $institution['id'];
    error_log("Found institution: " . $institution['name'] . " (ID: $institution_id)");

    // Get first and last name
    $first_name = trim($input['firstName']);
    $last_name = trim($input['lastName']);

    // Generate username from first and last name
    $username = strtolower(str_replace(' ', '', $first_name . $last_name));
    error_log("Generated username: $username");

    // Hash password
    $password_hash = password_hash($input['password'], PASSWORD_BCRYPT);

    // Check if username already exists for this institution
    $stmt = $db->prepare("SELECT id FROM users WHERE institution_id = :inst AND username = :username");
    $stmt->execute([
        ':inst' => $institution_id,
        ':username' => $username
    ]);
    
    if ($stmt->rowCount() > 0) {
        error_log("Username already exists: $username");
        respond(['error' => 'A user with this name already exists at your institution. Please use a middle name or initial.'], 400);
    }

    // Check if email already exists for this institution
    $stmt = $db->prepare("SELECT id FROM users WHERE institution_id = :inst AND email = :email");
    $stmt->execute([
        ':inst' => $institution_id,
        ':email' => $input['email']
    ]);
    
    if ($stmt->rowCount() > 0) {
        error_log("Email already exists: " . $input['email']);
        respond(['error' => 'An account with this email already exists'], 400);
    }

    error_log("Attempting to insert user into database");

    // Insert user
    $stmt = $db->prepare("
        INSERT INTO users (institution_id, username, email, password_hash, first_name, last_name)
        VALUES (:inst, :username, :email, :password_hash, :first_name, :last_name)
        RETURNING id, username, email, first_name, last_name
    ");
    
    $stmt->execute([
        ':inst' => $institution_id,
        ':username' => $username,
        ':email' => $input['email'],
        ':password_hash' => $password_hash,
        ':first_name' => $first_name,
        ':last_name' => $last_name
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("User created successfully: " . json_encode($user));

    respond([
        'message' => 'Account created successfully! You can now log in.',
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