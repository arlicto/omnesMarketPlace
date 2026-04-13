<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["buyer", "admin"]);

$user_id = (int)($_SESSION["user_id"] ?? 0);

if ($user_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid session"]);
    exit;
}

$sql = "UPDATE users SET offer_clause_accepted=1 WHERE id=$user_id";
if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Clause accepted"]);
} else {
    echo json_encode(["success" => false, "message" => "Could not record acceptance"]);
}
?>
