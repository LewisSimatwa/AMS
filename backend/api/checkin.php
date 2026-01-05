<?php
require "/config.php";
require __DIR__ . '/helpers.php';

$data = json_decode(file_get_contents("php://input"), true);

$db->beginTransaction();

$db->prepare("
INSERT INTO transactions
(institution_id, asset_id, transaction_type, from_user_id, performed_by)
VALUES (:inst, :asset, 'check_in', :user, :security)
")->execute([
  ":inst" => $data["institution_id"],
  ":asset" => $data["asset_id"],
  ":user" => $data["user_id"],
  ":security" => $data["security_id"]
]);

$db->prepare("
UPDATE assets
SET status='available', current_holder_id=NULL
WHERE id=:asset
")->execute([
  ":asset" => $data["asset_id"]
]);

$db->commit();

echo json_encode(["message" => "Asset checked in"]);
