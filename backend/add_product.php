<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["admin", "seller"]);

$post_user_id = $_POST["user_id"] ?? "";
$session_uid = (string)($_SESSION["user_id"] ?? "");
if ($post_user_id !== "" && (string)$post_user_id !== $session_uid) {
    echo json_encode(["success" => false, "message" => "Session does not match submitted user_id"]);
    exit;
}

// basic input reading
$name = $_POST["name"] ?? "";
$description = $_POST["description"] ?? "";
$price = $_POST["price"] ?? "";
$image = $_POST["image"] ?? "";
$images = $_POST["images"] ?? "";
$video = $_POST["video"] ?? "";
$category = trim($_POST["category"] ?? "");
$sale_type = $_POST["sale_type"] ?? "buy_now";

$allowed_categories = ["rare", "high-end", "regular"];
if ($category === "" || !in_array($category, $allowed_categories, true)) {
    $category = "regular";
}

if ($name == "" || $price == "") {
    echo json_encode(["success" => false, "message" => "Name and price are required"]);
    exit;
}

if (!is_numeric($price)) {
    echo json_encode(["success" => false, "message" => "Invalid price"]);
    exit;
}

$allowed_sale_types = ["buy_now", "auction", "negotiation"];
if (!in_array($sale_type, $allowed_sale_types)) {
    echo json_encode(["success" => false, "message" => "Invalid sale_type"]);
    exit;
}

$category = $conn->real_escape_string($category);

// this sets main image from images list if single image is empty
if ($image == "" && $images != "") {
    $img_parts = explode(",", $images);
    $image = trim($img_parts[0]);
}

// owner for negotiation routing (logged-in admin or seller)
$seller_id = $session_uid;

// this inserts new product
$sql = "INSERT INTO products (name, description, price, image, images, video, category, sale_type, seller_id)
        VALUES ('$name', '$description', '$price', '$image', '$images', '$video', '$category', '$sale_type', '$seller_id')";

if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Product added"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to add product"]);
}
?>
