<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["admin", "seller"]);

// basic input reading
$name = $_POST["name"] ?? "";
$description = $_POST["description"] ?? "";
$price = $_POST["price"] ?? "";
$image = $_POST["image"] ?? "";
$images = $_POST["images"] ?? "";
$video = $_POST["video"] ?? "";
$category = $_POST["category"] ?? "";
$sale_type = $_POST["sale_type"] ?? "buy_now";

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

// this sets main image from images list if single image is empty
if ($image == "" && $images != "") {
    $img_parts = explode(",", $images);
    $image = trim($img_parts[0]);
}

// this inserts new product
$sql = "INSERT INTO products (name, description, price, image, images, video, category, sale_type)
        VALUES ('$name', '$description', '$price', '$image', '$images', '$video', '$category', '$sale_type')";

if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Product added"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to add product"]);
}
?>
