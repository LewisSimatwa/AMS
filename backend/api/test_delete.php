<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

ob_start();

require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

ob_end_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: DELETE, GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Institution-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    verifyAuth();
    
    respond([
        'message' => 'Delete endpoint is accessible',
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'user_id' => $_GET['user_id'],
        'role' => $_GET['role'],
        'institution_id' => $_GET['institution_id'],
        'is_admin' => ($_GET['role'] === ROLE_ADMIN),
        'php_version' => PHP_VERSION
    ], 200);
} catch (Exception $e) {
    respond(['error' => 'Auth failed: ' . $e->getMessage()], 401);
}
?>