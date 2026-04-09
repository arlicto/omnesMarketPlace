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
$get_stmt = $conn->prepare("SELECT * FROM negotiations WHERE id = ? LIMIT 1");
if (!$get_stmt) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$get_stmt->bind_param("i", $negotiation_id);
$get_stmt->execute();
$res = $get_stmt->get_result();
if ($res->num_rows == 0) {
    $get_stmt->close();
    echo json_encode(["success" => false, "message" => "Negotiation not found"]);
    exit;
}
$neg = $res->fetch_assoc();
$get_stmt->close();

// stop negotiation actions if product already sold
$p_stmt = $conn->prepare("SELECT id, is_sold FROM products WHERE id = ? LIMIT 1");
if ($p_stmt) {
    $p_stmt->bind_param("i", $neg["product_id"]);
    $p_stmt->execute();
    $p_res = $p_stmt->get_result();
    if ($p_res->num_rows > 0) {
        $p = $p_res->fetch_assoc();
        if ((int)$p["is_sold"] === 1) {
            $p_stmt->close();
            echo json_encode(["success" => false, "message" => "Product already sold. Negotiation closed."]);
            exit;
        }
    }
    $p_stmt->close();
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

    $up_stmt = $conn->prepare("UPDATE negotiations SET offer_price = ?, status = 'countered', round = ? WHERE id = ?");
    if (!$up_stmt) {
        echo json_encode(["success" => false, "message" => "Database error"]);
        exit;
    }
    $up_stmt->bind_param("sii", $offer_price, $new_round, $negotiation_id);
    $up_stmt->execute();
    $up_stmt->close();

    $msg_text = $message != "" ? $message : "Counter offer sent";
    $msg_stmt = $conn->prepare("INSERT INTO negotiation_messages (negotiation_id, sender_role, message, offer_price, action) VALUES (?, ?, ?, ?, 'counter')");
    if (!$msg_stmt) {
        echo json_encode(["success" => false, "message" => "Database error"]);
        exit;
    }
    $msg_stmt->bind_param("isss", $negotiation_id, $sender_role, $msg_text, $offer_price);
    $msg_stmt->execute();
    $msg_stmt->close();

    echo json_encode(["success" => true, "message" => "Counter sent", "round" => $new_round, "status" => "countered"]);
    exit;
}

if ($action == "accept") {
    if ($session_role !== "seller" && $session_role !== "admin") {
        echo json_encode(["success" => false, "message" => "Only seller can accept"]);
        exit;
    }
    $acc_stmt = $conn->prepare("UPDATE negotiations SET status='accepted' WHERE id = ?");
    if ($acc_stmt) {
        $acc_stmt->bind_param("i", $negotiation_id);
        $acc_stmt->execute();
        $acc_stmt->close();
    }
    $prod_stmt = $conn->prepare("UPDATE products SET is_sold=1 WHERE id = ?");
    if ($prod_stmt) {
        $prod_stmt->bind_param("i", $neg["product_id"]);
        $prod_stmt->execute();
        $prod_stmt->close();
    }
    $msg_text = $message != "" ? $message : "Offer accepted";
    $msg_acc_stmt = $conn->prepare("INSERT INTO negotiation_messages (negotiation_id, sender_role, message, offer_price, action) VALUES (?, ?, ?, ?, 'accept')");
    if ($msg_acc_stmt) {
        $msg_acc_stmt->bind_param("isss", $negotiation_id, $sender_role, $msg_text, $neg["offer_price"]);
        $msg_acc_stmt->execute();
        $msg_acc_stmt->close();
    }
    echo json_encode(["success" => true, "message" => "Deal completed", "status" => "accepted"]);
    exit;
}

if ($action == "reject") {
    if ($session_role !== "seller" && $session_role !== "admin") {
        echo json_encode(["success" => false, "message" => "Only seller can reject"]);
        exit;
    }
    $rej_stmt = $conn->prepare("UPDATE negotiations SET status='rejected' WHERE id = ?");
    if ($rej_stmt) {
        $rej_stmt->bind_param("i", $negotiation_id);
        $rej_stmt->execute();
        $rej_stmt->close();
    }
    $msg_text = $message != "" ? $message : "Offer rejected";
    $msg_rej_stmt = $conn->prepare("INSERT INTO negotiation_messages (negotiation_id, sender_role, message, offer_price, action) VALUES (?, ?, ?, ?, 'reject')");
    if ($msg_rej_stmt) {
        $msg_rej_stmt->bind_param("isss", $negotiation_id, $sender_role, $msg_text, $neg["offer_price"]);
        $msg_rej_stmt->execute();
        $msg_rej_stmt->close();
    }
    echo json_encode(["success" => true, "message" => "Negotiation failed", "status" => "rejected"]);
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid action"]);
?>