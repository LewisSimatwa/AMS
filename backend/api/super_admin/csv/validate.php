<?php
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

// Maximum rows allowed
define('MAX_IMPORT_ROWS', 500);

try {
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
    
    // Required columns
    $requiredColumns = ['Asset Code', 'Name'];
    $missingColumns = array_diff($requiredColumns, $headers);
    
    if (!empty($missingColumns)) {
        respond(['error' => 'Missing required columns: ' . implode(', ', $missingColumns)], 400);
    }

    // Check row limit
    if (count($lines) > MAX_IMPORT_ROWS) {
        respond(['error' => "Maximum {MAX_IMPORT_ROWS} rows allowed per import"], 400);
    }

    $preview = [];
    $errors = [];
    $validRows = 0;
    $duplicates = 0;
    
    // Get existing asset codes for this institution
    $stmt = $db->prepare("SELECT asset_code FROM assets WHERE institution_id = ?");
    $stmt->execute([$institutionId]);
    $existingCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $existingCodesSet = array_flip($existingCodes);

    foreach ($lines as $lineNum => $line) {
        if (empty(trim($line))) continue;
        
        $row = str_getcsv($line);
        $rowNum = $lineNum + 2; // +2 because of header row and 0-based index
        
        if (count($row) !== count($headers)) {
            $errors[] = [
                'row' => $rowNum,
                'message' => 'Column count mismatch'
            ];
            continue;
        }
        
        $rowData = array_combine($headers, $row);
        $hasError = false;
        
        // Validate required fields
        if (empty($rowData['Asset Code'])) {
            $errors[] = [
                'row' => $rowNum,
                'message' => 'Asset Code is required'
            ];
            $hasError = true;
        }
        
        if (empty($rowData['Name'])) {
            $errors[] = [
                'row' => $rowNum,
                'message' => 'Name is required'
            ];
            $hasError = true;
        }
        
        // Check for duplicates
        if (isset($existingCodesSet[$rowData['Asset Code']])) {
            $errors[] = [
                'row' => $rowNum,
                'message' => 'Asset Code already exists: ' . $rowData['Asset Code']
            ];
            $hasError = true;
            $duplicates++;
        }
        
        // Validate acquisition cost if provided
        if (!empty($rowData['Acquisition Cost']) && !is_numeric($rowData['Acquisition Cost'])) {
            $errors[] = [
                'row' => $rowNum,
                'message' => 'Acquisition Cost must be numeric'
            ];
            $hasError = true;
        }
        
        // Validate status
        $validStatuses = ['available', 'on_loan', 'maintenance', 'retired'];
        $status = !empty($rowData['Status']) ? strtolower($rowData['Status']) : 'available';
        if (!in_array($status, $validStatuses)) {
            $errors[] = [
                'row' => $rowNum,
                'message' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)
            ];
            $hasError = true;
        }
        
        // Validate condition
        $validConditions = ['excellent', 'good', 'fair', 'poor'];
        $condition = !empty($rowData['Condition']) ? strtolower($rowData['Condition']) : 'good';
        if (!in_array($condition, $validConditions)) {
            $errors[] = [
                'row' => $rowNum,
                'message' => 'Invalid condition. Must be one of: ' . implode(', ', $validConditions)
            ];
            $hasError = true;
        }
        
        if (!$hasError) {
            $validRows++;
        }
        
        // Add to preview (first 10 rows)
        if (count($preview) < 10) {
            $preview[] = [
                'asset_code' => $rowData['Asset Code'],
                'name' => $rowData['Name'],
                'serial_number' => $rowData['Serial Number'] ?? null,
                'category' => $rowData['Category'] ?? null,
                'acquisition_cost' => $rowData['Acquisition Cost'] ?? null,
                'status' => $status,
                'has_error' => $hasError
            ];
        }
    }
    
    respond([
        'preview' => $preview,
        'errors' => $errors,
        'stats' => [
            'total_rows' => count($lines),
            'valid_rows' => $validRows,
            'duplicates' => $duplicates
        ]
    ]);

} catch (Exception $e) {
    error_log('CSV validation error: ' . $e->getMessage());
    respond(['error' => 'Validation failed: ' . $e->getMessage()], 500);
}
?>