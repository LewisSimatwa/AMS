<?php
function handleAssets($db, $method, $path) {
    global $db;
    
    error_log("handleAssets called - Method: $method, Path: $path");
    
    // Get authenticated user info
    $institutionId = $_GET['institution_id'] ?? null;
    $userId = $_GET['user_id'] ?? null;
    $userRole = $_GET['role'] ?? null;
    
    if (!$institutionId || !$userId) {
        respond(['error' => 'Missing authentication data'], 401);
    }
    
    // GET /assets - List all assets
    if ($method === 'GET' && $path === '/assets') {
        try {
            $stmt = $db->prepare("
                SELECT 
                    a.id,
                    a.asset_code,
                    a.serial_number,
                    a.name,
                    a.description,
                    a.acquisition_date,
                    a.acquisition_cost,
                    a.condition,
                    a.status,
                    a.created_at,
                    a.updated_at,
                    a.date_retired,
                    d.name as department,
                    at.name as category,
                    u.username as assigned_to,
                    l.name as location
                FROM assets a
                LEFT JOIN departments d ON a.department_id = d.id
                LEFT JOIN asset_types at ON a.asset_type_id = at.id
                LEFT JOIN users u ON a.current_holder_id = u.id
                LEFT JOIN locations l ON a.location_id = l.id
                WHERE a.institution_id = ?
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$institutionId]);
            $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            respond(['success' => true, 'assets' => $assets], 200);
        } catch (PDOException $e) {
            error_log("Error fetching assets: " . $e->getMessage());
            respond(['error' => 'Failed to fetch assets'], 500);
        }
    }
    
    // POST /assets - Create new asset
    elseif ($method === 'POST' && $path === '/assets') {
        try {
            // Only admin, ict can create assets
            if (!in_array($userRole, [ROLE_ADMIN, ROLE_ICT])) {
                respond(['error' => 'Permission denied. Only admins and ICT can register assets.'], 403);
            }
            
            $input = getInput();
            
            error_log("Creating asset with data: " . json_encode($input));
            
            // Validate required fields
            if (empty($input['name'])) {
                respond(['error' => 'Asset name is required'], 400);
            }
            
            if (empty($input['asset_code'])) {
                respond(['error' => 'Asset code is required'], 400);
            }
            
            // Check if asset code already exists
            $checkStmt = $db->prepare("
                SELECT id FROM assets 
                WHERE asset_code = ? AND institution_id = ?
            ");
            $checkStmt->execute([$input['asset_code'], $institutionId]);
            
            if ($checkStmt->fetch()) {
                respond(['error' => 'Asset code already exists'], 400);
            }
            
            // Generate serial number if not provided
            $serialNumber = $input['serial_number'] ?? null;
            if (empty($serialNumber)) {
                $serialNumber = generateSerialNumber($db, $institutionId, $input['asset_code']);
            }
            
            // Get or create asset type
            $assetTypeId = null;
            if (!empty($input['category'])) {
                $typeStmt = $db->prepare("
                    SELECT id FROM asset_types 
                    WHERE name = ? AND (institution_id = ? OR institution_id IS NULL)
                    LIMIT 1
                ");
                $typeStmt->execute([$input['category'], $institutionId]);
                $assetType = $typeStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($assetType) {
                    $assetTypeId = $assetType['id'];
                } else {
                    // Create new asset type
                    $createTypeStmt = $db->prepare("
                        INSERT INTO asset_types (institution_id, name, description)
                        VALUES (?, ?, ?)
                        RETURNING id
                    ");
                    $createTypeStmt->execute([
                        $institutionId,
                        $input['category'],
                        'Auto-created category'
                    ]);
                    $assetTypeId = $createTypeStmt->fetchColumn();
                }
            }
            
            $db->beginTransaction();
            
            // Insert asset (including location_id and generated serial_number)
            $stmt = $db->prepare("
                INSERT INTO assets (
                    institution_id,
                    asset_type_id,
                    asset_code,
                    serial_number,
                    name,
                    description,
                    acquisition_date,
                    acquisition_cost,
                    condition,
                    status,
                    location_id,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                RETURNING id
            ");
            
            $stmt->execute([
                $institutionId,
                $assetTypeId,
                $input['asset_code'],
                $serialNumber,  // Use generated serial number
                $input['name'],
                $input['description'] ?? null,
                $input['purchase_date'] ?? null,
                $input['purchase_cost'] ?? 0,
                'good', // default condition
                $input['status'] ?? 'available',
                $input['location_id'] ?? null
            ]);
            
            $assetId = $stmt->fetchColumn();
            
            error_log("Asset created with ID: $assetId, Serial Number: $serialNumber");
            
            // Create audit log
            try {
                $auditStmt = $db->prepare("
                    INSERT INTO audit_logs (
                        institution_id,
                        user_id,
                        entity_type,
                        entity_id,
                        action,
                        old_values,
                        new_values,
                        created_at
                    ) VALUES (?, ?, 'assets', ?, 'CREATE', NULL, ?, NOW())
                ");
                
                $auditStmt->execute([
                    $institutionId,
                    $userId,
                    $assetId,
                    json_encode([
                        'asset_code' => $input['asset_code'],
                        'serial_number' => $serialNumber,
                        'name' => $input['name'],
                        'status' => $input['status'] ?? 'available'
                    ])
                ]);
            } catch (Exception $e) {
                error_log("Audit log warning: " . $e->getMessage());
            }
            
            $db->commit();
            
            // Fetch the created asset with joins (including location)
            $getStmt = $db->prepare("
                SELECT 
                    a.*,
                    at.name as category,
                    l.name as location
                FROM assets a
                LEFT JOIN asset_types at ON a.asset_type_id = at.id
                LEFT JOIN locations l ON a.location_id = l.id
                WHERE a.id = ?
            ");
            $getStmt->execute([$assetId]);
            $newAsset = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            respond([
                'success' => true,
                'message' => 'Asset registered successfully',
                'asset' => $newAsset
            ], 201);
            
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error creating asset: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            error_log("Stack trace: " . $e->getTraceAsString());
            respond(['error' => 'Failed to register asset: ' . $e->getMessage()], 500);
        }
    }
    
    // GET /assets/{id} - Get single asset
    elseif ($method === 'GET' && preg_match('#^/assets/(\d+)$#', $path, $matches)) {
        $assetId = $matches[1];
        
        try {
            $stmt = $db->prepare("
                SELECT 
                    a.*,
                    d.name as department,
                    at.name as category,
                    u.username as assigned_to,
                    l.name as location
                FROM assets a
                LEFT JOIN departments d ON a.department_id = d.id
                LEFT JOIN asset_types at ON a.asset_type_id = at.id
                LEFT JOIN users u ON a.current_holder_id = u.id
                LEFT JOIN locations l ON a.location_id = l.id
                WHERE a.id = ? AND a.institution_id = ?
            ");
            $stmt->execute([$assetId, $institutionId]);
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$asset) {
                respond(['error' => 'Asset not found'], 404);
            }
            
            respond(['success' => true, 'asset' => $asset], 200);
        } catch (PDOException $e) {
            error_log("Error fetching asset: " . $e->getMessage());
            respond(['error' => 'Failed to fetch asset'], 500);
        }
    }
    
    // PUT /assets/{id} - Update asset
    elseif ($method === 'PUT' && preg_match('#^/assets/(\d+)$#', $path, $matches)) {
        $assetId = $matches[1];
        
        try {
            // Only admin, ict can update assets
            if (!in_array($userRole, [ROLE_ADMIN, ROLE_ICT])) {
                respond(['error' => 'Permission denied'], 403);
            }
            
            $input = getInput();
            
            // Get current asset
            $stmt = $db->prepare("SELECT * FROM assets WHERE id = ? AND institution_id = ?");
            $stmt->execute([$assetId, $institutionId]);
            $oldAsset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$oldAsset) {
                respond(['error' => 'Asset not found'], 404);
            }
            
            $db->beginTransaction();
            
            // Build update query
            $updateFields = [];
            $updateValues = [];
            
            $allowedFields = ['name', 'description', 'serial_number', 'status', 'condition', 'location_id'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $input[$field];
                }
            }
            
            if (empty($updateFields)) {
                $db->rollBack();
                respond(['error' => 'No fields to update'], 400);
            }
            
            $updateFields[] = "updated_at = NOW()";
            $updateValues[] = $assetId;
            $updateValues[] = $institutionId;
            
            $sql = "UPDATE assets SET " . implode(', ', $updateFields) . " WHERE id = ? AND institution_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($updateValues);
            
            // Create audit log
            logAudit($db, $userId, 'assets', $assetId, 'UPDATE', 
                ['status' => $oldAsset['status']], 
                ['status' => $input['status'] ?? $oldAsset['status']]
            );
            
            $db->commit();
            
            // Fetch updated asset (including location)
            $getStmt = $db->prepare("
                SELECT 
                    a.*,
                    at.name as category,
                    l.name as location
                FROM assets a
                LEFT JOIN asset_types at ON a.asset_type_id = at.id
                LEFT JOIN locations l ON a.location_id = l.id
                WHERE a.id = ?
            ");
            $getStmt->execute([$assetId]);
            $updatedAsset = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            respond([
                'success' => true,
                'message' => 'Asset updated successfully',
                'asset' => $updatedAsset
            ], 200);
            
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error updating asset: " . $e->getMessage());
            respond(['error' => 'Failed to update asset'], 500);
        }
    }
    
    else {
        respond(['error' => 'Invalid route or method for assets'], 404);
    }
}

/**
 * Generate a unique serial number for an asset
 * Format: SN-{YEAR}-{INSTITUTION_CODE}-{SEQUENCE}
 * Example: SN-2026-NUNI-00001
 */
function generateSerialNumber($db, $institutionId, $assetCode) {
    try {
        // Get institution code
        $instStmt = $db->prepare("SELECT code FROM institutions WHERE id = ?");
        $instStmt->execute([$institutionId]);
        $institution = $instStmt->fetch(PDO::FETCH_ASSOC);
        $instCode = $institution['code'] ?? 'INST';
        
        // Get current year
        $year = date('Y');
        
        // Get the count of assets for this institution to generate sequence
        $countStmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM assets 
            WHERE institution_id = ? 
            AND EXTRACT(YEAR FROM created_at) = ?
        ");
        $countStmt->execute([$institutionId, $year]);
        $result = $countStmt->fetch(PDO::FETCH_ASSOC);
        $sequence = ($result['count'] ?? 0) + 1;
        
        // Format: SN-2026-NUNI-00001
        $serialNumber = sprintf("SN-%s-%s-%05d", $year, $instCode, $sequence);
        
        // Check if this serial number already exists (very unlikely but just to be safe)
        $checkStmt = $db->prepare("
            SELECT id FROM assets 
            WHERE serial_number = ? AND institution_id = ?
        ");
        $checkStmt->execute([$serialNumber, $institutionId]);
        
        // If it exists, add a timestamp suffix
        if ($checkStmt->fetch()) {
            $serialNumber .= '-' . time();
        }
        
        return $serialNumber;
        
    } catch (PDOException $e) {
        error_log("Error generating serial number: " . $e->getMessage());
        // Fallback to a simple timestamp-based serial number
        return 'SN-' . date('YmdHis') . '-' . rand(1000, 9999);
    }
}
?>