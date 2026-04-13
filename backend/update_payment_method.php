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

if ($payment_type === "PayPal") {
    if ($paypal_email === "" || !filter_var($paypal_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Valid PayPal email is required"]);
        exit;
    }
} else {
    if ($card_number === "" || $card_name === "" || $expiry === "" || $security_code === "") {
        echo json_encode(["success" => false, "message" => "Please fill all card fields"]);
        exit;
    }

    $digits = preg_replace('/\D+/', '', $card_number);
    if (strlen($digits) < 12 || strlen($digits) > 19) {
        echo json_encode(["success" => false, "message" => "Invalid card number"]);
        exit;
    }

    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry)) {
        echo json_encode(["success" => false, "message" => "Expiration date must be in MM/YY"]);
        exit;
    }

    if (!preg_match('/^[0-9]{3,4}$/', $security_code)) {
        echo json_encode(["success" => false, "message" => "Security code must be 3 or 4 digits"]);
        exit;
    }
}

$pt = $conn->real_escape_string($payment_type);
$cn = $conn->real_escape_string($card_number);
$nm = $conn->real_escape_string($card_name);
$ex = $conn->real_escape_string($expiry);
$sc = $conn->real_escape_string($security_code);
$pp = $conn->real_escape_string($paypal_email);

$sql = "UPDATE users SET payment_type='$pt', payment_card_number='$cn', payment_card_name='$nm', payment_expiry='$ex', payment_cvc='$sc', payment_paypal_email='$pp' WHERE id=$user_id";
if ($conn->query($sql)) {
    $brand = $payment_type;
    $last4 = "";
    if ($payment_type !== "PayPal") {
        $digits2 = preg_replace('/\D+/', '', $card_number);
        $last4 = substr($digits2, -4);
    }
    $b = $conn->real_escape_string($brand);
    $l = $conn->real_escape_string($last4);
    $conn->query("UPDATE users SET payment_brand='$b', payment_last4='$l' WHERE id=$user_id");
    echo json_encode(["success" => true, "message" => "Payment method saved"]);
} else {
    echo json_encode(["success" => false, "message" => "Could not save payment method"]);
}
?>
