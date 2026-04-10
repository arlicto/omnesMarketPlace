<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();

$user_id = $_SESSION["user_id"];

$list = $conn->query("SELECT id, keyword, message FROM notifications WHERE user_id='$user_id' ORDER BY id DESC");

if ($list === false) {
    echo json_encode([
        "success" => false,
        "error" => $conn->error
    ]);
    exit;
}

$results = [];
while ($row = $list->fetch_assoc()) {
    // Escape LIKE metacharacters first, then apply real_escape_string
    $keyword = $row["keyword"];
    $keyword = str_replace("\\", "\\\\", $keyword);
    $keyword = str_replace("%", "\\%", $keyword);
    $keyword = str_replace("_", "\\_", $keyword);
    $k = $conn->real_escape_string($keyword);
    // Match product name with LIKE (simple beginner style)
    $pq = $conn->query("SELECT * FROM products WHERE is_sold=0 AND name LIKE '%$k%' ESCAPE '\\' ORDER BY id DESC");
    
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
