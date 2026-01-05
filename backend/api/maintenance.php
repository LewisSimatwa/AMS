<?php
// api/maintenance.php
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start session
session_start();

// Database configuration
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'miams_db';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'password';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['institution_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$institution_id = $_SESSION['institution_id'];

// Get action from query string or POST
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        listMaintenanceRecords($pdo, $institution_id);
        break;
    
    case 'schedule':
        scheduleMaintenance($pdo, $user_id, $institution_id);
        break;
    
    case 'get_assets':
        getAssets($pdo, $institution_id);
        break;
    
    case 'get_users':
        getUsers($pdo, $institution_id);
        break;
    
    case 'risk_scores':
        getRiskScores($pdo, $institution_id);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function listMaintenanceRecords($pdo, $institution_id) {
    try {
        $query = "
            SELECT 
                mr.id,
                mr.asset_id,
                mr.maintenance_type,
                mr.description,
                mr.status,
                mr.cost,
                TO_CHAR(mr.start_date, 'YYYY-MM-DD') as start_date,
                TO_CHAR(mr.end_date, 'YYYY-MM-DD') as end_date,
                TO_CHAR(mr.created_at, 'YYYY-MM-DD HH24:MI') as created_at,
                a.name as asset_name,
                a.asset_code,
                a.serial_number,
                u1.first_name || ' ' || u1.last_name as reported_by_name,
                u2.first_name || ' ' || u2.last_name as assigned_to_name
            FROM maintenance_records mr
            JOIN assets a ON mr.asset_id = a.id
            LEFT JOIN users u1 ON mr.reported_by = u1.id
            LEFT JOIN users u2 ON mr.assigned_to = u2.id
            WHERE mr.institution_id = :institution_id
            ORDER BY 
                CASE mr.status
                    WHEN 'open' THEN 1
                    WHEN 'in_progress' THEN 2
                    WHEN 'closed' THEN 3
                END,
                mr.created_at DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute(['institution_id' => $institution_id]);
        $records = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'records' => $records,
            'count' => count($records)
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function scheduleMaintenance($pdo, $user_id, $institution_id) {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate required fields
    if (empty($data['asset_id']) || empty($data['maintenance_type']) || 
        empty($data['description']) || empty($data['start_date'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    $asset_id = $data['asset_id'];
    $maintenance_type = $data['maintenance_type'];
    $description = $data['description'];
    $start_date = $data['start_date'];
    $assigned_to = !empty($data['assigned_to']) ? $data['assigned_to'] : null;
    
    try {
        // Verify asset belongs to institution
        $check_query = "SELECT id FROM assets WHERE id = :asset_id AND institution_id = :institution_id";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([
            'asset_id' => $asset_id,
            'institution_id' => $institution_id
        ]);
        
        if (!$check_stmt->fetch()) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Asset not found or does not belong to your institution'
            ]);
            return;
        }
        
        // Insert maintenance record
        $insert_query = "
            INSERT INTO maintenance_records (
                institution_id,
                asset_id,
                reported_by,
                assigned_to,
                maintenance_type,
                description,
                status,
                start_date,
                created_at,
                updated_at
            ) VALUES (
                :institution_id,
                :asset_id,
                :reported_by,
                :assigned_to,
                :maintenance_type,
                :description,
                'open',
                :start_date,
                NOW(),
                NOW()
            )
            RETURNING id
        ";
        
        $stmt = $pdo->prepare($insert_query);
        $stmt->execute([
            'institution_id' => $institution_id,
            'asset_id' => $asset_id,
            'reported_by' => $user_id,
            'assigned_to' => $assigned_to,
            'maintenance_type' => $maintenance_type,
            'description' => $description,
            'start_date' => $start_date
        ]);
        
        $result = $stmt->fetch();
        $maintenance_id = $result['id'];
        
        // Update asset status to 'maintenance'
        $update_query = "
            UPDATE assets 
            SET status = 'maintenance', updated_at = NOW()
            WHERE id = :asset_id AND status != 'maintenance'
        ";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute(['asset_id' => $asset_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Maintenance scheduled successfully',
            'maintenance_id' => $maintenance_id
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getAssets($pdo, $institution_id) {
    try {
        $query = "
            SELECT 
                a.id,
                a.asset_code,
                a.serial_number,
                a.name,
                a.description,
                a.status,
                a.condition,
                d.name as department_name,
                at.name as asset_type_name
            FROM assets a
            LEFT JOIN departments d ON a.department_id = d.id
            LEFT JOIN asset_types at ON a.asset_type_id = at.id
            WHERE a.institution_id = :institution_id
            ORDER BY a.name
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute(['institution_id' => $institution_id]);
        $assets = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'assets' => $assets,
            'count' => count($assets)
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getUsers($pdo, $institution_id) {
    try {
        $query = "
            SELECT 
                u.id,
                u.username,
                u.first_name,
                u.last_name,
                u.email,
                r.name as role
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN roles r ON ur.role_id = r.id
            WHERE u.institution_id = :institution_id
                AND u.is_active = true
                AND r.name IN ('ict', 'admin')
            ORDER BY u.first_name, u.last_name
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute(['institution_id' => $institution_id]);
        $users = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'users' => $users,
            'count' => count($users)
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getRiskScores($pdo, $institution_id) {
    try {
        $query = "
            SELECT 
                ars.id,
                ars.asset_id,
                ars.risk_score,
                ars.risk_level,
                TO_CHAR(ars.predicted_failure_date, 'YYYY-MM-DD') as predicted_failure_date,
                ars.model_version,
                TO_CHAR(ars.predicted_at, 'YYYY-MM-DD HH24:MI') as predicted_at,
                a.name as asset_name,
                a.asset_code,
                a.serial_number,
                a.status as asset_status,
                a.condition as asset_condition
            FROM asset_risk_scores ars
            JOIN assets a ON ars.asset_id = a.id
            WHERE ars.institution_id = :institution_id
                AND ars.risk_level IN ('MEDIUM', 'HIGH')
                AND ars.id IN (
                    SELECT MAX(id)
                    FROM asset_risk_scores
                    WHERE institution_id = :institution_id
                    GROUP BY asset_id
                )
            ORDER BY 
                CASE ars.risk_level
                    WHEN 'HIGH' THEN 1
                    WHEN 'MEDIUM' THEN 2
                    WHEN 'LOW' THEN 3
                END,
                ars.risk_score DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute(['institution_id' => $institution_id]);
        $scores = $stmt->fetchAll();
        
        // Convert risk_score to float
        foreach ($scores as &$score) {
            $score['risk_score'] = (float)$score['risk_score'];
        }
        
        echo json_encode([
            'success' => true,
            'scores' => $scores,
            'count' => count($scores)
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>