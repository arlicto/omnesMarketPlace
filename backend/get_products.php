<?php
header("Content-Type: application/json");
include "db.php";

// this gets all unsold products from database
$sql = "SELECT * FROM products WHERE is_sold=0 ORDER BY id DESC";
$result = $conn->query($sql);

if ($result === false) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch products: " . $conn->error
    ]);
    exit;
}

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode([
    "success" => true,
    "products" => $products
]);
?>