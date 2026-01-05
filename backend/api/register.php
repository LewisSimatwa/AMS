<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require __DIR__ . '/config.php';
    require __DIR__ . '/helpers.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Config error: ' . $e->getMessage()]);
    exit;
}

header("Content-Type: application/json");

function register() {
    global $db;
    
    try {
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
        $first_name = $input['first_name'] ?? "";
        $last_name = $input['last_name'] ?? "";

        // Check for duplicate email
        $checkEmail = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetch()) {
            respond(["error" => "Email already registered"], 409);
        }

        // Check for duplicate username in the same institution
        $checkUser = $db->prepare("SELECT id FROM users WHERE username = ? AND institution_id = ?");
        $checkUser->execute([$username, $institution_id]);
        if ($checkUser->fetch()) {
            respond(["error" => "Username already exists in this institution"], 409);
        }

        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $sql = "INSERT INTO users (institution_id, username, email, password_hash, first_name, last_name)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);

        $stmt->execute([$institution_id, $username, $email, $password_hash, $first_name, $last_name]);
        respond(["message" => "Account created successfully"], 201);
        
    } catch (PDOException $e) {
        respond(["error" => "Database error: " . $e->getMessage()], 500);
    } catch (Exception $e) {
        respond(["error" => "Server error: " . $e->getMessage()], 500);
    }
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(["error" => "Method not allowed"], 405);
}

register();