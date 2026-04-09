<?php
header("Content-Type: application/json");
include "db.php";

// this gets all unsold products from database
$sql = "SELECT * FROM products WHERE is_sold=0 ORDER BY id DESC";
$result = $conn->query($sql);

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode([
    "success" => true,
    "products" => $products
]);
?>
