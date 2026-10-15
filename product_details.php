<?php

session_start();
require_once 'database.php';

$is_logged_in = isset($_SESSION['user_id']);

$user_id = $is_logged_in
    ? (int)$_SESSION['user_id']
    : 0;
$product_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($product_id <= 0) {
    header("Location: products.php");
    exit;
}

try {

    $stmt = $pdo->prepare("
        SELECT
            p.*,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.id
        WHERE p.id = ?
        LIMIT 1
    ");

    $stmt->execute([$product_id]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Unable to load product.");

}


if (!$product) {

    header("Location: products.php");
    exit;

}
$product_name = $product['name'] ?? 'Product';

$category_name =
    $product['category_name']
    ?? 'Leather Shoes';

$description =
    $product['description']
    ?? 'Premium leather shoes crafted for style and comfort.';

$price =
    (float)($product['price'] ?? 0);

$stock =
    (int)($product['stock'] ?? 0);


$image = trim(
    (string)($product['image'] ?? '')
);

if ($image === '') {

    $image = 'images/default-shoe.jpg';

} elseif (
    strpos($image, 'images/') === 0 ||
    strpos($image, 'images\\') === 0
) {

    $image = str_replace(
        '\\',
        '/',
        $image
    );

} else {

    $image = 'images/' . $image;

}

$sizes = [];

if (
    isset($product['size']) &&
    trim((string)$product['size']) !== ''
) {

    $sizes = array_filter(
        array_map(
            'trim',
            explode(
                ',',
                $product['size']
            )
        )
    );

}

$colors = [];

if (
    isset($product['color']) &&
    trim((string)$product['color']) !== ''
) {

    $colors = array_filter(
        array_map(
            'trim',
            explode(
                ',',
                $product['color']
            )
        )
    );

}
$cart_count = 0;

if (
    isset($_SESSION['cart']) &&
    is_array($_SESSION['cart'])
) {

    foreach ($_SESSION['cart'] as $item) {

        if (is_array($item)) {

            $cart_count +=
                (int)($item['quantity'] ?? 1);

        }

    }

}
$notification_count = 0;

if ($is_logged_in) {

    try {

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM notifications
            WHERE user_id = ?
            AND is_read = 0
        ");

        $stmt->execute([
            $user_id
        ]);

        $notification_count =
            (int)$stmt->fetchColumn();

    } catch (PDOException $e) {

        $notification_count = 0;

    }

}

$has_purchased = false;

if ($is_logged_in) {

    try {

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM order_items oi

            INNER JOIN orders o
                ON oi.order_id = o.id

            WHERE o.user_id = ?
            AND oi.product_id = ?
            AND o.status != 'Cancelled'
        ");

        $stmt->execute([
            $user_id,
            $product_id
        ]);

        $has_purchased =
            ((int)$stmt->fetchColumn() > 0);

    } catch (PDOException $e) {

        $has_purchased = false;

    }

}
$already_reviewed = false;

if (
    $is_logged_in &&
    $has_purchased
) {

    try {

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM reviews
            WHERE user_id = ?
            AND product_id = ?
        ");

        $stmt->execute([
            $user_id,
            $product_id
        ]);

        $already_reviewed =
            ((int)$stmt->fetchColumn() > 0);

    } catch (PDOException $e) {

        $already_reviewed = false;

    }

}
$reviews = [];

try {

    $stmt = $pdo->prepare("
        SELECT
            r.*,
            u.name AS customer_name
        FROM reviews r
        LEFT JOIN users u
            ON r.user_id = u.id
        WHERE r.product_id = ?
        ORDER BY r.id DESC
    ");

    $stmt->execute([
        $product_id
    ]);

    $reviews =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $reviews = [];

}

$average_rating = 0;

$review_count =
    count($reviews);

if ($review_count > 0) {

    $rating_total = 0;

    foreach ($reviews as $review) {

        $rating_total +=
            (int)($review['rating'] ?? 0);

    }

    $average_rating =
        $rating_total / $review_count;

}

$rounded_rating =
    (int)round($average_rating);

$error_message = '';

$error = $_GET['error'] ?? '';

if ($error === 'size') {

    $error_message =
        'Please select a valid shoe size.';

}

if ($error === 'color') {

    $error_message =
        'Please select a valid shoe color.';

}

if ($error === 'outofstock') {

    $error_message =
        'Sorry, this product is currently out of stock.';

}

function displayStars($rating)
{

    $rating =
        (int)$rating;

    $html = '';

    for (
        $i = 1;
        $i <= 5;
        $i++
    ) {

        if ($i <= $rating) {

            $html .=
                '<i class="fa-solid fa-star"></i>';

        } else {

            $html .=
                '<i class="fa-regular fa-star"></i>';

        }

    }

    return $html;

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
    <?= htmlspecialchars($product_name) ?>
    | LeatherCraft
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>
<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<!-- GOOGLE FONT -->

<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
    rel="stylesheet"
>


<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {

    font-family: 'DM Sans', sans-serif;

    background: #fff;

    color: #17120e;

}

a {
    text-decoration: none;
}

:root {

    --black: #17120e;

    --brown: #5b3926;

    --brown-dark: #3b2418;

    --brown-light: #8b5e3c;

    --gold: #c59b45;

    --gold-light: #e0bd70;

    --cream: #f8f4ee;

    --border: #e7e0d8;

    --muted: #77716b;

}

.topbar {

    background: var(--black);

    color: #fff;

    padding: 9px 6%;

    font-size: 12px;

}

.topbar-inner {

    max-width: 1400px;

    margin: auto;

    display: flex;

    justify-content: space-between;

    align-items: center;

}

.topbar-left i {

    color: var(--gold);

    margin-right: 7px;

}

.topbar-right {

    display: flex;

    gap: 18px;

    color: #ddd;

}

.navbar-custom {

    background: #fff;

    border-bottom: 1px solid var(--border);

    position: sticky;

    top: 0;

    z-index: 1000;

}

.nav-inner {

    max-width: 1400px;

    min-height: 82px;

    margin: auto;

    padding: 0 6%;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 30px;

}

.logo {

    display: flex;

    align-items: center;

    gap: 11px;

    color: var(--black);

}

.logo:hover {

    color: var(--black);

}

.logo-icon {

    width: 43px;

    height: 43px;

    border-radius: 50%;

    background: var(--brown);

    color: var(--gold-light);

    display: flex;

    align-items: center;

    justify-content: center;

    transform: rotate(-10deg);

}

.logo-name {

    font-family: 'Playfair Display', serif;

    font-size: 27px;

    font-weight: 700;

    line-height: 1;

}

.logo-name span {

    color: var(--brown-light);

}

.logo-small {

    color: #958d84;

    font-size: 8px;

    letter-spacing: 3px;

    margin-top: 5px;

}


.nav-links {

    display: flex;

    align-items: center;

    gap: 30px;

}

.nav-links a {

    color: #4d4742;

    font-size: 13px;

    font-weight: 600;

    position: relative;

    padding: 31px 0;

}

.nav-links a:hover,

.nav-links a.active {

    color: var(--brown);

}

.nav-links a.active::after {

    content: '';

    position: absolute;

    left: 0;

    right: 0;

    bottom: 20px;

    height: 2px;

    background: var(--gold);

}

.nav-actions {

    display: flex;

    align-items: center;

    gap: 18px;

}

.nav-icon {

    color: var(--black);

    font-size: 17px;

    position: relative;

}

.nav-icon:hover {

    color: var(--brown-light);

}

.nav-badge {

    position: absolute;

    top: -9px;

    right: -9px;

    width: 18px;

    height: 18px;

    border-radius: 50%;

    background: var(--gold);

    color: #fff;

    font-size: 9px;

    font-weight: 700;

    display: flex;

    align-items: center;

    justify-content: center;

}

.login-btn {

    border: 1px solid var(--brown);

    color: var(--brown);

    padding: 9px 18px;

    border-radius: 4px;

    font-size: 12px;

    font-weight: 700;

}

.login-btn:hover {

    background: var(--brown);

    color: #fff;

}
.breadcrumb-section {

    background: var(--cream);

    padding: 15px 6%;

    border-bottom: 1px solid var(--border);

}

.breadcrumb-inner {

    max-width: 1300px;

    margin: auto;

    font-size: 11px;

    color: var(--muted);

}

.breadcrumb-inner a {

    color: var(--brown);

    font-weight: 600;

}

.breadcrumb-inner i {

    margin: 0 8px;

    font-size: 8px;

}

.product-section {

    padding: 70px 5% 80px;

}

.product-container {

    max-width: 1250px;

    margin: auto;

}

.product-image-box {

    background: #f6f3ef;

    border: 1px solid var(--border);

    border-radius: 8px;

    min-height: 570px;

    display: flex;

    align-items: center;

    justify-content: center;

    position: relative;

    overflow: hidden;

}

.product-image-box img {

    width: 100%;

    height: 540px;

    object-fit: contain;

    padding: 30px;

    transition: .4s;

}

.product-image-box:hover img {

    transform: scale(1.04);

}

.image-label {

    position: absolute;

    top: 18px;

    left: 18px;

    background: var(--brown);

    color: #fff;

    padding: 7px 12px;

    border-radius: 3px;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: 1px;

    z-index: 2;

}

.product-info {

    padding: 15px 10px 15px 35px;

}

.product-category {

    color: var(--gold);

    font-size: 10px;

    font-weight: 700;

    letter-spacing: 2px;

    text-transform: uppercase;

    margin-bottom: 9px;

}

.product-title {

    font-family: 'Playfair Display', serif;

    font-size: clamp(34px, 4vw, 50px);

    line-height: 1.1;

    color: var(--black);

    margin-bottom: 15px;

}

.product-description {

    color: var(--muted);

    font-size: 13px;

    line-height: 1.8;

    margin-bottom: 18px;
}

.product-rating {

    display: flex;

    align-items: center;

    gap: 3px;

    margin-bottom: 18px;

}

.product-rating i {

    color: var(--gold);

    font-size: 13px;

}

.rating-number {

    color: var(--black);

    font-size: 12px;

    font-weight: 700;

    margin-left: 7px;

}

.rating-reviews {

    color: var(--muted);

    font-size: 11px;

}

.product-price {

    font-size: 29px;

    font-weight: 700;

    color: var(--brown);

    padding: 17px 0;

    border-top: 1px solid var(--border);

    border-bottom: 1px solid var(--border);

    margin-bottom: 22px;

}

.stock-box {

    margin-bottom: 23px;

}

.stock {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    font-size: 11px;

    font-weight: 700;

}

.stock.available {

    color: #55714d;

}

.stock.low {

    color: #a16a27;

}

.stock.out {

    color: #9b3c30;

}

.option-title {

    font-size: 11px;

    font-weight: 700;

    color: var(--black);

    margin-bottom: 9px;

}

.option-title span {

    color: var(--muted);

    font-weight: 500;

}

.option-group {

    margin-bottom: 22px;

}

.size-options {

    display: flex;

    flex-wrap: wrap;

    gap: 7px;

}

.size-option {

    position: relative;

}

.size-option input {

    position: absolute;

    opacity: 0;

}

.size-option label {

    min-width: 47px;

    height: 39px;

    padding: 0 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    border: 1px solid #d9d0c7;

    background: #fff;

    color: #4f4842;

    border-radius: 4px;

    cursor: pointer;

    font-size: 11px;

    font-weight: 600;

    transition: .2s;

}

.size-option label:hover {

    border-color: var(--gold);

    color: var(--brown);

}

.size-option input:checked + label {

    background: var(--brown);

    color: #fff;

    border-color: var(--brown);

}

.color-options {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

}

.color-option {

    position: relative;

}

.color-option input {

    position: absolute;

    opacity: 0;

}

.color-option label {

    display: block;

    border: 1px solid #d9d0c7;

    background: #fff;

    padding: 8px 15px;

    border-radius: 4px;

    color: #4f4842;

    font-size: 10px;

    cursor: pointer;

    text-transform: capitalize;

    transition: .2s;

}

.color-option label:hover {

    border-color: var(--gold);

}

.color-option input:checked + label {

    background: var(--brown);

    color: #fff;

    border-color: var(--brown);

}

.quantity-row {

    display: flex;

    align-items: center;

    gap: 15px;

    margin-bottom: 20px;

}

.quantity-label {

    font-size: 11px;

    font-weight: 700;

}

.quantity-control {

    display: flex;

    align-items: center;

    border: 1px solid #d9d0c7;

    border-radius: 4px;

    overflow: hidden;

}

.quantity-control button {

    width: 35px;

    height: 39px;

    border: none;

    background: var(--cream);

    color: var(--brown);

    font-weight: 700;

    cursor: pointer;

}

.quantity-control input {

    width: 48px;

    height: 39px;

    border: none;

    border-left: 1px solid #ddd4ca;

    border-right: 1px solid #ddd4ca;

    text-align: center;

    outline: none;

    font-size: 12px;

}

.add-cart-btn {

    width: 100%;

    min-height: 50px;

    border: none;

    border-radius: 4px;

    background: var(--brown);

    color: #fff;

    font-size: 12px;

    font-weight: 700;

    letter-spacing: .4px;

    transition: .25s;

}

.add-cart-btn:hover {

    background: var(--gold);

}

.add-cart-btn i {

    margin-right: 7px;

}

.out-stock {

    width: 100%;

    padding: 15px;

    text-align: center;

    background: #eeeae5;

    color: #888078;

    border-radius: 4px;

    font-size: 12px;

    font-weight: 700;

}

.login-message {

    margin-top: 12px;

    text-align: center;

    font-size: 11px;

    color: var(--muted);

}

.login-message a {

    color: var(--brown);

    font-weight: 700;

}

.error-box {

    max-width: 1250px;

    margin: 25px auto 0;

    background: #fff3f0;

    border: 1px solid #e5b7ad;

    color: #8f382b;

    padding: 13px 17px;

    border-radius: 5px;

    font-size: 11px;

}

.feature-boxes {

    border-top: 1px solid var(--border);

    margin-top: 35px;

    padding-top: 25px;

    display: grid;

    grid-template-columns: repeat(3, 1fr);

    gap: 10px;

}

.feature-box {

    background: var(--cream);

    padding: 15px 12px;

    text-align: center;

    border-radius: 5px;

}

.feature-box i {

    color: var(--gold);

    font-size: 18px;

    margin-bottom: 7px;

}

.feature-box strong {

    display: block;

    color: var(--black);

    font-size: 10px;

    margin-bottom: 3px;

}

.feature-box span {

    color: var(--muted);

    font-size: 8px;

}

.reviews-section {

    background: var(--cream);

    padding: 70px 5% 80px;

}

.reviews-container {

    max-width: 1100px;

    margin: auto;

}

.section-label {

    color: var(--gold);

    font-size: 10px;

    font-weight: 700;

    letter-spacing: 3px;

    margin-bottom: 8px;

}

.section-title {

    font-family: 'Playfair Display', serif;

    font-size: 34px;

    color: var(--black);

    margin-bottom: 35px;

}
.review-form {

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 7px;

    padding: 25px;

    margin-bottom: 35px;

}

.review-form h4 {

    font-family: 'Playfair Display', serif;

    font-size: 20px;

    margin-bottom: 17px;

}

.review-form select,

.review-form textarea {

    width: 100%;

    border: 1px solid #ddd4ca;

    border-radius: 4px;

    padding: 10px 12px;

    font-size: 11px;

    outline: none;

    margin-bottom: 12px;

}

.review-form textarea {

    min-height: 100px;

    resize: vertical;

}

.review-submit {

    background: var(--brown);

    color: #fff;

    border: none;

    padding: 10px 19px;

    border-radius: 4px;

    font-size: 10px;

    font-weight: 700;

}

.review-submit:hover {

    background: var(--gold);

}

.review-card {

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 7px;

    padding: 22px;

    margin-bottom: 12px;

}

.review-top {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 9px;

}

.reviewer {

    font-size: 12px;

    font-weight: 700;

    color: var(--black);

}

.review-date {

    color: #aaa;

    font-size: 9px;

}

.review-stars {

    margin-bottom: 8px;

}

.review-stars i {

    color: var(--gold);

    font-size: 10px;

}

.review-text {

    color: var(--muted);

    font-size: 11px;

    line-height: 1.7;

}

.no-reviews {

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 7px;

    padding: 40px;

    text-align: center;

    color: var(--muted);

    font-size: 11px;

}

.footer {

    background: var(--black);

    color: #fff;

    padding: 60px 6% 20px;

}

.footer-inner {

    max-width: 1300px;

    margin: auto;

    display: grid;

    grid-template-columns: 2fr 1fr 1fr 1.3fr;

    gap: 45px;

}

.footer-brand {

    font-family: 'Playfair Display', serif;

    font-size: 27px;

    font-weight: 700;

    margin-bottom: 12px;

}

.footer-brand span {

    color: var(--gold-light);

}

.footer-description {

    color: #aaa;

    font-size: 11px;

    line-height: 1.8;

    max-width: 320px;

}

.footer-column h4 {

    color: var(--gold-light);

    font-size: 12px;

    margin-bottom: 17px;

}

.footer-column a {

    display: block;

    color: #aaa;

    font-size: 11px;

    margin-bottom: 10px;

}

.footer-column a:hover {

    color: #fff;

}

.footer-bottom {

    max-width: 1300px;

    margin: 35px auto 0;

    padding-top: 17px;

    border-top: 1px solid #302b27;

    text-align: center;

    color: #777;

    font-size: 10px;

}

@media(max-width:991px) {

    .topbar-right {

        display: none;

    }

    .nav-links {

        display: none;

    }

    .product-info {

        padding: 35px 5px 10px;

    }

    .product-image-box {

        min-height: 450px;

    }

    .product-image-box img {

        height: 420px;

    }

    .footer-inner {

        grid-template-columns: 1fr 1fr;

    }

}


@media(max-width:600px) {

    .nav-inner {

        min-height: 70px;

    }

    .logo-name {

        font-size: 22px;

    }

    .logo-icon {

        width: 37px;

        height: 37px;

    }

    .nav-actions {

        gap: 11px;

    }

    .login-btn {

        display: none;

    }

    .product-section {

        padding: 40px 5% 60px;

    }

    .product-image-box {

        min-height: 330px;

    }

    .product-image-box img {

        height: 320px;

        padding: 15px;

    }

    .product-title {

        font-size: 35px;

    }

    .feature-boxes {

        grid-template-columns: 1fr;

    }

    .footer-inner {

        grid-template-columns: 1fr;

    }

}

</style>

</head>


<body>

<div class="topbar">

    <div class="topbar-inner">

        <div class="topbar-left">

            <i class="fa-solid fa-truck-fast"></i>

            Free shipping on orders above Rs. 3,000

        </div>

        <div class="topbar-right">

            <span>
                Premium Leather Shoes
            </span>

            <span>|</span>

            <span>
                Kathmandu, Nepal
            </span>

        </div>

    </div>

</div>
<nav class="navbar-custom">

    <div class="nav-inner">


        <!-- LOGO -->

        <a
            href="index.php"
            class="logo"
        >

            <div class="logo-icon">

                <i class="fa-solid fa-shoe-prints"></i>

            </div>

            <div>

                <div class="logo-name">

                    Leather<span>Craft</span>

                </div>

                <div class="logo-small">

                    STEP INTO QUALITY

                </div>

            </div>

        </a>


        <!-- NAVIGATION -->

        <div class="nav-links">

            <a href="index.php">

                Home

            </a>

            <a
                href="products.php"
                class="active"
            >

                Shop

            </a>

            <a href="index.php#categories">

                Categories

            </a>

            <a href="index.php#about">

                About

            </a>

            <a href="index.php#footer">

                Contact

            </a>

        </div>


        <!-- ACTIONS -->

        <div class="nav-actions">


            <!-- SEARCH -->

            <a
                href="products.php#shop"
                class="nav-icon"
                title="Search"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

            </a>


            <?php if ($is_logged_in): ?>


                <!-- PROFILE -->

                <a
                    href="profile.php"
                    class="nav-icon"
                    title="My Account"
                >

                    <i class="fa-regular fa-user"></i>

                </a>


                <!-- NOTIFICATIONS -->

                <a
                    href="notifications.php"
                    class="nav-icon"
                    title="Notifications"
                >

                    <i class="fa-regular fa-bell"></i>


                    <?php if (
                        $notification_count > 0
                    ): ?>

                        <span class="nav-badge">

                            <?= $notification_count ?>

                        </span>

                    <?php endif; ?>

                </a>


            <?php endif; ?>


            <!-- CART -->

            <a
                href="cart.php"
                class="nav-icon"
                title="Shopping Cart"
            >

                <i class="fa-solid fa-bag-shopping"></i>


                <?php if (
                    $cart_count > 0
                ): ?>

                    <span class="nav-badge">

                        <?= $cart_count ?>

                    </span>

                <?php endif; ?>

            </a>


            <!-- LOGIN / LOGOUT -->

            <?php if ($is_logged_in): ?>

                <a
                    href="logout.php"
                    class="login-btn"
                >

                    Logout

                </a>

            <?php else: ?>

                <a
                    href="login.php"
                    class="login-btn"
                >

                    Login / Register

                </a>

            <?php endif; ?>


        </div>

    </div>

</nav>

<div class="breadcrumb-section">

    <div class="breadcrumb-inner">

        <a href="index.php">

            Home

        </a>

        <i class="fa-solid fa-chevron-right"></i>

        <a href="products.php">

            Shop

        </a>

        <i class="fa-solid fa-chevron-right"></i>

        <?= htmlspecialchars(
            $product_name
        ) ?>

    </div>

</div>

<?php if ($error_message !== ''): ?>

    <div class="container">

        <div class="error-box">

            <i
                class="fa-solid fa-circle-exclamation me-2"
            ></i>

            <?= htmlspecialchars(
                $error_message
            ) ?>

        </div>

    </div>

<?php endif; ?>

<section class="product-section">

    <div class="product-container">

        <div class="row g-4 align-items-center">

            <div class="col-lg-6">

                <div class="product-image-box">


                    <?php if ($stock <= 0): ?>

                        <div
                            class="image-label"
                            style="background:#8f382b;"
                        >

                            OUT OF STOCK

                        </div>

                    <?php else: ?>

                        <div class="image-label">

                            PREMIUM LEATHER

                        </div>

                    <?php endif; ?>


                    <img
                        src="<?= htmlspecialchars(
                            $image
                        ) ?>"
                        alt="<?= htmlspecialchars(
                            $product_name
                        ) ?>"
                        onerror="
                            this.onerror=null;
                            this.src='images/default-shoe.jpg';
                        "
                    >

                </div>

            </div>

            <div class="col-lg-6">

                <div class="product-info">


                    <!-- CATEGORY -->

                    <div class="product-category">

                        <?= htmlspecialchars(
                            $category_name
                        ) ?>

                    </div>


                    <!-- NAME -->

                    <h1 class="product-title">

                        <?= htmlspecialchars(
                            $product_name
                        ) ?>

                    </h1>


                    <!-- DESCRIPTION -->

                    <div class="product-description">

                        <?= nl2br(
                            htmlspecialchars(
                                $description
                            )
                        ) ?>

                    </div>


                    <!-- RATING -->

                    <div class="product-rating">

                        <?= displayStars(
                            $rounded_rating
                        ) ?>


                        <span class="rating-number">

                            <?= number_format(
                                $average_rating,
                                1
                            ) ?>

                        </span>


                        <span class="rating-reviews">

                            (
                            <?= $review_count ?>
                            review<?= $review_count !== 1
                                ? 's'
                                : '' ?>
                            )

                        </span>

                    </div>


                    <!-- PRICE -->

                    <div class="product-price">

                        Rs.
                        <?= number_format(
                            $price,
                            0
                        ) ?>

                    </div>


                    <!-- STOCK -->

                    <div class="stock-box">

                        <?php if ($stock <= 0): ?>

                            <div class="stock out">

                                <i
                                    class="fa-solid fa-circle-xmark"
                                ></i>

                                Out of Stock

                            </div>

                        <?php elseif ($stock <= 5): ?>

                            <div class="stock low">

                                <i
                                    class="fa-solid fa-triangle-exclamation"
                                ></i>

                                Only
                                <?= $stock ?>
                                left in stock

                            </div>

                        <?php else: ?>

                            <div class="stock available">

                                <i
                                    class="fa-solid fa-circle-check"
                                ></i>

                                <?= $stock ?>
                                available in stock

                            </div>

                        <?php endif; ?>

                    </div>


                    <?php if ($stock > 0): ?>


                        <form
                            action="add_to_cart.php"
                            method="POST"
                            id="cartForm"
                        >


                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= $product_id ?>"
                            >


                            <!-- SIZE -->

                            <?php if (!empty($sizes)): ?>

                                <div class="option-group">

                                    <div class="option-title">

                                        Select Size

                                        <span>
                                            — Required
                                        </span>

                                    </div>


                                    <div class="size-options">


                                        <?php foreach (
                                            $sizes
                                            as $index => $size
                                        ): ?>

                                            <div
                                                class="size-option"
                                            >

                                                <input
                                                    type="radio"
                                                    name="size"
                                                    id="size_<?= $index ?>"
                                                    value="<?= htmlspecialchars(
                                                        $size
                                                    ) ?>"
                                                    <?= $index === 0
                                                        ? ''
                                                        : '' ?>
                                                    required
                                                >

                                                <label
                                                    for="size_<?= $index ?>"
                                                >

                                                    <?= htmlspecialchars(
                                                        $size
                                                    ) ?>

                                                </label>

                                            </div>

                                        <?php endforeach; ?>


                                    </div>

                                </div>

                            <?php else: ?>

                                <input
                                    type="hidden"
                                    name="size"
                                    value="Standard"
                                >

                            <?php endif; ?>


                            <!-- COLOR -->

                            <?php if (!empty($colors)): ?>

                                <div class="option-group">

                                    <div class="option-title">

                                        Select Color

                                        <span>
                                            — Required
                                        </span>

                                    </div>


                                    <div class="color-options">


                                        <?php foreach (
                                            $colors
                                            as $index => $color
                                        ): ?>

                                            <div
                                                class="color-option"
                                            >

                                                <input
                                                    type="radio"
                                                    name="color"
                                                    id="color_<?= $index ?>"
                                                    value="<?= htmlspecialchars(
                                                        $color
                                                    ) ?>"
                                                    required
                                                >

                                                <label
                                                    for="color_<?= $index ?>"
                                                >

                                                    <?= htmlspecialchars(
                                                        $color
                                                    ) ?>

                                                </label>

                                            </div>

                                        <?php endforeach; ?>


                                    </div>

                                </div>

                            <?php else: ?>

                                <input
                                    type="hidden"
                                    name="color"
                                    value="Standard"
                                >

                            <?php endif; ?>


                            <!-- QUANTITY -->

                            <div class="quantity-row">

                                <span class="quantity-label">

                                    Quantity

                                </span>


                                <div class="quantity-control">

                                    <button
                                        type="button"
                                        onclick="changeQuantity(-1)"
                                    >

                                        −

                                    </button>


                                    <input
                                        type="number"
                                        name="quantity"
                                        id="quantity"
                                        value="1"
                                        min="1"
                                        max="<?= $stock ?>"
                                        required
                                    >


                                    <button
                                        type="button"
                                        onclick="changeQuantity(1)"
                                    >

                                        +

                                    </button>

                                </div>

                            </div>


                            <!-- ADD TO CART -->

                            <?php if ($is_logged_in): ?>

                                <button
                                    type="submit"
                                    class="add-cart-btn"
                                >

                                    <i
                                        class="fa-solid fa-bag-shopping"
                                    ></i>

                                    Add to Cart

                                </button>


                            <?php else: ?>

                                <a
                                    href="login.php"
                                    class="add-cart-btn d-flex align-items-center justify-content-center"
                                >

                                    <i
                                        class="fa-solid fa-right-to-bracket"
                                    ></i>

                                    Login to Add to Cart

                                </a>

                            <?php endif; ?>


                        </form>


                    <?php else: ?>


                        <div class="out-stock">

                            <i
                                class="fa-solid fa-ban me-2"
                            ></i>

                            This product is currently unavailable.

                        </div>


                    <?php endif; ?>


                    <div class="feature-boxes">


                        <div class="feature-box">

                            <i
                                class="fa-solid fa-shield-halved"
                            ></i>

                            <strong>

                                Quality

                            </strong>

                            <span>

                                Premium leather

                            </span>

                        </div>


                        <div class="feature-box">

                            <i
                                class="fa-solid fa-truck-fast"
                            ></i>

                            <strong>

                                Delivery

                            </strong>

                            <span>

                                Fast delivery

                            </span>

                        </div>


                        <div class="feature-box">

                            <i
                                class="fa-solid fa-headset"
                            ></i>

                            <strong>

                                Support

                            </strong>

                            <span>

                                Customer care

                            </span>

                        </div>


                    </div>


                </div>

            </div>


        </div>

    </div>

</section>

<section class="reviews-section">

    <div class="reviews-container">


        <div class="section-label">

            CUSTOMER FEEDBACK

        </div>

        <div class="section-title">

            Reviews

        </div>

        <?php if ($is_logged_in && $has_purchased): ?>


            <?php if (!$already_reviewed): ?>

                <div class="review-form">

                    <h4>

                        Share Your Experience

                    </h4>


                    <form
                        action="review.php"
                        method="POST"
                    >

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= $product_id ?>"
                        >


                        <select
                            name="rating"
                            required
                        >

                            <option value="">

                                Select Rating

                            </option>

                            <option value="5">

                                ★★★★★ — Excellent

                            </option>

                            <option value="4">

                                ★★★★☆ — Very Good

                            </option>

                            <option value="3">

                                ★★★☆☆ — Good

                            </option>

                            <option value="2">

                                ★★☆☆☆ — Fair

                            </option>

                            <option value="1">

                                ★☆☆☆☆ — Poor

                            </option>

                        </select>


                        <textarea
                            name="review"
                            placeholder="Write your review..."
                            required
                        ></textarea>


                        <button
                            type="submit"
                            class="review-submit"
                        >

                            <i
                                class="fa-regular fa-paper-plane me-1"
                            ></i>

                            Submit Review

                        </button>

                    </form>

                </div>


            <?php else: ?>

                <div class="review-form">

                    <i
                        class="fa-solid fa-circle-check"
                        style="color:#55714d;"
                    ></i>

                    <strong>

                        You have already reviewed this product.

                    </strong>

                </div>

            <?php endif; ?>


        <?php elseif ($is_logged_in): ?>


            <div class="review-form">

                <i
                    class="fa-solid fa-circle-info"
                    style="color:var(--gold);"
                ></i>

                You can review this product after purchasing it.

            </div>


        <?php else: ?>


            <div class="review-form">

                <i
                    class="fa-solid fa-user-lock"
                    style="color:var(--gold);"
                ></i>

                Please
                <a
                    href="login.php"
                    style="color:var(--brown);font-weight:700;"
                >
                    login
                </a>
                to review this product.

            </div>


        <?php endif; ?>

        <?php if (!empty($reviews)): ?>


            <?php foreach (
                $reviews as $review
            ): ?>

                <div class="review-card">


                    <div class="review-top">

                        <div class="reviewer">

                            <i
                                class="fa-regular fa-user me-1"
                            ></i>

                            <?= htmlspecialchars(
                                $review['customer_name']
                                ?? 'Customer'
                            ) ?>

                        </div>


                        <?php if (
                            !empty(
                                $review['created_at']
                            )
                        ): ?>

                            <div class="review-date">

                                <?= date(
                                    'M d, Y',
                                    strtotime(
                                        $review['created_at']
                                    )
                                ) ?>

                            </div>

                        <?php endif; ?>


                    </div>


                    <div class="review-stars">

                        <?= displayStars(
                            (int)(
                                $review['rating']
                                ?? 0
                            )
                        ) ?>

                    </div>


                    <div class="review-text">

                        <?= nl2br(
                            htmlspecialchars(
                                $review['review']
                                ??
                                $review['comment']
                                ??
                                ''
                            )
                        ) ?>

                    </div>


                </div>

            <?php endforeach; ?>


        <?php else: ?>


            <div class="no-reviews">

                <i
                    class="fa-regular fa-comment-dots"
                    style="font-size:28px;color:var(--gold);margin-bottom:12px;"
                ></i>

                <br>

                No reviews yet.

                Be the first customer to review this product.

            </div>


        <?php endif; ?>


    </div>

</section>

<footer class="footer">

    <div class="footer-inner">


        <!-- BRAND -->

        <div>

            <div class="footer-brand">

                Leather<span>Craft</span>

            </div>

            <p class="footer-description">

                Premium men's leather shoes crafted for
                timeless style, comfort and durability.

            </p>

        </div>


        <!-- QUICK LINKS -->

        <div class="footer-column">

            <h4>

                QUICK LINKS

            </h4>

            <a href="index.php">

                Home

            </a>

            <a href="products.php">

                Shop

            </a>

            <a href="index.php#categories">

                Categories

            </a>

            <a href="index.php#about">

                About Us

            </a>

        </div>


        <!-- CUSTOMER -->

        <div class="footer-column">

            <h4>

                CUSTOMER

            </h4>

            <a href="cart.php">

                Shopping Cart

            </a>

            <?php if ($is_logged_in): ?>

                <a href="orders.php">

                    My Orders

                </a>

                <a href="profile.php">

                    My Account

                </a>

                <a href="notifications.php">

                    Notifications

                </a>

            <?php else: ?>

                <a href="login.php">

                    Login / Register

                </a>

            <?php endif; ?>

        </div>


        <!-- CONTACT -->

        <div class="footer-column">

            <h4>

                CONTACT

            </h4>

            <a href="#">

                <i
                    class="fa-solid fa-location-dot me-2"
                ></i>

                Kathmandu, Nepal

            </a>

            <a href="#">

                <i
                    class="fa-solid fa-phone me-2"
                ></i>

                +977 98XXXXXXXX

            </a>

            <a href="#">

                <i
                    class="fa-solid fa-envelope me-2"
                ></i>

                info@leathercraft.com

            </a>

        </div>


    </div>


    <div class="footer-bottom">

        © <?= date('Y') ?>

        LeatherCraft.

        All Rights Reserved.

    </div>

</footer>


<script>

function changeQuantity(change)
{

    const input =
        document.getElementById('quantity');

    if (!input) {
        return;
    }

    let value =
        parseInt(input.value) || 1;

    const min =
        parseInt(input.min) || 1;

    const max =
        parseInt(input.max) || 1;


    value += change;


    if (value < min) {

        value = min;

    }

    if (value > max) {

        value = max;

    }


    input.value = value;

}


const quantityInput =
    document.getElementById('quantity');

if (quantityInput) {

    quantityInput.addEventListener(
        'input',
        function () {

            let value =
                parseInt(this.value) || 1;

            const max =
                parseInt(this.max);

            if (value < 1) {

                value = 1;

            }

            if (
                !isNaN(max) &&
                value > max
            ) {

                value = max;

            }

            this.value = value;

        }
    );

}


const cartForm =
    document.getElementById('cartForm');

if (cartForm) {

    cartForm.addEventListener(
        'submit',
        function (event) {


            const size =
                document.querySelector(
                    'input[name="size"]:checked'
                );

            const color =
                document.querySelector(
                    'input[name="color"]:checked'
                );


            const sizeInputs =
                document.querySelectorAll(
                    'input[name="size"][type="radio"]'
                );

            if (
                sizeInputs.length > 0 &&
                !size
            ) {

                event.preventDefault();

                alert(
                    'Please select a shoe size.'
                );

                return;

            }


            const colorInputs =
                document.querySelectorAll(
                    'input[name="color"][type="radio"]'
                );

            if (
                colorInputs.length > 0 &&
                !color
            ) {

                event.preventDefault();

                alert(
                    'Please select a shoe color.'
                );

                return;

            }

        }
    );

}

</script>


</body>

</html>