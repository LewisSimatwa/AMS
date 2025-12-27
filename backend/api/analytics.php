<?php

function handleAnalytics($db, $method, $path) {
    $institutionId = $_GET['institution_id'];
    
    // GET /analytics/risk-scores
    if ($method === 'GET' && $path === '/analytics/risk-scores') {
        try {
            $sql = "SELECT a.id, a.asset_code, a.name, 
                           COALESCE(ars.risk_score, 0) as risk_score,
                           COALESCE(ars.risk_level, 'LOW') as risk_level,
                           ars.predicted_failure_date
                    FROM assets a
                    LEFT JOIN asset_risk_scores ars ON a.id = ars.asset_id
                    WHERE a.institution_id = ?
                    ORDER BY COALESCE(ars.risk_score, 0) DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$institutionId]);
            $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            respond(['scores' => $scores]);
        } catch (Exception $e) {
            respond(['error' => $e->getMessage()], 500);
        }
    }
}

?>