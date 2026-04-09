<?php
header("Content-Type: application/json");
include "db.php";

// this fetches negotiation with messages
$negotiation_id = $_GET["negotiation_id"] ?? "";

if ($negotiation_id == "") {
    echo json_encode(["success" => false, "message" => "negotiation_id is required"]);
    exit;
}

$n_stmt = $conn->prepare("SELECT * FROM negotiations WHERE id = ? LIMIT 1");
if (!$n_stmt) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$n_stmt->bind_param("i", $negotiation_id);
$n_stmt->execute();
$n_res = $n_stmt->get_result();
if ($n_res->num_rows == 0) {
    $n_stmt->close();
    echo json_encode(["success" => false, "message" => "Negotiation not found"]);
    exit;
}
$negotiation = $n_res->fetch_assoc();
$n_stmt->close();

$m_stmt = $conn->prepare("SELECT * FROM negotiation_messages WHERE negotiation_id = ? ORDER BY id ASC");
if (!$m_stmt) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$m_stmt->bind_param("i", $negotiation_id);
$m_stmt->execute();
$m_res = $m_stmt->get_result();
$messages = [];
while ($row = $m_res->fetch_assoc()) {
    $messages[] = $row;
}
$m_stmt->close();

echo json_encode([
    "success" => true,
    "negotiation" => $negotiation,
    "messages" => $messages
]);
?>