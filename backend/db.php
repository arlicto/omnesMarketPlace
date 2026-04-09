<?php
// simple database connection file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Use environment variables for DB credentials
$host = getenv("DB_HOST") ?: "localhost";
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");
$db_name = getenv("DB_NAME");

// Validate required environment variables
if ($user === false || $pass === false || $db_name === false) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "message" => "Database configuration error: Missing environment variables (DB_USER, DB_PASS, DB_NAME)"
    ]);
    exit;
}

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