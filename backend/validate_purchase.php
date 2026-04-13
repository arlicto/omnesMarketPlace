<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();

$user_id = (int)($_SESSION["user_id"] ?? 0);

$payment_type = trim($_POST["payment_type"] ?? "");
$card_number = trim($_POST["card_number"] ?? "");
$card_name = trim($_POST["card_name"] ?? "");
$expiry = trim($_POST["expiry"] ?? "");
$security_code = trim($_POST["security_code"] ?? "");
$paypal_email = trim($_POST["paypal_email"] ?? "");

$allowed = ["Visa", "MasterCard", "American Express", "PayPal"];
if (!in_array($payment_type, $allowed, true)) {
    echo json_encode(["success" => false, "message" => "Invalid payment type"]);
    exit;
}

// Validate against details stored in DB for this logged in user
$u = $conn->query("SELECT email, name, payment_type, payment_card_number, payment_card_name, payment_expiry, payment_cvc, payment_paypal_email FROM users WHERE id=$user_id LIMIT 1");
if (!$u || $u->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}
$user = $u->fetch_assoc();

$ok = false;
if ($payment_type === "PayPal") {
    $ok = (
        (string)$user["payment_type"] === "PayPal" &&
        (string)$user["payment_paypal_email"] !== "" &&
        strtolower((string)$user["payment_paypal_email"]) === strtolower($paypal_email)
    );
} else {
    $ok = (
        (string)$user["payment_type"] === $payment_type &&
        (string)$user["payment_card_number"] === $card_number &&
        (string)$user["payment_card_name"] === $card_name &&
        (string)$user["payment_expiry"] === $expiry &&
        (string)$user["payment_cvc"] === $security_code
    );
}

if (!$ok) {
    echo json_encode(["success" => false, "message" => "Payment validation failed. Card details not found in database."]);
    exit;
}

// Simulate confirmation email (log to file, and try mail() if available)
$to = (string)$user["email"];
$subject = "Omnes MarketPlace - Purchase Confirmation";
$body = "Hello " . (string)$user["name"] . ",\n\nYour purchase has been confirmed.\n\nThank you for shopping with Omnes MarketPlace.";

$logLine = date('c') . " | to=" . $to . " | subject=" . $subject . " | body=" . str_replace("\n", " ", $body) . "\n";
@file_put_contents(__DIR__ . "/purchase_emails.log", $logLine, FILE_APPEND);

// Attempt sending via PHP mail if configured; ignore failure (project requirement is simulation)
@mail($to, $subject, $body);

echo json_encode(["success" => true, "message" => "Purchase validated and confirmation email sent (simulated)"]);
?>
