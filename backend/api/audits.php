<?php

function handleAudit($db, $method, $path) {
    $institutionId = $_GET['institution_id'];
    
    // GET /audit/logs
    if ($method === 'GET' && $path === '/audit/logs') {
        try {
            $sql = "SELECT al.id, al.action, al.entity_type, al.created_at, 
                           COALESCE(u.email, 'System') as username 
                    FROM audit_logs al
                    LEFT JOIN users u ON al.user_id = u.id
                    WHERE al.institution_id IS NULL OR al.institution_id = ?
                    ORDER BY al.created_at DESC
                    LIMIT 100";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$institutionId]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            respond(['logs' => $logs]);
        } catch (Exception $e) {
            respond(['error' => $e->getMessage()], 500);
        }
    }
}

?>