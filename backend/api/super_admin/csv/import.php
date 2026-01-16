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
    
    // Parse CSV - handle different line endings
    $csvData = str_replace(["\r\n", "\r"], "\n", $csvData);
    $lines = explode("\n", trim($csvData));
    
    if (count($lines) < 2) {
        respond(['error' => 'CSV file must have at least a header and one data row'], 400);
    }
    
    $headerLine = array_shift($lines);
    
    // Auto-detect delimiter (tab or comma)
    $delimiter = strpos($headerLine, "\t") !== false ? "\t" : ",";
    
    $headers = str_getcsv($headerLine, $delimiter);
    
    // Normalize headers (trim whitespace)
    $headers = array_map('trim', $headers);
    
    // Filter out empty lines
    $lines = array_filter($lines, function($line) {
        return !empty(trim($line));
    });
    
    if (count($lines) === 0) {
        respond(['error' => 'No data rows found in CSV'], 400);
    }
    
    if (count($lines) > MAX_IMPORT_ROWS) {
        respond(['error' => "Maximum " . MAX_IMPORT_ROWS . " rows allowed per import"], 400);
    }
    
    $imported = 0;
    $failed = 0;
    $errors = [];
    
    // Get existing asset codes for this institution
    $stmt = $db->prepare("SELECT asset_code FROM assets WHERE institution_id = ?");
    $stmt->execute([$institutionId]);
    $existingCodes = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
    
    foreach ($lines as $lineNum => $line) {
        $rowNumber = $lineNum + 2; // +2 because we removed header and arrays are 0-indexed
        
        $data = str_getcsv($line, $delimiter);
        
        // Pad array if needed
        while (count($data) < count($headers)) {
            $data[] = '';
        }
        
        // Check if data count matches headers
        if (count($data) > count($headers)) {
            $failed++;
            $errors[] = [
                'line' => $rowNumber,
                'error' => 'Too many columns in row'
            ];
            continue;
        }
        
        $row = array_combine($headers, array_slice($data, 0, count($headers)));
        
        // Map CSV columns to expected field names
        $assetCode = isset($row['Asset Code']) ? trim($row['Asset Code']) : '';
        $assetName = isset($row['Name']) ? trim($row['Name']) : '';
        $category = isset($row['Category']) ? trim($row['Category']) : '';
        $serialNumber = isset($row['Serial Number']) ? trim($row['Serial Number']) : '';
        $acquisitionDate = isset($row['Acquisition Date']) ? trim($row['Acquisition Date']) : '';
        $acquisitionCost = isset($row['Acquisition Cost']) ? trim($row['Acquisition Cost']) : '';
        $condition = isset($row['Condition']) ? trim($row['Condition']) : 'good';
        $status = isset($row['Status']) ? trim($row['Status']) : 'available';
        $location = isset($row['Location']) ? trim($row['Location']) : '';
        $description = isset($row['Description']) ? trim($row['Description']) : '';
        
        // Normalize condition to lowercase
        $condition = strtolower($condition);
        if (!in_array($condition, ['good', 'fair', 'poor', 'excellent'])) {
            $condition = 'good';
        }
        
        // Normalize status to lowercase
        $status = strtolower($status);
        if (!in_array($status, ['available', 'on_loan', 'maintenance', 'retired'])) {
            $status = 'available';
        }
        
        // Validate required fields
        if (empty($assetCode) || empty($assetName) || empty($category)) {
            $failed++;
            $missingFields = [];
            if (empty($assetCode)) $missingFields[] = 'Asset Code';
            if (empty($assetName)) $missingFields[] = 'Name';
            if (empty($category)) $missingFields[] = 'Category';
            
            $errors[] = [
                'line' => $rowNumber,
                'error' => 'Missing required fields: ' . implode(', ', $missingFields)
            ];
            continue;
        }
        
        // Check for duplicate asset code
        if (isset($existingCodes[$assetCode])) {
            $failed++;
            $errors[] = [
                'line' => $rowNumber,
                'error' => "Asset code '{$assetCode}' already exists"
            ];
            continue;
        }
        
        // Parse and validate date - handle multiple formats
        $finalAcquisitionDate = null;
        if (!empty($acquisitionDate)) {
            // Try different date formats
            $dateFormats = ['Y-m-d', 'n/j/Y', 'd/m/Y', 'm/d/Y', 'j/n/Y'];
            $dateObj = false;
            
            foreach ($dateFormats as $format) {
                $dateObj = DateTime::createFromFormat($format, $acquisitionDate);
                if ($dateObj !== false) {
                    $finalAcquisitionDate = $dateObj->format('Y-m-d');
                    break;
                }
            }
            
            if ($dateObj === false) {
                $failed++;
                $errors[] = [
                    'line' => $rowNumber,
                    'error' => "Invalid date format: '{$acquisitionDate}'. Use YYYY-MM-DD, MM/DD/YYYY, or DD/MM/YYYY"
                ];
                continue;
            }
        }
        
        // Parse acquisition cost
        $finalAcquisitionCost = null;
        if (!empty($acquisitionCost)) {
            $finalAcquisitionCost = floatval(str_replace(',', '', $acquisitionCost));
        }
        
        // Check if category exists as asset_type, create if not
        $stmt = $db->prepare("
            SELECT id FROM asset_types 
            WHERE institution_id = ? AND LOWER(name) = LOWER(?)
        ");
        $stmt->execute([$institutionId, $category]);
        $assetTypeId = $stmt->fetchColumn();
        
        if (!$assetTypeId) {
            // Create new asset type
            $stmt = $db->prepare("
                INSERT INTO asset_types (institution_id, name, description) 
                VALUES (?, ?, ?) 
                RETURNING id
            ");
            $stmt->execute([
                $institutionId, 
                $category, 
                'Auto-created from CSV import'
            ]);
            $assetTypeId = $stmt->fetchColumn();
        }
        
        // Insert asset - matching your actual database schema
        try {
            $stmt = $db->prepare("
                INSERT INTO assets (
                    institution_id, 
                    asset_code, 
                    name,
                    serial_number,
                    asset_type_id,
                    description,
                    acquisition_date,
                    acquisition_cost,
                    condition,
                    status,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $institutionId,
                $assetCode,
                $assetName,
                !empty($serialNumber) ? $serialNumber : null,
                $assetTypeId,
                !empty($description) ? $description : null,
                $finalAcquisitionDate,
                $finalAcquisitionCost,
                $condition,
                $status
            ]);
            
            if ($result) {
                // Add to existing codes to prevent duplicates within the same import
                $existingCodes[$assetCode] = true;
                $imported++;
            } else {
                $failed++;
                $errors[] = [
                    'line' => $rowNumber,
                    'error' => 'Failed to insert asset'
                ];
            }
            
        } catch (PDOException $e) {
            $failed++;
            $errors[] = [
                'line' => $rowNumber,
                'error' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    // Log the import activity only if we have something to log
    if ($imported > 0 || $failed > 0) {
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, details, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId,
            'CSV_IMPORT',
            'assets',
            json_encode([
                'institution_id' => $institutionId,
                'institution_name' => $institution['name'],
                'imported' => $imported,
                'failed' => $failed,
                'total_rows' => count($lines)
            ])
        ]);
    }
    
    // Commit transaction
    $db->commit();
    
    respond([
        'success' => true,
        'message' => $imported > 0 ? "Import completed successfully" : "No assets were imported",
        'imported_count' => $imported,
        'failed' => $failed,
        'total_rows' => count($lines),
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    respond(['error' => 'Import failed: ' . $e->getMessage()], 500);
}
?>