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
$p_stmt = $conn->prepare("SELECT id, is_sold FROM products WHERE id = ? LIMIT 1");
if (!$p_stmt) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$p_stmt->bind_param("i", $product_id);
$p_stmt->execute();
$p_res = $p_stmt->get_result();
if ($p_res->num_rows == 0) {
    $p_stmt->close();
    echo json_encode(["success" => false, "message" => "Product not found"]);
    exit;
}
$p = $p_res->fetch_assoc();
$p_stmt->close();
if ((int)$p["is_sold"] === 1) {
    echo json_encode(["success" => false, "message" => "Product already sold"]);
    exit;
}

// this checks latest negotiation for same buyer and product
$check_stmt = $conn->prepare("SELECT * FROM negotiations WHERE product_id = ? AND buyer_id = ? ORDER BY id DESC LIMIT 1");
if (!$check_stmt) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$check_stmt->bind_param("ii", $product_id, $buyer_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $old = $check_result->fetch_assoc();
    $check_stmt->close();

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
    $update_stmt = $conn->prepare("UPDATE negotiations SET offer_price = ?, status = 'pending', round = ? WHERE id = ?");
    if (!$update_stmt) {
        echo json_encode(["success" => false, "message" => "Database error"]);
        exit;
    }
    $update_stmt->bind_param("sii", $offer_price, $new_round, $old["id"]);
    if ($update_stmt->execute()) {
        $update_stmt->close();
        // this stores first DM-style chat message for offer
        $msg_stmt = $conn->prepare("INSERT INTO negotiation_messages (negotiation_id, sender_role, message, offer_price, action) VALUES (?, 'buyer', 'New counter offer from buyer', ?, 'counter')");
        if ($msg_stmt) {
            $msg_stmt->bind_param("is", $old["id"], $offer_price);
            $msg_stmt->execute();
            $msg_stmt->close();
        }

        echo json_encode([
            "success" => true,
            "message" => "Offer sent",
            "negotiation_id" => $old["id"],
            "round" => $new_round,
            "status" => "pending"
        ]);
    } else {
        $update_stmt->close();
        echo json_encode(["success" => false, "message" => "Could not send offer"]);
    }
    exit;
}

// buyer starts first negotiation
$check_stmt->close();

$insert_stmt = $conn->prepare("INSERT INTO negotiations (product_id, buyer_id, seller_id, offer_price, status, round) VALUES (?, ?, ?, ?, 'pending', 1)");
if (!$insert_stmt) {
    echo json_encode(["success" => false, "message" => "Could not start negotiation"]);
    exit;
}
$insert_stmt->bind_param("iiis", $product_id, $buyer_id, $seller_id, $offer_price);

if ($insert_stmt->execute()) {
    $new_id = $conn->insert_id;
    $insert_stmt->close();

    // this stores first DM-style chat message for offer
    $msg_stmt = $conn->prepare("INSERT INTO negotiation_messages (negotiation_id, sender_role, message, offer_price, action) VALUES (?, 'buyer', 'Starting negotiation with this offer', ?, 'counter')");
    if ($msg_stmt) {
        $msg_stmt->bind_param("is", $new_id, $offer_price);
        $msg_stmt->execute();
        $msg_stmt->close();
    }

    echo json_encode([
        "success" => true,
        "message" => "Negotiation started",
        "negotiation_id" => $new_id,
        "round" => 1,
        "status" => "pending"
    ]);
} else {
    $insert_stmt->close();
    echo json_encode(["success" => false, "message" => "Could not start negotiation"]);
}
?>