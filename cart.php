<?php

session_start();
include 'database.php';


if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;

}


if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {

    $_SESSION['cart'] = [];

}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    if (isset($_POST['update_cart'])) {

        $cart_key = $_POST['cart_key'] ?? '';

        $quantity = isset($_POST['quantity'])
            ? (int) $_POST['quantity']
            : 1;


        if (isset($_SESSION['cart'][$cart_key])) {

            if ($quantity < 1) {
                $quantity = 1;
            }


            $product_id = (int) (
                $_SESSION['cart'][$cart_key]['product_id'] ?? 0
            );


            try {

                $stmt = $pdo->prepare("
                    SELECT stock
                    FROM products
                    WHERE id = :id
                    LIMIT 1
                ");

                $stmt->execute([
                    ':id' => $product_id
                ]);

                $product = $stmt->fetch(PDO::FETCH_ASSOC);


                if ($product) {

                    $stock = (int) $product['stock'];


                    if ($stock <= 0) {

                        unset($_SESSION['cart'][$cart_key]);

                    } else {

                        if ($quantity > $stock) {
                            $quantity = $stock;
                        }

                        $_SESSION['cart'][$cart_key]['quantity']
                            = $quantity;

                    }

                } else {

                    unset($_SESSION['cart'][$cart_key]);

                }


            } catch (PDOException $e) {

                $_SESSION['cart'][$cart_key]['quantity']
                    = $quantity;

            }

        }


        header("Location: cart.php");
        exit;

    }


    if (isset($_POST['remove_item'])) {

        $cart_key = $_POST['cart_key'] ?? '';


        if (isset($_SESSION['cart'][$cart_key])) {

            unset($_SESSION['cart'][$cart_key]);

        }


        header("Location: cart.php");
        exit;

    }


    if (isset($_POST['clear_cart'])) {

        $_SESSION['cart'] = [];


        header("Location: cart.php");
        exit;

    }

}


$cart = $_SESSION['cart'];

$total_items = 0;
$grand_total = 0;


foreach ($cart as $item) {

    $quantity = isset($item['quantity'])
        ? (int) $item['quantity']
        : 1;


    $price = isset($item['price'])
        ? (float) $item['price']
        : 0;


    $subtotal = $price * $quantity;


    $total_items += $quantity;

    $grand_total += $subtotal;

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

    <title>Shopping Cart | LeatherCraft</title>


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

:root { --ink:#171411; --paper:#f7f5f0; --panel:#fff; --espresso:#211a15; --gold:#c09968; --gold-dark:#926b42; --muted:#91887d; --line:#e6e0d7; --red:#b9554b; }
* { box-sizing:border-box; }
body { margin:0; min-height:100vh; color:var(--ink); background:var(--paper); font-family:'DM Sans',Arial,sans-serif; }
.navbar { padding:20px clamp(20px,6vw,86px); border-bottom:1px solid var(--line); background:#fff!important; }
.navbar-brand { color:var(--espresso)!important; font-family:'Playfair Display',Georgia,serif; font-size:23px; font-weight:600; letter-spacing:-.04em; }
.navbar-brand i { color:var(--gold-dark); margin-right:8px; }
.nav-link { margin-left:20px; color:var(--muted)!important; font-size:11px; }
.nav-link:hover,.nav-link.active { color:var(--gold-dark)!important; }
.cart-section { max-width:1260px; margin:0 auto; padding:78px 28px 95px; }
.page-title { margin-bottom:52px; text-align:left; }
.eyebrow { margin-bottom:15px; color:var(--gold-dark); font-size:9px; font-weight:700; letter-spacing:.28em; text-transform:uppercase; }
.page-title h1 { margin:0; font-family:'Playfair Display',Georgia,serif; font-size:clamp(45px,6vw,76px); font-weight:500; letter-spacing:-.06em; line-height:.98; }
.page-title h1 span { color:var(--gold); font-style:italic; }
.page-title p { margin:22px 0 0; color:var(--muted); font-size:13px; }
.cart-item,.cart-summary,.empty-cart { border:1px solid var(--line); border-radius:5px; background:var(--panel); box-shadow:0 10px 28px rgba(33,26,21,.035); }
.cart-item { margin-bottom:16px; padding:22px; transition:border-color .2s ease,transform .2s ease; }
.cart-item:hover { border-color:#c6a77e; transform:translateY(-2px); }
.cart-product { display:flex; align-items:center; gap:18px; }
.cart-image { display:block; flex-shrink:0; width:110px; height:105px; padding:7px; border:1px solid var(--line); border-radius:4px; background:#fff; object-fit:contain; }
.cart-product-info h4 { margin:0 0 8px; font-family:'Playfair Display',Georgia,serif; font-size:20px; font-weight:600; }
.cart-category { margin-bottom:7px; color:var(--gold-dark); font-size:9px; font-weight:700; letter-spacing:.15em; text-transform:uppercase; }
.product-option { margin-top:4px; color:var(--muted); font-size:11px; }
.product-option strong { color:var(--ink); }
.remove-btn,.clear-btn { padding:0; color:var(--muted); border:0; background:transparent; font-size:11px; cursor:pointer; }
.remove-btn { margin-top:12px; }
.remove-btn:hover,.clear-btn:hover { color:var(--red); }
.item-price,.total-price { color:var(--gold-dark); font-size:16px; font-weight:700; }
.item-subtotal { color:var(--ink); font-size:16px; font-weight:700; }
.quantity-form { display:flex; align-items:center; gap:8px; }
.quantity-input { width:60px; padding:8px; color:var(--ink); border:1px solid var(--line); border-radius:4px; background:#fff; font-size:11px; text-align:center; }
.quantity-input:focus { outline:none; border-color:var(--gold); box-shadow:0 0 0 3px rgba(192,153,104,.13); }
.update-btn { padding:8px 11px; color:var(--gold-dark); border:1px solid #d6c4ac; border-radius:4px; background:#fff; font-size:10px; font-weight:700; cursor:pointer; }
.update-btn:hover { color:#fff; border-color:var(--espresso); background:var(--espresso); }
.cart-summary { position:sticky; top:24px; padding:27px; }
.cart-summary h3 { margin:0 0 22px; font-family:'Playfair Display',Georgia,serif; font-size:24px; font-weight:500; }
.summary-row { display:flex; justify-content:space-between; gap:18px; padding:12px 0; color:var(--muted); border-bottom:1px solid var(--line); font-size:12px; }
.summary-row strong { color:var(--ink); font-size:11px; text-align:right; }
.total-row { display:flex; justify-content:space-between; padding-top:20px; margin-top:8px; font-size:20px; font-weight:700; }
.checkout-btn,.continue-btn { display:block; width:100%; padding:13px; border-radius:4px; text-align:center; text-decoration:none; transition:.2s ease; }
.checkout-btn { margin-top:25px; color:#fff; border:1px solid var(--espresso); background:var(--espresso); font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
.checkout-btn:hover { color:#fff; background:var(--gold-dark); }
.continue-btn { margin-top:11px; color:var(--gold-dark); border:1px solid #d6c4ac; font-size:11px; }
.continue-btn:hover { color:#fff; border-color:var(--espresso); background:var(--espresso); }
.clear-btn { display:block; width:100%; margin-top:20px; }
.empty-cart { padding:80px 30px; text-align:center; }
.empty-cart i { margin-bottom:20px; color:var(--gold); font-size:56px; }
.empty-cart h2 { margin:0 0 10px; font-family:'Playfair Display',Georgia,serif; font-size:30px; font-weight:500; }
.empty-cart p { margin:0 0 25px; color:var(--muted); font-size:13px; }
.empty-cart-btn { max-width:240px; margin:0 auto; }
.navbar-toggler { border-color:var(--line); }
.navbar-toggler:focus { box-shadow:0 0 0 3px rgba(192,153,104,.16); }
@media (max-width:900px) { .cart-section { padding:58px 22px 70px; } }
@media (max-width:700px) { .navbar { padding:16px 20px; } .nav-link { margin-left:0; } .page-title { margin-bottom:35px; } .cart-item { padding:16px; } .cart-product { align-items:flex-start; gap:12px; } .cart-image { width:82px; height:82px; } .cart-product-info h4 { font-size:17px; } .cart-summary { position:static; } }
@media (max-width:480px) { .cart-section { padding:45px 16px 60px; } .page-title h1 { font-size:45px; } .cart-product { flex-direction:column; } .cart-image { width:100%; height:150px; } .quantity-form { justify-content:flex-start!important; } }
</style></head>


<body>


<nav class="navbar navbar-expand-lg navbar-dark">

    <div class="container-fluid">


        <a
            class="navbar-brand"
            href="index.php"
        >

            <i class="fa-solid fa-shoe-prints"></i>

            LeatherCraft

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="navbarMenu"
        >

            <ul class="navbar-nav ms-auto">


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="index.php"
                    >

                        Home

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="products.php"
                    >

                        Shop

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="index.php#categories"
                    >

                        Categories

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="cart.php"
                    >

                        <i class="fa-solid fa-cart-shopping"></i>

                        Cart

                        <?php if ($total_items > 0): ?>

                            (<?= $total_items ?>)

                        <?php endif; ?>

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="logout.php"
                    >

                        Logout

                    </a>

                </li>


            </ul>

        </div>

    </div>

</nav>

<section class="cart-section">


    <div class="page-title">

        <div class="eyebrow">
            LeatherCraft
        </div>

        <h1>

            Your <span>Shopping Cart</span>

        </h1>

        <p>

            Review your selected leather shoes before checkout.

        </p>

    </div>



    <?php if (empty($cart)): ?>


        <div class="empty-cart">


            <i class="fa-solid fa-cart-shopping"></i>


            <h2>

                Your Cart is Empty

            </h2>


            <p>

                You haven't added any leather shoes to your cart yet.

            </p>


            <a
                href="products.php"
                class="checkout-btn empty-cart-btn"
            >

                <i class="fa-solid fa-bag-shopping"></i>

                Browse Shoes

            </a>


        </div>


    <?php else: ?>


        <div class="row g-4">

            <div class="col-lg-8">


                <?php foreach ($cart as $cart_key => $item): ?>


                    <?php

                    $product_name = $item['name'] ?? 'Leather Shoes';

                    $price = isset($item['price'])
                        ? (float) $item['price']
                        : 0;

                    $quantity = isset($item['quantity'])
                        ? (int) $item['quantity']
                        : 1;

                    $size = $item['size'] ?? 'Not selected';

                    $color = $item['color'] ?? 'Not selected';

                    $category = $item['category']
                        ?? 'Leather Shoes';


                    $image = trim($item['image'] ?? '');


                    if ($image === '') {

                        $image_path = 'images/no-image.jpg';

                    } else {

                        $image = str_replace('\\', '/', $image);

                        $image = ltrim($image, '/');


                        if (
                            strpos($image, 'images/') !== 0 &&
                            strpos($image, 'http://') !== 0 &&
                            strpos($image, 'https://') !== 0
                        ) {

                            $image_path = 'images/' . $image;

                        } else {

                            $image_path = $image;

                        }

                    }


                    $subtotal = $price * $quantity;

                    ?>


                    <div class="cart-item">


                        <div class="row align-items-center g-3">

                            <div class="col-md-5">


                                <div class="cart-product">


                                    <img
                                        src="<?= htmlspecialchars($image_path) ?>"
                                        alt="<?= htmlspecialchars($product_name) ?>"
                                        class="cart-image"
                                        onerror="this.onerror=null; this.src='images/no-image.jpg';"
                                    >


                                    <div class="cart-product-info">


                                        <div class="cart-category">

                                            <?= htmlspecialchars($category) ?>

                                        </div>


                                        <h4>

                                            <?= htmlspecialchars($product_name) ?>

                                        </h4>


                                        <div class="product-option">

                                            <strong>
                                                Size:
                                            </strong>

                                            <?= htmlspecialchars($size) ?>

                                        </div>


                                        <div class="product-option">

                                            <strong>
                                                Color:
                                            </strong>

                                            <?= htmlspecialchars($color) ?>

                                        </div>


                                        <form
                                            method="POST"
                                            class="remove-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="cart_key"
                                                value="<?= htmlspecialchars($cart_key) ?>"
                                            >


                                            <button
                                                type="submit"
                                                name="remove_item"
                                                class="remove-btn"
                                            >

                                                <i class="fa-solid fa-trash"></i>

                                                Remove

                                            </button>

                                        </form>


                                    </div>


                                </div>


                            </div>

                            <div class="col-md-2 text-center">


                                <div class="item-price">

                                    Rs.
                                    <?= number_format($price, 2) ?>

                                </div>


                            </div>

                            <div class="col-md-3">


                                <form
                                    method="POST"
                                    class="quantity-form justify-content-center"
                                >


                                    <input
                                        type="hidden"
                                        name="cart_key"
                                        value="<?= htmlspecialchars($cart_key) ?>"
                                    >


                                    <input
                                        type="number"
                                        name="quantity"
                                        value="<?= $quantity ?>"
                                        min="1"
                                        class="quantity-input"
                                        required
                                    >


                                    <button
                                        type="submit"
                                        name="update_cart"
                                        class="update-btn"
                                    >

                                        Update

                                    </button>


                                </form>


                            </div>

                            <div class="col-md-2 text-md-end text-center">


                                <div class="item-subtotal">

                                    Rs.
                                    <?= number_format($subtotal, 2) ?>

                                </div>


                            </div>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


            <div class="col-lg-4">


                <div class="cart-summary">


                    <h3>

                        Order Summary

                    </h3>


                    <div class="summary-row">

                        <span>
                            Items
                        </span>

                        <strong>

                            <?= $total_items ?>

                        </strong>

                    </div>



                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <strong>

                            Rs.

                            <?= number_format(
                                $grand_total,
                                2
                            ) ?>

                        </strong>

                    </div>



                    <div class="summary-row">

                        <span>
                            Delivery
                        </span>

                        <strong>
                            Calculated at checkout
                        </strong>

                    </div>


                    <div class="total-row">

                        <span>
                            Total
                        </span>

                        <span class="total-price">

                            Rs.

                            <?= number_format(
                                $grand_total,
                                2
                            ) ?>

                        </span>

                    </div>


                    <a
                        href="checkout.php"
                        class="checkout-btn"
                    >

                        <i class="fa-solid fa-credit-card"></i>

                        Proceed to Checkout

                    </a>


                    <a
                        href="products.php"
                        class="continue-btn"
                    >

                        <i class="fa-solid fa-arrow-left"></i>

                        Continue Shopping

                    </a>

                    <form method="POST">


                        <button
                            type="submit"
                            name="clear_cart"
                            class="clear-btn"
                            onclick="return confirm('Are you sure you want to clear your cart?');"
                        >

                            <i class="fa-solid fa-trash"></i>

                            Clear Cart

                        </button>


                    </form>


                </div>


            </div>


        </div>


    <?php endif; ?>


</section>

<footer>

    <div>

        <i class="fa-solid fa-shoe-prints"></i>

        LeatherCraft

    </div>


    <p>

        Premium Men's Leather Shoes Collection.

    </p>


    <div>

        © <?= date('Y') ?>

        LeatherCraft. All Rights Reserved.

    </div>

</footer>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>