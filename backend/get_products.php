<?php
header("Content-Type: application/json");
include "db.php";

// optional filter: ?category=rare | high-end | regular
$allowed_category = ["rare", "high-end", "regular"];
$cat = isset($_GET["category"]) ? trim($_GET["category"]) : "";

$sql = "SELECT p.*, u.name AS seller_name FROM products p LEFT JOIN users u ON p.seller_id = u.id WHERE p.is_sold=0";
if ($cat !== "" && in_array($cat, $allowed_category, true)) {
    $c = $conn->real_escape_string($cat);
    $sql .= " AND p.category='$c'";
}
$sql .= " ORDER BY p.id DESC";

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
