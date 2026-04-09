<?php
// simple session auth helper
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (!isset($_SESSION["user_id"])) {
        echo json_encode(["success" => false, "message" => "Login required"]);
        exit;
    }
}

function require_role($roles) {
    $role = $_SESSION["user_role"] ?? "";
    if (!in_array($role, $roles)) {
        echo json_encode(["success" => false, "message" => "Not allowed"]);
        exit;
    }
}
?>
