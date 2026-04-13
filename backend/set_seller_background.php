<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["seller", "admin"]);

$user_id = (int)($_SESSION["user_id"] ?? 0);
$file = trim($_POST["file"] ?? "");

if ($file === "") {
    echo json_encode(["success" => false, "message" => "file is required"]);
    exit;
}

// Validate selected file exists in allowed folder
$base = realpath(__DIR__ . "/../images/seller_bg_profile");
if ($base === false || !is_dir($base)) {
    echo json_encode(["success" => false, "message" => "Background folder not found"]);
    exit;
}

// prevent path traversal
if (strpos($file, "..") !== false || strpos($file, "/") !== false || strpos($file, "\\") !== false) {
    echo json_encode(["success" => false, "message" => "Invalid file"]);
    exit;
}

$path = $base . DIRECTORY_SEPARATOR . $file;
if (!is_file($path)) {
    echo json_encode(["success" => false, "message" => "File not found"]);
    exit;
}

$url = "../images/seller_bg_profile/" . $file;
$urlEsc = $conn->real_escape_string($url);

$sql = "UPDATE users SET background_image='$urlEsc' WHERE id=$user_id";
if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Background updated", "background_image" => $url]);
} else {
    echo json_encode(["success" => false, "message" => "Could not update background"]);
}
?>
