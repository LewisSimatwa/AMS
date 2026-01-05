<?php
session_start();
header("Content-Type: application/json");

echo json_encode([
    "session_data" => $_SESSION,
    "logged_in" => isset($_SESSION['user_id']),
    "user_id" => $_SESSION['user_id'] ?? null,
    "institution_id" => $_SESSION['institution_id'] ?? null
]);