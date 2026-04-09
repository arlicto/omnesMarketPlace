<?php
header("Content-Type: application/json");
include "db.php";

// this gets all negotiations for one product
$product_id = $_GET["product_id"] ?? "";

if ($product_id == "") {
    echo json_encode(["success" => false, "message" => "product_id is required"]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM negotiations WHERE product_id = ? ORDER BY id DESC");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

echo json_encode([
    "success" => true,
    "negotiations" => $items
]);
?>