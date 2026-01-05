<?php
// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header("Content-Type: application/json");

// Check if config file exists
if (!file_exists("config.php")) {
    echo json_encode(["error" => "config.php not found"]);
    exit;
}

require_once "config.php";

// 1️⃣ Check if user is logged in via session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['institution_id'])) {
    echo json_encode(["error" => "Not authenticated. Please login."]);
    exit;
}

$institution_id = $_SESSION['institution_id'];

// 2️⃣ Fetch assets for institution
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $stmt = $pdo->prepare("
        SELECT a.id, a.asset_code, a.name, a.status,
               at.name AS asset_type_name,
               d.name AS department_name
        FROM assets a
        LEFT JOIN asset_types at ON a.asset_type_id = at.id
        LEFT JOIN departments d ON a.department_id = d.id
        WHERE a.institution_id = :institution_id
        ORDER BY a.name
    ");
    
    $stmt->execute(['institution_id' => $institution_id]);
    $assets = $stmt->fetchAll();

    echo json_encode($assets);

} catch (PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    echo json_encode(["error" => "Error: " . $e->getMessage()]);
    exit;
}