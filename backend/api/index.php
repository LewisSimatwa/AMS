<?php
require 'config.php';
require 'helpers.php';


// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Institution-ID');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove /api prefix if present
$path = str_replace('/api', '', $path);

// Log for debugging
error_log("Request path: $path, Method: $method");

try {
    // ============================================
    // PUBLIC ROUTES (NO TOKEN REQUIRED)
    // ============================================
    
    if ($path === '/login' && $method === 'POST') {
        require 'auth.php';
        login();
    }
    elseif ($path === '/register' && $method === 'POST') {
        require 'register.php';
        register();
    }
    elseif ($path === '/register.php' && $method === 'POST') {
        require 'register.php';
        register();
    }
    elseif ($path === '/auth.php' && $method === 'POST') {
        require 'auth.php';
        $input = getInput();
        $action = $input['action'] ?? 'login';
        if ($action === 'login') {
            login();
        } elseif ($action === 'logout') {
            logout();
        }
    }
    elseif ($path === '/logout' && $method === 'POST') {
        require 'auth.php';
        logout();
    }

    // ============================================
    // PROTECTED ROUTES (TOKEN REQUIRED)
    // ============================================
    else {
        verifyAuth();
        
        // ----------------------------------------
        // ASSET MANAGEMENT ROUTES
        // ----------------------------------------
        
        // Get available assets
        if ($path === '/available' && $method === 'GET') {
            require __DIR__ . '/available.php';
        }
        // Delete asset route (must come before general /assets route)
        elseif ($path === '/delete_asset.php' && $method === 'POST') {
            require __DIR__ . '/delete_asset.php';
        }
        elseif (preg_match('#^/assets/(\d+)$#', $path, $matches) && $method === 'DELETE') {
            $_GET['id'] = $matches[1];
            require __DIR__ . '/delete_asset.php';
        }
        // General assets routes
        elseif (strpos($path, '/assets') === 0) {
            require 'assets.php';
            handleAssets($db, $method, $path);
        }
        
        // ----------------------------------------
        // TRANSACTION ROUTES
        // ----------------------------------------
        elseif (strpos($path, '/transactions') === 0) {
            require 'transactions.php';
            handleTransactions($db, $method, $path);
        }
        
        // ----------------------------------------
        // MAINTENANCE ROUTES
        // ----------------------------------------
        elseif (strpos($path, '/maintenance') === 0) {
            require 'maintenance.php';
            handleMaintenance($db, $method, $path);
        }
        
        // ----------------------------------------
        // ANALYTICS ROUTES
        // ----------------------------------------
        elseif (strpos($path, '/analytics') === 0) {
            require 'analytics.php';
            handleAnalytics($db, $method, $path);
        }
        
        // ----------------------------------------
        // REPORTS ROUTES
        // ----------------------------------------
        elseif (strpos($path, '/reports') === 0) {
            require 'report.php';
            handleReports($db, $method, $path);
        }
        
        // ----------------------------------------
        // AUDIT LOGS ROUTES
        // ----------------------------------------
        elseif (strpos($path, '/audit') === 0) {
            require 'audit_logs.php';
            handleAudit($db, $method, $path);
        }
        
        // ----------------------------------------
        // USER MANAGEMENT ROUTES (ADMIN ONLY)
        // ----------------------------------------
        
        // Handle user management with query parameters
        elseif ($path === '/users.php' && $method === 'GET') {
            require 'users.php';
            // users.php handles its own routing based on $_GET['action']
        }
        elseif ($path === '/users.php' && $method === 'POST') {
            require 'users.php';
            // users.php handles its own routing based on $_GET['action']
        }
        // Handle RESTful user routes
        elseif ($path === '/users' && $method === 'GET') {
            $_GET['action'] = 'list';
            require 'users.php';
        }
        elseif ($path === '/users' && $method === 'POST') {
            // Determine action from request body or default to create
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input['action'])) {
                $_GET['action'] = $input['action'];
            } else {
                $_GET['action'] = 'create';
            }
            require 'users.php';
        }
        elseif (preg_match('#^/users/(\d+)$#', $path, $matches) && $method === 'GET') {
            $_GET['action'] = 'get';
            $_GET['id'] = $matches[1];
            require 'users.php';
        }
        elseif (preg_match('#^/users/(\d+)$#', $path, $matches) && $method === 'PUT') {
            $_GET['action'] = 'update';
            $_GET['id'] = $matches[1];
            require 'users.php';
        }
        elseif (preg_match('#^/users/(\d+)$#', $path, $matches) && $method === 'DELETE') {
            $_GET['action'] = 'delete';
            $_GET['id'] = $matches[1];
            require 'users.php';
        }
        // User roles endpoint
        elseif ($path === '/users/roles' && $method === 'GET') {
            $_GET['action'] = 'get_roles';
            require 'users.php';
        }
        // User departments endpoint
        elseif ($path === '/users/departments' && $method === 'GET') {
            $_GET['action'] = 'get_departments';
            require 'users.php';
        }
        // Toggle user status endpoint
        elseif (preg_match('#^/users/(\d+)/toggle-status$#', $path, $matches) && $method === 'POST') {
            $_GET['action'] = 'toggle_status';
            $_GET['id'] = $matches[1];
            require 'users.php';
        }
        // General users routes (fallback for any /users/* patterns)
        elseif (strpos($path, '/users') === 0) {
            require 'users.php';
        }
        
        // ----------------------------------------
        // 404 - ROUTE NOT FOUND
        // ----------------------------------------
        else {
            respond(['error' => 'Route not found: ' . $path], 404);
        }
    }

} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    respond([
        'error' => $e->getMessage(),
        'success' => false
    ], 500);
}
?>