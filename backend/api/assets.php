<?php

function handleAssets($db, $method, $path) {
    $institution_id = $_GET['institution_id'];
    $user_id = $_GET['user_id'];
    
    // GET /assets
    if ($method === 'GET' && $path === '/assets') {
        try {
            $status = $_GET['status'] ?? null;
            
            $sql = "SELECT * FROM assets WHERE institution_id = ?";
            if ($status) {
                $sql .= " AND status = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$institution_id, $status]);
            } else {
                $stmt = $db->prepare($sql);
                $stmt->execute([$institution_id]);
            }
            
            respond(['assets' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            respond(['error' => $e->getMessage()], 500);
        }
    }
    
    // GET /assets/{id}
    elseif ($method === 'GET' && preg_match('/^\/assets\/(\d+)$/', $path, $m)) {
        try {
            $asset_id = $m[1];
            
            $sql = "SELECT * FROM assets WHERE id = ? AND institution_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$asset_id, $institution_id]);
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$asset) respond(['error' => 'Asset not found'], 404);
            
            // Get history
            $sql = "SELECT * FROM transactions WHERE asset_id = ? ORDER BY performed_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$asset_id]);
            $asset['history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            respond(['asset' => $asset]);
        } catch (Exception $e) {
            respond(['error' => $e->getMessage()], 500);
        }
    }
    
    // POST /assets (Create)
    elseif ($method === 'POST' && $path === '/assets') {
        try {
            $input = getInput();
            if (!$input['name']) respond(['error' => 'Name required'], 400);
            
            // Generate asset code
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM assets WHERE institution_id = ?");
            $stmt->execute([$institution_id]);
            $count = $stmt->fetch()['cnt'] + 1;
            $asset_code = "ASSET-" . str_pad($count, 5, '0', STR_PAD_LEFT);
            
            $sql = "INSERT INTO assets (institution_id, asset_code, name, serial_number, 
                    acquisition_date, acquisition_cost, status, condition)
                    VALUES (?, ?, ?, ?, ?, ?, 'available', 'good')
                    RETURNING *";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $institution_id,
                $asset_code,
                $input['name'],
                $input['serial_number'] ?? null,
                $input['acquisition_date'] ?? null,
                $input['acquisition_cost'] ?? null
            ]);
            
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
            logAudit($db, $user_id, 'assets', $asset['id'], 'CREATE', null, $asset);
            
            respond(['asset' => $asset], 201);
        } catch (Exception $e) {
            respond(['error' => $e->getMessage()], 500);
        }
    }
}

?>