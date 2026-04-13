<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();

$user_id = $_SESSION["user_id"];
$product_id = $_POST["product_id"] ?? "";

if ($product_id === "") {
    echo json_encode(["success" => false, "message" => "product_id is required"]);
    exit;
}

if (!is_numeric($product_id)) {
    echo json_encode(["success" => false, "message" => "Invalid product_id"]);
    exit;
}

$sql = "DELETE FROM cart WHERE user_id='$user_id' AND product_id='$product_id'";
if ($conn->query($sql)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Could not remove item"]);
}
?>
