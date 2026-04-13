<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();

$user_id = $_SESSION["user_id"];

$latest_sql = "SELECT MAX(id) AS max_id FROM notifications WHERE user_id='$user_id' GROUP BY keyword";
$list = $conn->query("SELECT n.id, n.keyword, n.message FROM notifications n JOIN ($latest_sql) t ON n.id = t.max_id ORDER BY n.id DESC");

if ($list === false) {
    echo json_encode([
        "success" => false,
        "error" => $conn->error
    ]);
    exit;
}

$results = [];
while ($row = $list->fetch_assoc()) {
    $k = $conn->real_escape_string((string)($row["keyword"] ?? ""));
    // Match product name with LIKE (simple beginner style)
    $pq = $conn->query("SELECT * FROM products WHERE is_sold=0 AND name LIKE '%$k%' ORDER BY id DESC");
    
    if ($pq === false) {
        echo json_encode([
            "success" => false,
            "error" => $conn->error
        ]);
        exit;
    }
    
    $products = [];
    while ($p = $pq->fetch_assoc()) {
        $products[] = $p;
    }
    
    $results[] = [
        "notification_id" => $row["id"],
        "keyword" => $row["keyword"],
        "message" => $row["message"],
        "products" => $products
    ];
}

echo json_encode([
    "success" => true,
    "results" => $results
]);
?>
