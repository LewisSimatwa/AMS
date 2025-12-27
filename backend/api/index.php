<?php
require 'config.php';
require 'helpers.php';

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Institution-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

try {

    // PUBLIC ROUTES (NO TOKEN)
    if ($path === '/login' && $method === 'POST') {
        require 'auth.php';
        login();
    }
    elseif ($path === '/register' && $method === 'POST') {
        require 'register.php';
        register();
    }
    elseif ($path === '/logout' && $method === 'POST') {
        require 'auth.php';
        logout();
    }

    // PROTECTED ROUTES (TOKEN REQUIRED)
    else {
        verifyAuth();

        if (strpos($path, '/assets') === 0) {
            require 'assets.php';
            handleAssets($db, $method, $path);
        }
        elseif (strpos($path, '/transactions') === 0) {
            require 'transactions.php';
            handleTransactions($db, $method, $path);
        }
        elseif (strpos($path, '/maintenance') === 0) {
            require 'maintenance.php';
            handleMaintenance($db, $method, $path);
        }
        elseif (strpos($path, '/analytics') === 0) {
            require 'analytics.php';
            handleAnalytics($db, $method, $path);
        }
        elseif (strpos($path, '/reports') === 0) {
            require 'report.php';
            handleReports($db, $method, $path);
        }
        elseif (strpos($path, '/audit') === 0) {
            require 'audit.php';
            handleAudit($db, $method, $path);
        }
        elseif (strpos($path, '/users') === 0) {
            require 'users.php';
            handleUsers($db, $method, $path);
        }
        else {
            respond(['error' => 'Route not found: ' . $path], 404);
        }
    }

} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    respond(['error' => $e->getMessage()], 500);
}
