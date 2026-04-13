<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["seller", "admin"]);

$user_id = (int)($_SESSION["user_id"] ?? 0);

if (!isset($_FILES["photo"])) {
    echo json_encode(["success" => false, "message" => "photo is required"]);
    exit;
}

$f = $_FILES["photo"];
if (!isset($f["tmp_name"]) || $f["tmp_name"] === "") {
    echo json_encode(["success" => false, "message" => "Invalid upload"]);
    exit;
}

if (!is_uploaded_file($f["tmp_name"])) {
    echo json_encode(["success" => false, "message" => "Invalid upload"]);
    exit;
}

$maxBytes = 5 * 1024 * 1024;
if (isset($f["size"]) && (int)$f["size"] > $maxBytes) {
    echo json_encode(["success" => false, "message" => "File too large (max 5MB)"]);
    exit;
}

$info = @getimagesize($f["tmp_name"]);
if ($info === false || !isset($info["mime"])) {
    echo json_encode(["success" => false, "message" => "Uploaded file is not an image"]);
    exit;
}

$mime = strtolower($info["mime"]);
$ext = "";
if ($mime === "image/jpeg") { $ext = "jpg"; }
elseif ($mime === "image/png") { $ext = "png"; }
elseif ($mime === "image/gif") { $ext = "gif"; }
elseif ($mime === "image/webp") { $ext = "webp"; }
else {
    echo json_encode(["success" => false, "message" => "Only JPG, PNG, GIF, WEBP allowed"]);
    exit;
}

$publicDir = realpath(__DIR__ . "/../images");
if ($publicDir === false) {
    echo json_encode(["success" => false, "message" => "Images folder not found"]);
    exit;
}

$targetFolder = $publicDir . DIRECTORY_SEPARATOR . "seller_profile_photos";
if (!is_dir($targetFolder)) {
    @mkdir($targetFolder, 0755, true);
}

if (!is_dir($targetFolder)) {
    echo json_encode(["success" => false, "message" => "Could not create upload folder"]);
    exit;
}

$filename = "seller_" . $user_id . "_" . time() . "." . $ext;
$targetPath = $targetFolder . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($f["tmp_name"], $targetPath)) {
    echo json_encode(["success" => false, "message" => "Could not save uploaded file"]);
    exit;
}

$url = "../images/seller_profile_photos/" . $filename;
$urlEsc = $conn->real_escape_string($url);

if ($conn->query("UPDATE users SET profile_photo='$urlEsc' WHERE id=$user_id")) {
    echo json_encode(["success" => true, "message" => "Profile photo updated", "profile_photo" => $url]);
} else {
    echo json_encode(["success" => false, "message" => "Could not update profile photo"]);
}
?>
