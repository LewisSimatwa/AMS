<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

header("Content-Type: application/json");

function register() {
    global $db;

    $input = getInput();

    // Validate required fields
    if (
        empty($input['institution_id']) ||
        empty($input['username']) ||
        empty($input['email']) ||
        empty($input['password'])
    ) {
        respond(["error" => "Missing required fields"], 400);
    }

    $institution_id = (int)$input['institution_id'];
    $username = trim($input['username']);
    $email = trim($input['email']);
    $password = $input['password'];
    $first_name = $input['first_name'] ?? null;
    $last_name = $input['last_name'] ?? null;

    // Check if email already exists
    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        respond(["error" => "Email already registered"], 409);
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $sql = "INSERT INTO users 
        (institution_id, username, email, password_hash, first_name, last_name)
        VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($sql);

    try {
        $stmt->execute([
            $institution_id,
            $username,
            $email,
            $password_hash,
            $first_name,
            $last_name
        ]);

        // Return success message only, no token
        respond(["message" => "Account created successfully"], 201);

    } catch (PDOException $e) {
        respond(["error" => "Failed to create account"], 500);
    }
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

register();
