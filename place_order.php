<?php

session_start();
include 'database.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit;
}

if (
    !isset($_SESSION['cart']) ||
    !is_array($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {
    header("Location: products.php");
    exit;
}
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$city = trim($_POST['city'] ?? '');
$address = trim($_POST['address'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');

if (
    $name === '' ||
    $email === '' ||
    $phone === '' ||
    $city === '' ||
    $address === '' ||
    $payment_method === ''
) {
    die("Please fill in all required fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Please enter a valid email address.");
}

$allowed_payment_methods = [
    'Cash on Delivery',
    'eSewa',
    'Khalti'
];

if (!in_array($payment_method, $allowed_payment_methods, true)) {
    die("Invalid payment method.");
}

$subtotal = 0;


/*
   We will store verified products here.
   This prevents trusting price information
   coming from the browser/session.
*/

$order_items = [];


try {

    $pdo->beginTransaction();

    foreach ($_SESSION['cart'] as $cart_item) {

        $product_id = isset($cart_item['product_id'])
            ? (int)$cart_item['product_id']
            : 0;

        $quantity = isset($cart_item['quantity'])
            ? (int)$cart_item['quantity']
            : 0;

        $size = trim($cart_item['size'] ?? '');

        if ($product_id <= 0 || $quantity <= 0) {

            throw new Exception(
                "Invalid product information."
            );
        }

        $stmt = $pdo->prepare("
            SELECT
                p.*,
                c.name AS category_name
            FROM products p
            LEFT JOIN categories c
                ON p.category_id = c.id
            WHERE p.id = :id
            FOR UPDATE
        ");

        $stmt->execute([
            ':id' => $product_id
        ]);

        $product = $stmt->fetch();

        if (!$product) {

            throw new Exception(
                "One of the selected products no longer exists."
            );
        }
        $stock = (int)$product['stock'];

        if ($stock <= 0) {

            throw new Exception(
                $product['name'] . " is currently out of stock."
            );
        }


        if ($quantity > $stock) {

            throw new Exception(
                "Only " .
                $stock .
                " unit(s) of " .
                $product['name'] .
                " are available."
            );
        }
        $price = (float)$product['price'];

        $item_total = $price * $quantity;

        $subtotal += $item_total;
        $order_items[] = [

            'product_id' => $product_id,

            'product_name' => $product['name'],

            'price' => $price,

            'quantity' => $quantity,

            'size' => $size,

            'item_total' => $item_total

        ];
    }
    $shipping = 150;

    $total = $subtotal + $shipping;
    $user_id = null;

    if (isset($_SESSION['user_id'])) {
        $user_id = (int)$_SESSION['user_id'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO orders
        (
            user_id,
            customer_name,
            email,
            phone,
            city,
            address,
            payment_method,
            subtotal,
            shipping,
            total,
            status,
            created_at
        )
        VALUES
        (
            :user_id,
            :customer_name,
            :email,
            :phone,
            :city,
            :address,
            :payment_method,
            :subtotal,
            :shipping,
            :total,
            :status,
            NOW()
        )
    ");


    $stmt->execute([

        ':user_id' => $user_id,

        ':customer_name' => $name,

        ':email' => $email,

        ':phone' => $phone,

        ':city' => $city,

        ':address' => $address,

        ':payment_method' => $payment_method,

        ':subtotal' => $subtotal,

        ':shipping' => $shipping,

        ':total' => $total,

        ':status' => 'Pending'

    ]);

    $order_id = $pdo->lastInsertId();

    $item_stmt = $pdo->prepare("
        INSERT INTO order_items
        (
            order_id,
            product_id,
            product_name,
            price,
            quantity,
            size,
            total
        )
        VALUES
        (
            :order_id,
            :product_id,
            :product_name,
            :price,
            :quantity,
            :size,
            :total
        )
    ");

    $stock_stmt = $pdo->prepare("
        UPDATE products
        SET stock = stock - :quantity
        WHERE id = :product_id
    ");


    foreach ($order_items as $item) {

        $item_stmt->execute([

            ':order_id' => $order_id,

            ':product_id' => $item['product_id'],

            ':product_name' => $item['product_name'],

            ':price' => $item['price'],

            ':quantity' => $item['quantity'],

            ':size' => $item['size'],

            ':total' => $item['item_total']

        ]);
        $stock_stmt->execute([

            ':quantity' => $item['quantity'],

            ':product_id' => $item['product_id']

        ]);
    }
    $pdo->commit();

    $_SESSION['cart'] = [];

    $_SESSION['last_order_id'] = $order_id;

    header(
        "Location: order_success.php?order_id=" .
        urlencode($order_id)
    );

    exit;


} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    echo "<!DOCTYPE html>";

    echo "<html>";

    echo "<head>";

    echo "<title>Order Error</title>";

    echo "<style>

        body {
            background:#0b0b0b;
            color:white;
            font-family:Arial;
            text-align:center;
            padding:100px 20px;
        }

        .box {
            max-width:600px;
            margin:auto;
            background:#151515;
            border:1px solid #333;
            padding:40px;
            border-radius:10px;
        }

        h1 {
            color:#dc3545;
        }

        a {
            display:inline-block;
            margin-top:20px;
            padding:12px 25px;
            background:#d4af37;
            color:#000;
            text-decoration:none;
            border-radius:5px;
            font-weight:bold;
        }

    </style>";

    echo "</head>";

    echo "<body>";

    echo "<div class='box'>";

    echo "<h1>Order Could Not Be Placed</h1>";

    echo "<p>" .
        htmlspecialchars($e->getMessage()) .
        "</p>";

    echo "<a href='cart.php'>Return to Cart</a>";

    echo "</div>";

    echo "</body>";

    echo "</html>";

    exit;
}

?>