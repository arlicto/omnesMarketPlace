<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["admin"]);

$user_id = $_POST["user_id"] ?? "";
if ($user_id === "" || !is_numeric($user_id)) {
    echo json_encode(["success" => false, "message" => "Invalid user_id"]);
    exit;
}

$user_id = (int)$user_id;
$session_uid = (int)($_SESSION["user_id"] ?? 0);

if ($user_id === $session_uid) {
    echo json_encode(["success" => false, "message" => "You cannot delete your own account"]);
    exit;
}

$check = $conn->query("SELECT id, role FROM users WHERE id=$user_id LIMIT 1");
if (!$check || $check->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$u = $check->fetch_assoc();
if (($u["role"] ?? "") !== "seller") {
    echo json_encode(["success" => false, "message" => "Only seller users can be removed here"]);
    exit;
}

if ($conn->query("DELETE FROM users WHERE id=$user_id")) {
    if ($conn->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "Seller removed"]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Remove failed"]);
}
?>
