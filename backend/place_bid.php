<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["buyer", "admin"]);

$user_id = (int)($_SESSION["user_id"] ?? 0);
$product_id = $_POST["product_id"] ?? "";
$amount = $_POST["amount"] ?? "";

if ($product_id === "" || !is_numeric($product_id)) {
    echo json_encode(["success" => false, "message" => "Invalid product_id"]);
    exit;
}
if ($amount === "" || !is_numeric($amount)) {
    echo json_encode(["success" => false, "message" => "Invalid bid amount"]);
    exit;
}

$product_id = (int)$product_id;
$amount = (float)$amount;

if ($amount <= 0) {
    echo json_encode(["success" => false, "message" => "Bid amount must be positive"]);
    exit;
}

// ensure product exists and is an auction and not sold
$p_res = $conn->query("SELECT id, is_sold, sale_type, price, auction_start, auction_end FROM products WHERE id=$product_id LIMIT 1");
if (!$p_res || $p_res->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Product not found"]);
    exit;
}
$p = $p_res->fetch_assoc();
if ((int)$p["is_sold"] === 1) {
    echo json_encode(["success" => false, "message" => "Product already sold"]);
    exit;
}
if (strtolower((string)$p["sale_type"]) !== "auction") {
    echo json_encode(["success" => false, "message" => "This product is not an auction"]);
    exit;
}

// enforce auction time window if set
$now = time();
if (!empty($p["auction_start"])) {
    $start_ts = strtotime((string)$p["auction_start"]);
    if ($start_ts !== false && $now < $start_ts) {
        echo json_encode(["success" => false, "message" => "Auction has not started yet"]);
        exit;
    }
}
if (!empty($p["auction_end"])) {
    $end_ts = strtotime((string)$p["auction_end"]);
    if ($end_ts !== false && $now > $end_ts) {
        echo json_encode(["success" => false, "message" => "Auction has ended"]);
        exit;
    }
}

$base_price = isset($p["price"]) ? (float)$p["price"] : 0.0;

// current winning bid (computed bids table)
$top_res = $conn->query("SELECT bidder_id, amount FROM auction_bids WHERE product_id=$product_id ORDER BY amount DESC, id DESC LIMIT 1");
$current_amount = $base_price;
$current_bidder_id = 0;
if ($top_res && $top_res->num_rows > 0) {
    $row = $top_res->fetch_assoc();
    $current_amount = (float)$row["amount"];
    $current_bidder_id = (int)$row["bidder_id"];
}

// amount is the maximum bid user is willing to pay
if ($amount < $base_price) {
    echo json_encode(["success" => false, "message" => "Maximum bid must be at least the starting price (" . $base_price . ")"]);
    exit;
}

// if there's already a leading bidder, require others to beat the current price
if ($amount < $current_amount) {
    echo json_encode(["success" => false, "message" => "Maximum bid must be at least the current price (" . $current_amount . ")"]);
    exit;
}

if ($current_bidder_id !== 0 && $current_bidder_id !== $user_id && $amount == $current_amount) {
    echo json_encode(["success" => false, "message" => "Maximum bid must be higher than current price (" . $current_amount . ")"]);
    exit;
}

// upsert max bid
$amt = $conn->real_escape_string((string)$amount);
$upsert = "INSERT INTO auction_max_bids (product_id, bidder_id, max_amount) VALUES ($product_id, $user_id, '$amt')
           ON DUPLICATE KEY UPDATE max_amount='$amt'";
if (!$conn->query($upsert)) {
    echo json_encode(["success" => false, "message" => "Could not save max bid"]);
    exit;
}

// compute new current winning bid via proxy rule: min(topMax, secondMax + 1) with base price floor
$max_res = $conn->query("SELECT bidder_id, max_amount FROM auction_max_bids WHERE product_id=$product_id ORDER BY max_amount DESC, updated_at DESC LIMIT 2");
if (!$max_res || $max_res->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No max bids found"]);
    exit;
}

$top1 = $max_res->fetch_assoc();
$top1_bidder = (int)$top1["bidder_id"];
$top1_max = (float)$top1["max_amount"];

$top2_max = null;
if ($max_res->num_rows > 1) {
    $top2 = $max_res->fetch_assoc();
    $top2_max = (float)$top2["max_amount"];
}

$increment = 1.0;
if ($top2_max === null) {
    $new_current = $base_price;
} else {
    $target = $top2_max + $increment;
    if ($target < $base_price) {
        $target = $base_price;
    }
    $new_current = $target;
    if ($new_current > $top1_max) {
        $new_current = $top1_max;
    }
}

$new_bidder = $top1_bidder;

// record computed winning bid if changed
if ((float)$new_current !== (float)$current_amount || (int)$new_bidder !== (int)$current_bidder_id) {
    $new_amt = $conn->real_escape_string(number_format((float)$new_current, 2, '.', ''));
    $ins = "INSERT INTO auction_bids (product_id, bidder_id, amount) VALUES ($product_id, $new_bidder, '$new_amt')";
    $conn->query($ins);
}

echo json_encode([
    "success" => true,
    "message" => "Max bid saved",
    "max_amount" => $amount,
    "current_price" => $new_current,
    "current_winner_user_id" => $new_bidder
]);
exit;
?>
