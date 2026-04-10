<?php
// simple database connection file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Use localhost so mysqli can connect using MySQL's local socket auth (common on Linux).
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "omnes_marketplace";

$conn = new mysqli($host, $user, $pass, $db_name);

if ($conn->connect_error) {
    // Return JSON so the frontend can show a useful message
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
    exit;
}
?>
