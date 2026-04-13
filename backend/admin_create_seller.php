<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["admin"]);

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($name === "" || $email === "" || $username === "" || $password === "") {
    echo json_encode(["success" => false, "message" => "Please fill all fields"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    echo json_encode(["success" => false, "message" => "Invalid username. Use 3-20 letters, numbers, underscore"]);
    exit;
}

$e = $conn->real_escape_string($email);
$u = $conn->real_escape_string($username);

$checkEmail = $conn->query("SELECT id FROM users WHERE email='$e' LIMIT 1");
if ($checkEmail && $checkEmail->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email already exists"]);
    exit;
}

$checkUname = $conn->query("SELECT id FROM users WHERE username='$u' LIMIT 1");
if ($checkUname && $checkUname->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username already exists"]);
    exit;
}

$n = $conn->real_escape_string($name);
$pass_hash = password_hash($password, PASSWORD_DEFAULT);
$ph = $conn->real_escape_string($pass_hash);

$sql = "INSERT INTO users (name, email, password, role, offer_clause_accepted, username) VALUES ('$n', '$e', '$ph', 'seller', 0, '$u')";
if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Seller created"]);
} else {
    echo json_encode(["success" => false, "message" => "Could not create seller"]);
}
?>
