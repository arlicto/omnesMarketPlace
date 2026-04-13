<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["seller", "admin"]);

$product_id = $_POST["product_id"] ?? "";
if ($product_id === "" || !is_numeric($product_id)) {
    echo json_encode(["success" => false, "message" => "Invalid product_id"]);
    exit;
}

$product_id = (int)$product_id;
$session_user_id = (int)($_SESSION["user_id"] ?? 0);
$session_role = $_SESSION["user_role"] ?? "";

// verify product and ownership
$p_res = $conn->query("SELECT id, seller_id, is_sold, sale_type, auction_start, auction_end FROM products WHERE id=$product_id LIMIT 1");
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

if ($session_role !== "admin") {
    $seller_id = (int)($p["seller_id"] ?? 0);
    if ($seller_id <= 0 || $seller_id !== $session_user_id) {
        echo json_encode(["success" => false, "message" => "Not allowed to end this auction"]);
        exit;
    }
}

// enforce end time if set
if (!empty($p["auction_end"])) {
    $end_ts = strtotime((string)$p["auction_end"]);
    if ($end_ts !== false && time() < $end_ts) {
        echo json_encode(["success" => false, "message" => "Auction is still running. You can end it after the end time."]);
        exit;
    }
}

// find highest bid
$top_res = $conn->query("SELECT id, bidder_id, amount FROM auction_bids WHERE product_id=$product_id ORDER BY amount DESC, id DESC LIMIT 1");
if (!$top_res || $top_res->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No bids yet. Cannot end auction."]);
    exit;
}

$bid = $top_res->fetch_assoc();
$bidder_id = (int)$bid["bidder_id"];
$amount = $bid["amount"];

// avoid duplicate order
$dup = $conn->query("SELECT id FROM orders WHERE product_id=$product_id LIMIT 1");
if ($dup && $dup->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Auction already ended"]);
    exit;
}

$amt = $conn->real_escape_string((string)$amount);

// store a demo cart row for the winning bidder (optional, mirrors buy_product behavior)
$conn->query("INSERT IGNORE INTO cart (user_id, product_id) VALUES ($bidder_id, $product_id)");

// create order
$order_sql = "INSERT INTO orders (user_id, product_id, final_price, negotiation_id) VALUES ($bidder_id, $product_id, '$amt', NULL)";
$order_ok = $conn->query($order_sql);
if (!$order_ok) {
    echo json_encode(["success" => false, "message" => "Order could not be created"]);
    exit;
}

// mark product sold
$conn->query("UPDATE products SET is_sold=1 WHERE id=$product_id");

echo json_encode([
    "success" => true,
    "message" => "Auction ended. Winner selected.",
    "winner_user_id" => $bidder_id,
    "final_price" => $amount
]);
?>
