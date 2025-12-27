<?php
require 'config.php';

$stmt = $db->prepare("SELECT password_hash FROM users WHERE email = ?");
$stmt->execute(['admin@nuni.ac.ke']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

var_dump($row['password_hash']);
var_dump(password_verify('password123', $row['password_hash']));
