<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();

$user_id = (int)($_SESSION["user_id"] ?? 0);
$res = $conn->query("SELECT id, name, email, role, username, profile_photo, background_image, address, payment_brand, payment_last4, offer_clause_accepted, first_name, last_name, address_line1, address_line2, city, postal_code, country, phone, payment_type, payment_card_number, payment_card_name, payment_expiry, payment_cvc, payment_paypal_email FROM users WHERE id=$user_id LIMIT 1");
if (!$res || $res->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$user = $res->fetch_assoc();

echo json_encode([
    "success" => true,
    "user" => $user
]);
?>
