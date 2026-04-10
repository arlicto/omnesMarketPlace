<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["admin"]);

// this reads product id from request
$product_id = $_POST["product_id"] ?? "";

if ($product_id == "") {
    echo json_encode(["success" => false, "message" => "product_id is required"]);
    exit;
}

if (!is_numeric($product_id)) {
    echo json_encode(["success" => false, "message" => "Invalid product_id"]);
    exit;
}

// this deletes the product
$sql = "DELETE FROM products WHERE id='$product_id'";
if ($conn->query($sql)) {
    if ($conn->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "Product deleted"]);
    } else {
        echo json_encode(["success" => false, "message" => "Product not found"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Delete failed"]);
}
?>
