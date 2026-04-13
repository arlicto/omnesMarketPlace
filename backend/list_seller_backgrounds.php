<?php
header("Content-Type: application/json");
include "auth.php";

require_login();
require_role(["seller", "admin"]);

$dir = realpath(__DIR__ . "/../images/seller_bg_profile");
if ($dir === false || !is_dir($dir)) {
    echo json_encode(["success" => false, "message" => "Background folder not found"]);
    exit;
}

$allowed_ext = ["jpg", "jpeg", "png", "gif", "webp"];
$files = scandir($dir);
$items = [];

foreach ($files as $f) {
    if ($f === "." || $f === "..") {
        continue;
    }
    $path = $dir . DIRECTORY_SEPARATOR . $f;
    if (!is_file($path)) {
        continue;
    }
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) {
        continue;
    }
    $items[] = [
        "file" => $f,
        "url" => "../images/seller_bg_profile/" . rawurlencode($f)
    ];
}

echo json_encode(["success" => true, "backgrounds" => $items]);
?>
