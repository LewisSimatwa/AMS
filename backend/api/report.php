<?php

function handleReports($method, $path) {
    global $db;
    
    $institutionId = $_GET['institution_id'];
    
    // GET /reports/assets-by-status
    if ($method === 'GET' && $path === '/reports/assets-by-status') {
        $sql = "SELECT status, COUNT(*) as count FROM assets WHERE institution_id = ? GROUP BY status";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$institutionId]);
        
        respond(['report' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    
    // GET /reports/maintenance-summary
    elseif ($method === 'GET' && $path === '/reports/maintenance-summary') {
        $sql = "SELECT COUNT(*) as total, SUM(cost) as total_cost FROM maintenance_records WHERE institution_id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$institutionId]);
        
        respond(['report' => $stmt->fetch(PDO::FETCH_ASSOC)]);
    }
}

?>