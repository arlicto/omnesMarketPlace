<?php
header("Content-Type: application/json");
include "db.php";
include "auth.php";

require_login();
require_role(["buyer", "admin"]);

// buyer id comes from server session
$user_id = $_SESSION["user_id"];
$product_id = $_POST["product_id"] ?? "";
$negotiation_id = $_POST["negotiation_id"] ?? "";

if ($product_id == "") {
    echo json_encode(["success" => false, "message" => "product_id is required"]);
    exit;
}

if (!is_numeric($product_id)) {
    echo json_encode(["success" => false, "message" => "Invalid product_id"]);
    exit;
}

// if checkout comes from negotiation, enforce accepted status
if ($negotiation_id != "") {
    if (!is_numeric($negotiation_id)) {
        echo json_encode(["success" => false, "message" => "Invalid negotiation_id"]);
        exit;
    }

    $n_sql = "SELECT * FROM negotiations WHERE id='$negotiation_id' LIMIT 1";
    $n_res = $conn->query($n_sql);
    if ($n_res->num_rows == 0) {
        echo json_encode(["success" => false, "message" => "Negotiation not found"]);
        exit;
    }
    $neg = $n_res->fetch_assoc();

    if ((string)$neg["buyer_id"] !== (string)$user_id) {
        echo json_encode(["success" => false, "message" => "This negotiation does not belong to you"]);
        exit;
    }
    if ((string)$neg["product_id"] !== (string)$product_id) {
        echo json_encode(["success" => false, "message" => "Negotiation product mismatch"]);
        exit;
    }
    if ($neg["status"] !== "accepted") {
        echo json_encode(["success" => false, "message" => "Negotiation is not accepted"]);
        exit;
    }
}

// check product exists
$check_product = $conn->query("SELECT id, is_sold, sale_type FROM products WHERE id='$product_id' LIMIT 1");
if ($check_product->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Product not found"]);
    exit;
}
$product_row = $check_product->fetch_assoc();
if ((int)$product_row["is_sold"] === 1) {
    echo json_encode(["success" => false, "message" => "Product already sold"]);
    exit;
}

// strict rule: negotiation products require accepted negotiation tied to this buyer
if ($product_row["sale_type"] === "negotiation") {
    if ($negotiation_id == "") {
        echo json_encode(["success" => false, "message" => "Accepted negotiation required for this product"]);
        exit;
    }
}

// avoid duplicate purchase/cart rows
$dup = $conn->query("SELECT id FROM cart WHERE user_id='$user_id' AND product_id='$product_id' LIMIT 1");
if ($dup->num_rows > 0) {
    echo json_encode(["success" => true, "message" => "Already purchased in demo cart"]);
    exit;
}

// this stores buy/cart info in database
$sql = "INSERT INTO cart (user_id, product_id) VALUES ('$user_id', '$product_id')";

if ($conn->query($sql)) {
    $final_price = "NULL";
    if ($negotiation_id != "") {
        $np = $conn->query("SELECT offer_price FROM negotiations WHERE id='$negotiation_id' LIMIT 1");
        if ($np->num_rows > 0) {
            $npr = $np->fetch_assoc();
            $final_price = "'" . $npr["offer_price"] . "'";
        }
    }

    // this stores final order record
    $order_sql = "INSERT INTO orders (user_id, product_id, final_price, negotiation_id)
                  VALUES ('$user_id', '$product_id', $final_price, " . ($negotiation_id != "" ? "'$negotiation_id'" : "NULL") . ")";
    $order_ok = $conn->query($order_sql);
    if (!$order_ok) {
        echo json_encode(["success" => false, "message" => "Order could not be created"]);
        exit;
    }

    // mark product as sold after successful purchase
    $conn->query("UPDATE products SET is_sold=1 WHERE id='$product_id'");

    // keep chosen negotiation accepted and close others
    if ($negotiation_id != "") {
        $conn->query("UPDATE negotiations SET status='accepted' WHERE id='$negotiation_id'");
        $conn->query("UPDATE negotiations SET status='rejected' WHERE product_id='$product_id' AND id!='$negotiation_id' AND status IN ('pending','countered')");
    } else {
        // direct buy closes all open negotiations for this product
        $conn->query("UPDATE negotiations SET status='rejected' WHERE product_id='$product_id' AND status IN ('pending','countered')");
    }

    echo json_encode(["success" => true, "message" => "Purchase successful (demo only). Product marked as sold."]);
} else {
    echo json_encode(["success" => false, "message" => "Could not complete purchase"]);
}
?>
