<?php
require "/config.php";
require __DIR__ . '/helpers.php';

$data = json_decode(file_get_contents("php://input"), true);

$db->beginTransaction();

$db->prepare("
UPDATE checkout_requests
SET status='approved', approved_by=:admin, decided_at=now()
WHERE id=:id
")->execute([
  ":admin" => $data["admin_id"],
  ":id" => $data["request_id"]
]);

$db->prepare("
UPDATE assets SET status='reserved'
WHERE id=:asset
")->execute([
  ":asset" => $data["asset_id"]
]);

$db->commit();

echo json_encode(["message" => "Approved"]);
