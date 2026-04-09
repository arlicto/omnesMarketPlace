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

    $n_stmt = $conn->prepare("SELECT * FROM negotiations WHERE id=? LIMIT 1");
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
    $neg = $n_res->fetch_assoc();
    $n_stmt->close();

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
$check_stmt = $conn->prepare("SELECT id, is_sold, sale_type FROM products WHERE id=? LIMIT 1");
if (!$check_stmt) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$check_stmt->bind_param("i", $product_id);
$check_stmt->execute();
$check_product = $check_stmt->get_result();
if ($check_product->num_rows == 0) {
    $check_stmt->close();
    echo json_encode(["success" => false, "message" => "Product not found"]);
    exit;
}
$product_row = $check_product->fetch_assoc();
$check_stmt->close();
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
$dup_stmt = $conn->prepare("SELECT id FROM cart WHERE user_id=? AND product_id=? LIMIT 1");
if (!$dup_stmt) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$dup_stmt->bind_param("ii", $user_id, $product_id);
$dup_stmt->execute();
$dup = $dup_stmt->get_result();
if ($dup->num_rows > 0) {
    $dup_stmt->close();
    echo json_encode(["success" => true, "message" => "Already purchased in demo cart"]);
    exit;
}
$dup_stmt->close();

// this stores buy/cart info in database
$cart_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id) VALUES (?, ?)");
if (!$cart_stmt) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}
$cart_stmt->bind_param("ii", $user_id, $product_id);

if ($cart_stmt->execute()) {
    $cart_stmt->close();

    $final_price = null;
    if ($negotiation_id != "") {
        $np_stmt = $conn->prepare("SELECT offer_price FROM negotiations WHERE id=? LIMIT 1");
        if ($np_stmt) {
            $np_stmt->bind_param("i", $negotiation_id);
            $np_stmt->execute();
            $np = $np_stmt->get_result();
            if ($np->num_rows > 0) {
                $npr = $np->fetch_assoc();
                $final_price = $npr["offer_price"];
            }
            $np_stmt->close();
        }
    }

    // this stores final order record
    $order_stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, final_price, negotiation_id) VALUES (?, ?, ?, ?)");
    if (!$order_stmt) {
        echo json_encode(["success" => false, "message" => "Order could not be created"]);
        exit;
    }
    $neg_id_val = ($negotiation_id != "") ? $negotiation_id : null;
    $order_stmt->bind_param("iisi", $user_id, $product_id, $final_price, $neg_id_val);
    $order_ok = $order_stmt->execute();
    $order_stmt->close();
    if (!$order_ok) {
        echo json_encode(["success" => false, "message" => "Order could not be created"]);
        exit;
    }

    // mark product as sold after successful purchase
    $sold_stmt = $conn->prepare("UPDATE products SET is_sold=1 WHERE id=?");
    if ($sold_stmt) {
        $sold_stmt->bind_param("i", $product_id);
        $sold_stmt->execute();
        $sold_stmt->close();
    }

    // keep chosen negotiation accepted and close others
    if ($negotiation_id != "") {
        $acc_stmt = $conn->prepare("UPDATE negotiations SET status='accepted' WHERE id=?");
        if ($acc_stmt) {
            $acc_stmt->bind_param("i", $negotiation_id);
            $acc_stmt->execute();
            $acc_stmt->close();
        }
        $rej_stmt = $conn->prepare("UPDATE negotiations SET status='rejected' WHERE product_id=? AND id!=? AND status IN ('pending','countered')");
        if ($rej_stmt) {
            $rej_stmt->bind_param("ii", $product_id, $negotiation_id);
            $rej_stmt->execute();
            $rej_stmt->close();
        }
    } else {
        // direct buy closes all open negotiations for this product
        $rej_all_stmt = $conn->prepare("UPDATE negotiations SET status='rejected' WHERE product_id=? AND status IN ('pending','countered')");
        if ($rej_all_stmt) {
            $rej_all_stmt->bind_param("i", $product_id);
            $rej_all_stmt->execute();
            $rej_all_stmt->close();
        }
    }

    echo json_encode(["success" => true, "message" => "Purchase successful (demo only). Product marked as sold."]);
} else {
    $cart_stmt->close();
    echo json_encode(["success" => false, "message" => "Could not complete purchase"]);
}
?>