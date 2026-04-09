<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();

// this reads chat action data
$negotiation_id = $_POST["negotiation_id"] ?? "";
$sender_role = $_SESSION["user_role"] ?? "buyer";
$action = $_POST["action"] ?? "counter"; // counter / accept / reject
$offer_price = $_POST["offer_price"] ?? "";
$message = $_POST["message"] ?? "";

if ($negotiation_id == "") {
    echo json_encode(["success" => false, "message" => "negotiation_id is required"]);
    exit;
}

if (!is_numeric($negotiation_id)) {
    echo json_encode(["success" => false, "message" => "Invalid negotiation_id"]);
    exit;
}

// this gets negotiation first
$get_sql = "SELECT * FROM negotiations WHERE id='$negotiation_id' LIMIT 1";
$res = $conn->query($get_sql);
if ($res->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Negotiation not found"]);
    exit;
}
$neg = $res->fetch_assoc();

// stop negotiation actions if product already sold
$p_res = $conn->query("SELECT id, is_sold FROM products WHERE id='" . $neg["product_id"] . "' LIMIT 1");
if ($p_res->num_rows > 0) {
    $p = $p_res->fetch_assoc();
    if ((int)$p["is_sold"] === 1) {
        echo json_encode(["success" => false, "message" => "Product already sold. Negotiation closed."]);
        exit;
    }
}

// only seller/buyer from this negotiation (or admin) can send
$session_user_id = $_SESSION["user_id"];
$session_role = $_SESSION["user_role"] ?? "";
if ($session_role !== "admin") {
    $ok_party = false;
    if ($session_role === "buyer" && (string)$neg["buyer_id"] === (string)$session_user_id) {
        $ok_party = true;
    }
    if ($session_role === "seller" && (string)$neg["seller_id"] === (string)$session_user_id) {
        $ok_party = true;
    }
    if (!$ok_party) {
        echo json_encode(["success" => false, "message" => "Not allowed for this negotiation"]);
        exit;
    }
}

if ($neg["status"] == "accepted" || $neg["status"] == "rejected") {
    echo json_encode(["success" => false, "message" => "Negotiation already closed"]);
    exit;
}

if ($action == "counter") {
    if ($offer_price == "") {
        echo json_encode(["success" => false, "message" => "offer_price is required for counter"]);
        exit;
    }
    if (!is_numeric($offer_price)) {
        echo json_encode(["success" => false, "message" => "Invalid offer_price"]);
        exit;
    }

    // this checks max 5 rounds
    $new_round = (int)$neg["round"] + 1;
    if ($new_round > 5) {
        echo json_encode(["success" => false, "message" => "Max 5 rounds allowed"]);
        exit;
    }

    $up_sql = "UPDATE negotiations
               SET offer_price='$offer_price', status='countered', round='$new_round'
               WHERE id='$negotiation_id'";
    $conn->query($up_sql);

    $msg_text = $message != "" ? $message : "Counter offer sent";
    $msg_sql = "INSERT INTO negotiation_messages (negotiation_id, sender_role, message, offer_price, action)
                VALUES ('$negotiation_id', '$sender_role', '$msg_text', '$offer_price', 'counter')";
    $conn->query($msg_sql);

    echo json_encode(["success" => true, "message" => "Counter sent", "round" => $new_round, "status" => "countered"]);
    exit;
}

if ($action == "accept") {
    if ($session_role !== "seller" && $session_role !== "admin") {
        echo json_encode(["success" => false, "message" => "Only seller can accept"]);
        exit;
    }
    $conn->query("UPDATE negotiations SET status='accepted' WHERE id='$negotiation_id'");
    $conn->query("UPDATE products SET is_sold=1 WHERE id='" . $neg["product_id"] . "'");
    $msg_text = $message != "" ? $message : "Offer accepted";
    $conn->query("INSERT INTO negotiation_messages (negotiation_id, sender_role, message, offer_price, action)
                  VALUES ('$negotiation_id', '$sender_role', '$msg_text', '" . $neg["offer_price"] . "', 'accept')");
    echo json_encode(["success" => true, "message" => "Deal completed", "status" => "accepted"]);
    exit;
}

if ($action == "reject") {
    if ($session_role !== "seller" && $session_role !== "admin") {
        echo json_encode(["success" => false, "message" => "Only seller can reject"]);
        exit;
    }
    $conn->query("UPDATE negotiations SET status='rejected' WHERE id='$negotiation_id'");
    $msg_text = $message != "" ? $message : "Offer rejected";
    $conn->query("INSERT INTO negotiation_messages (negotiation_id, sender_role, message, offer_price, action)
                  VALUES ('$negotiation_id', '$sender_role', '$msg_text', '" . $neg["offer_price"] . "', 'reject')");
    echo json_encode(["success" => true, "message" => "Negotiation failed", "status" => "rejected"]);
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid action"]);
?>
