<?php
header("Content-Type: application/json");
include "db.php";

// Get seller ID from query string
$seller_id = isset($_GET["seller_id"]) ? intval($_GET["seller_id"]) : 0;

if ($seller_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid seller ID"
    ]);
    exit;
}

// Get seller info
$seller_sql = "SELECT id, name FROM users WHERE id=$seller_id AND role='seller'";
$seller_result = $conn->query($seller_sql);

if ($seller_result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Seller not found"
    ]);
    exit;
}

$seller = $seller_result->fetch_assoc();

// Get seller's products
$products_sql = "SELECT * FROM products WHERE seller_id=$seller_id AND is_sold=0 ORDER BY id DESC";
$products_result = $conn->query($products_sql);

$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode([
    "success" => true,
    "seller" => $seller,
    "products" => $products
]);
?>
