<?php
// Load .env
$env = parse_ini_file('.env');

// Database
$db = new PDO(
    "pgsql:host={$env['DB_HOST']};dbname={$env['DB_NAME']}",
    $env['DB_USER'],
    $env['DB_PASSWORD']
);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// JWT Secret
if (!defined('JWT_SECRET')) define('JWT_SECRET', $env['JWT_SECRET']);
if (!defined('INSTITUTION_ID')) define('INSTITUTION_ID', $_GET['institution_id'] ?? $_POST['institution_id'] ?? 1);

// Roles
if (!defined('ROLE_ADMIN')) define('ROLE_ADMIN', 'admin');
if (!defined('ROLE_SECURITY')) define('ROLE_SECURITY', 'security');
if (!defined('ROLE_ICT')) define('ROLE_ICT', 'ict');
if (!defined('ROLE_MANAGER')) define('ROLE_MANAGER', 'manager');
if (!defined('ROLE_AUDITOR')) define('ROLE_AUDITOR', 'auditor');
if (!defined('ROLE_STAFF')) define('ROLE_STAFF', 'staff');
?>
