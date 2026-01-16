<?php
// api/super_admin/csv/import.php
require __DIR__ . '/../../cors.php';
require __DIR__ . '/../../config.php';
require __DIR__ . '/../../helpers.php';

// Verify super admin authentication
try {
    $decoded = verifyAuth();
    if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
        respond(['error' => 'Access denied. Super admin privileges required.'], 403);
    }
} catch (Exception $e) {
    respond(['error' => 'Authentication failed'], 401);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['csv_data']) || !isset($input['institution_id'])) {
    respond(['error' => 'Missing required fields'], 400);
}

$csvData = $input['csv_data'];
$institutionId = (int)$input['institution_id'];
$userId = $decoded['user_id'];

define('MAX_IMPORT_ROWS', 500);

try {
    // Start transaction
    $db->beginTransaction();
    
    // Verify institution exists
    $stmt = $db->prepare("SELECT id, name FROM institutions WHERE id = ?");
    $stmt->execute([$institutionId]);
    $institution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$institution) {
        respond(['error' => 'Institution not found'], 404);
    }
    
    // Parse CSV
    $lines = explode("\n", trim($csvData));
    $headers = str_getcsv(array_shift($lines));
    
    if (count($lines) > MAX_IMPORT_ROWS) {
        respond(['error' => "Maximum " . MAX_IMPORT_ROWS . " rows allowed per import"], 400);
    }
    
    $imported = 0;
    $failed = 0;
    $errors = [];
    
    // Get existing asset codes
    $stmt = $db->prepare("SELECT asset_code FROM assets WHERE institution_id = ?");
    $stmt->execute([$institutionId]);
    $existingCodes = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
    
    foreach ($lines as $lineNum => $line) {
        if (empty(trim($line))) continue;
        
        $data = str_getcsv($line);
        $row = array_combine($headers, $data);
        
        // Validate required fields
        $requiredFields = ['asset_code', 'asset_name', 'category'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($row[$field]) || empty(trim($row[$field]))) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            $failed++;
            $errors[] = [
                'line' => $lineNum + 2,
                'error' => 'Missing required fields: ' . implode(', ', $missingFields)
            ];
            continue;
        }
        
        // Check for duplicate asset code
        $assetCode = trim($row['asset_code']);
        if (isset($existingCodes[$assetCode])) {
            $failed++;
            $errors[] = [
                'line' => $lineNum + 2,
                'error' => "Asset code '{$assetCode}' already exists"
            ];
            continue;
        }
        
        // Insert asset
        try {
            $stmt = $db->prepare("
                INSERT INTO assets (
                    institution_id, 
                    asset_code, 
                    asset_name, 
                    category,
                    description,
                    purchase_date,
                    purchase_cost,
                    location,
                    status,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $institutionId,
                $assetCode,
                trim($row['asset_name']),
                trim($row['category']),
                isset($row['description']) ? trim($row['description']) : null,
                isset($row['purchase_date']) ? trim($row['purchase_date']) : null,
                isset($row['purchase_cost']) ? floatval($row['purchase_cost']) : null,
                isset($row['location']) ? trim($row['location']) : null,
                isset($row['status']) ? trim($row['status']) : 'active'
            ]);
            
            $existingCodes[$assetCode] = true;
            $imported++;
            
        } catch (PDOException $e) {
            $failed++;
            $errors[] = [
                'line' => $lineNum + 2,
                'error' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    // Log the import activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, details, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $userId,
        'csv_import',
        json_encode([
            'institution_id' => $institutionId,
            'institution_name' => $institution['name'],
            'imported' => $imported,
            'failed' => $failed
        ])
    ]);
    
    // Commit transaction
    $db->commit();
    
    respond([
        'success' => true,
        'message' => "Import completed",
        'imported' => $imported,
        'failed' => $failed,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    respond(['error' => 'Import failed: ' . $e->getMessage()], 500);
}