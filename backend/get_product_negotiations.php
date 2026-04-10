<?php
header("Content-Type: application/json");
include "db.php";

// this gets all negotiations for one product
$product_id = $_GET["product_id"] ?? "";

if ($product_id == "") {
    echo json_encode(["success" => false, "message" => "product_id is required"]);
    exit;
}

$sql = "SELECT * FROM negotiations WHERE product_id='$product_id' ORDER BY id DESC";
$result = $conn->query($sql);

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode([
    "success" => true,
    "negotiations" => $items
]);
?>
