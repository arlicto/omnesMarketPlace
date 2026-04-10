<?php
header("Content-Type: application/json");
include "db.php";

// this fetches negotiation details for product
$product_id = $_GET["product_id"] ?? "";
$buyer_id = $_GET["buyer_id"] ?? "";

if ($product_id == "") {
    echo json_encode(["success" => false, "message" => "product_id is required"]);
    exit;
}

$sql = "SELECT * FROM negotiations WHERE product_id='$product_id'";
if ($buyer_id != "") {
    $sql .= " AND buyer_id='$buyer_id'";
}
$sql .= " ORDER BY id DESC LIMIT 1";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo json_encode(["success" => true, "message" => "No negotiation yet", "negotiation" => null]);
    exit;
}

$row = $result->fetch_assoc();
echo json_encode(["success" => true, "negotiation" => $row]);
?>
