<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["buyer", "admin"]);

// this reads buyer offer data
$product_id = $_POST["product_id"] ?? "";
$buyer_id = $_SESSION["user_id"];
$seller_id = $_POST["seller_id"] ?? "";
$offer_price = $_POST["offer_price"] ?? "";

if ($product_id == "" || $seller_id == "" || $offer_price == "") {
    echo json_encode(["success" => false, "message" => "Please send all fields"]);
    exit;
}

if (!is_numeric($product_id) || !is_numeric($seller_id) || !is_numeric($offer_price)) {
    echo json_encode(["success" => false, "message" => "Invalid numeric fields"]);
    exit;
}

// do not allow negotiation for sold product
$p_res = $conn->query("SELECT id, is_sold FROM products WHERE id='$product_id' LIMIT 1");
if ($p_res->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Product not found"]);
    exit;
}
$p = $p_res->fetch_assoc();
if ((int)$p["is_sold"] === 1) {
    echo json_encode(["success" => false, "message" => "Product already sold"]);
    exit;
}

// this checks latest negotiation for same buyer and product
$check_sql = "SELECT * FROM negotiations WHERE product_id='$product_id' AND buyer_id='$buyer_id' ORDER BY id DESC LIMIT 1";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows > 0) {
    $old = $check_result->fetch_assoc();

    if ($old["status"] == "accepted") {
        echo json_encode(["success" => false, "message" => "Deal already completed"]);
        exit;
    }

    if ($old["status"] == "rejected") {
        echo json_encode(["success" => false, "message" => "Negotiation already rejected"]);
        exit;
    }

    // this checks negotiation round (max 5)
    $new_round = (int)$old["round"] + 1;
    if ($new_round > 5) {
        echo json_encode(["success" => false, "message" => "Max 5 rounds allowed"]);
        exit;
    }

    // buyer sends new offer after counter
    $update_sql = "UPDATE negotiations
                   SET offer_price='$offer_price', status='pending', round='$new_round'
                   WHERE id='" . $old["id"] . "'";
    if ($conn->query($update_sql)) {
        // this stores first DM-style chat message for offer
        $msg_sql = "INSERT INTO negotiation_messages (negotiation_id, sender_role, message, offer_price, action)
                    VALUES ('" . $old["id"] . "', 'buyer', 'New counter offer from buyer', '$offer_price', 'counter')";
        $conn->query($msg_sql);

        echo json_encode([
            "success" => true,
            "message" => "Offer sent",
            "negotiation_id" => $old["id"],
            "round" => $new_round,
            "status" => "pending"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Could not send offer"]);
    }
    exit;
}

// buyer starts first negotiation
$sql = "INSERT INTO negotiations (product_id, buyer_id, seller_id, offer_price, status, round)
        VALUES ('$product_id', '$buyer_id', '$seller_id', '$offer_price', 'pending', 1)";

if ($conn->query($sql)) {
    $new_id = $conn->insert_id;

    // this stores first DM-style chat message for offer
    $msg_sql = "INSERT INTO negotiation_messages (negotiation_id, sender_role, message, offer_price, action)
                VALUES ('$new_id', 'buyer', 'Starting negotiation with this offer', '$offer_price', 'counter')";
    $conn->query($msg_sql);

    echo json_encode([
        "success" => true,
        "message" => "Negotiation started",
        "negotiation_id" => $new_id,
        "round" => 1,
        "status" => "pending"
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Could not start negotiation"]);
}
?>
