<?php
/**
 * MIAMS User Management API
 * Handles CRUD operations for users (Admin only)
 */
// NO WHITESPACE BEFORE THIS LINE!

// Include CORS handler FIRST
require __DIR__ . '/cors.php';

// Disable display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Include required files
require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

/**
 * Verify JWT token and check admin role
 */
function verifyAdminToken($db) {
    // Get authorization header using helper function
    $authHeader = getAuthorizationHeader();
    
    error_log("=== Admin Token Verification ===");
    error_log("Auth header found: " . ($authHeader ? 'YES' : 'NO'));
    
    if (!$authHeader) {
        error_log("No authorization header found");
        respond(['success' => false, 'error' => 'No authorization header provided'], 401);
    }

    error_log("Auth header (first 30 chars): " . substr($authHeader, 0, 30));
    
    // Extract token
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    } else {
        $token = $authHeader;
    }
    
    error_log("Token extracted (first 30 chars): " . substr($token, 0, 30));
    
    try {
        // Use the verifyToken function from helpers.php
        $tokenData = verifyToken($token);
        error_log("Token verified, user_id: " . $tokenData['user_id']);
    } catch (Exception $e) {
        error_log("Token verification failed: " . $e->getMessage());
        respond(['success' => false, 'error' => 'Invalid token: ' . $e->getMessage()], 401);
    }

    // Check if user has admin role
    $stmt = $db->prepare("
        SELECT u.*, r.name as role_name, u.institution_id
        FROM users u
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE u.id = :user_id AND r.name IN ('admin', 'super_admin')
        LIMIT 1
    ");
    $stmt->execute(['user_id' => $tokenData['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        error_log("User not found or not admin");
        respond(['success' => false, 'error' => 'Access denied. Admin privileges required.'], 403);
    }

    error_log("Admin verified: " . $user['username']);
    return $user;
}

/**
 * Log audit trail
 */
function logAuditTrail($db, $institutionId, $userId, $entityType, $entityId, $action, $oldValues = null, $newValues = null) {
    try {
        $stmt = $db->prepare("
            INSERT INTO audit_logs (institution_id, user_id, entity_type, entity_id, action, old_values, new_values, details)
            VALUES (:institution_id, :user_id, :entity_type, :entity_id, :action, :old_values, :new_values, :details)
        ");
        
        $stmt->execute([
            'institution_id' => $institutionId,
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'details' => json_encode(['source' => 'user_management'])
        ]);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

try {
    $action = $_GET['action'] ?? '';
    error_log("=== User Management API Called ===");
    error_log("Action: $action");
    error_log("Method: " . $_SERVER['REQUEST_METHOD']);
    
    // PUBLIC ACTIONS - No authentication required
    if ($action === 'get_roles') {
        error_log("Public action: get_roles");
        $stmt = $db->query("SELECT id, name, description FROM roles ORDER BY name");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        respond([
            'success' => true,
            'roles' => $roles
        ]);
    }
    
    if ($action === 'get_departments') {
        error_log("Public action: get_departments");
        // Get all departments (or limit to first 50 for testing)
        $stmt = $db->query("SELECT id, name, code FROM departments ORDER BY name LIMIT 50");
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        respond([
            'success' => true,
            'departments' => $departments
        ]);
    }
    
    // PROTECTED ACTIONS - Authentication required
    error_log("Verifying admin token...");
    $currentUser = verifyAdminToken($db);
    error_log("Authenticated user: " . $currentUser['id']);

    switch ($action) {
        
        /**
         * List all users for the institution
         */
        case 'list':
            $stmt = $db->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.first_name,
                    u.last_name,
                    u.is_active,
                    u.last_login,
                    u.created_at,
                    ur.role_id,
                    r.name as role_name,
                    r.description as role_description
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.institution_id = :institution_id
                ORDER BY u.created_at DESC
            ");
            
            $stmt->execute(['institution_id' => $currentUser['institution_id']]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            respond([
                'success' => true,
                'users' => $users
            ]);
            break;

        /**
         * Create new user
         */
        case 'create':
            $input = getInput();
            
            error_log('Create user attempt: ' . json_encode($input));
            
            // Validate required fields
            $required = ['username', 'email', 'first_name', 'last_name', 'password', 'role_id'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    respond(['success' => false, 'message' => "Field '$field' is required"], 400);
                }
            }

            // Check if username already exists in this institution
            $stmt = $db->prepare("
                SELECT id FROM users 
                WHERE institution_id = :institution_id AND username = :username
            ");
            $stmt->execute([
                'institution_id' => $currentUser['institution_id'],
                'username' => $input['username']
            ]);
            
            if ($stmt->rowCount() > 0) {
                respond(['success' => false, 'message' => 'Username already exists in this institution'], 400);
            }

            // Hash password
            $passwordHash = hashPassword($input['password']);

            // Begin transaction
            $db->beginTransaction();

            // Insert user
            $stmt = $db->prepare("
                INSERT INTO users (
                    institution_id, username, email, password_hash, 
                    first_name, last_name, is_active
                ) VALUES (
                    :institution_id, :username, :email, :password_hash,
                    :first_name, :last_name, :is_active
                )
                RETURNING id
            ");

            $stmt->execute([
                'institution_id' => $currentUser['institution_id'],
                'username' => $input['username'],
                'email' => $input['email'],
                'password_hash' => $passwordHash,
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'is_active' => $input['is_active'] ?? true
            ]);

            $newUserId = $stmt->fetchColumn();

            // Assign role
            $stmt = $db->prepare("
                INSERT INTO user_roles (user_id, role_id, institution_id)
                VALUES (:user_id, :role_id, :institution_id)
            ");

            $stmt->execute([
                'user_id' => $newUserId,
                'role_id' => $input['role_id'],
                'institution_id' => $currentUser['institution_id']
            ]);

            // Log audit
            logAuditTrail(
                $db,
                $currentUser['institution_id'],
                $currentUser['id'],
                'users',
                $newUserId,
                'CREATE',
                null,
                [
                    'username' => $input['username'],
                    'email' => $input['email'],
                    'role_id' => $input['role_id']
                ]
            );

            $db->commit();

            respond([
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => $newUserId
            ], 201);
            break;

        /**
         * Update user
         */
        case 'update':
            $input = getInput();
            
            error_log("Update user input: " . json_encode($input));
            
            if (empty($input['id'])) {
                respond(['success' => false, 'message' => 'User ID is required'], 400);
            }

            // Get old values for audit
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND institution_id = :institution_id");
            $stmt->execute([
                'id' => $input['id'],
                'institution_id' => $currentUser['institution_id']
            ]);
            $oldUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldUser) {
                respond(['success' => false, 'message' => 'User not found'], 404);
            }

            // Begin transaction
            $db->beginTransaction();

            try {
                // Build update query
                $updateFields = [];
                $params = ['id' => $input['id'], 'institution_id' => $currentUser['institution_id']];

                if (!empty($input['email'])) {
                    $updateFields[] = "email = :email";
                    $params['email'] = $input['email'];
                }

                if (!empty($input['first_name'])) {
                    $updateFields[] = "first_name = :first_name";
                    $params['first_name'] = $input['first_name'];
                }

                if (!empty($input['last_name'])) {
                    $updateFields[] = "last_name = :last_name";
                    $params['last_name'] = $input['last_name'];
                }

                if (isset($input['is_active'])) {
                    $updateFields[] = "is_active = :is_active";
                    $params['is_active'] = $input['is_active'] ? 'true' : 'false';
                }

                if (!empty($input['password'])) {
                    $updateFields[] = "password_hash = :password_hash";
                    $params['password_hash'] = hashPassword($input['password']);
                }

                $updateFields[] = "updated_at = NOW()";

                // Update user
                $sql = "UPDATE users SET " . implode(', ', $updateFields) . 
                       " WHERE id = :id AND institution_id = :institution_id";
                
                error_log("Update SQL: " . $sql);
                error_log("Update params: " . json_encode($params));
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);

                // Update role if changed
                if (!empty($input['role_id'])) {
                    // Check if user_roles entry exists
                    $stmt = $db->prepare("
                        SELECT id FROM user_roles 
                        WHERE user_id = :user_id AND institution_id = :institution_id
                    ");
                    $stmt->execute([
                        'user_id' => $input['id'],
                        'institution_id' => $currentUser['institution_id']
                    ]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Update existing role
                        $stmt = $db->prepare("
                            UPDATE user_roles 
                            SET role_id = :role_id 
                            WHERE user_id = :user_id AND institution_id = :institution_id
                        ");
                    } else {
                        // Insert new role
                        $stmt = $db->prepare("
                            INSERT INTO user_roles (user_id, role_id, institution_id)
                            VALUES (:user_id, :role_id, :institution_id)
                        ");
                    }
                    
                    $stmt->execute([
                        'role_id' => $input['role_id'],
                        'user_id' => $input['id'],
                        'institution_id' => $currentUser['institution_id']
                    ]);
                }

                // Log audit
                logAuditTrail(
                    $db,
                    $currentUser['institution_id'],
                    $currentUser['id'],
                    'users',
                    $input['id'],
                    'UPDATE',
                    [
                        'email' => $oldUser['email'],
                        'first_name' => $oldUser['first_name'],
                        'last_name' => $oldUser['last_name'],
                        'is_active' => $oldUser['is_active']
                    ],
                    [
                        'email' => $input['email'] ?? $oldUser['email'],
                        'first_name' => $input['first_name'] ?? $oldUser['first_name'],
                        'last_name' => $input['last_name'] ?? $oldUser['last_name'],
                        'is_active' => $input['is_active'] ?? $oldUser['is_active']
                    ]
                );

                $db->commit();

                respond([
                    'success' => true,
                    'message' => 'User updated successfully'
                ]);
            } catch (Exception $e) {
                $db->rollBack();
                error_log("Update user error: " . $e->getMessage());
                throw $e;
            }
            break;

        /**
         * Delete user
         */
        case 'delete':
            $input = getInput();
            
            if (empty($input['id'])) {
                respond(['success' => false, 'message' => 'User ID is required'], 400);
            }

            // Prevent self-deletion
            if ($input['id'] == $currentUser['id']) {
                respond(['success' => false, 'message' => 'You cannot delete your own account'], 400);
            }

            // Get user info before deletion
            $stmt = $db->prepare("
                SELECT * FROM users 
                WHERE id = :id AND institution_id = :institution_id
            ");
            $stmt->execute([
                'id' => $input['id'],
                'institution_id' => $currentUser['institution_id']
            ]);
            $userToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userToDelete) {
                respond(['success' => false, 'message' => 'User not found'], 404);
            }

            // Begin transaction
            $db->beginTransaction();

            // Delete user (cascade will delete user_roles)
            $stmt = $db->prepare("
                DELETE FROM users 
                WHERE id = :id AND institution_id = :institution_id
            ");
            $stmt->execute([
                'id' => $input['id'],
                'institution_id' => $currentUser['institution_id']
            ]);

            // Log audit
            logAuditTrail(
                $db,
                $currentUser['institution_id'],
                $currentUser['id'],
                'users',
                $input['id'],
                'DELETE',
                [
                    'username' => $userToDelete['username'],
                    'email' => $userToDelete['email']
                ],
                null
            );

            $db->commit();

            respond([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
            break;

        /**
         * Toggle user active status
         */
        case 'toggle_status':
            $input = getInput();
            
            error_log("Toggle status input: " . json_encode($input));
            
            if (empty($input['id'])) {
                respond(['success' => false, 'message' => 'User ID is required'], 400);
            }

            // Prevent deactivating own account
            if ($input['id'] == $currentUser['id']) {
                respond(['success' => false, 'message' => 'You cannot deactivate your own account'], 400);
            }

            // Get current status
            $stmt = $db->prepare("
                SELECT is_active FROM users 
                WHERE id = :id AND institution_id = :institution_id
            ");
            $stmt->execute([
                'id' => $input['id'],
                'institution_id' => $currentUser['institution_id']
            ]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                respond(['success' => false, 'message' => 'User not found'], 404);
            }

            // Determine new status - handle both boolean and string values
            $newStatus = null;
            if (isset($input['is_active'])) {
                if (is_bool($input['is_active'])) {
                    $newStatus = $input['is_active'];
                } else if (is_string($input['is_active'])) {
                    $newStatus = ($input['is_active'] === 'true' || $input['is_active'] === '1');
                } else {
                    $newStatus = (bool)$input['is_active'];
                }
            } else {
                // Toggle current status if not specified
                $newStatus = !$user['is_active'];
            }
            
            error_log("Current status: " . ($user['is_active'] ? 'true' : 'false'));
            error_log("New status: " . ($newStatus ? 'true' : 'false'));

            // Update status - use boolean for PostgreSQL
            $stmt = $db->prepare("
                UPDATE users 
                SET is_active = :is_active, updated_at = NOW()
                WHERE id = :id AND institution_id = :institution_id
            ");
            
            $result = $stmt->execute([
                'is_active' => $newStatus ? 'true' : 'false',
                'id' => $input['id'],
                'institution_id' => $currentUser['institution_id']
            ]);
            
            error_log("Update result: " . ($result ? 'success' : 'failed'));
            error_log("Rows affected: " . $stmt->rowCount());

            // Log audit
            logAuditTrail(
                $db,
                $currentUser['institution_id'],
                $currentUser['id'],
                'users',
                $input['id'],
                'UPDATE',
                ['is_active' => $user['is_active']],
                ['is_active' => $newStatus]
            );

            respond([
                'success' => true,
                'message' => 'User status updated successfully'
            ]);
            break;

        default:
            respond(['success' => false, 'message' => 'Invalid action: ' . $action], 400);
    }

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Database error in users.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    error_log("SQL State: " . ($e->getCode() ? $e->getCode() : 'N/A'));
    respond([
        'success' => false, 
        'message' => 'Database error occurred',
        'error' => $e->getMessage(), // Include error in response for debugging
        'code' => $e->getCode()
    ], 500);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("General error in users.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    respond([
        'success' => false, 
        'message' => 'Server error occurred',
        'error' => $e->getMessage() // Include error in response for debugging
    ], 500);
}
?>