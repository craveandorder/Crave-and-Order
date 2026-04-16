<?php
$db_host = 'sql100.infinityfree.com';
$db_user = 'if0_41666569';
$db_pass = 'mahi11aastha12';
$db_name = 'if0_41666569_crave_and_order';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

if (session_status() != PHP_SESSION_ACTIVE) {
    session_start();
}
?>
