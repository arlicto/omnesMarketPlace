<?php
header("Content-Type: application/json");
include "db.php";

// basic input reading
$name = $_POST["name"] ?? "";
$email = $_POST["email"] ?? "";
$password = $_POST["password"] ?? "";
// security rule: public registration can only create buyer accounts
$role = "buyer";

if ($name == "" || $email == "" || $password == "") {
    echo json_encode(["success" => false, "message" => "Please fill all fields"]);
    exit;
}

// basic email format validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

// this checks if email already exists
$check = $conn->query("SELECT id FROM users WHERE email='$email'");
if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email already exists"]);
    exit;
}

// this stores password hash (not plain text)
$pass_hash = password_hash($password, PASSWORD_DEFAULT);

// this inserts new user
$sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$pass_hash', '$role')";
if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Registration successful"]);
} else {
    echo json_encode(["success" => false, "message" => "Registration failed"]);
}
?>
