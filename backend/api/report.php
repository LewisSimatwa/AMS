<?php
function handleReports($db, $method, $path) {
    header('Content-Type: application/json');

    $institutionId = $_GET['institution_id'] ?? null;

    if (!$institutionId) {
        http_response_code(400);
        echo json_encode(['error' => 'institution_id is required']);
        exit;
    }

    try {

        if ($method === 'GET' && $path === '/reports/assets-by-status') {

            $stmt = $db->prepare(
                "SELECT status, COUNT(*) AS count
                 FROM assets
                 WHERE institution_id = ?
                 GROUP BY status"
            );
            $stmt->execute([$institutionId]);

            echo json_encode([
                'report' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            exit;
        }

        if ($method === 'GET' && $path === '/reports/maintenance-summary') {

            $stmt = $db->prepare(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(cost), 0) AS total_cost
                 FROM maintenance_records
                 WHERE institution_id = ?"
            );
            $stmt->execute([$institutionId]);

            echo json_encode([
                'report' => $stmt->fetch(PDO::FETCH_ASSOC)
            ]);
            exit;
        }

        if ($method === 'GET' && $path === '/reports/generate') {
            http_response_code(501);
            echo json_encode(['error' => 'PDF generation not implemented yet']);
            exit;
        }

        http_response_code(404);
        echo json_encode(['error' => 'Reports route not found']);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Database error',
            'message' => $e->getMessage()
        ]);
        exit;
    }
}
