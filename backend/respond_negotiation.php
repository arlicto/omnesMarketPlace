<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["seller", "admin"]);

// this reads seller response data
$negotiation_id = $_POST["negotiation_id"] ?? "";
$action = $_POST["action"] ?? ""; // accept / reject / counter
$counter_price = $_POST["counter_price"] ?? "";

if ($negotiation_id == "" || $action == "") {
    echo json_encode(["success" => false, "message" => "Please send negotiation_id and action"]);
    exit;
}

if (!is_numeric($negotiation_id)) {
    echo json_encode(["success" => false, "message" => "Invalid negotiation_id"]);
    exit;
}

// this gets current negotiation
$get_sql = "SELECT * FROM negotiations WHERE id='$negotiation_id' LIMIT 1";
$get_result = $conn->query($get_sql);

if ($get_result->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Negotiation not found"]);
    exit;
}

$neg = $get_result->fetch_assoc();

// only assigned seller (or admin) can respond
$session_user_id = $_SESSION["user_id"];
$session_role = $_SESSION["user_role"];
if ($session_role !== "admin" && (string)$neg["seller_id"] !== (string)$session_user_id) {
    echo json_encode(["success" => false, "message" => "Not allowed for this negotiation"]);
    exit;
}

if ($neg["status"] == "accepted" || $neg["status"] == "rejected") {
    echo json_encode(["success" => false, "message" => "Negotiation already closed"]);
    exit;
}

if ($action == "accept") {
    $sql = "UPDATE negotiations SET status='accepted' WHERE id='$negotiation_id'";
    $conn->query($sql);
    $conn->query("UPDATE products SET is_sold=1 WHERE id='" . $neg["product_id"] . "'");
    echo json_encode(["success" => true, "message" => "Deal completed", "status" => "accepted", "round" => (int)$neg["round"]]);
    exit;
}

if ($action == "reject") {
    $sql = "UPDATE negotiations SET status='rejected' WHERE id='$negotiation_id'";
    $conn->query($sql);
    echo json_encode(["success" => true, "message" => "Negotiation failed", "status" => "rejected", "round" => (int)$neg["round"]]);
    exit;
}

if ($action == "counter") {
    if ($counter_price == "") {
        echo json_encode(["success" => false, "message" => "Please enter counter price"]);
        exit;
    }
    if (!is_numeric($counter_price)) {
        echo json_encode(["success" => false, "message" => "Invalid counter price"]);
        exit;
    }

    // this checks max 5 rounds
    $new_round = (int)$neg["round"] + 1;
    if ($new_round > 5) {
        echo json_encode(["success" => false, "message" => "Max 5 rounds allowed"]);
        exit;
    }

    $sql = "UPDATE negotiations
            SET offer_price='$counter_price', status='countered', round='$new_round'
            WHERE id='$negotiation_id'";
    $conn->query($sql);

    echo json_encode([
        "success" => true,
        "message" => "Counter offer sent",
        "status" => "countered",
        "round" => $new_round,
        "offer_price" => $counter_price
    ]);
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid action"]);
?>
