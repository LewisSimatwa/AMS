<?php

function handleTransactions($method, $path) {
    global $db;
    
    $institutionId = $_GET['institution_id'];
    $userId = $_GET['user_id'];
    $role = $_GET['role'];
    
    // POST /transactions/checkout-request (Staff/Manager)
    if ($method === 'POST' && $path === '/transactions/checkout-request') {
        $input = getInput();
        
        $sql = "INSERT INTO transactions (institution_id, asset_id, transaction_type, to_user_id, remarks, performed_by, performed_at)
                VALUES (?, ?, 'checkout_request', ?, ?, ?, NOW())
                RETURNING *";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $institutionId,
            $input['asset_id'],
            $userId,
            $input['remarks'] ?? 'Checkout request',
            $userId
        ]);
        
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        logAudit($db, $userId, 'transactions', $transaction['id'], 'CHECKOUT_REQUEST', null, $transaction);
        
        respond(['transaction' => $transaction], 201);
    }
    
    // GET /transactions/pending (Security only)
    elseif ($method === 'GET' && $path === '/transactions/pending') {
        checkRole($role, ['security', 'admin']);
        
        $sql = "SELECT t.*, a.asset_code, a.name, u.username 
                FROM transactions t
                JOIN assets a ON t.asset_id = a.id
                JOIN users u ON t.to_user_id = u.id
                WHERE t.transaction_type = 'checkout_request' AND a.institution_id = ?
                ORDER BY t.performed_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$institutionId]);
        
        respond(['transactions' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    
    // POST /transactions/{id}/approve (Security only)
    elseif ($method === 'POST' && preg_match('/^\/transactions\/(\d+)\/approve$/', $path, $m)) {
        checkRole($role, ['security', 'admin']);
        
        $transactionId = $m[1];
        
        // Update transaction
        $stmt = $db->prepare("UPDATE transactions SET transaction_type = 'checked_out' WHERE id = ? RETURNING *");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update asset status
        $stmt = $db->prepare("UPDATE assets SET status = 'on_loan', current_holder_id = ? WHERE id = ?");
        $stmt->execute([$transaction['to_user_id'], $transaction['asset_id']]);
        
        logAudit($db, $userId, 'transactions', $transactionId, 'APPROVED', null, $transaction);
        
        respond(['transaction' => $transaction]);
    }
    
    // POST /transactions/{id}/checkin (Security only)
    elseif ($method === 'POST' && preg_match('/^\/transactions\/(\d+)\/checkin$/', $path, $m)) {
        checkRole($role, ['security', 'admin']);
        
        $transactionId = $m[1];
        $input = getInput();
        
        // Get transaction
        $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Create checkin transaction
        $sql = "INSERT INTO transactions (institution_id, asset_id, transaction_type, from_user_id, remarks, performed_by, performed_at)
                VALUES (?, ?, 'checked_in', ?, ?, ?, NOW())
                RETURNING *";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $institutionId,
            $transaction['asset_id'],
            $transaction['to_user_id'],
            $input['remarks'] ?? 'Condition: ' . ($input['condition'] ?? 'good'),
            $userId
        ]);
        
        $checkin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update asset status back to available
        $stmt = $db->prepare("UPDATE assets SET status = 'available', condition = ?, current_holder_id = NULL WHERE id = ?");
        $stmt->execute([$input['condition'] ?? 'good', $transaction['asset_id']]);
        
        logAudit($db, $userId, 'transactions', $checkin['id'], 'CHECKED_IN', null, $checkin);
        
        respond(['transaction' => $checkin]);
    }
    
    // POST /transactions/transfer (Transfer between departments)
    elseif ($method === 'POST' && $path === '/transactions/transfer') {
        $input = getInput();
        
        $sql = "INSERT INTO transactions (institution_id, asset_id, transaction_type, from_department_id, to_department_id, remarks, performed_by, performed_at)
                VALUES (?, ?, 'transfer', ?, ?, ?, ?, NOW())
                RETURNING *";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $institutionId,
            $input['asset_id'],
            $input['from_department_id'],
            $input['to_department_id'],
            $input['remarks'] ?? 'Department transfer',
            $userId
        ]);
        
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update asset department
        $stmt = $db->prepare("UPDATE assets SET department_id = ? WHERE id = ?");
        $stmt->execute([$input['to_department_id'], $input['asset_id']]);
        
        logAudit($db, $userId, 'transactions', $transaction['id'], 'TRANSFER', null, $transaction);
        
        respond(['transaction' => $transaction]);
    }
}

?>