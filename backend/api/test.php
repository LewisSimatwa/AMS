<?php
header("Content-Type: application/json");

// Load config
require __DIR__ . "/config.php";

try {
    // Simple query to test connection
    $stmt = $pdo->query("SELECT 1");

    echo json_encode([
        "status" => "ok",
        "db" => "connected"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
