<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();

$user_id = $_SESSION["user_id"];

// Join cart -> products and get the last known final price from orders (auction/negotiation/direct).
// If no order exists, fallback to product price.
$sql = "SELECT 
            p.id,
            p.name,
            p.description,
            p.price AS product_price,
            p.image,
            p.images,
            (
              SELECT o.final_price
              FROM orders o
              WHERE o.user_id = c.user_id AND o.product_id = c.product_id
              ORDER BY o.id DESC
              LIMIT 1
            ) AS final_price
        FROM cart c
        JOIN products p ON p.id = c.product_id
        WHERE c.user_id = '$user_id'
        ORDER BY c.id DESC";

$res = $conn->query($sql);
if (!$res) {
    echo json_encode(["success" => false, "message" => "Could not load cart"]);
    exit;
}

$items = [];
while ($row = $res->fetch_assoc()) {
    $price = $row["final_price"];
    if ($price === null || $price === "") {
        $price = $row["product_price"];
    }

    $items[] = [
        "id" => $row["id"],
        "name" => $row["name"],
        "description" => $row["description"],
        "price" => $price,
        "image" => $row["image"],
        "images" => $row["images"]
    ];
}

echo json_encode(["success" => true, "items" => $items]);
?>
