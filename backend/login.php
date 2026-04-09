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
$sql = "SELECT id, name, email, role, password FROM users WHERE email='$email' LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $ok = false;

    // supports secure hashes and old plain text rows
    if (password_verify($password, $user["password"])) {
        $ok = true;
    } elseif ($password === $user["password"]) {
        $ok = true;
        // optional migration: convert old plain text to hash after login
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$new_hash' WHERE id='" . $user["id"] . "'");
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
    echo json_encode(["success" => false, "message" => "Invalid email or password"]);
}
?>
