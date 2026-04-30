<?php
error_log("=== SYSTEM-CONFIG.PHP CALLED ===");

require __DIR__ . '/../cors.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

// Verify super admin authentication
try {
    $decoded = verifyAuth();
    
    if (!isset($decoded['role']) || $decoded['role'] !== 'super_admin') {
        respond(['error' => 'Access denied. Super admin privileges required.'], 403);
    }
} catch (Exception $e) {
    error_log("Auth error in system-config: " . $e->getMessage());
    respond(['error' => 'Authentication failed'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Fetch current configuration
        error_log("Fetching system configuration...");
        
        // Get asset categories from asset_types table (global categories only)
        try {
            $categoriesStmt = $db->query("
                SELECT 
                    id,
                    name,
                    COALESCE(description, '') as description
                FROM asset_types
                WHERE institution_id IS NULL
                ORDER BY name ASC
            ");
            $assetCategories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($assetCategories) . " global asset categories");
            
            // Ensure description is never null
            foreach ($assetCategories as &$category) {
                if ($category['description'] === null) {
                    $category['description'] = '';
                }
            }
            
        } catch (PDOException $e) {
            error_log("Error fetching asset categories: " . $e->getMessage());
            $assetCategories = [];
        }
        
        // Get global statuses from system_settings
        $statusesStmt = $db->prepare("
            SELECT value 
            FROM system_settings 
            WHERE key = 'global_statuses' 
            AND institution_id = 1
            LIMIT 1
        ");
        $statusesStmt->execute();
        $statusesRow = $statusesStmt->fetch(PDO::FETCH_ASSOC);
        
        $globalStatuses = [];
        if ($statusesRow && $statusesRow['value']) {
            $decoded_statuses = json_decode($statusesRow['value'], true);
            $globalStatuses = $decoded_statuses ?: [];
        }
        
        // Default statuses if none exist
        if (empty($globalStatuses)) {
            $globalStatuses = [
                ['id' => 1, 'name' => 'available', 'description' => 'Asset is available for use', 'color' => '#10b981'],
                ['id' => 2, 'name' => 'on_loan', 'description' => 'Asset is checked out', 'color' => '#3b82f6'],
                ['id' => 3, 'name' => 'maintenance', 'description' => 'Asset is under maintenance', 'color' => '#f59e0b'],
                ['id' => 4, 'name' => 'retired', 'description' => 'Asset is retired', 'color' => '#ef4444']
            ];
        }
        
        // Get CSV import rules
        $csvRulesStmt = $db->prepare("
            SELECT value 
            FROM system_settings 
            WHERE key = 'csv_import_rules' 
            AND institution_id = 1
            LIMIT 1
        ");
        $csvRulesStmt->execute();
        $csvRulesRow = $csvRulesStmt->fetch(PDO::FETCH_ASSOC);
        
        $csvRules = [
            'max_file_size_mb' => 10,
            'allowed_delimiters' => [',', ';', "\t"],
            'required_columns' => ['name', 'asset_code'],
            'date_formats' => ['Y-m-d', 'd/m/Y', 'm/d/Y'],
            'encoding' => 'UTF-8'
        ];
        
        if ($csvRulesRow && $csvRulesRow['value']) {
            $decoded_rules = json_decode($csvRulesRow['value'], true);
            $csvRules = array_merge($csvRules, $decoded_rules ?: []);
        }
        
        // Get password policies
        $passwordPoliciesStmt = $db->prepare("
            SELECT value 
            FROM system_settings 
            WHERE key = 'password_policies' 
            AND institution_id = 1
            LIMIT 1
        ");
        $passwordPoliciesStmt->execute();
        $passwordPoliciesRow = $passwordPoliciesStmt->fetch(PDO::FETCH_ASSOC);
        
        $passwordPolicies = [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special' => true,
            'max_age_days' => 90,
            'prevent_reuse' => 5,
            'lockout_attempts' => 5,
            'lockout_duration_minutes' => 30
        ];
        
        if ($passwordPoliciesRow && $passwordPoliciesRow['value']) {
            $decoded_policies = json_decode($passwordPoliciesRow['value'], true);
            $passwordPolicies = array_merge($passwordPolicies, $decoded_policies ?: []);
        }
        
        error_log("Sending response with " . count($assetCategories) . " categories");
        
        respond([
            'asset_categories' => $assetCategories,
            'global_statuses' => $globalStatuses,
            'csv_rules' => $csvRules,
            'password_policies' => $passwordPolicies
        ]);
        
    } elseif ($method === 'PUT') {
        // Update configuration
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['section']) || !isset($input['data'])) {
            respond(['error' => 'Section and data are required'], 400);
        }
        
        $section = $input['section'];
        $data = $input['data'];
        
        error_log("Updating configuration section: $section");
        error_log("Data: " . json_encode($data));
        
        switch ($section) {
            case 'asset_categories':
                // Update asset_types table
                // First, get existing global categories
                $existingStmt = $db->query("
                    SELECT id FROM asset_types WHERE institution_id IS NULL
                ");
                $existingIds = $existingStmt->fetchAll(PDO::FETCH_COLUMN);
                
                error_log("Existing global category IDs: " . json_encode($existingIds));
                
                // Process each category from input
                foreach ($data as $category) {
                    $categoryId = isset($category['id']) ? (int)$category['id'] : null;
                    $description = isset($category['description']) ? $category['description'] : '';
                    
                    if ($categoryId && in_array($categoryId, $existingIds)) {
                        // Update existing
                        error_log("Updating category ID: $categoryId");
                        $updateStmt = $db->prepare("
                            UPDATE asset_types 
                            SET name = :name, description = :description 
                            WHERE id = :id AND institution_id IS NULL
                        ");
                        $updateStmt->execute([
                            ':name' => $category['name'],
                            ':description' => $description,
                            ':id' => $categoryId
                        ]);
                        
                        // Remove from existing IDs list
                        $existingIds = array_diff($existingIds, [$categoryId]);
                    } else {
                        // Insert new
                        error_log("Inserting new category: " . $category['name']);
                        $insertStmt = $db->prepare("
                            INSERT INTO asset_types (institution_id, name, description) 
                            VALUES (NULL, :name, :description)
                        ");
                        $insertStmt->execute([
                            ':name' => $category['name'],
                            ':description' => $description
                        ]);
                    }
                }
                
                // Delete removed categories
                if (!empty($existingIds)) {
                    error_log("Deleting removed category IDs: " . json_encode($existingIds));
                    $placeholders = implode(',', array_fill(0, count($existingIds), '?'));
                    $deleteStmt = $db->prepare("
                        DELETE FROM asset_types 
                        WHERE id IN ($placeholders) AND institution_id IS NULL
                    ");
                    $deleteStmt->execute($existingIds);
                }
                break;
                
            case 'global_statuses':
            case 'csv_rules':
            case 'password_policies':
                // Store in system_settings as JSON
                $key = $section === 'global_statuses' ? 'global_statuses' : 
                       ($section === 'csv_rules' ? 'csv_import_rules' : 'password_policies');
                
                // Check if setting exists
                $checkStmt = $db->prepare("
                    SELECT id FROM system_settings 
                    WHERE institution_id = 1 AND key = :key
                ");
                $checkStmt->execute([':key' => $key]);
                $exists = $checkStmt->fetch();
                
                if ($exists) {
                    // Update existing
                    $updateStmt = $db->prepare("
                        UPDATE system_settings 
                        SET value = :value 
                        WHERE institution_id = 1 AND key = :key
                    ");
                    $updateStmt->execute([
                        ':value' => json_encode($data),
                        ':key' => $key
                    ]);
                } else {
                    // Insert new
                    $insertStmt = $db->prepare("
                        INSERT INTO system_settings (institution_id, key, value) 
                        VALUES (1, :key, :value)
                    ");
                    $insertStmt->execute([
                        ':key' => $key,
                        ':value' => json_encode($data)
                    ]);
                }
                break;
                
            default:
                respond(['error' => 'Invalid section'], 400);
        }
        
        // Log the configuration change in audit_logs
        $auditStmt = $db->prepare("
            INSERT INTO audit_logs 
            (institution_id, user_id, entity_type, action, new_values, details, created_at) 
            VALUES (1, :user_id, 'system_config', 'UPDATE', :new_values, :details, NOW())
        ");
        
        $auditStmt->execute([
            ':user_id' => $decoded['user_id'],
            ':new_values' => json_encode($data),
            ':details' => json_encode([
                'section' => $section,
                'updated_by' => $decoded['username'] ?? 'super_admin'
            ])
        ]);
        
        respond([
            'success' => true,
            'message' => 'Configuration updated successfully'
        ]);
        
    } else {
        respond(['error' => 'Method not allowed'], 405);
    }
    
} catch (PDOException $e) {
    error_log('Database error in system-config: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    respond(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log('System config error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    respond(['error' => 'Failed to process configuration: ' . $e->getMessage()], 500);
}
?>