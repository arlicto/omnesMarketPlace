<?php
header("Content-Type: application/json");
include "db.php";

// basic input reading
$email = $_POST["email"] ?? "";
$password = $_POST["password"] ?? "";

if ($email == "" || $password == "") {
    echo json_encode(["success" => false, "message" => "Please enter email and password"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

// this checks user by email
$stmt = $conn->prepare("SELECT id, name, email, role, password FROM users WHERE email = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $stmt->close();
    $ok = false;

    // supports secure hashes and old plain text rows
    if (password_verify($password, $user["password"])) {
        $ok = true;
    } elseif ($password === $user["password"]) {
        $ok = true;
        // optional migration: convert old plain text to hash after login
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("si", $new_hash, $user["id"]);
            $update_stmt->execute();
            $update_stmt->close();
        }
    }

    if ($ok) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["name"];
        $_SESSION["user_role"] = $user["role"];

        unset($user["password"]);
        echo json_encode(["success" => true, "message" => "Login successful", "user" => $user]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    }
} else {
    $stmt->close();
    echo json_encode(["success" => false, "message" => "Invalid email or password"]);
}
?>