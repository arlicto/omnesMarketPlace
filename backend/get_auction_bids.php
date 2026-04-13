<?php
header("Content-Type: application/json");
include "db.php";

$product_id = $_GET["product_id"] ?? "";
if ($product_id === "" || !is_numeric($product_id)) {
    echo json_encode(["success" => false, "message" => "Invalid product_id"]);
    exit;
}

$product_id = (int)$product_id;

$p = $conn->query("SELECT auction_start, auction_end FROM products WHERE id=$product_id LIMIT 1");
$auction_start = null;
$auction_end = null;
$is_open = true;
if ($p && $p->num_rows > 0) {
    $pr = $p->fetch_assoc();
    $auction_start = $pr["auction_start"];
    $auction_end = $pr["auction_end"];
    $now = time();
    if (!empty($auction_start)) {
        $st = strtotime((string)$auction_start);
        if ($st !== false && $now < $st) {
            $is_open = false;
        }
    }
    if (!empty($auction_end)) {
        $et = strtotime((string)$auction_end);
        if ($et !== false && $now > $et) {
            $is_open = false;
        }
    }
}

// get highest bid
$top_res = $conn->query("SELECT b.amount, b.bidder_id, u.name AS bidder_name, b.created_at FROM auction_bids b LEFT JOIN users u ON b.bidder_id = u.id WHERE b.product_id=$product_id ORDER BY b.amount DESC, b.id DESC LIMIT 1");
$highest = null;
if ($top_res && $top_res->num_rows > 0) {
    $highest = $top_res->fetch_assoc();
}

// get recent bids
$res = $conn->query("SELECT b.id, b.amount, b.bidder_id, u.name AS bidder_name, b.created_at FROM auction_bids b LEFT JOIN users u ON b.bidder_id = u.id WHERE b.product_id=$product_id ORDER BY b.amount DESC, b.id DESC LIMIT 10");
$bids = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $bids[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "highest" => $highest,
    "bids" => $bids,
    "auction_start" => $auction_start,
    "auction_end" => $auction_end,
    "is_open" => $is_open
]);
?>
