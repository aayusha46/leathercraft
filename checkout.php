<?php

session_start();
require_once 'database.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


if (
    !isset($_SESSION['cart']) ||
    !is_array($_SESSION['cart']) ||
    count($_SESSION['cart']) === 0
) {
    header("Location: cart.php");
    exit;
}


try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            email,
            phone,
            address
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $_SESSION['user_id']
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Could not load customer information: " . $e->getMessage());
}


if (!$user) {

    session_destroy();

    header("Location: login.php");
    exit;
}


$cart_items = [];
$subtotal = 0;
$total_items = 0;


foreach ($_SESSION['cart'] as $key => $cart_item) {

    $product_id = 0;
    $quantity = 1;


    if (is_array($cart_item)) {

        $product_id = (int) (
            $cart_item['product_id']
            ?? $cart_item['id']
            ?? $key
        );

        $quantity = (int) (
            $cart_item['quantity']
            ?? 1
        );

    } else {

        $product_id = (int) $key;
        $quantity = (int) $cart_item;
    }


    if ($product_id <= 0) {
        continue;
    }


    if ($quantity <= 0) {
        $quantity = 1;
    }

    try {

        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                description,
                price,
                stock,
                image
            FROM products
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $product_id
        ]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {

        die("Could not load product: " . $e->getMessage());
    }


    if (!$product) {
        continue;
    }

    if ((int)$product['stock'] <= 0) {
        continue;
    }


    if ($quantity > (int)$product['stock']) {

        $quantity = (int)$product['stock'];
    }


    $item_total =
        (float)$product['price'] * $quantity;


    $subtotal += $item_total;

    $total_items += $quantity;


    $product['quantity'] = $quantity;

    $product['item_total'] = $item_total;


    if (is_array($cart_item)) {

        $product['size'] =
            $cart_item['size']
            ?? 'N/A';

        $product['color'] =
            $cart_item['color']
            ?? 'Default';

    } else {

        $product['size'] = 'N/A';
        $product['color'] = 'Default';
    }


    $cart_items[] = $product;
}


if (count($cart_items) === 0) {

    header("Location: cart.php");
    exit;
}


$shipping = 0;

$grand_total =
    $subtotal + $shipping;


$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $name =
        trim($_POST['name'] ?? '');

    $email =
        trim($_POST['email'] ?? '');

    $phone =
        trim($_POST['phone'] ?? '');

    $address =
        trim($_POST['address'] ?? '');

    $payment_method =
        trim($_POST['payment_method'] ?? '');


    if ($name === '') {

        $error =
            "Please enter your name.";

    } elseif ($email === '') {

        $error =
            "Please enter your email.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error =
            "Please enter a valid email address.";

    } elseif ($phone === '') {

        $error =
            "Please enter your phone number.";

    } elseif ($address === '') {

        $error =
            "Please enter your delivery address.";

    } elseif ($payment_method === '') {

        $error =
            "Please select a payment method.";
    }


    if ($error === '') {

        try {


            $pdo->beginTransaction();



            foreach ($cart_items as $item) {

                $stmt = $pdo->prepare("
                    SELECT
                        id,
                        price,
                        stock
                    FROM products
                    WHERE id = ?
                    FOR UPDATE
                ");

                $stmt->execute([
                    $item['id']
                ]);

                $fresh_product =
                    $stmt->fetch(PDO::FETCH_ASSOC);


                if (!$fresh_product) {

                    throw new Exception(
                        "A product in your cart is no longer available."
                    );
                }


                if (
                    (int)$fresh_product['stock']
                    <
                    (int)$item['quantity']
                ) {

                    throw new Exception(
                        "Not enough stock for "
                        . $item['name']
                        . "."
                    );
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO orders
                (
                    user_id,
                    total_amount,
                    payment_method,
                    payment_status,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


            $stmt->execute([

                $_SESSION['user_id'],

                $grand_total,

                $payment_method,

                'Pending',

                'Pending'
            ]);

            $order_id =
                $pdo->lastInsertId();

            foreach ($cart_items as $item) {

                $stmt = $pdo->prepare("
                    INSERT INTO order_items
                    (
                        order_id,
                        product_id,
                        quantity,
                        price
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");


                $stmt->execute([

                    $order_id,

                    $item['id'],

                    $item['quantity'],

                    $item['price']
                ]);



                $stmt = $pdo->prepare("
                    UPDATE products
                    SET stock = stock - ?
                    WHERE id = ?
                ");


                $stmt->execute([

                    $item['quantity'],

                    $item['id']
                ]);
            }



            $stmt = $pdo->prepare("
                UPDATE users
                SET
                    name = ?,
                    email = ?,
                    phone = ?,
                    address = ?
                WHERE id = ?
            ");


            $stmt->execute([

                $name,

                $email,

                $phone,

                $address,

                $_SESSION['user_id']
            ]);


            $pdo->commit();


            $_SESSION['cart'] = [];



            $_SESSION['last_order_id'] =
                $order_id;


           

            header(
                "Location: order_success.php"
            );

            exit;


        } catch (Exception $e) {

            $error = $e->getMessage();

            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }


            $error =
                $e->getMessage();
        }
    }
}



function checkoutImage($image)
{
    $image = trim($image ?? '');

    if ($image === '') {
        return 'images/no-image.jpg';
    }

    $image = str_replace('\\', '/', $image);

    $image = ltrim($image, '/');


    
    if (strpos($image, 'images/') === 0) {
        return $image;
    }


    if (
        strpos($image, 'http://') === 0 ||
        strpos($image, 'https://') === 0
    ) {
        return $image;
    }


    return 'images/' . $image;
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Checkout | LeatherCraft
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap');
:root { --ink:#171411; --paper:#f7f5f0; --panel:#fff; --espresso:#211a15; --gold:#c09968; --gold-dark:#926b42; --muted:#91887d; --line:#e6e0d7; }
* { box-sizing:border-box; }
body { margin:0; min-height:100vh; color:var(--ink); background:var(--paper); font-family:'DM Sans',Arial,sans-serif; }
.navbar { padding:20px clamp(20px,6vw,86px); border-bottom:1px solid var(--line); background:#fff; }
.brand { color:var(--espresso); font-family:'Playfair Display',Georgia,serif; font-size:23px; font-weight:600; letter-spacing:-.04em; text-decoration:none; }
.brand i { color:var(--gold-dark); margin-right:8px; }
.brand:hover { color:var(--gold-dark); }
.nav-link { margin-left:22px; color:var(--muted)!important; font-size:11px; text-decoration:none; }
.nav-link:hover { color:var(--gold-dark)!important; }
.checkout-container { max-width:1220px; margin:0 auto; padding:76px 28px 95px; }
.page-title { margin-bottom:46px; }
.page-title::before { display:block; margin-bottom:15px; color:var(--gold-dark); content:'LEATHERCRAFT CHECKOUT'; font-size:9px; font-weight:700; letter-spacing:.28em; }
.page-title h1 { margin:0; color:var(--ink); font-family:'Playfair Display',Georgia,serif; font-size:clamp(44px,6vw,70px); font-weight:500; letter-spacing:-.06em; line-height:.98; }
.page-title h1 span { color:var(--gold); font-style:italic; }
.page-title p { margin:19px 0 0; color:var(--muted); font-size:13px; }
.checkout-card { margin-bottom:22px; padding:28px; border:1px solid var(--line); border-radius:5px; background:var(--panel); box-shadow:0 10px 28px rgba(33,26,21,.035); }
.checkout-card h3 { margin:0 0 24px; color:var(--ink); font-family:'Playfair Display',Georgia,serif; font-size:23px; font-weight:500; }
.checkout-card h3 i { margin-right:9px; color:var(--gold-dark); font-size:17px; }
.form-label { margin-bottom:8px; color:var(--ink); font-size:11px; font-weight:700; }
.form-control,.form-select { min-height:44px; padding:10px 12px; color:var(--ink); border:1px solid var(--line); border-radius:4px; background:#fff; font-size:12px; }
textarea.form-control { min-height:100px; resize:vertical; }
.form-control:focus,.form-select:focus { color:var(--ink); border-color:var(--gold); box-shadow:0 0 0 3px rgba(192,153,104,.13); background:#fff; }
.form-control::placeholder { color:#aaa39a; }
.form-select option { color:var(--ink); background:#fff; }
.error-box { margin-bottom:24px; padding:14px 16px; color:#8b4437; border:1px solid #e4c4ba; border-radius:5px; background:#fff6f3; font-size:12px; }
.checkout-product { display:flex; align-items:center; gap:14px; padding:15px 0; border-bottom:1px solid var(--line); }
.checkout-product:last-child { border-bottom:0; }
.product-image { flex:0 0 auto; width:72px; height:72px; padding:5px; border:1px solid var(--line); border-radius:4px; background:#fff; object-fit:contain; }
.product-name { color:var(--ink); font-size:12px; font-weight:700; }
.product-quantity { margin-top:5px; color:var(--muted); font-size:10px; }
.product-price { margin-left:auto; color:var(--gold-dark); font-size:13px; font-weight:700; white-space:nowrap; }
.summary-row { display:flex; justify-content:space-between; gap:20px; padding:12px 0; color:var(--muted); border-bottom:1px solid var(--line); font-size:12px; }
.summary-row strong { color:var(--ink); }
.summary-total { display:flex; justify-content:space-between; margin-top:9px; padding-top:19px; font-size:20px; font-weight:700; }
.summary-total span:last-child { color:var(--gold-dark); }
.place-order-btn { width:100%; margin-top:24px; padding:14px; color:#fff; border:1px solid var(--espresso); border-radius:4px; background:var(--espresso); font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; cursor:pointer; }
.place-order-btn:hover { background:var(--gold-dark); }
.secure-text { margin-top:13px; color:var(--muted); font-size:10px; text-align:center; }
@media (max-width:768px) { .navbar { padding:16px 20px; } .nav-link { margin-left:10px; } .checkout-container { padding:52px 18px 70px; } .checkout-card { padding:21px; } .page-title h1 { font-size:48px; } .product-price { font-size:12px; } }
@media (max-width:480px) { .checkout-container { padding:42px 15px 60px; } .page-title h1 { font-size:42px; } .navbar .container-fluid { align-items:flex-start; flex-direction:column; gap:13px; } .navbar .container-fluid > div { width:100%; } .nav-link { margin-left:0; margin-right:17px; } }
</style>

</head>


<body>

<nav class="navbar">

    <div class="container-fluid">

        <a
            href="index.php"
            class="brand"
        >

            <i class="fa-solid fa-shoe-prints"></i>

            LeatherCraft

        </a>


        <div>

            <a
                href="products.php"
                class="nav-link d-inline-block"
            >
                Shop
            </a>


            <a
                href="cart.php"
                class="nav-link d-inline-block"
            >

                <i class="fa-solid fa-cart-shopping"></i>

                Cart

            </a>

        </div>

    </div>

</nav>

<div class="checkout-container">


    <!-- PAGE TITLE -->

    <div class="page-title">

        <h1>

            Secure
            <span>Checkout</span>

        </h1>


        <p>
            Complete your information and place your leather shoe order.
        </p>

    </div>


    <?php if ($error !== ''): ?>

        <div class="error-box">

            <i class="fa-solid fa-circle-exclamation"></i>

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="checkout.php"
    >

        <div class="row g-4">

            <div class="col-lg-7">

                <div class="checkout-card">

                    <h3>

                        <i class="fa-solid fa-user"></i>

                        Delivery Information

                    </h3>

                    <div class="mb-3">

                        <label class="form-label">
                            Full Name
                        </label>


                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $_POST['name']
                                ?? $user['name']
                                ?? ''
                            ) ?>"
                            placeholder="Enter your full name"
                            required
                        >

                    </div>


                

                    <div class="mb-3">

                        <label class="form-label">
                            Email Address
                        </label>


                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $_POST['email']
                                ?? $user['email']
                                ?? ''
                            ) ?>"
                            placeholder="Enter your email"
                            required
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Phone Number
                        </label>


                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $_POST['phone']
                                ?? $user['phone']
                                ?? ''
                            ) ?>"
                            placeholder="Enter your phone number"
                            required
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Delivery Address
                        </label>


                        <textarea
                            name="address"
                            class="form-control"
                            rows="4"
                            placeholder="Enter your complete delivery address"
                            required
                        ><?= htmlspecialchars(
                            $_POST['address']
                            ?? $user['address']
                            ?? ''
                        ) ?></textarea>

                    </div>

                </div>


                <div class="checkout-card">

                    <h3>

                        <i class="fa-solid fa-credit-card"></i>

                        Payment Method

                    </h3>


                    <div class="mb-3">

                        <label class="form-label">
                            Select Payment Method
                        </label>


                        <select
                            name="payment_method"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Select payment method
                            </option>


                            <option
                                value="Cash on Delivery"
                                <?= (
                                    ($_POST['payment_method'] ?? '')
                                    === 'Cash on Delivery'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Cash on Delivery
                            </option>


                            <option
                                value="eSewa"
                                <?= (
                                    ($_POST['payment_method'] ?? '')
                                    === 'eSewa'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                eSewa
                            </option>


                            <option
                                value="Khalti"
                                <?= (
                                    ($_POST['payment_method'] ?? '')
                                    === 'Khalti'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Khalti
                            </option>

                        </select>

                    </div>

                </div>

            </div>

            <div class="col-lg-5">

                <div class="checkout-card">

                    <h3>

                        <i class="fa-solid fa-bag-shopping"></i>

                        Your Order

                    </h3>

                    <?php foreach ($cart_items as $item): ?>

                        <div class="checkout-product">


                            <img
                                src="<?= htmlspecialchars(
                                    checkoutImage(
                                        $item['image'] ?? ''
                                    )
                                ) ?>"
                                class="product-image"
                                alt="<?= htmlspecialchars(
                                    $item['name']
                                ) ?>"
                                onerror="
                                    this.onerror=null;
                                    this.src='images/no-image.jpg';
                                "
                            >


                            <div>

                                <div class="product-name">

                                    <?= htmlspecialchars(
                                        $item['name']
                                    ) ?>

                                </div>


                                <div class="product-quantity">

                                    Quantity:
                                    <?= (int)$item['quantity'] ?>


                                    <?php if (
                                        !empty($item['size']) &&
                                        $item['size'] !== 'N/A'
                                    ): ?>

                                        <br>

                                        Size:
                                        <?= htmlspecialchars(
                                            $item['size']
                                        ) ?>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty($item['color']) &&
                                        $item['color'] !== 'Default'
                                    ): ?>

                                        <br>

                                        Color:
                                        <?= htmlspecialchars(
                                            $item['color']
                                        ) ?>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <div class="product-price">

                                Rs.
                                <?= number_format(
                                    $item['item_total'],
                                    2
                                ) ?>

                            </div>


                        </div>

                    <?php endforeach; ?>


                    <!-- SUBTOTAL -->

                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>


                        <span>

                            Rs.
                            <?= number_format(
                                $subtotal,
                                2
                            ) ?>

                        </span>

                    </div>
                    <div class="summary-row">

                        <span>
                            Delivery
                        </span>


                        <span>
                            Free
                        </span>

                    </div>


                    <div class="summary-total">

                        <span>
                            Total
                        </span>


                        <span>

                            Rs.
                            <?= number_format(
                                $grand_total,
                                2
                            ) ?>

                        </span>

                    </div>
                    <button
                        type="submit"
                        class="place-order-btn"
                    >

                        <i class="fa-solid fa-lock"></i>

                        Place Order

                    </button>


                    <div class="secure-text">

                        <i class="fa-solid fa-shield-halved"></i>

                        Your order information is securely processed.

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>


</body>

</html>