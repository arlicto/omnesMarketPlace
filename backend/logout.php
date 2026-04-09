<?php
header("Content-Type: application/json");
include "db.php";

// simple session logout
$_SESSION = [];
session_destroy();

echo json_encode(["success" => true, "message" => "Logged out"]);
?>
