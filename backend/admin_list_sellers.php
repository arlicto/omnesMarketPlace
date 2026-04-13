<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["admin"]);

$res = $conn->query("SELECT id, name, email, username, profile_photo, background_image FROM users WHERE role='seller' ORDER BY id DESC");
if (!$res) {
    echo json_encode(["success" => false, "message" => "Could not load sellers"]);
    exit;
}

$sellers = [];
while ($row = $res->fetch_assoc()) {
    $sellers[] = $row;
}

echo json_encode(["success" => true, "sellers" => $sellers]);
?>
