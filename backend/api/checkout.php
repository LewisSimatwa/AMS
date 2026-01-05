<?php
require "/config.php";
require __DIR__ . '/helpers.php';

$data = json_decode(file_get_contents("php://input"), true);

$db->beginTransaction();

$db->prepare("
INSERT INTO transactions
(institution_id, asset_id, transaction_type, to_user_id, performed_by)
VALUES (:inst, :asset, 'check_out', :to_user, :security)
")->execute([
  ":inst" => $data["institution_id"],
  ":asset" => $data["asset_id"],
  ":to_user" => $data["user_id"],
  ":security" => $data["security_id"]
]);

$db->prepare("
UPDATE assets
SET status='on_loan', current_holder_id=:user
WHERE id=:asset
")->execute([
  ":user" => $data["user_id"],
  ":asset" => $data["asset_id"]
]);

$db->commit();

echo json_encode(["message" => "Asset checked out"]);
