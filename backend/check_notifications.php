<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();

$user_id = $_SESSION["user_id"];

$list = $conn->query("SELECT id, keyword, message FROM notifications WHERE user_id='$user_id' ORDER BY id DESC");

$results = [];
if ($list) {
    while ($row = $list->fetch_assoc()) {
        $k = $conn->real_escape_string($row["keyword"]);
        // Match product name with LIKE (simple beginner style)
        $pq = $conn->query("SELECT * FROM products WHERE is_sold=0 AND name LIKE '%$k%' ORDER BY id DESC");
        $products = [];
        if ($pq) {
            while ($p = $pq->fetch_assoc()) {
                $products[] = $p;
            }
        }
        $results[] = [
            "notification_id" => $row["id"],
            "keyword" => $row["keyword"],
            "message" => $row["message"],
            "products" => $products
        ];
    }
}

echo json_encode([
    "success" => true,
    "results" => $results
]);
?>
