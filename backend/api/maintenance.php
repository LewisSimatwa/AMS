<?php
require __DIR__ . '/config.php';  // DB connection
require __DIR__ . '/helpers.php'; // getInput(), respond()

$method = $_SERVER['REQUEST_METHOD'];
$input = getInput();
$institutionId = $_SERVER['HTTP_X_INSTITUTION_ID'] ?? null;

if (!$institutionId) {
    respond(['error' => 'Missing institution ID'], 400);
}

// GET: list maintenance records
if ($method === 'GET') {
    $sql = "
        SELECT mr.*, a.name AS asset_name, u.first_name || ' ' || u.last_name AS assigned_to_name
        FROM maintenance_records mr
        JOIN assets a ON mr.asset_id = a.id
        LEFT JOIN users u ON mr.assigned_to = u.id
        WHERE mr.institution_id = $1
        ORDER BY mr.created_at DESC
    ";
    $res = pg_query_params($db, $sql, [$institutionId]);
    $records = pg_fetch_all($res) ?: [];
    respond($records);
}

// POST: create new maintenance
if ($method === 'POST') {
    $required = ['asset_id', 'maintenance_type', 'assigned_to', 'start_date'];
    foreach ($required as $field) {
        if (empty($input[$field])) respond(['error' => "$field is required"], 400);
    }

    $sql = "
        INSERT INTO maintenance_records
        (institution_id, asset_id, maintenance_type, description, assigned_to, start_date)
        VALUES ($1,$2,$3,$4,$5,$6)
        RETURNING *
    ";
    $params = [
        $institutionId,
        $input['asset_id'],
        $input['maintenance_type'],
        $input['description'] ?? null,
        $input['assigned_to'],
        $input['start_date']
    ];
    $res = pg_query_params($db, $sql, $params);
    $newRecord = pg_fetch_assoc($res);

    // Log in audit_logs
    $auditSql = "
        INSERT INTO audit_logs (institution_id, user_id, entity_type, entity_id, action, details)
        VALUES ($1,$2,'maintenance_records',$3,'CREATE',$4)
    ";
    pg_query_params($db, $auditSql, [
        $institutionId,
        $_SESSION['user_id'] ?? null,
        $newRecord['id'],
        json_encode(['asset_id' => $newRecord['asset_id'], 'type' => $newRecord['maintenance_type']])
    ]);

    respond($newRecord);
}

// PUT: update existing maintenance
if ($method === 'PUT') {
    $id = $input['id'] ?? null;
    if (!$id) respond(['error' => 'Maintenance ID required'], 400);

    $fields = [];
    $params = [];
    $i = 1;
    foreach (['status', 'description', 'cost', 'end_date'] as $field) {
        if (isset($input[$field])) {
            $fields[] = "$field = \$$i";
            $params[] = $input[$field];
            $i++;
        }
    }

    if (empty($fields)) respond(['error' => 'No fields to update'], 400);
    $params[] = $id;
    $sql = "UPDATE maintenance_records SET " . implode(',', $fields) . " , updated_at = now() WHERE id = \$" . $i . " RETURNING *";
    $res = pg_query_params($db, $sql, $params);
    $updated = pg_fetch_assoc($res);

    // Audit log
    $auditSql = "
        INSERT INTO audit_logs (institution_id, user_id, entity_type, entity_id, action, old_values, new_values)
        VALUES ($1,$2,'maintenance_records',$3,'UPDATE',$4,$5)
    ";
    pg_query_params($db, $auditSql, [
        $institutionId,
        $_SESSION['user_id'] ?? null,
        $id,
        json_encode([]),  // optionally fetch old values
        json_encode($input)
    ]);

    respond($updated);
}

// DELETE not implemented
respond(['error' => 'Method not allowed'], 405);
