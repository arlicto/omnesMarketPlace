<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();

$user_id = (int)($_SESSION["user_id"] ?? 0);

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$address = trim($_POST["address"] ?? "");
$payment_brand = trim($_POST["payment_brand"] ?? "");
$payment_last4 = trim($_POST["payment_last4"] ?? "");

if ($name === "" || $email === "") {
    echo json_encode(["success" => false, "message" => "Name and email are required"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

$emailEsc = $conn->real_escape_string($email);
$dup = $conn->query("SELECT id FROM users WHERE email='$emailEsc' AND id<>$user_id LIMIT 1");
if ($dup && $dup->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email already exists"]);
    exit;
}

if ($payment_last4 !== "") {
    if (!preg_match('/^[0-9]{4}$/', $payment_last4)) {
        echo json_encode(["success" => false, "message" => "payment_last4 must be exactly 4 digits"]);
        exit;
    }
}

$nm = $conn->real_escape_string($name);
$em = $conn->real_escape_string($email);
$addr = $conn->real_escape_string($address);
$brand = $conn->real_escape_string($payment_brand);
$last4 = $conn->real_escape_string($payment_last4);

$sql = "UPDATE users SET name='$nm', email='$em', address='$addr', payment_brand='$brand', payment_last4='$last4' WHERE id=$user_id";
if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Account updated"]);
} else {
    echo json_encode(["success" => false, "message" => "Could not update account"]);
}
?>
