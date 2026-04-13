<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();

$user_id = (int)($_SESSION["user_id"] ?? 0);

$first_name = trim($_POST["first_name"] ?? "");
$last_name = trim($_POST["last_name"] ?? "");
$address_line1 = trim($_POST["address_line1"] ?? "");
$address_line2 = trim($_POST["address_line2"] ?? "");
$city = trim($_POST["city"] ?? "");
$postal_code = trim($_POST["postal_code"] ?? "");
$country = trim($_POST["country"] ?? "");
$phone = trim($_POST["phone"] ?? "");

if ($first_name === "" || $last_name === "" || $address_line1 === "" || $city === "" || $postal_code === "" || $country === "" || $phone === "") {
    echo json_encode(["success" => false, "message" => "Please fill all required delivery fields"]);
    exit;
}

$fn = $conn->real_escape_string($first_name);
$ln = $conn->real_escape_string($last_name);
$a1 = $conn->real_escape_string($address_line1);
$a2 = $conn->real_escape_string($address_line2);
$ct = $conn->real_escape_string($city);
$pc = $conn->real_escape_string($postal_code);
$co = $conn->real_escape_string($country);
$ph = $conn->real_escape_string($phone);

$sql = "UPDATE users SET first_name='$fn', last_name='$ln', address_line1='$a1', address_line2='$a2', city='$ct', postal_code='$pc', country='$co', phone='$ph' WHERE id=$user_id";
if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Delivery information saved"]);
} else {
    echo json_encode(["success" => false, "message" => "Could not save delivery information"]);
}
?>
