<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();

// this fetches negotiation details for product
$product_id = $_GET["product_id"] ?? "";
$buyer_id = $_GET["buyer_id"] ?? "";
$user_id = $_SESSION["user_id"];

if ($product_id == "") {
    echo json_encode(["success" => false, "message" => "product_id is required"]);
    exit;
}

// Build prepared statement dynamically based on buyer_id presence
if ($buyer_id != "") {
    $stmt = $conn->prepare("SELECT * FROM negotiations WHERE product_id = ? AND buyer_id = ? ORDER BY id DESC LIMIT 1");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Database error"]);
        exit;
    }
    $stmt->bind_param("ii", $product_id, $buyer_id);
} else {
    $stmt = $conn->prepare("SELECT * FROM negotiations WHERE product_id = ? ORDER BY id DESC LIMIT 1");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Database error"]);
        exit;
    }
    $stmt->bind_param("i", $product_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    echo json_encode(["success" => true, "message" => "No negotiation yet", "negotiation" => null]);
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();

// Authorization check: ensure user is buyer or seller
$prod_stmt = $conn->prepare("SELECT seller_id FROM products WHERE id = ? LIMIT 1");
if ($prod_stmt) {
    $prod_stmt->bind_param("i", $product_id);
    $prod_stmt->execute();
    $prod_result = $prod_stmt->get_result();
    if ($prod_result->num_rows > 0) {
        $prod_row = $prod_result->fetch_assoc();
        $seller_id = $prod_row["seller_id"];

        // Check if user is authorized (buyer, seller, or admin)
        $is_buyer = ((string)$row["buyer_id"] === (string)$user_id);
        $is_seller = ((string)$seller_id === (string)$user_id);
        $is_admin = ($_SESSION["user_role"] ?? "") === "admin";

        if (!$is_buyer && !$is_seller && !$is_admin) {
            $prod_stmt->close();
            echo json_encode(["success" => false, "message" => "Not authorized to view this negotiation"]);
            exit;
        }
    }
    $prod_stmt->close();
}

echo json_encode(["success" => true, "negotiation" => $row]);
?>