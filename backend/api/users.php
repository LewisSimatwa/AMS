<?php

function handleUsers($db, $method, $path) {
    $institution_id = $_GET['institution_id'] ?? null;
    $user_id = $_GET['user_id'] ?? null;

    // GET /users
    if ($method === 'GET' && $path === '/users') {
        if (!$institution_id) {
            respond(['error' => 'institution_id is required'], 400);
            return;
        }

        try {
            $sql = "SELECT id, username, email FROM users WHERE institution_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$institution_id]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            respond(['users' => $users]);
        } catch (Exception $e) {
            respond(['error' => $e->getMessage()], 500);
        }
    }
    else {
        respond(['error' => 'Route not found: ' . $path], 404);
    }
}

?>
