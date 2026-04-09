<?php
header("Content-Type: application/json");
include "db.php";

// this fetches negotiation with messages
$negotiation_id = $_GET["negotiation_id"] ?? "";

if ($negotiation_id == "") {
    echo json_encode(["success" => false, "message" => "negotiation_id is required"]);
    exit;
}

$n_sql = "SELECT * FROM negotiations WHERE id='$negotiation_id' LIMIT 1";
$n_res = $conn->query($n_sql);
if ($n_res->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Negotiation not found"]);
    exit;
}
$negotiation = $n_res->fetch_assoc();

$m_sql = "SELECT * FROM negotiation_messages WHERE negotiation_id='$negotiation_id' ORDER BY id ASC";
$m_res = $conn->query($m_sql);
$messages = [];
while ($row = $m_res->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode([
    "success" => true,
    "negotiation" => $negotiation,
    "messages" => $messages
]);
?>
