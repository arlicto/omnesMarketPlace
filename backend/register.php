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
$check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$check_stmt) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check = $check_stmt->get_result();
if ($check->num_rows > 0) {
    $check_stmt->close();
    echo json_encode(["success" => false, "message" => "Email already exists"]);
    exit;
}
$check_stmt->close();

// this stores password hash (not plain text)
$pass_hash = password_hash($password, PASSWORD_DEFAULT);

// this inserts new user
$stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Registration failed"]);
    exit;
}
$stmt->bind_param("ssss", $name, $email, $pass_hash, $role);
if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Registration successful"]);
} else {
    echo json_encode(["success" => false, "message" => "Registration failed"]);
}
$stmt->close();
?>