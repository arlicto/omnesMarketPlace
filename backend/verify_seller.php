<?php
header("Content-Type: application/json");
include "db.php";

$username = trim($_POST["username"] ?? "");
$email = trim($_POST["email"] ?? "");

if ($username === "" || $email === "") {
    echo json_encode(["success" => false, "message" => "username and email are required"]);
    exit;
}

$emailEsc = $conn->real_escape_string($email);
$userEsc = $conn->real_escape_string($username);

$sql = "SELECT id, name, email, username, profile_photo, background_image FROM users WHERE role='seller' AND username='$userEsc' AND email='$emailEsc' LIMIT 1";
$res = $conn->query($sql);
if (!$res || $res->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Seller not found"]);
    exit;
}

$seller = $res->fetch_assoc();
echo json_encode(["success" => true, "seller" => $seller]);
?>
