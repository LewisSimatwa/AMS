<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();
ob_end_clean();

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load dependencies
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Verify authentication
try {
    verifyAuth();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Authentication failed: " . $e->getMessage()]);
    exit;
}

$institution_id = $_GET['institution_id'];
$user_id = $_GET['user_id'];
$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            listMaintenanceRecords($db, $institution_id);
            break;
        
        case 'get_assets':
            getAssets($db, $institution_id);
            break;
        
        case 'get_users':
            getUsers($db, $institution_id);
            break;
        
        case 'risk_scores':
            getRiskScores($db, $institution_id);
            break;
        
        case 'schedule':
            scheduleMaintenance($db, $institution_id, $user_id);
            break;
        
        case 'close':
            closeMaintenance($db, $institution_id, $user_id);
            break;
        
        default:
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Invalid action"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

// List all maintenance records
function listMaintenanceRecords($db, $institution_id) {
    $stmt = $db->prepare("
        SELECT 
            mr.id,
            mr.maintenance_type,
            mr.description,
            mr.status,
            mr.estimated_cost,
            mr.actual_cost,
            mr.start_date,
            mr.end_date,
            mr.actual_completion_date,
            mr.completion_notes,
            mr.created_at,
            mr.closed_at,
            a.name AS asset_name,
            a.asset_code,
            a.id AS asset_id,
            u1.first_name || ' ' || u1.last_name AS reported_by_name,
            u2.first_name || ' ' || u2.last_name AS assigned_to_name,
            u3.first_name || ' ' || u3.last_name AS closed_by_name
        FROM maintenance_records mr
        LEFT JOIN assets a ON mr.asset_id = a.id
        LEFT JOIN users u1 ON mr.reported_by = u1.id
        LEFT JOIN users u2 ON mr.assigned_to = u2.id
        LEFT JOIN users u3 ON mr.closed_by = u3.id
        WHERE mr.institution_id = ?
        ORDER BY mr.created_at DESC
    ");
    
    $stmt->execute([$institution_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    foreach ($records as &$record) {
        $record['start_date'] = $record['start_date'] ? date('Y-m-d', strtotime($record['start_date'])) : null;
        $record['end_date'] = $record['end_date'] ? date('Y-m-d', strtotime($record['end_date'])) : null;
        $record['actual_completion_date'] = $record['actual_completion_date'] ? date('Y-m-d H:i', strtotime($record['actual_completion_date'])) : null;
        $record['created_at'] = date('Y-m-d H:i', strtotime($record['created_at']));
        $record['closed_at'] = $record['closed_at'] ? date('Y-m-d H:i', strtotime($record['closed_at'])) : null;
    }
    
    echo json_encode([
        "success" => true,
        "records" => $records
    ]);
}

// Get assets for scheduling
function getAssets($db, $institution_id) {
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.name,
            a.asset_code,
            a.status,
            at.name AS asset_type_name,
            d.name AS department_name
        FROM assets a
        LEFT JOIN asset_types at ON a.asset_type_id = at.id
        LEFT JOIN departments d ON a.department_id = d.id
        WHERE a.institution_id = ?
        ORDER BY a.name
    ");
    
    $stmt->execute([$institution_id]);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "assets" => $assets
    ]);
}

// Get users for assignment
function getUsers($db, $institution_id) {
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.username,
            u.first_name,
            u.last_name,
            u.email
        FROM users u
        WHERE u.institution_id = ?
        AND u.is_active = true
        ORDER BY u.first_name, u.last_name
    ");
    
    $stmt->execute([$institution_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "users" => $users
    ]);
}

// Get risk scores for predictive maintenance
function getRiskScores($db, $institution_id) {
    $stmt = $db->prepare("
        SELECT 
            ars.id,
            ars.risk_score,
            ars.risk_level,
            ars.predicted_failure_date,
            ars.model_version,
            ars.predicted_at,
            a.id AS asset_id,
            a.name AS asset_name,
            a.asset_code
        FROM asset_risk_scores ars
        JOIN assets a ON ars.asset_id = a.id
        WHERE ars.institution_id = ?
        AND ars.risk_level IN ('MEDIUM', 'HIGH')
        ORDER BY ars.risk_score DESC
    ");
    
    $stmt->execute([$institution_id]);
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    foreach ($scores as &$score) {
        $score['predicted_failure_date'] = $score['predicted_failure_date'] ? 
            date('Y-m-d', strtotime($score['predicted_failure_date'])) : null;
        $score['predicted_at'] = date('Y-m-d H:i', strtotime($score['predicted_at']));
    }
    
    echo json_encode([
        "success" => true,
        "scores" => $scores
    ]);
}

// Schedule new maintenance
function scheduleMaintenance($db, $institution_id, $user_id) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method not allowed");
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception("Invalid JSON input");
    }
    
    // Validate required fields
    $asset_id = $input['asset_id'] ?? null;
    $maintenance_type = $input['maintenance_type'] ?? 'preventive';
    $description = $input['description'] ?? null;
    $start_date = $input['start_date'] ?? null;
    $assigned_to = $input['assigned_to'] ?? null;
    $estimated_cost = $input['estimated_cost'] ?? 0;
    
    if (!$asset_id || !$description || !$start_date) {
        throw new Exception("Asset, description, and start date are required");
    }
    
    // Validate maintenance type
    $valid_types = ['preventive', 'corrective', 'predictive'];
    if (!in_array($maintenance_type, $valid_types)) {
        throw new Exception("Invalid maintenance type");
    }
    
    // Validate and sanitize cost
    $estimated_cost = floatval($estimated_cost);
    if ($estimated_cost < 0) {
        throw new Exception("Cost cannot be negative");
    }
    
    $db->beginTransaction();
    
    try {
        // Verify asset exists
        $stmt = $db->prepare("
            SELECT id, name, asset_code 
            FROM assets 
            WHERE id = ? AND institution_id = ?
        ");
        $stmt->execute([$asset_id, $institution_id]);
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$asset) {
            throw new Exception("Asset not found");
        }
        
        // If assigned to someone, verify user exists
        if ($assigned_to) {
            $stmt = $db->prepare("
                SELECT id 
                FROM users 
                WHERE id = ? AND institution_id = ? AND is_active = true
            ");
            $stmt->execute([$assigned_to, $institution_id]);
            if (!$stmt->fetch()) {
                throw new Exception("Assigned user not found or inactive");
            }
        }
        
        // Insert maintenance record
        $stmt = $db->prepare("
            INSERT INTO maintenance_records (
                institution_id,
                asset_id,
                reported_by,
                assigned_to,
                maintenance_type,
                description,
                status,
                estimated_cost,
                start_date,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'open', ?, ?, now(), now())
            RETURNING id
        ");
        
        $stmt->execute([
            $institution_id,
            $asset_id,
            $user_id,
            $assigned_to ?: null,
            $maintenance_type,
            $description,
            $estimated_cost,
            $start_date
        ]);
        
        $maintenance_id = $stmt->fetchColumn();
        
        // Update asset status to maintenance if not already
        $stmt = $db->prepare("
            UPDATE assets 
            SET status = 'maintenance', updated_at = now()
            WHERE id = ? AND status != 'maintenance'
        ");
        $stmt->execute([$asset_id]);
        
        // Create audit log
        $stmt = $db->prepare("
            INSERT INTO audit_logs (
                institution_id,
                user_id,
                entity_type,
                entity_id,
                action,
                new_values,
                details,
                created_at
            ) VALUES (?, ?, 'maintenance_records', ?, 'CREATE', ?, ?, now())
        ");
        
        $stmt->execute([
            $institution_id,
            $user_id,
            $maintenance_id,
            json_encode([
                'asset_id' => $asset_id,
                'asset_name' => $asset['name'],
                'maintenance_type' => $maintenance_type,
                'status' => 'open',
                'estimated_cost' => $estimated_cost
            ]),
            json_encode([
                'description' => $description,
                'start_date' => $start_date
            ])
        ]);
        
        $db->commit();
        
        echo json_encode([
            "success" => true,
            "message" => "Maintenance scheduled successfully",
            "maintenance_id" => $maintenance_id,
            "estimated_cost" => $estimated_cost
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

// Close maintenance
function closeMaintenance($db, $institution_id, $user_id) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method not allowed");
    }

    // VERIFY USER HAS ICT ROLE
    $stmt = $db->prepare("
        SELECT r.name 
        FROM user_roles ur
        JOIN roles r ON ur.role_id = r.id
        WHERE ur.user_id = ? 
        AND ur.institution_id = ?
        AND r.name IN ('ict', 'admin')
    ");
    $stmt->execute([$user_id, $institution_id]);
    $hasIctRole = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hasIctRole) {
        http_response_code(403);
        echo json_encode([
            "success" => false, 
            "error" => "Access denied. Only ICT staff can close maintenance records."
        ]);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception("Invalid JSON input");
    }
    
    // Validate required fields
    $maintenance_id = $input['maintenance_id'] ?? null;
    $actual_cost = $input['actual_cost'] ?? 0;
    $completion_notes = $input['completion_notes'] ?? '';
    
    if (!$maintenance_id) {
        throw new Exception("Maintenance ID is required");
    }
    
    // Validate and sanitize cost
    $actual_cost = floatval($actual_cost);
    if ($actual_cost < 0) {
        throw new Exception("Actual cost cannot be negative");
    }
    
    $db->beginTransaction();
    
    try {
        // Get maintenance record and verify it exists
        $stmt = $db->prepare("
            SELECT mr.id, mr.asset_id, mr.status, a.name as asset_name, a.asset_code
            FROM maintenance_records mr
            JOIN assets a ON mr.asset_id = a.id
            WHERE mr.id = ? AND mr.institution_id = ?
        ");
        $stmt->execute([$maintenance_id, $institution_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) {
            throw new Exception("Maintenance record not found");
        }
        
        if ($record['status'] === 'closed') {
            throw new Exception("Maintenance record is already closed");
        }
        
        // Update maintenance record
        $stmt = $db->prepare("
            UPDATE maintenance_records 
            SET 
                status = 'closed',
                actual_cost = ?,
                actual_completion_date = now(),
                completion_notes = ?,
                closed_by = ?,
                closed_at = now(),
                updated_at = now()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $actual_cost,
            $completion_notes,
            $user_id,
            $maintenance_id
        ]);
        
        // Update asset status back to available
        $stmt = $db->prepare("
            UPDATE assets 
            SET status = 'available', updated_at = now()
            WHERE id = ?
        ");
        $stmt->execute([$record['asset_id']]);
        
        // Create audit log
        $stmt = $db->prepare("
            INSERT INTO audit_logs (
                institution_id,
                user_id,
                entity_type,
                entity_id,
                action,
                old_values,
                new_values,
                details,
                created_at
            ) VALUES (?, ?, 'maintenance_records', ?, 'CLOSE', ?, ?, ?, now())
        ");
        
        $stmt->execute([
            $institution_id,
            $user_id,
            $maintenance_id,
            json_encode([
                'status' => $record['status']
            ]),
            json_encode([
                'status' => 'closed',
                'actual_cost' => $actual_cost,
                'closed_by' => $user_id
            ]),
            json_encode([
                'asset_name' => $record['asset_name'],
                'asset_code' => $record['asset_code'],
                'completion_notes' => $completion_notes
            ])
        ]);
        
        $db->commit();
        
        echo json_encode([
            "success" => true,
            "message" => "Maintenance closed successfully",
            "maintenance_id" => $maintenance_id,
            "actual_cost" => $actual_cost
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
?>