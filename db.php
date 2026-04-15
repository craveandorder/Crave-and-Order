<?php
// ============================================
//  Crave & Order - Database Configuration
//  Edit these settings to match your server
// ============================================

$db_host = 'localhost';
$db_user = 'root';       // Change to your MySQL username
$db_pass = '';           // Change to your MySQL password
$db_name = 'crave_order';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("<div style='font-family:Arial;background:#fee;color:#c00;padding:20px;margin:20px;border-radius:8px;'>
        <strong>Database Connection Failed:</strong> " . $conn->connect_error . "<br><br>
        Please import <code>database.sql</code> in phpMyAdmin and update <code>db.php</code> with your credentials.
    </div>");
}

$conn->set_charset("utf8mb4");

// Start session if not started
if (session_status() != PHP_SESSION_ACTIVE) {
    session_start();
}
?>
