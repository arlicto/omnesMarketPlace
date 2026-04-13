<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["seller", "admin"]);

$user_id = (int)($_SESSION["user_id"] ?? 0);
$username = trim($_POST["username"] ?? "");

if ($username === "") {
    echo json_encode(["success" => false, "message" => "Username is required"]);
    exit;
}

if (strlen($username) < 3 || strlen($username) > 80) {
    echo json_encode(["success" => false, "message" => "Username must be 3-80 characters"]);
    exit;
}

// Basic character allowlist
if (!preg_match('/^[A-Za-z0-9_\.\-]+$/', $username)) {
    echo json_encode(["success" => false, "message" => "Username can only contain letters, numbers, underscore, dot, and dash"]);
    exit;
}

$u = $conn->real_escape_string($username);

// Ensure username is unique (other than this user)
$dup = $conn->query("SELECT id FROM users WHERE username='$u' AND id<>$user_id LIMIT 1");
if ($dup && $dup->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username already taken"]);
    exit;
}

$sql = "UPDATE users SET username='$u' WHERE id=$user_id";
if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Seller profile updated"]);
} else {
    echo json_encode(["success" => false, "message" => "Could not update seller profile"]);
}
?>
