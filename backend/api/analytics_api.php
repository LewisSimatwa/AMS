<?php
/**
 * Handles analytics endpoints and connects to Python analytics microservice
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/cors.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration for Analytics Service
define('ANALYTICS_SERVICE_URL', getenv('ANALYTICS_SERVICE_URL') ?: 'http://localhost:5001/api/analytics');

/**
 * Make HTTP request to Python analytics microservice
 */
function callAnalyticsService($endpoint, $method = 'GET', $data = null) {
    $url = ANALYTICS_SERVICE_URL . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("cURL error calling analytics service: " . $error);
        return ['success' => false, 'error' => 'Analytics service connection failed'];
    }
    
    if ($httpCode === 200 && $response) {
        return json_decode($response, true);
    }
    
    error_log("Analytics service returned status: " . $httpCode);
    return ['success' => false, 'error' => 'Analytics service unavailable', 'status' => $httpCode];
}

/**
 * Verify user is authenticated and return user data
 */
function verifyAuthenticatedUser($db) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
    
    if (!$authHeader) {
        error_log("No authorization header provided");
        respond(['success' => false, 'error' => 'No authorization header provided'], 401);
    }
    
    // Extract token
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    } else {
        $token = $authHeader;
    }
    
    try {
        $tokenData = verifyToken($token);
        error_log("Token verified, user_id: " . $tokenData['user_id']);
    } catch (Exception $e) {
        error_log("Token verification failed: " . $e->getMessage());
        respond(['success' => false, 'error' => 'Invalid token: ' . $e->getMessage()], 401);
    }
    
    // Get user information
    $stmt = $db->prepare("
        SELECT u.*, u.institution_id 
        FROM users u 
        WHERE u.id = :user_id AND u.is_active = true 
        LIMIT 1
    ");
    $stmt->execute(['user_id' => $tokenData['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        error_log("User not found or inactive");
        respond(['success' => false, 'error' => 'User not found or inactive'], 404);
    }
    
    error_log("User verified: " . $user['username'] . " (ID: " . $user['id'] . ")");
    return $user;
}

/**
 * Parse the request path and method
 */
function parseRequest() {
    $requestUri = $_SERVER['REQUEST_URI'];
    $path = parse_url($requestUri, PHP_URL_PATH);
    $path = preg_replace('#^/api/analytics/?#', '', $path);
    $segments = array_values(array_filter(explode('/', $path)));
    
    // Handle empty segments
    if (empty($segments)) {
        $segments = [''];
    }
    
    return [
        'method' => $_SERVER['REQUEST_METHOD'],
        'path' => $path,
        'segments' => $segments,
        'query' => $_GET
    ];
}

try {
    error_log("=== Analytics API Called ===");
    
    // Get database connection
    $db = getDbConnection();
    
    // Verify user is authenticated
    $currentUser = verifyAuthenticatedUser($db);
    $institutionId = $currentUser['institution_id'];
    
    error_log("Authenticated user: " . $currentUser['id'] . " from institution: " . $institutionId);
    
    // Parse request
    $request = parseRequest();
    error_log("Request: " . $request['method'] . " " . $request['path']);
    
    $segments = $request['segments'];
    $method = $request['method'];
    
    // Check if segments exist
    if (empty($segments) || empty($segments[0])) {
        respond([
            'success' => false,
            'error' => 'Invalid endpoint',
            'path' => $request['path']
        ], 400);
    }
    
    // Route: GET /api/analytics/dashboard
    if ($segments[0] === 'dashboard' && $method === 'GET') {
        error_log("Fetching dashboard for institution: " . $institutionId);
        $result = callAnalyticsService("/dashboard/{$institutionId}");
        respond($result);
    }
    
    // Route: POST /api/analytics/extract-features
    if ($segments[0] === 'extract-features' && $method === 'POST') {
        error_log("Extracting features for institution: " . $institutionId);
        $result = callAnalyticsService("/extract-features/{$institutionId}", 'POST');
        respond($result);
    }
    
    // Route: POST /api/analytics/calculate-risks
    if ($segments[0] === 'calculate-risks' && $method === 'POST') {
        error_log("Calculating risks for institution: " . $institutionId);
        $result = callAnalyticsService("/calculate-risks/{$institutionId}", 'POST');
        respond($result);
    }
    
    // Route: GET /api/analytics/asset-details/{asset_id}
    if ($segments[0] === 'asset-details' && $method === 'GET' && isset($segments[1])) {
        $assetId = (int)$segments[1];
        
        // Verify asset belongs to user's institution
        $stmt = $db->prepare("SELECT institution_id FROM assets WHERE id = :id");
        $stmt->execute(['id' => $assetId]);
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$asset || $asset['institution_id'] != $institutionId) {
            respond(['success' => false, 'error' => 'Asset not found or access denied'], 403);
        }
        
        error_log("Fetching details for asset: " . $assetId);
        $result = callAnalyticsService("/asset-details/{$assetId}");
        respond($result);
    }
    
    // Route: GET /api/analytics/risk-alerts
    if ($segments[0] === 'risk-alerts' && $method === 'GET') {
        error_log("Fetching risk alerts for institution: " . $institutionId);
        
        $stmt = $db->prepare("
            SELECT 
                a.id, a.asset_code, a.name,
                ars.risk_level, ars.risk_score, ars.predicted_failure_date,
                d.name as department_name
            FROM asset_risk_scores ars
            JOIN assets a ON ars.asset_id = a.id
            LEFT JOIN departments d ON a.department_id = d.id
            WHERE ars.institution_id = :institution_id
                AND ars.risk_level = 'HIGH'
                AND (ars.predicted_failure_date IS NULL 
                     OR ars.predicted_failure_date <= CURRENT_DATE + INTERVAL '30 days')
            ORDER BY ars.risk_score DESC
            LIMIT 10
        ");
        $stmt->execute(['institution_id' => $institutionId]);
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($alerts) . " high-risk alerts");
        
        respond([
            'success' => true,
            'alerts' => $alerts,
            'count' => count($alerts)
        ]);
    }
    
    // Route: GET /api/analytics/stats (alias for summary)
    if (($segments[0] === 'stats' || $segments[0] === 'basic-stats') && $method === 'GET') {
        error_log("Fetching stats/basic-stats for institution: " . $institutionId);
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT a.id) as total_assets,
                COUNT(DISTINCT CASE WHEN ars.risk_level = 'HIGH' THEN a.id END) as high_risk,
                COUNT(DISTINCT CASE WHEN ars.risk_level = 'MEDIUM' THEN a.id END) as medium_risk,
                COUNT(DISTINCT CASE WHEN ars.risk_level = 'LOW' THEN a.id END) as low_risk,
                COUNT(DISTINCT CASE WHEN mr.status = 'open' THEN mr.id END) as open_maintenance,
                COUNT(DISTINCT CASE 
                    WHEN ars.predicted_failure_date IS NOT NULL 
                    AND ars.predicted_failure_date <= CURRENT_DATE + INTERVAL '30 days' 
                    THEN a.id 
                END) as maintenance_due_30d
            FROM assets a
            LEFT JOIN asset_risk_scores ars ON a.id = ars.asset_id
            LEFT JOIN maintenance_records mr ON a.id = mr.asset_id
            WHERE a.institution_id = :institution_id
                AND a.status != 'disposed'
        ");
        $stmt->execute(['institution_id' => $institutionId]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Ensure numeric values
        $summary = [
            'total_assets' => (int)($summary['total_assets'] ?? 0),
            'high_risk' => (int)($summary['high_risk'] ?? 0),
            'medium_risk' => (int)($summary['medium_risk'] ?? 0),
            'low_risk' => (int)($summary['low_risk'] ?? 0),
            'open_maintenance' => (int)($summary['open_maintenance'] ?? 0),
            'maintenance_due_30d' => (int)($summary['maintenance_due_30d'] ?? 0)
        ];
        
        error_log("Stats data: " . json_encode($summary));
        
        respond([
            'success' => true,
            'data' => $summary,
            'summary' => $summary // Include both for compatibility
        ]);
    }
    
    // Route: GET /api/analytics/summary
    if ($segments[0] === 'summary' && $method === 'GET') {
        error_log("Fetching summary for institution: " . $institutionId);
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT a.id) as total_assets,
                COUNT(DISTINCT CASE WHEN ars.risk_level = 'HIGH' THEN a.id END) as high_risk,
                COUNT(DISTINCT CASE WHEN ars.risk_level = 'MEDIUM' THEN a.id END) as medium_risk,
                COUNT(DISTINCT CASE WHEN ars.risk_level = 'LOW' THEN a.id END) as low_risk,
                COUNT(DISTINCT CASE WHEN mr.status = 'open' THEN mr.id END) as open_maintenance,
                COUNT(DISTINCT CASE 
                    WHEN ars.predicted_failure_date IS NOT NULL 
                    AND ars.predicted_failure_date <= CURRENT_DATE + INTERVAL '30 days' 
                    THEN a.id 
                END) as maintenance_due_30d
            FROM assets a
            LEFT JOIN asset_risk_scores ars ON a.id = ars.asset_id
            LEFT JOIN maintenance_records mr ON a.id = mr.asset_id
            WHERE a.institution_id = :institution_id
                AND a.status != 'disposed'
        ");
        $stmt->execute(['institution_id' => $institutionId]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Ensure numeric values
        $summary = [
            'total_assets' => (int)($summary['total_assets'] ?? 0),
            'high_risk' => (int)($summary['high_risk'] ?? 0),
            'medium_risk' => (int)($summary['medium_risk'] ?? 0),
            'low_risk' => (int)($summary['low_risk'] ?? 0),
            'open_maintenance' => (int)($summary['open_maintenance'] ?? 0),
            'maintenance_due_30d' => (int)($summary['maintenance_due_30d'] ?? 0)
        ];
        
        error_log("Summary data: " . json_encode($summary));
        
        respond([
            'success' => true,
            'summary' => $summary
        ]);
    }
    
    // Route: GET /api/analytics/trends
    if ($segments[0] === 'trends' && $method === 'GET') {
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $days = max(1, min($days, 365)); // Limit between 1 and 365 days
        
        error_log("Fetching trends for last {$days} days");
        
        $stmt = $db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as maintenance_count,
                COALESCE(SUM(cost), 0) as total_cost
            FROM maintenance_records
            WHERE institution_id = :institution_id
                AND created_at >= CURRENT_DATE - INTERVAL '{$days} days'
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute(['institution_id' => $institutionId]);
        $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        respond([
            'success' => true,
            'trends' => $trends,
            'days' => $days
        ]);
    }
    
    // Route: GET /api/analytics/department-risks
    if ($segments[0] === 'department-risks' && $method === 'GET') {
        error_log("Fetching department risks for institution: " . $institutionId);
        
        $stmt = $db->prepare("
            SELECT 
                d.id,
                d.name as department_name,
                d.code as department_code,
                COUNT(DISTINCT a.id) as total_assets,
                COUNT(DISTINCT CASE WHEN ars.risk_level = 'HIGH' THEN a.id END) as high_risk,
                COUNT(DISTINCT CASE WHEN ars.risk_level = 'MEDIUM' THEN a.id END) as medium_risk,
                COUNT(DISTINCT CASE WHEN ars.risk_level = 'LOW' THEN a.id END) as low_risk,
                COALESCE(AVG(ars.risk_score), 0) as avg_risk_score
            FROM departments d
            LEFT JOIN assets a ON d.id = a.department_id AND a.status != 'disposed'
            LEFT JOIN asset_risk_scores ars ON a.id = ars.asset_id
            WHERE d.institution_id = :institution_id
            GROUP BY d.id, d.name, d.code
            ORDER BY avg_risk_score DESC
        ");
        $stmt->execute(['institution_id' => $institutionId]);
        $departmentRisks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        respond([
            'success' => true,
            'departments' => $departmentRisks
        ]);
    }
    
    // Route: POST /api/analytics/schedule-maintenance
    if ($segments[0] === 'schedule-maintenance' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['asset_id'])) {
            respond(['success' => false, 'error' => 'asset_id is required'], 400);
        }
        
        $assetId = (int)$input['asset_id'];
        $description = $input['description'] ?? 'Scheduled based on predictive analytics';
        
        // Verify asset belongs to user's institution
        $stmt = $db->prepare("SELECT institution_id FROM assets WHERE id = :id");
        $stmt->execute(['id' => $assetId]);
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$asset || $asset['institution_id'] != $institutionId) {
            respond(['success' => false, 'error' => 'Asset not found or access denied'], 403);
        }
        
        error_log("Scheduling maintenance for asset: " . $assetId);
        
        // Create maintenance record
        $stmt = $db->prepare("
            INSERT INTO maintenance_records 
            (institution_id, asset_id, reported_by, maintenance_type, description, status, start_date)
            VALUES (:institution_id, :asset_id, :user_id, 'preventive', :description, 'open', CURRENT_TIMESTAMP)
            RETURNING id
        ");
        
        $stmt->execute([
            'institution_id' => $institutionId,
            'asset_id' => $assetId,
            'user_id' => $currentUser['id'],
            'description' => $description
        ]);
        
        $maintenanceId = $stmt->fetchColumn();
        
        // Log audit
        $auditStmt = $db->prepare("
            INSERT INTO audit_logs 
            (institution_id, user_id, entity_type, entity_id, action, new_values, details)
            VALUES (:institution_id, :user_id, 'maintenance_records', :entity_id, 'CREATE', 
                    :new_values, :details)
        ");
        
        $auditStmt->execute([
            'institution_id' => $institutionId,
            'user_id' => $currentUser['id'],
            'entity_id' => $maintenanceId,
            'new_values' => json_encode(['asset_id' => $assetId, 'status' => 'open']),
            'details' => json_encode(['source' => 'predictive_analytics'])
        ]);
        
        error_log("Maintenance scheduled successfully: " . $maintenanceId);
        
        respond([
            'success' => true,
            'maintenance_id' => $maintenanceId,
            'message' => 'Maintenance scheduled successfully'
        ]);
    }
    
    // Route: GET /api/analytics/health
    if ($segments[0] === 'health' && $method === 'GET') {
        error_log("Health check requested");
        
        $analyticsHealth = callAnalyticsService('/health');
        
        respond([
            'success' => true,
            'database' => 'connected',
            'analytics_service' => $analyticsHealth['status'] ?? 'unknown',
            'institution_id' => $institutionId,
            'user_id' => $currentUser['id'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Route: GET /api/analytics/test - Debug endpoint
    if ($segments[0] === 'test' && $method === 'GET') {
        error_log("Test endpoint called");
        
        respond([
            'success' => true,
            'message' => 'Analytics API is working',
            'user' => [
                'id' => $currentUser['id'],
                'username' => $currentUser['username'],
                'institution_id' => $institutionId
            ],
            'request' => [
                'method' => $method,
                'path' => $request['path'],
                'segments' => $segments
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Route not found
    error_log("Route not found: " . $request['path']);
    respond([
        'success' => false,
        'error' => 'Endpoint not found',
        'path' => $request['path'],
        'available_endpoints' => [
            'GET /api/analytics/dashboard',
            'GET /api/analytics/summary',
            'GET /api/analytics/stats',
            'GET /api/analytics/basic-stats',
            'GET /api/analytics/risk-alerts',
            'GET /api/analytics/trends',
            'GET /api/analytics/department-risks',
            'GET /api/analytics/health',
            'GET /api/analytics/test',
            'POST /api/analytics/extract-features',
            'POST /api/analytics/calculate-risks',
            'POST /api/analytics/schedule-maintenance'
        ]
    ], 404);
    
} catch (PDOException $e) {
    error_log("Database error in analytics.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    respond([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    error_log("General error in analytics.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    respond([
        'success' => false,
        'message' => 'Server error occurred',
        'error' => $e->getMessage()
    ], 500);
}
?>