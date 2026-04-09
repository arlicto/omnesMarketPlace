<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();

$keyword = trim($_POST["keyword"] ?? "");
$message = trim($_POST["message"] ?? "");

if ($keyword === "") {
    echo json_encode(["success" => false, "message" => "Please enter what you are looking for"]);
    exit;
}

if ($message === "") {
    $message = "Looking for: " . $keyword;
}

$user_id = $_SESSION["user_id"];
$kw = $conn->real_escape_string($keyword);
$msg = $conn->real_escape_string($message);

$sql = "INSERT INTO notifications (user_id, keyword, message) VALUES ('$user_id', '$kw', '$msg')";

if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Saved. We will help you find matches."]);
} else {
    echo json_encode(["success" => false, "message" => "Could not save"]);
}
?>
